<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\TeamItem;
use App\Model\TeamMember;
use App\Model\TeamItemMember;
use App\Model\Team;
use App\Model\ItemChangeLog;
use App\Common\Helper\Security;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 团队项目相关 Api（新架构）。
 */
class TeamItemController extends BaseController
{
    /**
     * 添加和编辑团队项目关联（兼容旧接口 Api/TeamItem/save）。
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemIdStr = $this->getParam($request, 'item_id', '');
        $teamId = $this->getParam($request, 'team_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($teamId <= 0 || empty($itemIdStr)) {
            return $this->error($response, 10101, '参数错误');
        }

        if (!$this->checkTeamManage($uid, $teamId)) {
            return $this->error($response, 10103, '没有权限');
        }

        $team = Team::findById($teamId);
        if (!$team) {
            return $this->error($response, 10101, '团队不存在');
        }

        // 转义并分割项目ID
        $itemIdStr = Security::safeLike($itemIdStr, false);
        $itemIdArray = explode(',', $itemIdStr);

        $return = [];
        $lastId = 0;

        foreach ($itemIdArray as $value) {
            $itemId = (int) trim($value);
            if ($itemId <= 0) {
                continue;
            }

            if (!$this->checkItemManage($uid, $itemId)) {
                return $this->error($response, 10303, "项目 {$itemId} 没有权限");
            }

            // 如果该项目已经加入团队了，跳过
            if (TeamItem::exists($teamId, $itemId)) {
                continue;
            }

            // 添加团队项目关联
            $data = [
                'item_id' => $itemId,
                'team_id' => $teamId,
                'addtime' => time(),
            ];
            $id = TeamItem::add($data);
            if ($id) {
                $lastId = $id;

                // 记录变更日志
                ItemChangeLog::addLog(
                    $uid,
                    $itemId,
                    'binding',
                    'team',
                    $teamId,
                    $team->team_name ?? ''
                );

                // 获取该团队的所有成员并加入项目
                $teamMembers = TeamMember::getMemberUidsByTeamId($teamId);
                if (!empty($teamMembers)) {
                    foreach ($teamMembers as $member) {
                        $memberUid = (int) ($member['member_uid'] ?? 0);
                        $memberUsername = $member['member_username'] ?? '';

                        $memberData = [
                            'team_id'        => $teamId,
                            'member_uid'     => $memberUid,
                            'member_username' => $memberUsername,
                            'item_id'        => $itemId,
                            'member_group_id' => 1, // 默认添加的权限为1，即编辑权限
                            'addtime'        => time(),
                        ];
                        TeamItemMember::add($memberData);
                    }
                }
            }
        }

        if (!$lastId) {
            return $this->error($response, 10103, '添加失败');
        }

        return $this->success($response, ['id' => $lastId]);
    }

    /**
     * 根据项目来获取其绑定的团队列表（兼容旧接口 Api/TeamItem/getList）。
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        $ret = TeamItem::getListByItemId($itemId);
        if (!empty($ret)) {
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }

    /**
     * 根据团队来获取项目列表（兼容旧接口 Api/TeamItem/getListByTeam）。
     */
    public function getListByTeam(Request $request, Response $response): Response
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

        $ret = TeamItem::getListByTeamId($teamId);
        if (!empty($ret)) {
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }

    /**
     * 删除团队项目关联（兼容旧接口 Api/TeamItem/delete）。
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

        $teamItem = TeamItem::findById($id);
        if (!$teamItem) {
            return $this->error($response, 10101, '关联不存在');
        }

        $itemId = (int) ($teamItem->item_id ?? 0);
        $teamId = (int) ($teamItem->team_id ?? 0);

        if (!$this->checkTeamManage($uid, $teamId)) {
            return $this->error($response, 10103, '没有权限');
        }

        // 删除团队项目成员关联
        TeamItemMember::deleteByItemIdAndTeamId($itemId, $teamId);

        // 删除团队项目关联
        $ret = TeamItem::delete($id);
        if ($ret) {
            // 记录变更日志
            $team = Team::findById($teamId);
            if ($team) {
                ItemChangeLog::addLog(
                    $uid,
                    $itemId,
                    'unbound',
                    'team',
                    $teamId,
                    $team->team_name ?? ''
                );
            }

            return $this->success($response, ['success' => true]);
        }

        return $this->error($response, 10103, '删除失败');
    }
}
