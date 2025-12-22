<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * 订阅相关 Api（新架构）。
 */
class SubscriptionController extends BaseController
{
    /**
     * 获取页面的订阅人员列表（兼容旧接口 Api/Subscription/getPageList）。
     *
     * 功能：
     * - 获取页面的订阅人员列表
     * - 权限检查（checkItemEdit）
     */
    public function getPageList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($pageId <= 0) {
            return $this->success($response, []);
        }

        // 获取页面信息
        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        // 获取订阅列表
        $subscriptionArray = \App\Model\Subscription::getListByObjectId($pageId, 'page', 'update');
        $subscriptionArray = $subscriptionArray ?: [];

        // 填充用户信息
        foreach ($subscriptionArray as $key => $value) {
            $user = \App\Model\User::findById((int) ($value['uid'] ?? 0));
            if ($user) {
                $subscriptionArray[$key]['username'] = $user->username ?? '';
                $subscriptionArray[$key]['name'] = $user->name ?? '';
            } else {
                $subscriptionArray[$key]['username'] = '';
                $subscriptionArray[$key]['name'] = '';
            }
        }

        return $this->success($response, $subscriptionArray);
    }

    /**
     * 保存页面（或者接口）的订阅信息（兼容旧接口 Api/Subscription/savePage）。
     *
     * 功能：
     * - 保存页面的订阅信息
     * - 权限检查（checkItemEdit）
     */
    public function savePage(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $uids = $this->getParam($request, 'uids', '');
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10100, '缺少 page_id 参数');
        }

        // 获取页面信息
        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        // 处理用户 ID 列表
        $uidsArray = [];
        if (!empty($uids)) {
            $uidsArray = array_filter(array_map('intval', explode(',', $uids)));
        }

        // 添加订阅
        foreach ($uidsArray as $sUid) {
            if ($sUid > 0) {
                \App\Model\Subscription::addSub($sUid, $pageId, 'page', 'update');
            }
        }

        return $this->success($response, []);
    }

    /**
     * 删除页面（或者接口）的订阅信息（兼容旧接口 Api/Subscription/deletePage）。
     *
     * 功能：
     * - 删除页面的订阅信息
     * - 权限检查（checkItemEdit）
     */
    public function deletePage(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $uids = $this->getParam($request, 'uids', '');
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10100, '缺少 page_id 参数');
        }

        // 获取页面信息
        $page = \App\Model\Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 检查编辑权限
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        // 处理用户 ID 列表
        $uidsArray = [];
        if (!empty($uids)) {
            $uidsArray = array_filter(array_map('intval', explode(',', $uids)));
        }

        // 删除订阅
        foreach ($uidsArray as $sUid) {
            if ($sUid > 0) {
                \App\Model\Subscription::deleteSub($sUid, $pageId, 'page', 'update');
            }
        }

        return $this->success($response, []);
    }
}
