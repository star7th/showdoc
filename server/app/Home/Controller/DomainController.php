<?php

namespace App\Home\Controller;

use App\Common\BaseController;
use App\Model\Item;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DomainController extends BaseController
{
    // 通过个性域名访问项目：/{domain}
    public function show(Request $request, Response $response): Response
    {
        $domain = (string) $this->getParam($request, 'domain', '');
        $domain = trim($domain);
        if ($domain === '') {
            return $this->fallbackWeb($response);
        }

        $item = Item::findByDomain($domain);
        if (!$item) {
            return $this->fallbackWeb($response);
        }

        // 将 item_id 注入 attribute，复用 ItemController 的逻辑
        $request = $request->withAttribute('item_id', (int) $item->item_id);
        $controller = new ItemController();
        return $controller->show($request, $response);
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

