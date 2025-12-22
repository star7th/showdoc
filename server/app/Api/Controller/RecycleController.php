<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 回收站相关 Api（新架构）。
 */
class RecycleController extends BaseController
{
    /**
     * 获取被删除的页面列表（兼容旧接口 Api/Recycle/getList）。
     *
     * 功能：
     * - 获取项目的回收站列表
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

        // 检查管理权限
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 获取回收站列表
        $ret = \App\Model\Recycle::getList($itemId);

        return $this->success($response, $ret ?: []);
    }

    /**
     * 恢复页面（兼容旧接口 Api/Recycle/recover）。
     *
     * 功能：
     * - 恢复被删除的页面
     * - 权限检查（checkItemManage）
     * - 删除菜单和页面缓存
     */
    public function recover(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($itemId <= 0 || $pageId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理权限
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10303, '您没有管理权限');
        }

        // 恢复页面（更新 is_del 为 0，cat_id 置 0，与旧版逻辑一致）
        DB::table('page')
            ->where('page_id', $pageId)
            ->where('item_id', $itemId)
            ->update([
                'is_del' => 0,
                'cat_id' => 0,
            ]);

        // 删除回收站记录
        \App\Model\Recycle::delete($itemId, $pageId);

        // 删除缓存
        \App\Model\Item::deleteCache($itemId);
        \App\Model\Page::deleteCache($pageId);

        return $this->success($response, []);
    }
}
