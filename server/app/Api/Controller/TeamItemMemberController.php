<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\TeamItemMember;
use App\Model\Team;
use App\Model\Catalog;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 团队项目成员相关 Api（新架构）。
 */
class TeamItemMemberController extends BaseController
{
    /**
     * 添加和编辑团队项目成员（兼容旧接口 Api/TeamItemMember/save）。
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $id = $this->getParam($request, 'id', 0);
        $memberGroupId = $this->getParam($request, 'member_group_id', 0);
        $catId = $this->getParam($request, 'cat_id', 0);
        $catIds = $this->getParam($request, 'cat_ids', '');
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($id <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $teamItemMember = TeamItemMember::findById($id);
        if (!$teamItemMember) {
            return $this->error($response, 10101, '成员不存在');
        }

        $itemId = (int) ($teamItemMember->item_id ?? 0);
        $teamId = (int) ($teamItemMember->team_id ?? 0);

        if (!$this->checkTeamManage($uid, $teamId)) {
            return $this->error($response, 10103, '没有权限');
        }

        $updateData = [];

        // 更新成员权限
        if ($request->getParsedBody() && array_key_exists('member_group_id', $request->getParsedBody())) {
            $updateData['member_group_id'] = $memberGroupId;
        }

        // 更新单目录（向后兼容）
        if ($request->getParsedBody() && array_key_exists('cat_id', $request->getParsedBody())) {
            $updateData['cat_id'] = $catId;
        }

        // 更新多目录（仅根下一层）
        if ($request->getParsedBody() && array_key_exists('cat_ids', $request->getParsedBody())) {
            $ids = [];
            if (is_array($catIds)) {
                $ids = $catIds;
            } else {
                $tmp = json_decode(htmlspecialchars_decode($catIds), true);
                if (is_array($tmp)) {
                    $ids = $tmp;
                } elseif (is_string($catIds)) {
                    if (strpos($catIds, ',') !== false) {
                        $ids = preg_split('/\s*,\s*/', trim($catIds));
                    } elseif (ctype_digit($catIds)) {
                        $ids = [(int) $catIds];
                    }
                }
            }

            // 过滤非法与去重
            $ids2 = [];
            if (!empty($ids)) {
                // 校验每个目录必须为该项目的 level=2
                foreach ($ids as $v) {
                    $v = (int) $v;
                    if ($v <= 0) {
                        continue;
                    }
                    $cat = DB::table('catalog')
                        ->where('cat_id', $v)
                        ->where('item_id', $itemId)
                        ->where('level', 2)
                        ->first();
                    if ($cat) {
                        $ids2[] = $v;
                    }
                }
                $ids2 = array_values(array_unique($ids2));
            }

            // 统一以逗号分隔字符串存储
            $updateData['cat_ids'] = !empty($ids2) ? implode(',', $ids2) : '';
        }

        if (!empty($updateData)) {
            $ret = TeamItemMember::update($id, $updateData);
            return $this->success($response, ['success' => $ret]);
        }

        return $this->success($response, []);
    }

    /**
     * 获取团队项目成员列表（兼容旧接口 Api/TeamItemMember/getList）。
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = $this->getParam($request, 'item_id', 0);
        $teamId = $this->getParam($request, 'team_id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if ($itemId <= 0 || $teamId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        if (!$this->checkTeamManage($uid, $teamId)) {
            return $this->error($response, 10103, '没有权限');
        }

        $ret = TeamItemMember::getListByItemIdAndTeamId($itemId, $teamId);
        if (!empty($ret)) {
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }
}
