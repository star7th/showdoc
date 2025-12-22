<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\TeamMember;
use App\Model\TeamItem;
use App\Model\TeamItemMember;
use App\Model\Team;
use App\Model\User;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 团队成员相关 Api（新架构）。
 */
class TeamMemberController extends BaseController
{
    /**
     * 添加和编辑团队成员（兼容旧接口 Api/TeamMember/save）。
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $memberUsername = $this->getParam($request, 'member_username', '');
        $teamId = $this->getParam($request, 'team_id', 0);
        $teamMemberGroupId = $this->getParam($request, 'team_member_group_id', 1);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($teamId <= 0 || empty($memberUsername)) {
            return $this->error($response, 10101, '参数错误');
        }

        if (!$this->checkTeamManage($uid, $teamId)) {
            return $this->error($response, 10103, '没有权限');
        }

        // 开源版无团队成员数量限制
        $count = User::getMemberCount($uid);

        // 查找成员用户
        $memberInfo = User::findByUsernameOrEmail($memberUsername);
        if (!$memberInfo) {
            return $this->error($response, 10209, '用户不存在');
        }

        $memberUid = (int) ($memberInfo->uid ?? 0);

        // 检查是否已经是成员
        if (TeamMember::exists($teamId, $memberUid)) {
            return $this->error($response, 10101, '该用户已经是成员');
        }

        // 添加团队成员
        $data = [
            'team_id'            => $teamId,
            'member_uid'         => $memberUid,
            'member_username'    => $memberInfo->username ?? '',
            'team_member_group_id' => $teamMemberGroupId,
            'addtime'            => time(),
        ];
        $id = TeamMember::add($data);
        if (!$id) {
            return $this->error($response, 10103, '添加失败');
        }

        // 检查该团队已经加入了哪些项目，自动添加成员到这些项目
        $teamItems = TeamItem::getItemIdsByTeamId($teamId);
        if (!empty($teamItems)) {
            foreach ($teamItems as $itemId) {
                $memberData = [
                    'team_id'        => $teamId,
                    'member_uid'     => $memberUid,
                    'member_username' => $memberInfo->username ?? '',
                    'item_id'        => $itemId,
                    'member_group_id' => 1, // 默认添加的权限为1，即编辑权限
                    'addtime'        => time(),
                ];
                TeamItemMember::add($memberData);
            }
        }

        return $this->success($response, ['id' => $id]);
    }

    /**
     * 获取团队成员列表（兼容旧接口 Api/TeamMember/getList）。
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $teamId = $this->getParam($request, 'team_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($teamId <= 0) {
            return $this->error($response, 10101, '团队ID不能为空');
        }

        // 权限判断。团队管理者和团队成员可以看到该列表
        if (!$this->checkTeamManage($uid, $teamId)) {
            $member = DB::table('team_member')
                ->where('member_uid', $uid)
                ->where('team_id', $teamId)
                ->first();
            if (!$member) {
                return $this->error($response, 10103, '没有权限');
            }
        }

        $ret = TeamMember::getListByTeamId($teamId);
        if (!empty($ret)) {
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }

    /**
     * 删除团队成员（兼容旧接口 Api/TeamMember/delete）。
     */
    public function delete(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $id = $this->getParam($request, 'id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($id <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $teamMember = TeamMember::findById($id);
        if (!$teamMember) {
            return $this->error($response, 10101, '成员不存在');
        }

        $teamId = (int) ($teamMember->team_id ?? 0);
        $memberUid = (int) ($teamMember->member_uid ?? 0);

        if (!$this->checkTeamManage($uid, $teamId)) {
            return $this->error($response, 10103, '没有权限');
        }

        // 删除团队项目成员关联
        TeamItemMember::deleteByMemberUidAndTeamId($memberUid, $teamId);

        // 删除团队成员
        $ret = TeamMember::delete($id);
        if ($ret) {
            return $this->success($response, ['success' => true]);
        }

        return $this->error($response, 10103, '删除失败');
    }
}
