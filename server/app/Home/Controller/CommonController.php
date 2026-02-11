<?php

namespace App\Home\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Helper\Security;

class CommonController extends BaseController
{
    /**
     * 重置管理员用户密码
     * 
     * 使用方式：
     * php ../showdoc/index.php home/common/repasswd
     * 
     * 执行后会把管理员用户 showdoc 的密码重置为 123456
     */
    public function repasswd(Request $request, Response $response): Response
    {
        // 只允许在 CLI 模式下运行
        if (PHP_SAPI !== 'cli') {
            $response->getBody()->write("please run in command line");
            return $response->withStatus(403);
        }

        $username = 'showdoc';
        $password = '123456';

        // 查找用户
        $user = DB::table('user')->where('username', $username)->first();

        if ($user) {
            // 更新现有用户
            $salt = Security::generateSalt();
            $hashedPassword = Security::hashPassword($password, $salt);

            DB::table('user')
                ->where('username', $username)
                ->update([
                    'groupid' => 1,
                    'password' => $hashedPassword,
                    'salt' => $salt,
                ]);
        } else {
            // 创建新用户
            $salt = Security::generateSalt();
            $hashedPassword = Security::hashPassword($password, $salt);

            DB::table('user')->insert([
                'username' => $username,
                'groupid' => 1,
                'password' => $hashedPassword,
                'salt' => $salt,
                'reg_time' => time(),
            ]);
        }

        $response->getBody()->write("ok\n");
        return $response;
    }
}
