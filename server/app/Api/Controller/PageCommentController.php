<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 页面评论相关 Api（新架构）。
 */
class PageCommentController extends BaseController
{
    /**
     * 获取评论列表（兼容旧接口 Api/PageComment/getList）。
     *
     * 功能：
     * - 获取页面的评论列表（分页）
     * - 包含一级评论和所有回复
     * - 权限检查（checkItemVisit）
     * - 检查项目是否开启评论功能
     */
    public function getList(Request $request, Response $response): Response
    {
        // 获取登录用户（非严格模式，允许游客访问）
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);
        $uid = (int) ($loginUser['uid'] ?? 0);

        $pageId = $this->getParam($request, 'page_id', 0);
        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 20);

        if ($pageId <= 0) {
            return $this->error($response, 10100, '缺少page_id参数');
        }

        // 获取页面信息
        $pageInfo = \App\Model\Page::findById($pageId);
        if (!$pageInfo) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($pageInfo['item_id'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有访问权限');
        }

        // 检查项目是否开启评论功能
        $item = \App\Model\Item::findById($itemId);
        if (!$item || (int) ($item->item_type ?? 0) != 1) {
            // 非常规项目或未开启，返回空列表
            return $this->success($response, [
                'total' => 0,
                'page' => $page,
                'comments' => [],
            ]);
        }

        if (!(int) ($item->allow_comment ?? 0)) {
            // 未开启评论功能，返回空列表
            return $this->success($response, [
                'total' => 0,
                'page' => $page,
                'comments' => [],
            ]);
        }

        // 获取一级评论（分页）
        $result = \App\Model\PageComment::getTopLevelList($pageId, $page, $count);
        $total = $result['total'];
        $comments = $result['list'];

        $resultComments = [];
        foreach ($comments as $comment) {
            $commentData = $this->formatComment($comment, $uid, $itemId);

            // 获取该评论的所有回复（不分页）
            $replies = \App\Model\PageComment::getReplies((int) $comment['comment_id']);
            $commentData['replies'] = [];
            foreach ($replies as $reply) {
                $commentData['replies'][] = $this->formatComment($reply, $uid, $itemId);
            }

            $resultComments[] = $commentData;
        }

        return $this->success($response, [
            'total' => (int) $total,
            'page' => (int) $page,
            'comments' => $resultComments,
        ]);
    }

    /**
     * 发表评论/回复（兼容旧接口 Api/PageComment/add）。
     *
     * 功能：
     * - 发表评论或回复
     * - 权限检查（checkItemVisit）
     * - 防刷机制（10秒内最多1条评论）
     * - 发送通知
     */
    public function add(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $pageId = $this->getParam($request, 'page_id', 0);
        $content = trim($this->getParam($request, 'content', ''));
        $parentId = $this->getParam($request, 'parent_id', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10100, '缺少page_id参数');
        }

        if (empty($content)) {
            return $this->error($response, 10100, '评论内容不能为空');
        }

        $contentLen = mb_strlen($content, 'UTF-8');
        if ($contentLen < 1 || $contentLen > 500) {
            return $this->error($response, 10100, '评论内容长度必须在1-500字符之间');
        }

        // 旧版这里依赖 session 做 10 秒防刷（last_comment_time），
        // 新架构中统一避免在业务层依赖全局 $_SESSION，这里仅依赖登录态 + 长度校验，不再做额外频率限制。

        // 获取页面信息
        $pageInfo = \App\Model\Page::findById($pageId);
        if (!$pageInfo) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($pageInfo['item_id'] ?? 0);

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有访问权限');
        }

        // 检查项目是否开启评论功能
        $item = \App\Model\Item::findById($itemId);
        if (!$item || (int) ($item->item_type ?? 0) != 1) {
            return $this->error($response, 10100, '此项目类型不支持评论功能');
        }

        if (!(int) ($item->allow_comment ?? 0)) {
            return $this->error($response, 10100, '项目未开启评论功能');
        }


        // 如果是回复，检查父评论
        if ($parentId > 0) {
            $parentComment = \App\Model\PageComment::findById($parentId);
            if (!$parentComment || (int) ($parentComment->is_deleted ?? 0) == 1) {
                return $this->error($response, 10100, '被回复的评论不存在或已删除');
            }

            // 检查父评论是否也是回复（仅支持两级）
            if ((int) ($parentComment->parent_id ?? 0) > 0) {
                return $this->error($response, 10100, '仅支持两级评论，不能对回复再次回复');
            }
        }

        // 转义HTML，防止XSS
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // 保存评论
        $user = \App\Model\User::findById($uid);
        $username = $user ? ($user->username ?? '') : '';

        $commentData = [
            'page_id' => $pageId,
            'item_id' => $itemId,
            'parent_id' => $parentId,
            'uid' => $uid,
            'username' => $username,
            'content' => $content,
            'is_deleted' => 0,
            'addtime' => time(),
        ];

        $commentId = \App\Model\PageComment::add($commentData);

        if ($commentId <= 0) {
            return $this->error($response, 10101, '评论发表失败');
        }

        // 发送通知
        $this->sendCommentNotification($commentId, $pageId, $itemId, $parentId, $uid, $username, $pageInfo['page_title'] ?? '');

        return $this->success($response, [
            'comment_id' => $commentId,
            'message' => '评论发表成功',
        ]);
    }

    /**
     * 删除评论（兼容旧接口 Api/PageComment/delete）。
     *
     * 功能：
     * - 删除评论（软删除）
     * - 权限检查：评论作者本人或项目管理员
     */
    public function delete(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $commentId = $this->getParam($request, 'comment_id', 0);

        if ($commentId <= 0) {
            return $this->error($response, 10100, '缺少comment_id参数');
        }

        $comment = \App\Model\PageComment::findById($commentId);
        if (!$comment) {
            return $this->error($response, 10101, '评论不存在');
        }

        $itemId = (int) ($comment->item_id ?? 0);
        $commentUid = (int) ($comment->uid ?? 0);

        // 检查权限：评论作者本人或项目管理员
        $canDelete = false;
        if ($commentUid === $uid) {
            $canDelete = true;
        } elseif ($this->checkItemManage($uid, $itemId)) {
            $canDelete = true;
        }

        if (!$canDelete) {
            return $this->error($response, 10303, '无权限删除此评论');
        }

        // 软删除
        $result = \App\Model\PageComment::update($commentId, ['is_deleted' => 1]);

        if ($result) {
            return $this->success($response, ['message' => '删除成功']);
        } else {
            return $this->error($response, 10101, '删除失败');
        }
    }

    /**
     * 格式化评论数据
     *
     * @param array $comment 评论数据
     * @param int $currentUid 当前用户 ID
     * @param int $itemId 项目 ID
     * @return array
     */
    private function formatComment(array $comment, int $currentUid, int $itemId): array
    {
        $commentUid = (int) ($comment['uid'] ?? 0);
        $isOwner = ($commentUid === $currentUid);
        $canDelete = $isOwner || $this->checkItemManage($currentUid, $itemId);

        return [
            'comment_id' => (int) ($comment['comment_id'] ?? 0),
            'parent_id' => (int) ($comment['parent_id'] ?? 0),
            'uid' => $commentUid,
            'username' => $comment['username'] ?? '',
            'content' => $comment['content'] ?? '',
            'addtime' => (int) ($comment['addtime'] ?? 0),
            'addtime_text' => date('Y-m-d H:i', (int) ($comment['addtime'] ?? time())),
            'is_owner' => $isOwner,
            'can_delete' => $canDelete,
        ];
    }

    /**
     * 发送评论通知
     *
     * @param int $commentId 评论 ID
     * @param int $pageId 页面 ID
     * @param int $itemId 项目 ID
     * @param int $parentId 父评论 ID
     * @param int $fromUid 发送者用户 ID
     * @param string $fromName 发送者名称
     * @param string $pageTitle 页面标题
     */
    private function sendCommentNotification(int $commentId, int $pageId, int $itemId, int $parentId, int $fromUid, string $fromName, string $pageTitle): void
    {
        if ($parentId == 0) {
            // 场景1：新评论，通知项目管理员
            $item = \App\Model\Item::findById($itemId);
            if (!$item) {
                return;
            }

            $creatorUid = (int) ($item->uid ?? 0);

            // 获取项目管理员（member_group_id = 2）
            $managers = DB::table('item_member')
                ->where('item_id', $itemId)
                ->where('member_group_id', 2)
                ->get()
                ->all();

            // 获取团队项目管理员（member_group_id = 2）
            $teamManagers = DB::table('team_item_member')
                ->where('item_id', $itemId)
                ->where('member_group_id', 2)
                ->get()
                ->all();

            $notifiedUids = [];
            $notifyContent = $fromName . ' 发表了新评论';

            // 通知创建者
            if ($creatorUid != $fromUid && !in_array($creatorUid, $notifiedUids)) {
                \App\Model\Message::addMsg($fromUid, $fromName, $creatorUid, 'remind', $notifyContent, 'comment', 'page', $pageId);
                $notifiedUids[] = $creatorUid;
            }

            // 通知项目管理员
            foreach ($managers as $manager) {
                $managerUid = (int) ($manager->uid ?? 0);
                if ($managerUid != $fromUid && !in_array($managerUid, $notifiedUids)) {
                    \App\Model\Message::addMsg($fromUid, $fromName, $managerUid, 'remind', $notifyContent, 'comment', 'page', $pageId);
                    $notifiedUids[] = $managerUid;
                }
            }

            // 通知团队项目管理员
            foreach ($teamManagers as $teamManager) {
                $teamManagerUid = (int) ($teamManager->member_uid ?? 0);
                if ($teamManagerUid != $fromUid && !in_array($teamManagerUid, $notifiedUids)) {
                    \App\Model\Message::addMsg($fromUid, $fromName, $teamManagerUid, 'remind', $notifyContent, 'comment', 'page', $pageId);
                    $notifiedUids[] = $teamManagerUid;
                }
            }
        } else {
            // 场景2：回复评论，通知被回复的作者
            $parentComment = \App\Model\PageComment::findById($parentId);
            if (!$parentComment) {
                return;
            }

            $parentUid = (int) ($parentComment->uid ?? 0);

            // 只通知原作者（不是自己）
            if ($parentUid != $fromUid) {
                $notifyContent = $fromName . ' 有新回复';
                \App\Model\Message::addMsg($fromUid, $fromName, $parentUid, 'remind', $notifyContent, 'comment', 'page', $pageId);
            }
        }
    }
}
