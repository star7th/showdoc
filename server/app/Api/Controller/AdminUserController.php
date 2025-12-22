<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\User;
use App\Model\Item;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 管理后台用户相关 Api（开源版）
 */
class AdminUserController extends BaseController
{
    /**
     * 获取所有用户列表（兼容旧接口 Api/AdminUser/getList）
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 20);
        $username = $this->getParam($request, 'username', '');

        $query = DB::table('user');

        if (!empty($username)) {
            $like = $this->safeLike($username);
            $query->where(function ($q) use ($like) {
                $q->where('uid', 'like', "%{$like}%")
                    ->orWhere('username', 'like', "%{$like}%")
                    ->orWhere('email', 'like', "%{$like}%")
                    ->orWhere('mobile', 'like', "%{$like}%");
            });
        }

        $total = $query->count();
        $users = $query->orderBy('uid', 'desc')
            ->offset(($page - 1) * $count)
            ->limit($count)
            ->get();

        $return = [];
        $return['total'] = (int) $total;
        $return['users'] = [];

        if ($users) {
            foreach ($users as $user) {
                $userData = (array) $user;
                $userData['reg_time'] = date("Y-m-d H:i:s", $userData['reg_time']);
                
                if ($userData['last_login_time']) {
                    $userData['last_login_time'] = date("Y-m-d H:i:s", $userData['last_login_time']);
                } else {
                    $userData['last_login_time'] = '';
                }

                $return['users'][] = $userData;
            }
        }

        return $this->success($response, $return);
    }

    /**
     * 删除用户
     */
    public function deleteUser(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $uid = $this->getParam($request, 'uid', 0);
        if ($uid <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 检查用户是否还有项目
        $hasItem = DB::table('item')
            ->where('uid', $uid)
            ->where('is_del', 0)
            ->first();

        if ($hasItem) {
            return $this->error($response, 10101, "该用户名下还有项目，不允许删除。请先将其项目删除或者重新分配/转让");
        }

        $result = User::deleteUser($uid);
        if (!$result) {
            return $this->error($response, 10101, '删除失败');
        }

        return $this->success($response, []);
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $uid = $this->getParam($request, 'uid', 0);
        $newPassword = $this->getParam($request, 'new_password', '');

        if ($uid <= 0 || empty($newPassword)) {
            return $this->error($response, 10101, '参数错误');
        }

        $result = User::updatePwd($uid, $newPassword);
        if (!$result) {
            return $this->error($response, 10101, '修改失败');
        }

        return $this->success($response, []);
    }

    /**
     * 新增用户
     */
    public function addUser(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $username = $this->getParam($request, 'username', '');
        $password = $this->getParam($request, 'password', '');
        $uid = $this->getParam($request, 'uid', 0);
        $name = $this->getParam($request, 'name', '');

        if (empty($username)) {
            return $this->error($response, 10101, '用户名不允许为空');
        }

        if ($uid) {
            // 更新用户
            if ($password) {
                User::updatePwd($uid, $password);
            }
            if ($name) {
                DB::table('user')->where('uid', $uid)->update(['name' => $name]);
            }
            return $this->success($response, []);
        } else {
            // 新增用户
            if (User::isExist($username)) {
                return $this->error($response, 10101, '用户名已存在');
            }

            $newUid = User::register($username, $password);
            if (!$newUid) {
                return $this->error($response, 10101, '注册失败');
            }

            if ($name) {
                DB::table('user')->where('uid', $newUid)->update(['name' => $name]);
            }

            return $this->success($response, []);
        }
    }
}

