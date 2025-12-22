<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Model\PageFeedback;
use App\Model\Page;
use App\Model\Item;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 页面反馈相关 Api（新架构）。
 */
class PageFeedbackController extends BaseController
{
    /**
     * 获取反馈统计（兼容旧接口 Api/PageFeedback/getStat）。
     */
    public function getStat(Request $request, Response $response): Response
    {
        // 获取登录用户（非必需，不强制登录）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        $pageId = $this->getParam($request, 'page_id', 0);
        $clientId = $this->getParam($request, 'client_id', '');

        if ($pageId <= 0) {
            return $this->error($response, 10100, '缺少page_id参数');
        }

        // 获取页面信息
        $page = Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        // 检查项目是否开启反馈功能
        $item = Item::findById($itemId);
        if (!$item || (int) ($item->item_type ?? 0) !== 1) {
            // 非常规项目或未开启，返回空统计
            return $this->success($response, [
                'helpful_count'  => 0,
                'unhelpful_count' => 0,
                'user_feedback'  => 0,
            ]);
        }

        if (empty($item->allow_feedback)) {
            // 未开启反馈功能，返回空统计
            return $this->success($response, [
                'helpful_count'  => 0,
                'unhelpful_count' => 0,
                'user_feedback'  => 0,
            ]);
        }

        // 统计数量
        $helpfulCount = PageFeedback::countByType($pageId, 1);
        $unhelpfulCount = PageFeedback::countByType($pageId, 2);

        // 获取当前用户/浏览器的反馈
        $userFeedback = 0;
        if ($uid > 0) {
            // 登录用户
            $feedback = PageFeedback::findByUid($pageId, $uid);
            if ($feedback) {
                $userFeedback = (int) ($feedback->feedback_type ?? 0);
            }
        } elseif (!empty($clientId)) {
            // 游客
            $feedback = PageFeedback::findByClientId($pageId, $clientId);
            if ($feedback) {
                $userFeedback = (int) ($feedback->feedback_type ?? 0);
            }
        }

        return $this->success($response, [
            'helpful_count'  => $helpfulCount,
            'unhelpful_count' => $unhelpfulCount,
            'user_feedback'  => $userFeedback,
        ]);
    }

    /**
     * 提交/修改反馈（兼容旧接口 Api/PageFeedback/submit）。
     */
    public function submit(Request $request, Response $response): Response
    {
        // 获取登录用户（非必需，不强制登录）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        $pageId = $this->getParam($request, 'page_id', 0);
        $feedbackType = $this->getParam($request, 'feedback_type', 0);
        $clientId = $this->getParam($request, 'client_id', '');

        if ($pageId <= 0) {
            return $this->error($response, 10100, '缺少page_id参数');
        }

        // 兼容新前端：feedback_type = 0 表示“取消反馈”，1=有帮助，2=无帮助
        if (!in_array($feedbackType, [0, 1, 2], true)) {
            return $this->error($response, 10100, 'feedback_type参数错误，必须为0、1或2');
        }

        // 获取页面信息
        $page = Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page['item_id'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '没有权限');
        }

        // 检查项目是否开启反馈功能
        $item = Item::findById($itemId);
        if (!$item || (int) ($item->item_type ?? 0) !== 1) {
            return $this->error($response, 10100, '此项目类型不支持反馈功能');
        }

        if (empty($item->allow_feedback)) {
            return $this->error($response, 10100, '项目未开启反馈功能');
        }

        // 游客必须提供client_id
        if ($uid === 0 && empty($clientId)) {
            return $this->error($response, 10100, '游客必须提供client_id参数');
        }

        // 查询现有反馈
        $existingFeedback = null;
        if ($uid > 0) {
            $existingFeedback = PageFeedback::findByUid($pageId, $uid);
        } else {
            $existingFeedback = PageFeedback::findByClientId($pageId, $clientId);
        }

        if ($existingFeedback) {
            // 已有反馈记录
            $existingType = (int) ($existingFeedback->feedback_type ?? 0);
            $feedbackId   = (int) ($existingFeedback->feedback_id ?? 0);

            if ($feedbackType === 0) {
                // 前端显式请求“取消反馈”
                PageFeedback::delete($feedbackId);
            } elseif ($existingType === $feedbackType) {
                // 相同类型：按旧逻辑也视为“取消反馈”
                PageFeedback::delete($feedbackId);
                // 同时将 feedbackType 标记为 0，方便前端更新 userFeedback 状态
                $feedbackType = 0;
            } else {
                // 不同类型：更新反馈
                PageFeedback::update($feedbackId, [
                    'feedback_type' => $feedbackType,
                    'addtime'       => time(),
                ]);
            }
        } else {
            if ($feedbackType !== 0) {
                // 新增反馈（feedback_type 为 1 或 2 才需要新增，0 表示本来就没有反馈，无需写库）
                $feedbackData = [
                    'page_id'       => $pageId,
                    'item_id'       => $itemId,
                    // 与旧版保持一致：游客 uid 记为 0，而不是 NULL，避免 SQLite NOT NULL 约束问题
                    'uid'           => $uid,
                    'client_id'     => $uid > 0 ? null : $clientId,
                    'feedback_type' => $feedbackType,
                    'addtime'       => time(),
                ];

                PageFeedback::add($feedbackData);
            }
        }

        // 重新统计并返回
        $helpfulCount = PageFeedback::countByType($pageId, 1);
        $unhelpfulCount = PageFeedback::countByType($pageId, 2);

        return $this->success($response, [
            'message'       => '感谢您的反馈',
            'helpful_count' => $helpfulCount,
            'unhelpful_count' => $unhelpfulCount,
            'user_feedback' => $feedbackType,
        ]);
    }
}
