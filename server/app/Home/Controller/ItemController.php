<?php

namespace App\Home\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Model\Page;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ItemController extends BaseController
{
    public function show(Request $request, Response $response): Response
    {
        $itemId = (int) $this->getParam($request, 'item_id');
        $pageId = (int) $this->getParam($request, 'page_id');

        if ($itemId <= 0) {
            return $this->fallbackWeb($response);
        }

        $item = Item::findById($itemId);
        if (!$item) {
            return $this->fallbackWeb($response);
        }

        // 加密项目不做 SEO 渲染
        if (!empty($item->password)) {
            return $this->fallbackWeb($response);
        }

        // 页面列表
        $pages = Page::listTitles((int) $item->item_id);
        if (empty($pages)) {
            return $this->fallbackWeb($response);
        }

        if ($pageId <= 0 && isset($pages[0])) {
            $pageId = (int) $pages[0]->page_id;
        }

        $page = Page::findByIdWithContent((int) $item->item_id, $pageId);
        if (!$page) {
            return $this->fallbackWeb($response);
        }

        // 渲染正文内容
        $pageContent = (string) ($page['page_content'] ?? '');

        // 引入 Parsedown 类（Markdown 解析器）
        if (!class_exists('Parsedown')) {
            require_once __DIR__ . '/../../Common/Vendor/Parsedown.php';
        }
        // 使用 Parsedown 将 Markdown 转为 HTML
        $parsedown = new \Parsedown();
        $pageHtml  = $parsedown->text($pageContent);
        $pageHtml  = htmlspecialchars($pageHtml, ENT_QUOTES, 'UTF-8');

        $html  = '';
        $html .= '<h2>' . htmlspecialchars($item->item_name ?? '', ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<p>' . htmlspecialchars($item->item_description ?? '', ENT_QUOTES, 'UTF-8') . '</p><br>';

        $html .= '<article><h1>' . htmlspecialchars($page['page_title'] ?? '', ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<p><div>' . $pageHtml . '</div><p></article>';

        // 页面列表
        $html .= '<h3>页面列表</h3><nav>';
        foreach ($pages as $idx => $p) {
            if ($idx === 0) {
                continue; // 与旧实现一致：隐藏第一个
            }
            $pid   = (int) $p->page_id;
            $title = htmlspecialchars($p->page_title ?? '', ENT_QUOTES, 'UTF-8');
            $path  = $item->item_domain ?: $item->item_id;
            $html .= "<a href=\"/{$path}/{$pid}\" title=\"{$title}\">{$title}</a>";
        }
        $html .= '</nav>';

        // 注入 web.html
        $rootPath = dirname(__DIR__, 4);
        $tplPath  = $rootPath . '/web.html';
        if (!is_file($tplPath)) {
            return $this->fallbackWeb($response);
        }

        $tpl  = file_get_contents($tplPath);
        $html = str_replace('INDEX_HTML', $html, $tpl);

        // 更新 <title> 与 meta 信息
        $title = htmlspecialchars($item->item_name ?? '', ENT_QUOTES, 'UTF-8');
        $desc  = htmlspecialchars($item->item_description ?? '', ENT_QUOTES, 'UTF-8');

        $html = preg_replace('/(<title>)(.*?)(<\/title>)/i', '${1}' . $title . '${3}', $html);
        $html = preg_replace('/(<meta\s+name="keywords"\s+content=")([^"]*)(")/i', '${1}' . $desc . '${3}', $html);
        $html = preg_replace('/(<meta\s+name="description"\s+content=")([^"]*)(")/i', '${1}' . $desc . '${3}', $html);

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function fallbackWeb(Response $response): Response
    {
        $rootPath = dirname(__DIR__, 4);
        $tplPath  = $rootPath . '/web.html';
        if (!is_file($tplPath)) {
            return $response->withStatus(404);
        }

        $html = file_get_contents($tplPath);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}

