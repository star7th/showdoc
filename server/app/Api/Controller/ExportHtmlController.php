<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\ExportLog;
use App\Model\Item;
use App\Model\Member;
use App\Model\ItemChangeLog;
use App\Common\Helper\Convert;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ExportHtmlController extends BaseController
{
    /**
     * 导出项目为离线HTML包
     */
    public function export(Request $request, Response $response): Response
    {
        set_time_limit(600);
        ini_set('memory_limit', '2G');

        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        $uid = (int) ($user['uid'] ?? 0);

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '没有权限');
        }

        // 获取项目信息
        $item = Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }
        $item = (array) $item;

        // 导出频率限制：同一用户当天最多 100 次（防刷）
        $timesToday = ExportLog::getTodayCount($uid, 'html');
        if ($timesToday >= 100) {
            $message = "为防止影响服务器负载，你当天导出次数已达上限(100次)。如有疑问请联系网站管理员";
            return $this->error($response, 10100, $message);
        }

        // 检查项目类型
        $itemType = (int) ($item['item_type'] ?? 0);
        if (!in_array($itemType, [1, 3, 5])) {
            return $this->error($response, 10101, '不支持的项目类型');
        }

        // 成员目录权限：获取该用户在此项目下允许的目录集合
        $allowedCatIds = Member::getCatIds($itemId, $uid);

        // 获取项目内容（解压内容）
        $menu = Item::getContent($itemId, true);

        // 应用目录权限过滤
        if (!empty($allowedCatIds)) {
            $allowed = array_flip(array_map('intval', $allowedCatIds));
            // 过滤根目录下的页面
            if (!empty($menu['pages'])) {
                $menu['pages'] = [];
            }
            // 过滤目录
            if (!empty($menu['catalogs'])) {
                $filteredCatalogs = [];
                foreach ($menu['catalogs'] as $cat) {
                    if (isset($allowed[(int) ($cat['cat_id'] ?? 0)])) {
                        $filteredCatalogs[] = $cat;
                    }
                }
                $menu['catalogs'] = $filteredCatalogs;
            }
        }

        // 创建临时目录
        $tempDir = sys_get_temp_dir() . "/showdoc_html_" . time() . "_" . rand(1000, 9999);
        if (!mkdir($tempDir, 0755, true)) {
            return $this->error($response, 10101, '创建临时目录失败');
        }

        try {
            // 创建目录结构
            mkdir($tempDir . '/pages', 0755, true);
            mkdir($tempDir . '/assets/css', 0755, true);
            mkdir($tempDir . '/assets/js', 0755, true);
            mkdir($tempDir . '/assets/uploads', 0755, true);

            // 收集所有页面
            $allPages = $this->collectAllPages($menu);

            if (empty($allPages)) {
                return $this->error($response, 10101, '没有可导出的页面');
            }

            // 生成页面HTML
            $this->generatePagesHtml($allPages, $item, $tempDir);

            // 生成首页
            $this->generateIndexHtml($item, $tempDir);

            // 生成数据文件
            $this->generateDataJs($menu, $item, $allPages, $tempDir);

            // 验证：确保生成的文件名和data.js中的page_id一致
            $this->validatePageIds($allPages, $tempDir);

            // 生成搜索索引
            $this->generateSearchIndex($allPages, $tempDir);

            // 复制静态资源
            $this->copyStaticFiles($tempDir);

            // 复制图片和附件
            $this->copyAssets($allPages, $tempDir);

            // 生成README
            $this->generateReadme($item, $tempDir);

            // 打包ZIP
            $zipFile = sys_get_temp_dir() . "/showdoc_html_" . $itemId . "_" . time() . ".zip";
            if (!$this->zip($tempDir, $zipFile)) {
                return $this->error($response, 10101, '打包失败');
            }

            // 写导出记录
            ExportLog::add([
                "uid" => $uid,
                "export_type" => 'html',
                "item_id" => $itemId,
                "addtime" => date("y-m-d H:i:s")
            ]);

            // 记录项目变更日志
            ItemChangeLog::addLog($uid, $itemId, 'export', 'item', $itemId, $item['item_name'] ?? '');

            // 输出文件
            $filename = 'showdoc_offline_' . $this->sanitizeFilename($item['item_name'] ?? '') . '_' . date('YmdHis') . '.zip';

            // 读取文件内容
            $fileContent = file_get_contents($zipFile);
            unlink($zipFile);

            // 处理中文文件名编码
            $userAgent = $request->getHeaderLine('User-Agent') ?: '';
            $encodedFilename = rawurlencode($filename);

            // 设置响应头
            $response = $response
                ->withHeader('Cache-Control', 'max-age=0')
                ->withHeader('Content-Description', 'File Transfer')
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Transfer-Encoding', 'binary')
                ->withHeader('Content-Length', (string) strlen($fileContent));

            // 根据浏览器类型设置文件名编码
            if (preg_match('/MSIE|Trident/i', $userAgent)) {
                // IE浏览器使用GBK编码
                $filenameGbk = mb_convert_encoding($filename, 'GBK', 'UTF-8');
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="' . $filenameGbk . '"');
            } else {
                // 现代浏览器使用RFC 5987编码
                $response = $response->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . $encodedFilename);
            }

            // 写入文件内容
            $response->getBody()->write($fileContent);

            return $response;
        } catch (\Exception $e) {
            error_log("Export HTML Error: " . $e->getMessage());
            return $this->error($response, 10101, '导出失败: ' . $e->getMessage());
        } finally {
            // 清理临时文件
            $this->clearTempDir($tempDir);
        }
    }

    /**
     * 收集所有页面（扁平化）
     */
    private function collectAllPages(array $menu): array
    {
        $pages = [];

        // 根目录下的页面
        if (!empty($menu['pages'])) {
            foreach ($menu['pages'] as $page) {
                $pages[] = $page;
            }
        }

        // 递归收集目录下的页面
        if (!empty($menu['catalogs'])) {
            $this->collectPagesFromCatalogs($menu['catalogs'], $pages);
        }

        return $pages;
    }

    /**
     * 从目录中递归收集页面
     */
    private function collectPagesFromCatalogs(array $catalogs, array &$pages): void
    {
        foreach ($catalogs as $cat) {
            if (!empty($cat['pages'])) {
                foreach ($cat['pages'] as $page) {
                    $pages[] = $page;
                }
            }
            if (!empty($cat['catalogs'])) {
                $this->collectPagesFromCatalogs($cat['catalogs'], $pages);
            }
        }
    }

    /**
     * 生成所有页面的HTML
     */
    private function generatePagesHtml(array $pages, array $item, string $tempDir): void
    {
        // 引入 Parsedown 类（Markdown 解析器）
        if (!class_exists('Parsedown')) {
            require_once __DIR__ . '/../../Common/Vendor/Parsedown.php';
        }
        $parsedown = new \Parsedown();
        $convert = new Convert();

        foreach ($pages as $page) {
            // 确保 page_id 存在且有效
            if (empty($page['page_id'])) {
                continue;
            }
            // 统一获取page_id，确保后续使用一致
            $pageId = (int) $page['page_id'];
            if ($pageId <= 0) {
                continue;
            }
            // 将page_id传递给生成方法，避免重复获取导致不一致
            $html = $this->generatePageHtml($page, $item, $parsedown, $convert, $pageId);
            $filePath = $tempDir . '/pages/page-' . $pageId . '.html';
            file_put_contents($filePath, $html);
        }
    }

    /**
     * 生成单个页面HTML
     */
    private function generatePageHtml(array $page, array $item, \Parsedown $parsedown, Convert $convert, int $pageId): string
    {
        $pageTitle = htmlspecialchars($page['page_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $itemName = htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8');

        // 处理内容
        $pageContent = $page['page_content'] ?? '';

        // RunAPI项目转换
        if (($item['item_type'] ?? 0) == 3) {
            $mdContent = $convert->runapiToMd($pageContent);
            if ($mdContent) {
                $pageContent = $mdContent;
            }
        }

        // Markdown转HTML
        $htmlContent = $parsedown->text($pageContent);

        // 转义还原（数据库内容经过了html转义，需还原以正确显示）
        $htmlContent = htmlspecialchars_decode($htmlContent);

        // HTML安全过滤
        $htmlContent = $this->sanitizeHtml($htmlContent);

        // 图片路径重写
        $htmlContent = $this->rewriteImagePaths($htmlContent);

        // 生成HTML
        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $pageTitle . ' - ' . $itemName . '</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/highlight.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="project-name">' . $itemName . '</h1>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="搜索页面...">
                <div id="searchResults" class="search-results"></div>
            </div>
            <button class="menu-toggle" id="menuToggle">☰</button>
        </div>
    </div>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div id="catalogTree" class="catalog-tree"></div>
        </aside>
        <main class="main-content">
            <article class="page-content">
                <h1 class="page-title">' . $pageTitle . '</h1>
                <div class="markdown-body">' . $htmlContent . '</div>
                <div class="page-nav" id="pageNav"></div>
            </article>
        </main>
    </div>
    <script src="../assets/js/data.js"></script>
    <script src="../assets/js/search-index.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/highlight.min.js"></script>
    <script>
        if (typeof hljs !== "undefined") {
            hljs.highlightAll();
        }
        // 设置当前页面ID（使用字符串形式，避免大整数精度丢失）
        window.CURRENT_PAGE_ID = \'' . $pageId . '\';
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * 生成首页HTML
     */
    private function generateIndexHtml(array $item, string $tempDir): void
    {
        $itemName = htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $itemDescription = htmlspecialchars($item['item_description'] ?? '', ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $itemName . ' - 项目概览</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/highlight.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="project-name">' . $itemName . '</h1>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="搜索页面...">
                <div id="searchResults" class="search-results"></div>
            </div>
            <button class="menu-toggle" id="menuToggle">☰</button>
        </div>
    </div>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div id="catalogTree" class="catalog-tree"></div>
        </aside>
        <main class="main-content">
            <article class="page-content">
                <h1 class="page-title">' . $itemName . '</h1>
                ' . ($itemDescription ? '<p class="project-description">' . $itemDescription . '</p>' : '') . '
                <div class="project-overview">
                    <p>这是一个离线HTML文档包，您可以在浏览器中离线浏览所有文档。</p>
                    <p>使用左侧目录树导航，或使用顶部搜索框快速查找内容。</p>
                </div>
            </article>
        </main>
    </div>
    <script src="assets/js/data.js"></script>
    <script src="assets/js/search-index.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        window.CURRENT_PAGE_ID = \'0\';
    </script>
</body>
</html>';

        file_put_contents($tempDir . '/index.html', $html);
    }

    /**
     * 生成data.js文件
     */
    private function generateDataJs(array $menu, array $item, array $allPages, string $tempDir): void
    {
        // 收集所有目录（扁平化）
        $allCatalogs = $this->collectAllCatalogs($menu);

        // 构建页面列表
        $pagesList = [];
        foreach ($allPages as $page) {
            // 确保 page_id 存在且有效
            if (empty($page['page_id'])) {
                continue;
            }
            $pageId = (int) $page['page_id'];
            if ($pageId <= 0) {
                continue;
            }
            // page_id 使用字符串形式，避免JavaScript大整数精度丢失
            $pagesList[] = [
                'page_id' => (string) $pageId,
                'page_title' => $page['page_title'] ?? '',
                'cat_id' => (int) ($page['cat_id'] ?? 0),
                's_number' => (int) ($page['s_number'] ?? 0),
                'file_path' => 'pages/page-' . $pageId . '.html'
            ];
        }

        // 构建目录列表
        $catalogsList = [];
        foreach ($allCatalogs as $cat) {
            $catalogsList[] = [
                'cat_id' => (int) ($cat['cat_id'] ?? 0),
                'cat_name' => $cat['cat_name'] ?? '',
                'parent_cat_id' => (int) ($cat['parent_cat_id'] ?? 0),
                'level' => (int) ($cat['level'] ?? 2),
                's_number' => (int) ($cat['s_number'] ?? 0)
            ];
        }

        $data = [
            'item_id' => (int) ($item['item_id'] ?? 0),
            'item_name' => $item['item_name'] ?? '',
            'item_type' => $item['item_type'] ?? 0,
            'item_description' => $item['item_description'] ?? '',
            'catalogs' => $catalogsList,
            'pages' => $pagesList
        ];

        $jsContent = 'window.PROJECT_DATA = ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ';';
        file_put_contents($tempDir . '/assets/js/data.js', $jsContent);
    }

    /**
     * 收集所有目录（扁平化）
     */
    private function collectAllCatalogs(array $menu): array
    {
        $catalogs = [];

        if (!empty($menu['catalogs'])) {
            $this->collectCatalogsRecursive($menu['catalogs'], $catalogs);
        }

        return $catalogs;
    }

    /**
     * 递归收集目录
     */
    private function collectCatalogsRecursive(array $catalogsList, array &$result): void
    {
        foreach ($catalogsList as $cat) {
            $result[] = [
                'cat_id' => $cat['cat_id'] ?? 0,
                'cat_name' => $cat['cat_name'] ?? '',
                'parent_cat_id' => $cat['parent_cat_id'] ?? 0,
                'level' => $cat['level'] ?? 2,
                's_number' => $cat['s_number'] ?? 0
            ];
            if (!empty($cat['catalogs'])) {
                $this->collectCatalogsRecursive($cat['catalogs'], $result);
            }
        }
    }

    /**
     * 生成搜索索引
     */
    private function generateSearchIndex(array $pages, string $tempDir): void
    {
        $index = [];

        foreach ($pages as $page) {
            // 确保 page_id 存在且有效
            if (empty($page['page_id'])) {
                continue;
            }
            $pageId = (int) $page['page_id'];
            if ($pageId <= 0) {
                continue;
            }
            // 先还原转义再提取文本
            $decoded = htmlspecialchars_decode($page['page_content'] ?? '');
            $content = strip_tags($decoded);
            $contentPreview = mb_substr($content, 0, 200);

            // page_id 使用字符串形式，避免JavaScript大整数精度丢失
            $index[] = [
                'page_id' => (string) $pageId,
                'page_title' => $page['page_title'] ?? '',
                'content_preview' => $contentPreview,
                'cat_id' => (int) ($page['cat_id'] ?? 0)
            ];
        }

        $jsContent = 'window.SEARCH_INDEX = ' . json_encode($index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ';';
        file_put_contents($tempDir . '/assets/js/search-index.js', $jsContent);
    }

    /**
     * 复制静态文件（CSS/JS）
     */
    private function copyStaticFiles(string $tempDir): void
    {
        // 获取项目根目录（server目录的父目录）
        $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
        // 获取 server 目录
        $serverPath = dirname(dirname(dirname(__DIR__)));

        // 复制highlight.js（在项目根目录的 Public 目录下）
        $highlightJs = $rootPath . '/Public/highlight/highlight.min.js';
        $targetJs = $tempDir . '/assets/js/highlight.min.js';
        if (file_exists($highlightJs)) {
            if (!copy($highlightJs, $targetJs)) {
                error_log("Export HTML: Failed to copy highlight.js from {$highlightJs} to {$targetJs}");
            } elseif (!file_exists($targetJs)) {
                error_log("Export HTML: highlight.js copy failed, target file not exists: {$targetJs}");
            }
        } else {
            error_log("Export HTML: highlight.js not found: " . $highlightJs);
        }

        // 复制highlight.css（在项目根目录的 Public 目录下）
        $highlightCss = $rootPath . '/Public/highlight/default.min.css';
        $targetCss = $tempDir . '/assets/css/highlight.css';
        if (file_exists($highlightCss)) {
            if (!copy($highlightCss, $targetCss)) {
                error_log("Export HTML: Failed to copy highlight.css from {$highlightCss} to {$targetCss}");
            } elseif (!file_exists($targetCss)) {
                error_log("Export HTML: highlight.css copy failed, target file not exists: {$targetCss}");
            }
        } else {
            error_log("Export HTML: highlight.css not found: " . $highlightCss);
        }

        // 生成common.css和app.js（在 server/app/Static 目录下）
        $this->generateCommonCss($tempDir, $serverPath);
        $this->generateAppJs($tempDir, $serverPath);
    }

    /**
     * 生成common.css
     */
    private function generateCommonCss(string $tempDir, string $serverPath): void
    {
        // $serverPath 是 server 目录，所以使用 app/Static
        $cssFile = $serverPath . '/app/Static/export-html/common.css';
        $targetCss = $tempDir . '/assets/css/common.css';
        if (file_exists($cssFile)) {
            $css = file_get_contents($cssFile);
            if ($css === false) {
                error_log("Export HTML: Failed to read CSS file: " . $cssFile);
                return;
            }
            if (file_put_contents($targetCss, $css) === false) {
                error_log("Export HTML: Failed to write CSS file: " . $targetCss);
            } elseif (!file_exists($targetCss) || filesize($targetCss) === 0) {
                error_log("Export HTML: CSS file write failed or empty: " . $targetCss);
            }
        } else {
            error_log("Export HTML: CSS file not found: " . $cssFile);
        }
    }

    /**
     * 生成app.js
     */
    private function generateAppJs(string $tempDir, string $serverPath): void
    {
        // $serverPath 是 server 目录，所以使用 app/Static
        $jsFile = $serverPath . '/app/Static/export-html/app.js';
        $targetJs = $tempDir . '/assets/js/app.js';
        if (file_exists($jsFile)) {
            $js = file_get_contents($jsFile);
            if ($js === false) {
                error_log("Export HTML: Failed to read JS file: " . $jsFile);
                return;
            }
            if (file_put_contents($targetJs, $js) === false) {
                error_log("Export HTML: Failed to write JS file: " . $targetJs);
            } elseif (!file_exists($targetJs) || filesize($targetJs) === 0) {
                error_log("Export HTML: JS file write failed or empty: " . $targetJs);
            }
        } else {
            error_log("Export HTML: JS file not found: " . $jsFile);
        }
    }

    /**
     * 复制图片和附件
     */
    private function copyAssets(array $pages, string $tempDir): void
    {
        // 获取项目根目录（server目录的父目录）
        $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
        $uploadsDir = $rootPath . '/Public/Uploads';
        $targetDir = $tempDir . '/assets/uploads';

        if (!is_dir($uploadsDir)) {
            return;
        }

        // 从页面内容中提取图片路径
        $imagePaths = [];
        foreach ($pages as $page) {
            $content = $page['page_content'] ?? '';
            // 匹配 /Public/Uploads/xxx 格式的路径
            preg_match_all('/\/Public\/Uploads\/([^\s"\'\)]+)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $imgPath) {
                    $imagePaths[$imgPath] = true;
                }
            }
        }

        // 复制图片文件
        foreach (array_keys($imagePaths) as $imgPath) {
            $source = $uploadsDir . '/' . $imgPath;
            if (file_exists($source) && is_file($source)) {
                $target = $targetDir . '/' . basename($imgPath);
                // 确保目录存在
                $targetDirPath = dirname($target);
                if (!is_dir($targetDirPath)) {
                    mkdir($targetDirPath, 0755, true);
                }
                copy($source, $target);
            }
        }
    }

    /**
     * 生成README文件
     */
    private function generateReadme(array $item, string $tempDir): void
    {
        $itemName = htmlspecialchars($item['item_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $readme = "ShowDoc 离线HTML文档包
====================

项目名称: {$itemName}
导出时间: " . date('Y-m-d H:i:s') . "

使用说明:
---------
1. 解压此压缩包到任意目录
2. 用浏览器打开 index.html 文件
3. 使用左侧目录树导航，或使用顶部搜索框查找内容
4. 所有资源已包含在压缩包中，可完全离线使用

注意事项:
---------
- 此文档包为只读版本，无法编辑
- 建议使用现代浏览器（Chrome、Firefox、Edge、Safari）
- 如需更新文档，请重新导出

";
        file_put_contents($tempDir . '/README.txt', $readme);
    }

    /**
     * HTML安全过滤
     */
    private function sanitizeHtml(string $html): string
    {
        // 移除script标签
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);

        // 移除事件处理器
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        $html = preg_replace('/\s*on\w+\s*=\s*[^\s>]+/i', '', $html);

        // 过滤危险协议
        $html = preg_replace('/href\s*=\s*["\'](javascript|data):/i', 'href="#"', $html);
        $html = preg_replace('/src\s*=\s*["\'](javascript|data):/i', 'src="#"', $html);

        return $html;
    }

    /**
     * 重写图片路径
     */
    private function rewriteImagePaths(string $html): string
    {
        // 处理图片路径：/Public/Uploads/xxx 转换为相对路径
        $html = preg_replace_callback(
            '/src\s*=\s*["\']([^"\']*\/Public\/Uploads\/[^"\']+)["\']/i',
            function ($matches) {
                $path = $matches[1];
                $filename = basename($path);
                return 'src="../assets/uploads/' . $filename . '"';
            },
            $html
        );

        return $html;
    }

    /**
     * 清理文件名
     */
    private function sanitizeFilename(string $filename): string
    {
        $filename = trim($filename);
        $filename = preg_replace('/[<>:"\/\\\|\?\*\x00-\x1F]/', '_', $filename);
        $filename = preg_replace('/[_\.]+/', '_', $filename);
        $filename = trim($filename, '_.');
        if (empty($filename)) {
            $filename = 'unnamed';
        }
        if (mb_strlen($filename) > 200) {
            $filename = mb_substr($filename, 0, 200);
        }
        return $filename;
    }

    /**
     * 打包ZIP
     */
    private function zip(string $fromName, string $toName): bool
    {
        if (!file_exists($fromName) || !is_dir($fromName)) {
            return false;
        }

        $zipArc = new \ZipArchive();
        if (!$zipArc->open($toName, \ZipArchive::CREATE)) {
            return false;
        }

        $this->addDirectoryToZip($fromName, $zipArc, '');
        return $zipArc->close();
    }

    /**
     * 递归添加目录到ZIP
     */
    private function addDirectoryToZip(string $dir, \ZipArchive $zipArc, string $zipPath): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $filePath = $dir . '/' . $file;
            $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;

            if (is_dir($filePath)) {
                $this->addDirectoryToZip($filePath, $zipArc, $zipFilePath);
            } else {
                $zipArc->addFile($filePath, $zipFilePath);
            }
        }
    }

    /**
     * 清理临时目录
     */
    private function clearTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->clearTempDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * 验证页面ID一致性
     * 确保生成的文件名和data.js中的page_id完全一致
     */
    private function validatePageIds(array $allPages, string $tempDir): void
    {
        // 读取生成的data.js文件
        $dataJsFile = $tempDir . '/assets/js/data.js';
        if (!file_exists($dataJsFile)) {
            error_log("Export HTML: data.js file not found for validation");
            return;
        }

        $dataJsContent = file_get_contents($dataJsFile);
        // 提取PROJECT_DATA中的pages数组
        if (preg_match('/window\.PROJECT_DATA\s*=\s*({.*?});/s', $dataJsContent, $matches)) {
            $dataJson = $matches[1];
            $data = json_decode($dataJson, true);

            if ($data && isset($data['pages'])) {
                $dataPages = $data['pages'];
                $dataPageIds = [];
                foreach ($dataPages as $dp) {
                    $dataPageIds[] = (string) ($dp['page_id'] ?? '');
                }

                // 检查实际生成的文件
                $pagesDir = $tempDir . '/pages';
                if (is_dir($pagesDir)) {
                    $files = scandir($pagesDir);
                    $filePageIds = [];
                    foreach ($files as $file) {
                        if (preg_match('/^page-(\d+)\.html$/', $file, $matches)) {
                            $filePageIds[] = $matches[1];
                        }
                    }

                    // 验证：data.js中的page_id应该和实际文件一致
                    $missingInData = array_diff($filePageIds, $dataPageIds);
                    $missingInFiles = array_diff($dataPageIds, $filePageIds);

                    if (!empty($missingInData)) {
                        error_log("Export HTML Validation: Files exist but not in data.js: " . implode(', ', $missingInData));
                    }
                    if (!empty($missingInFiles)) {
                        error_log("Export HTML Validation: page_id in data.js but file missing: " . implode(', ', $missingInFiles));
                    }

                    // 验证原始数据中的page_id
                    $originalPageIds = [];
                    foreach ($allPages as $page) {
                        if (!empty($page['page_id'])) {
                            $pid = (int) $page['page_id'];
                            if ($pid > 0) {
                                $originalPageIds[] = (string) $pid;
                            }
                        }
                    }

                    // 检查是否有page_id不一致
                    $missingInOriginal = array_diff($dataPageIds, $originalPageIds);
                    if (!empty($missingInOriginal)) {
                        error_log("Export HTML Validation: page_id in data.js but not in original data: " . implode(', ', $missingInOriginal));
                    }
                }
            }
        }
    }
}
