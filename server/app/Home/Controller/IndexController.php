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
        // 使用相对路径，与旧版保持一致（参考旧版逻辑）
        // 不存在安装文件夹的，表示已经安装过
        if (!file_exists("./install")) {
            return $response
                ->withHeader('Location', './web/#/')
                ->withStatus(302);
        }

        // 如果 install 存在 && install.lock 存在 && install 可写 && install.lock 可写
        if (file_exists("./install") && file_exists("./install/install.lock") && $this->newIsWriteable("./install") && $this->newIsWriteable("./install/install.lock")) {
            return $response
                ->withHeader('Location', './web/#/')
                ->withStatus(302);
        }
        
        // 其他情况都跳转到安装页面
        return $response
            ->withHeader('Location', './install/index.php')
            ->withStatus(302);
    }

    /**
     * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
     *
     * @param string $file 文件/目录
     * @return boolean
     */
    private function newIsWriteable($file)
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        }

        return $writeable;
    }
}

