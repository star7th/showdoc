<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 成员管理相关 Api（新架构）。
 */
class MemberController extends BaseController
{
    /**
     * 保存成员（添加项目成员）（兼容旧接口 Api/Member/save）。
     *
     * 功能：
     * - 添加项目成员
     * - 权限检查（checkItemManage）
     * - 支持单目录和多目录权限
     */
    public function save(Request $request, Response $response): Response
    {
        $memberGroupId = $this->getParam($request, 'member_group_id', 0);
        $itemId        = $this->getParam($request, 'item_id', 0);
        $catId         = $this->getParam($request, 'cat_id', 0);
        $catIds        = $this->getParam($request, 'cat_ids', '');
        $username      = trim($this->getParam($request, 'username', ''));

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理权限（项目管理权限或系统管理员权限）
        if (!$this->checkItemManage($uid, $itemId) && !$this->checkAdmin($request, $response, false)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 开源版无成员数量限制

        if ($username === '') {
            return $this->error($response, 10209, '用户不存在');
        }

        // 兼容旧版：支持逗号分隔的一批用户名
        $usernameArray = array_filter(array_map('trim', explode(',', $username)));
        $lastId        = 0;

        // 预先解析多目录权限（cat_ids），以便重复使用
        $catIdsField = '';
        if (!empty($catIds)) {
            $ids = [];
            if (is_array($catIds)) {
                $ids = $catIds;
            } else {
                // 优先尝试 JSON
                $tmp = json_decode($catIds, true);
                if (is_array($tmp)) {
                    $ids = $tmp;
                } elseif (is_string($catIds)) {
                    // 兼容 form 提交的逗号分隔字符串
                    if (strpos($catIds, ',') !== false) {
                        $ids = preg_split('/\s*,\s*/', trim($catIds));
                    } elseif (ctype_digit($catIds)) {
                        // 单个数字字符串
                        $ids = [(int) $catIds];
                    }
                }
            }

            // 过滤非法并校验每个目录必须为该项目的 level=2
            $ids2 = [];
            if (!empty($ids)) {
                foreach ($ids as $v) {
                    $v = (int) $v;
                    if ($v <= 0) {
                        continue;
                    }
                    $cat = \App\Model\Catalog::findByIdAndItemId($v, $itemId);
                    if ($cat && (int) ($cat->level ?? 0) === 2) {
                        $ids2[] = $v;
                    }
                }
                $ids2 = array_values(array_unique($ids2));
            }

            if (!empty($ids2)) {
                // 统一以逗号分隔字符串存储
                $catIdsField = implode(',', $ids2);
            }
        }

        foreach ($usernameArray as $name) {
            // 查找用户（支持用户名或邮箱）
            $member = \App\Model\User::findByUsernameOrEmail($name);
            if (!$member) {
                // 与旧版一致：找不到用户则跳过
                continue;
            }

            // 检查是否已经是成员
            $existing = \App\Model\ItemMember::findByUidAndItemId((int) $member->uid, $itemId);
            if ($existing) {
                // 与旧版一致：已是成员则跳过
                continue;
            }

            // 准备数据
            $data = [
                'username'        => $member->username ?? '',
                'uid'             => (int) $member->uid,
                'item_id'         => $itemId,
                'member_group_id' => $memberGroupId,
                'cat_id'          => $catId,
                'addtime'         => time(),
            ];

            if ($catIdsField !== '') {
                $data['cat_ids'] = $catIdsField;
            }

            // 添加成员
            $id = \App\Model\ItemMember::add($data);
            if ($id <= 0) {
                // 某个成员失败时，继续处理其他成员
                continue;
            }

            $lastId = $id;

            // 记录变更日志
            \App\Model\ItemChangeLog::addLog($uid, $itemId, 'binding', 'member', (int) $member->uid, $member->username ?? '');
        }

        if ($lastId <= 0) {
            return $this->error($response, 10101, '添加成员失败');
        }

        // 为兼容新前端，继续只返回最后一个成功的 id
        return $this->success($response, ['id' => $lastId]);
    }

    /**
     * 获取成员列表（兼容旧接口 Api/Member/getList）。
     *
     * 功能：
     * - 获取项目的成员列表
     * - 权限检查（checkItemManage）
     */
    public function getList(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->success($response, []);
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理权限（项目管理权限或系统管理员权限）
        if (!$this->checkItemManage($uid, $itemId) && !$this->checkAdmin($request, $response, false)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 获取成员列表
        $ret = \App\Model\ItemMember::getList($itemId);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 删除成员（兼容旧接口 Api/Member/delete）。
     *
     * 功能：
     * - 删除项目成员
     * - 权限检查（checkItemManage）
     */
    public function delete(Request $request, Response $response): Response
    {
        $itemId       = $this->getParam($request, 'item_id', 0);
        $itemMemberId = $this->getParam($request, 'item_member_id', 0);

        if ($itemId <= 0 || $itemMemberId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理权限（项目管理权限或系统管理员权限）
        if (!$this->checkItemManage($uid, $itemId) && !$this->checkAdmin($request, $response, false)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 删除成员
        $member = \App\Model\ItemMember::delete($itemId, $itemMemberId);
        if (!$member) {
            return $this->error($response, 10101, '删除失败');
        }

        // 记录变更日志
        \App\Model\ItemChangeLog::addLog($uid, $itemId, 'unbound', 'member', (int) ($member['uid'] ?? 0), $member['username'] ?? '');

        return $this->success($response, ['success' => true]);
    }

    /**
     * 获取项目的所有成员列表（包括单独成员和绑定的团队成员）（兼容旧接口 Api/Member/getAllList）。
     *
     * 功能：
     * - 获取项目的所有成员（包括项目创建者、单独成员、团队成员）
     * - 权限检查（checkItemEdit）
     */
    public function getAllList(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);

        if ($itemId <= 0) {
            return $this->success($response, []);
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查编辑权限（项目编辑权限或系统管理员权限）
        if (!$this->checkItemEdit($uid, $itemId) && !$this->checkAdmin($request, $response, false)) {
            return $this->error($response, 10301, '您没有编辑权限');
        }

        // 获取所有成员列表
        $ret = \App\Model\ItemMember::getAllList($itemId);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 获取当前登录用户参与的所有项目成员信息（兼容旧接口 Api/Member/getMyAllList）。
     *
     * 功能：
     * - 聚合当前用户创建的所有项目成员（item_member）
     * - 聚合当前用户创建的所有团队成员（team_member）
     * - 对 username 进行去重，并按 addtime 倒序排序
     *
     * 说明：
     * - 仅依赖 Illuminate 查询构造器，不再执行原始 SQL 字符串拼接。
     */
    public function getMyAllList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if ($uid <= 0) {
            return $this->error($response, 10101, '用户未登录');
        }

        // 当前用户创建的项目的单独成员
        $itemMembers = DB::table('item_member')
            ->leftJoin('item', 'item_member.item_id', '=', 'item.item_id')
            ->where('item.uid', $uid)
            ->select([
                'item_member.uid',
                'item_member.username',
                'item_member.addtime',
            ])
            ->get()
            ->all();

        // 当前用户创建的团队的成员
        $teamMembers = DB::table('team_member')
            ->leftJoin('team', 'team_member.team_id', '=', 'team.id')
            ->where('team.uid', $uid)
            ->select([
                'team_member.member_uid as uid',
                'team_member.member_username as username',
                'team_member.addtime',
            ])
            ->get()
            ->all();

        $rows = [];
        foreach ($itemMembers as $row) {
            $rows[] = [
                'uid'      => (int) ($row->uid ?? 0),
                'username' => (string) ($row->username ?? ''),
                'addtime'  => (int) ($row->addtime ?? 0),
            ];
        }
        foreach ($teamMembers as $row) {
            $rows[] = [
                'uid'      => (int) ($row->uid ?? 0),
                'username' => (string) ($row->username ?? ''),
                'addtime'  => (int) ($row->addtime ?? 0),
            ];
        }

        if (empty($rows)) {
            return $this->success($response, []);
        }

        // 先按 addtime 倒序排序
        usort($rows, function (array $a, array $b): int {
            return ($b['addtime'] ?? 0) <=> ($a['addtime'] ?? 0);
        });

        // 按 uid + username 去重，保留最近的记录
        $seen = [];
        $result = [];
        foreach ($rows as $row) {
            $key = $row['uid'] . '|' . $row['username'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            // 返回结构与旧接口一致：uid, username, addtime（时间戳）
            $result[] = [
                'uid'      => $row['uid'],
                'username' => $row['username'],
                'addtime'  => $row['addtime'],
            ];
        }

        return $this->success($response, $result);
    }
}
