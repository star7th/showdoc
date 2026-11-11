<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\ContentCodec;
use App\Common\Helper\Convert;
use App\Common\Helper\IpHelper;
use App\Common\Helper\UrlHelper;
use App\Model\Options;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Markdown 直出 Api（供 AI 工具 / llms.txt 抓取使用）。
 *
 * 路由（零入口改动，靠 s= 通用路由自动映射）：
 * - GET /server/index.php?s=api/md/page&page_id=xx  单页 .md 原文输出
 * - GET /server/index.php?s=api/md/llms&item_id=xx  项目 llms.txt 索引
 * - GET /server/index.php?s=api/md/p&key=xx         单页分享 unique_key 短链版 .md
 *
 * 约定：
 * - 只输出公开（无密码）项目；密码项目一律 404（不泄露存在性，也不支持 URL 带密码）
 * - 错误返回真实 HTTP 状态码 + 纯文本，不用 JSON error 包装
 */
class MdController extends BaseController
{
    /**
     * 独立限流：每 IP 每小时最大请求数。
     * 开源版没有 Redis/Limiter 基础设施，采用 Runtime 目录下的文件计数器实现。
     */
    const RATE_LIMIT_PER_HOUR = 600;

    /**
     * 单页 .md 输出（?s=api/md/page&page_id=xx）。
     */
    public function page(Request $request, Response $response): Response
    {
        if (!$this->rateLimit()) {
            return $this->textResponse($response, 'Too Many Requests', 429, 'text/plain');
        }

        $pageId = (int) $this->getParam($request, 'page_id', 0);
        if ($pageId <= 0) {
            return $this->textResponse($response, '页面不存在或不可访问', 404, 'text/plain');
        }

        $page = DB::table('page')->where('page_id', $pageId)->first();
        if (!$page || (int) ($page->is_del ?? 0) === 1) {
            sleep(1);
            return $this->textResponse($response, '页面不存在或不可访问', 404, 'text/plain');
        }

        $itemId = (int) ($page->item_id ?? 0);
        $item   = DB::table('item')->where('item_id', $itemId)->first();
        if (!$item || (int) ($item->is_del ?? 0) === 1) {
            return $this->textResponse($response, '页面不存在或不可访问', 404, 'text/plain');
        }

        // 密码项目：一律 404 中性文案，不接受 _item_pwd 透传（避免密码进 URL 被 AI 工具记录）
        if (!empty($item->password)) {
            sleep(1);
            return $this->textResponse($response, '页面不存在或不可访问', 404, 'text/plain');
        }

        // 复用 info() 的权限链（公开项目游客可读；成员/管理者直通）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->textResponse($response, '您没有访问权限', 403, 'text/plain');
        }

        // 草稿页仅作者可见
        if ((int) ($page->is_draft ?? 0) === 1) {
            $authorUid = (int) ($page->author_uid ?? 0);
            if ($uid <= 0 || $uid !== $authorUid) {
                return $this->textResponse($response, '该页面是草稿状态，暂不可访问', 403, 'text/plain');
            }
        }

        $markdown = $this->buildPageMarkdown($item, $page, true);

        $response = $this->textResponse($response, $markdown, 200, 'text/markdown');
        return $this->applyFilenameHeader($request, $response);
    }

    /**
     * 单页分享短链版 .md 输出（?s=api/md/p&key=xx）。
     *
     * 与 PageController::infoByKey 语义一致：作者主动生成的分享链接即视为公开，
     * 跳过项目密码检查；过期链接惰性删除。
     */
    public function p(Request $request, Response $response): Response
    {
        if (!$this->rateLimit()) {
            return $this->textResponse($response, 'Too Many Requests', 429, 'text/plain');
        }

        $uniqueKey = (string) $this->getParam($request, 'key', '');
        if ($uniqueKey === '') {
            return $this->textResponse($response, '该分享链接已过期或不存在', 404, 'text/plain');
        }

        $singlePage = DB::table('single_page')->where('unique_key', $uniqueKey)->first();
        if (!$singlePage) {
            return $this->textResponse($response, '该分享链接已过期或不存在', 404, 'text/plain');
        }

        $expireTime = (int) ($singlePage->expire_time ?? 0);
        if ($expireTime > 0 && $expireTime < time()) {
            DB::table('single_page')->where('unique_key', $uniqueKey)->delete();
            return $this->textResponse($response, '该分享链接已过期或不存在', 404, 'text/plain');
        }

        $pageId = (int) ($singlePage->page_id ?? 0);
        $page   = DB::table('page')->where('page_id', $pageId)->first();
        if (!$page || (int) ($page->is_del ?? 0) === 1) {
            sleep(1);
            return $this->textResponse($response, '该分享链接已过期或不存在', 404, 'text/plain');
        }

        // 草稿页不可通过分享链接访问
        if ((int) ($page->is_draft ?? 0) === 1) {
            return $this->textResponse($response, '该页面是草稿状态，暂不可访问', 403, 'text/plain');
        }

        $item = DB::table('item')->where('item_id', (int) ($page->item_id ?? 0))->first();

        // 仅当项目完全公开时才注入 llms.txt 指引
        $markdown = $this->buildPageMarkdown($item, $page, $item && empty($item->password));

        $response = $this->textResponse($response, $markdown, 200, 'text/markdown')
            ->withHeader('X-Robots-Tag', 'noindex');
        return $this->applyFilenameHeader($request, $response);
    }

    /**
     * 项目 llms.txt 索引（?s=api/md/llms&item_id=xx）。
     *
     * 仅公开（无密码）项目；密码项目 404，不泄露存在性。
     */
    public function llms(Request $request, Response $response): Response
    {
        if (!$this->rateLimit()) {
            return $this->textResponse($response, 'Too Many Requests', 429, 'text/plain');
        }

        $itemId = (int) $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->textResponse($response, '项目不存在或不可访问', 404, 'text/plain');
        }

        $item = DB::table('item')->where('item_id', $itemId)->first();
        if (!$item || (int) ($item->is_del ?? 0) === 1 || !empty($item->password)) {
            sleep(1);
            return $this->textResponse($response, '项目不存在或不可访问', 404, 'text/plain');
        }

        $pages = DB::table('page')
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->where('is_draft', 0)
            ->where('page_title', '<>', '__kanban_board__')
            ->orderBy('s_number', 'asc')
            ->orderBy('page_id', 'asc')
            ->get();

        $lines   = [];
        $lines[] = '# ' . ($item->item_name ?? '');
        $lines[] = '';
        $description = trim((string) ($item->item_description ?? ''));
        if ($description !== '') {
            $lines[] = '> ' . $description;
            $lines[] = '';
        }

        $mdBase = $this->mdBaseUrl();
        foreach ($pages as $p) {
            $lines[] = '- [' . ($p->page_title ?? '') . '](' . $mdBase . 'page&page_id=' . $p->page_id . '&f=' . $p->page_id . '.md)';
        }
        $lines[] = '';

        $response = $this->textResponse($response, implode("\n", $lines), 200, 'text/plain');
        return $this->applyFilenameHeader($request, $response);
    }

    /**
     * 组装单页 Markdown：H1 标题 + 项目名 + llms.txt 指引 + 正文。
     *
     * @param object|null $item          项目记录
     * @param object      $page          页面记录
     * @param bool        $withLlmsGuide 是否注入 llms.txt 指引（仅完全公开项目）
     */
    private function buildPageMarkdown($item, $page, bool $withLlmsGuide): string
    {
        $title    = (string) ($page->page_title ?? '');
        $itemName = (string) ($item->item_name ?? '');

        // 兼容旧压缩数据（开源版默认不压缩）
        $content = ContentCodec::decompress((string) ($page->page_content ?? ''));
        if ($content === '') {
            $content = (string) ($page->page_content ?? '');
        }

        $itemType = (int) ($item->item_type ?? 1);

        // RunAPI 项目：JSON 序列化为 Markdown；失败回退原始 JSON 并注明
        if ($itemType === 3) {
            $converted = (new Convert())->runapiToMd($content);
            if ($converted !== false && $converted !== '') {
                $content = $converted;
            } else {
                $content = "> 注：本页为 RunAPI 接口数据，自动转换 Markdown 失败，以下为原始 JSON。\n\n```json\n"
                    . $content . "\n```";
            }
        } elseif ($itemType === 4) {
            $content = "> 注：本页属于表格类型页面，暂不支持 Markdown 渲染，以下为原始数据。\n\n" . $content;
        } elseif ($itemType === 5) {
            $content = "> 注：本页属于白板类型页面，暂不支持 Markdown 渲染，以下为原始数据。\n\n" . $content;
        } elseif ($itemType === 6) {
            $content = "> 注：本页属于看板项目页面，暂不支持 Markdown 渲染，以下为原始数据。\n\n" . $content;
        }

        $lines   = [];
        $lines[] = '# ' . $title;
        $lines[] = '';
        if ($itemName !== '') {
            $lines[] = '> 项目：' . $itemName . '（ShowDoc）';
        }
        if ($withLlmsGuide) {
            $llmsUrl = $this->mdBaseUrl() . 'llms&item_id=' . ($item->item_id ?? 0) . '&f=llms.txt';
            $lines[] = '> 完整文档索引见 llms.txt：' . $llmsUrl;
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
            $lines[] = $content;
            $lines[] = '';
            $lines[] = '---';
            $lines[] = '';
            $lines[] = '[llms.txt](' . $llmsUrl . ')';
        } else {
            $lines[] = '';
            $lines[] = $content;
        }
        $lines[] = '';

        return implode("\n", $lines);
    }

    /**
     * 可选装饰参数 f：仅用于让 URL 以 .md/.txt 等文件名结尾，便于识别。
     *
     * 忽略其值本身对输出内容的影响，仅在合法（白名单：字母数字下划线点连字符）时
     * 追加 Content-Disposition: inline 响应头；非法则按未传处理，防 header 注入。
     */
    private function applyFilenameHeader(Request $request, Response $response): Response
    {
        $f = (string) $this->getParam($request, 'f', '');
        if ($f === '' || !preg_match('/^[A-Za-z0-9_.-]+$/', $f)) {
            return $response;
        }

        return $response->withHeader('Content-Disposition', 'inline; filename="' . $f . '"');
    }

    /**
     * 生成 .md 端点的基础 URL（绝对地址）。
     *
     * 优先使用系统配置的站点地址（options 表 site_url，后台「高级设置」里配置），
     * 避免经反向代理（如 Vite changeOrigin）转发时 Host 变成 127.0.0.1 导致链接不可用；
     * 配置不存在或不合法（非 http/https URL）时，回退到现有请求 Host 逻辑（UrlHelper::siteUrl）。
     */
    private function mdBaseUrl(): string
    {
        $base = '';

        $siteUrl = trim((string) Options::get('site_url'));
        if ($siteUrl !== '') {
            $parts = parse_url($siteUrl);
            if ($parts !== false
                && isset($parts['scheme'], $parts['host'])
                && in_array(strtolower($parts['scheme']), ['http', 'https'], true)
            ) {
                $base = rtrim($siteUrl, '/');
            }
        }

        if ($base === '') {
            // 回退：按当前请求 Host 推导。注意不能直接用 UrlHelper::siteUrl()，
            // 它在 site_url 已配置（哪怕非法）时仍会返回配置值。
            $phpSelf = $_SERVER['PHP_SELF'] ?? '/';
            $dir = substr($phpSelf, 0, strrpos($phpSelf, '/'));
            $base = str_replace('/server', '', UrlHelper::getDomain() . $dir);
        }

        return $base . '/server/index.php?s=api/md/';
    }

    /**
     * 纯文本 / Markdown 响应（真实状态码，非 JSON 包装）。
     */
    private function textResponse(Response $response, string $body, int $status, string $contentType): Response
    {
        $response->getBody()->write($body);

        return $response
            ->withHeader('Content-Type', $contentType . '; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'public, max-age=300')
            ->withStatus($status);
    }

    /**
     * 简单 IP 级限流：每小时 RATE_LIMIT_PER_HOUR 次。
     *
     * 开源版没有 Redis/Limiter，使用 Runtime 下的文件计数器（含 flock 并发保护），
     * 文件名含小时数，跨小时自动失效，由下次写入顺带清理。
     */
    private function rateLimit(): bool
    {
        $dir = dirname(__DIR__) . '/Runtime/MdRate';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ip   = IpHelper::getClientIp();
        $hour = gmdate('YmdH');
        $file = $dir . '/' . md5($ip . $hour) . '.cnt';

        $count = 0;
        $fh = @fopen($file, 'c+');
        if (!$fh) {
            // 文件不可写时不拦截（与全站无 Redis 时不拦截的口径一致）
            return true;
        }

        try {
            flock($fh, LOCK_EX);
            $raw   = (string) stream_get_contents($fh);
            $count = (int) $raw;
            $count++;
            ftruncate($fh, 0);
            rewind($fh);
            fwrite($fh, (string) $count);
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        // 顺带清理上一个小时的计数文件（概率触发，避免每次 glob）
        if ($count === 1 && random_int(1, 20) === 1) {
            $oldPrefix = $dir . '/' . md5($ip . gmdate('YmdH', time() - 3600)) . '.cnt';
            @unlink($oldPrefix);
        }

        return $count <= self::RATE_LIMIT_PER_HOUR;
    }
}
