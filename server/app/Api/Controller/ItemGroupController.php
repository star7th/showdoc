<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\ItemGroup;
use App\Model\ItemSort;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 项目分组相关 Api（新架构）。
 */
class ItemGroupController extends BaseController
{
    /**
     * 添加和编辑项目组（兼容旧接口 Api/ItemGroup/save）。
     */
    public function save(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $groupName = $this->getParam($request, 'group_name', '');
        $itemIds = $this->getParam($request, 'item_ids', '');
        $id = $this->getParam($request, 'id', 0);
        $uid = (int) ($loginUser['uid'] ?? 0);

        if (empty($groupName)) {
            return $this->error($response, 10101, '分组名称不能为空');
        }

        if ($id > 0) {
            // 更新现有分组（仅允许当前用户自己的分组）
            $group = ItemGroup::findById($id);
            if (!$group || (int) ($group->uid ?? 0) !== $uid) {
                return $this->error($response, 10103, '没有权限');
            }

            $affected = DB::table('item_group')
                ->where('id', $id)
                ->where('uid', $uid)
                ->update([
                    'group_name' => $groupName,
                    'item_ids'   => $itemIds,
                ]);

            if ($affected <= 0) {
                return $this->error($response, 10103, '更新失败');
            }

            $finalId = $id;
        } else {
            // 创建新分组（内部会自动写入 uid/时间字段，行为与旧版保持一致）
            $finalId = ItemGroup::add($uid, $groupName, $itemIds);
            if (!$finalId) {
                return $this->error($response, 10103, '创建失败');
            }
        }

        // 延迟 200ms（兼容旧代码）
        usleep(200000);

        $return = ItemGroup::findById($finalId);
        if (!$return) {
            return $this->error($response, 10103, '获取分组信息失败');
        }

        $data = (array) $return;
        unset($data['uid']);

        return $this->success($response, $data);
    }

    /**
     * 获取项目组列表（兼容旧接口 Api/ItemGroup/getList）。
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

        $ret = ItemGroup::getList($uid);
        if (!empty($ret)) {
            return $this->success($response, $ret);
        }

        return $this->success($response, []);
    }

    /**
     * 删除项目组（兼容旧接口 Api/ItemGroup/delete）。
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

        if ($id <= 0 || $uid <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 验证分组是否属于当前用户
        $group = ItemGroup::findById($id);
        if (!$group || (int) ($group->uid ?? 0) !== $uid) {
            return $this->error($response, 10101, '没有权限');
        }

        // 删除分组及其关联的排序记录
        ItemGroup::delete($id, $uid);

        return $this->success($response, ['success' => true]);
    }

    /**
     * 保存项目组顺序（兼容旧接口 Api/ItemGroup/saveSort）。
     */
    public function saveSort(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $groups = $this->getParam($request, 'groups', '');
        $uid = (int) ($loginUser['uid'] ?? 0);

        if (empty($groups)) {
            return $this->error($response, 10101, '参数错误');
        }

        $dataArray = json_decode(htmlspecialchars_decode($groups), true);
        if ($dataArray) {
            foreach ($dataArray as $value) {
                $id = (int) ($value['id'] ?? 0);
                $sNumber = (int) ($value['s_number'] ?? 0);

                if ($id > 0) {
                    // 验证分组是否属于当前用户
                    $group = ItemGroup::findById($id);
                    if ($group && (int) ($group->uid ?? 0) === $uid) {
                        DB::table('item_group')
                            ->where('id', $id)
                            ->where('uid', $uid)
                            ->update(['s_number' => $sNumber]);
                    }
                }
            }
        }

        return $this->success($response, []);
    }
}
