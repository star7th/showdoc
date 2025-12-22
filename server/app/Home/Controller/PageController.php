<?php

namespace App\Home\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Model\Page;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PageController extends BaseController
{
    public function show(Request $request, Response $response): Response
    {
        $pageId = (int) $this->getParam($request, 'page_id');
        if ($pageId <= 0) {
            return $this->fallbackWeb($response);
        }

        // 从主 page 表获取 item_id
        $pageRow = DB::table('page')
            ->where('page_id', $pageId)
            ->where('is_del', 0)
            ->first();

        if (!$pageRow) {
            return $this->fallbackWeb($response);
        }

        $itemId = (int) $pageRow->item_id;

        $item = Item::findById($itemId);
        if (!$item || !empty($item->password)) {
            return $this->fallbackWeb($response);
        }

        $page = Page::findByIdWithContent($itemId, $pageId);
        if (!$page) {
            return $this->fallbackWeb($response);
        }

        $content = (string) ($page['page_content'] ?? '');

        // 引入 Parsedown 类（Markdown 解析器）
        if (!class_exists('Parsedown')) {
            require_once __DIR__ . '/../../Common/Vendor/Parsedown.php';
        }
        $parsedown = new \Parsedown();
        $pageHtml  = $parsedown->text($content);
        $pageHtml  = htmlspecialchars_decode($pageHtml, ENT_QUOTES, 'UTF-8');

        $html  = '';
        $html .= '<h2>' . htmlspecialchars($item->item_name ?? '', ENT_QUOTES, 'UTF-8') . '</h2>';
        $html .= '<p>' . htmlspecialchars($item->item_description ?? '', ENT_QUOTES, 'UTF-8') . '</p><br>';
        $html .= '<article><h1>' . htmlspecialchars($page['page_title'] ?? '', ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<div>' . $pageHtml . '</div></article>';

        $rootPath = dirname(__DIR__, 4);
        $tplPath  = $rootPath . '/web.html';
        if (!is_file($tplPath)) {
            return $this->fallbackWeb($response);
        }

        $tpl  = file_get_contents($tplPath);
        $html = str_replace('INDEX_HTML', $html, $tpl);

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

