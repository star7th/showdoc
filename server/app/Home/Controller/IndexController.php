<?php

namespace App\Home\Controller;

use App\Common\BaseController;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class IndexController extends BaseController
{
    public function index(Request $request, Response $response): Response
    {
        // 计算站点基础路径（脚本所在目录），避免相对路径在深层路径下重定向失效
        $webBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/server/index.php')), '/');

        // 不存在安装文件夹的，表示已经安装过
        if (!file_exists("./install")) {
            return $response
                ->withHeader('Location', $webBase . '/web/#/')
                ->withStatus(302);
        }

        // 存在安装锁文件的，表示已经安装过。
        // 不依赖目录可写性：部署后常会把 install 目录设为只读加固，此时应视为已安装。
        if (file_exists("./install/install.lock")) {
            return $response
                ->withHeader('Location', $webBase . '/web/#/')
                ->withStatus(302);
        }

        // 其他情况都跳转到安装页面
        return $response
            ->withHeader('Location', $webBase . '/install/index.php')
            ->withStatus(302);
    }
}

