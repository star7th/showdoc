<?php

namespace App\Home\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UserController extends BaseController
{
    // 暂时保持与旧版类似行为：所有 /uid/{username} 直接交给前端应用处理
    public function profile(Request $request, Response $response): Response
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

