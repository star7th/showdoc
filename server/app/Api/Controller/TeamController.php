<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 团队管理相关 Api（新架构）。
 */
class TeamController extends BaseController
{
    /**
     * 添加和编辑团队（兼容旧接口 Api/Team/save）。
     *
     * 功能：
     * - 支持新建和更新团队
     * - 更新时需要检查团队管理权限
     */
    public function save(Request $request, Response $response): Response
    {
        $teamName = $this->getParam($request, 'team_name', '');
        $id       = $this->getParam($request, 'id', 0);

        if (empty($teamName)) {
            return $this->error($response, 10101, '团队名称不能为空');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid      = (int) ($loginUser['uid'] ?? 0);
        $username = (string) ($loginUser['username'] ?? '');

        // 如果是更新，检查管理权限
        if ($id > 0) {
            if (!$this->checkTeamManage($uid, $id)) {
                return $this->error($response, 10103, '您没有管理权限');
            }
        }

        // 保存团队
        $teamId = \App\Model\Team::save($id, $uid, $username, $teamName);

        if ($teamId <= 0) {
            return $this->error($response, 10103, '操作失败');
        }

        // 等待一下，确保数据已写入
        usleep(500000);

        // 获取保存后的团队信息
        $return = \App\Model\Team::findById($teamId);
        if (!$return) {
            return $this->error($response, 10103, '操作失败');
        }

        return $this->success($response, (array) $return);
    }

    /**
     * 获取团队列表（兼容旧接口 Api/Team/getList）。
     *
     * 功能：
     * - 获取当前用户创建的和参与的团队列表
     * - 包含团队成员数和项目数
     * - 包含管理权限标识
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($uid <= 0) {
            return $this->success($response, []);
        }

        // 获取团队列表
        $ret = \App\Model\Team::getList($uid);

        // 添加管理权限标识
        foreach ($ret as $key => &$value) {
            $teamId = (int) ($value['id'] ?? 0);
            $value['team_manage'] = $this->checkTeamManage($uid, $teamId) ? 1 : 0;
        }

        return $this->success($response, $ret ?: []);
    }

    /**
     * 删除团队（兼容旧接口 Api/Team/delete）。
     *
     * 功能：
     * - 只有团队创建者才能删除
     * - 删除团队及其关联数据
     */
    public function delete(Request $request, Response $response): Response
    {
        $id = $this->getParam($request, 'id', 0);

        if ($id <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 删除团队
        $ret = \App\Model\Team::delete($id, $uid);

        if (!$ret) {
            return $this->error($response, 10103, '删除失败');
        }

        return $this->success($response, ['success' => true]);
    }

    /**
     * 转让团队（兼容旧接口 Api/Team/attorn）。
     *
     * 功能：
     * - 只有团队创建者才能转让
     * - 需要验证密码
     * - 转让团队及其关联的项目
     */
    public function attorn(Request $request, Response $response): Response
    {
        $username = $this->getParam($request, 'username', '');
        $teamId   = $this->getParam($request, 'team_id', 0);
        $password = $this->getParam($request, 'password', '');

        if ($teamId <= 0 || empty($username) || empty($password)) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查是否是团队创建者
        $team = \App\Model\Team::findById($teamId, $uid);
        if (!$team) {
            return $this->error($response, 10101, '团队不存在或您没有权限');
        }

        // 验证密码
        $loginUsername = (string) ($loginUser['username'] ?? '');
        $user = \App\Model\User::checkLogin($loginUsername, $password);
        if (!$user) {
            return $this->error($response, 10208, '密码错误');
        }

        // 查找目标用户
        $member = \App\Model\User::findByUsernameOrEmail($username);
        if (!$member) {
            return $this->error($response, 10209, '用户不存在');
        }

        // 转让团队
        $success = \App\Model\Team::attorn(
            $teamId,
            $uid,
            (int) $member->uid,
            $member->username ?? ''
        );

        if (!$success) {
            return $this->error($response, 10103, '转让失败');
        }

        return $this->success($response, []);
    }

    /**
     * 退出团队（兼容旧接口 Api/Team/exitTeam）。
     *
     * 功能：
     * - 用户主动退出团队
     * - 删除团队成员关系和项目成员关系
     */
    public function exitTeam(Request $request, Response $response): Response
    {
        $id = $this->getParam($request, 'id', 0);

        if ($id <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 退出团队
        $success = \App\Model\Team::exitTeam($id, $uid);

        if (!$success) {
            return $this->error($response, 10103, '操作失败');
        }

        return $this->success($response, []);
    }
}
