<?php

namespace Api\Controller;

use Think\Controller;

class PageCommentController extends BaseController
{

    //获取评论列表
    public function getList()
    {
        $this->checkLogin(false);
        $page_id = I("page_id/d");
        $page = I("page/d", 1);
        $count = I("count/d", 20);

        if (!$page_id) {
            $this->sendError(10100, '缺少page_id参数');
            return;
        }

        // 获取页面信息
        $page_info = D("Page")->where("page_id = %d", array($page_id))->find();
        if (!$page_info) {
            $this->sendError(10101, '页面不存在');
            return;
        }

        $item_id = $page_info['item_id'];
        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0;

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }

        // 检查项目是否开启评论功能
        $item_info = D("Item")->where("item_id = %d", array($item_id))->find();
        if (!$item_info || $item_info['item_type'] != 1) {
            // 非常规项目或未开启，返回空列表
            $this->sendResult(array(
                'total' => 0,
                'page' => $page,
                'comments' => array()
            ));
            return;
        }

        if (!$item_info['allow_comment']) {
            // 未开启评论功能，返回空列表
            $this->sendResult(array(
                'total' => 0,
                'page' => $page,
                'comments' => array()
            ));
            return;
        }

        // 获取一级评论（分页）
        $where = array(
            'page_id' => $page_id,
            'parent_id' => 0,
            'is_deleted' => 0
        );
        $total = D("PageComment")->where($where)->count();
        $comments = D("PageComment")->where($where)->order("addtime desc")->page("$page, $count")->select();

        $result_comments = array();
        if ($comments) {
            foreach ($comments as $comment) {
                $comment_data = $this->_formatComment($comment, $uid, $item_id);
                // 获取该评论的所有回复（不分页）
                $replies = D("PageComment")->where(array(
                    'parent_id' => $comment['comment_id'],
                    'is_deleted' => 0
                ))->order("addtime asc")->select();
                $comment_data['replies'] = array();
                if ($replies) {
                    foreach ($replies as $reply) {
                        $comment_data['replies'][] = $this->_formatComment($reply, $uid, $item_id);
                    }
                }
                $result_comments[] = $comment_data;
            }
        }

        $this->sendResult(array(
            'total' => (int)$total,
            'page' => (int)$page,
            'comments' => $result_comments
        ));
    }

    //发表评论/回复
    public function add()
    {
        $login_user = $this->checkLogin();
        $page_id = I("page_id/d");
        $content = I("content");
        $parent_id = I("parent_id/d", 0);

        if (!$page_id) {
            $this->sendError(10100, '缺少page_id参数');
            return;
        }

        if (empty($content)) {
            $this->sendError(10100, '评论内容不能为空');
            return;
        }

        $content = trim($content);
        $content_len = mb_strlen($content, 'UTF-8');
        if ($content_len < 1 || $content_len > 500) {
            $this->sendError(10100, '评论内容长度必须在1-500字符之间');
            return;
        }

        // 防刷：10秒内最多1条评论
        $last_comment_time = session('last_comment_time');
        if ($last_comment_time && (time() - $last_comment_time) < 10) {
            $this->sendError(10100, '评论过于频繁，请稍后再试');
            return;
        }

        // 获取页面信息
        $page_info = D("Page")->where("page_id = %d", array($page_id))->find();
        if (!$page_info) {
            $this->sendError(10101, '页面不存在');
            return;
        }

        $item_id = $page_info['item_id'];
        $uid = $login_user['uid'];

        // 检查访问权限
        if (!$this->checkItemVisit($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }

        // 检查项目是否开启评论功能
        $item_info = D("Item")->where("item_id = %d", array($item_id))->find();
        if (!$item_info || $item_info['item_type'] != 1) {
            $this->sendError(10100, '此项目类型不支持评论功能');
            return;
        }

        if (!$item_info['allow_comment']) {
            $this->sendError(10100, '项目未开启评论功能');
            return;
        }

        // 如果是回复，检查父评论
        if ($parent_id > 0) {
            $parent_comment = D("PageComment")->where("comment_id = %d", array($parent_id))->find();
            if (!$parent_comment || $parent_comment['is_deleted'] == 1) {
                $this->sendError(10100, '被回复的评论不存在或已删除');
                return;
            }
            // 检查父评论是否也是回复（仅支持两级）
            if ($parent_comment['parent_id'] > 0) {
                $this->sendError(10100, '仅支持两级评论，不能对回复再次回复');
                return;
            }
        }

        // 转义HTML，防止XSS
        $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // 保存评论
        $comment_data = array(
            'page_id' => $page_id,
            'item_id' => $item_id,
            'parent_id' => $parent_id,
            'uid' => $uid,
            'username' => $login_user['username'],
            'content' => $content,
            'is_deleted' => 0,
            'addtime' => time()
        );
        $comment_id = D("PageComment")->add($comment_data);

        if (!$comment_id) {
            $this->sendError(10101, '评论发表失败');
            return;
        }

        // 更新最后评论时间
        session('last_comment_time', time());

        // 发送通知
        $this->_sendCommentNotification($comment_id, $page_id, $item_id, $parent_id, $uid, $login_user['username'], $page_info['page_title']);

        $this->sendResult(array(
            'comment_id' => $comment_id,
            'message' => '评论发表成功'
        ));
    }

    //删除评论
    public function delete()
    {
        $login_user = $this->checkLogin();
        $comment_id = I("comment_id/d");

        if (!$comment_id) {
            $this->sendError(10100, '缺少comment_id参数');
            return;
        }

        $comment = D("PageComment")->where("comment_id = %d", array($comment_id))->find();
        if (!$comment) {
            $this->sendError(10101, '评论不存在');
            return;
        }

        $uid = $login_user['uid'];
        $item_id = $comment['item_id'];

        // 检查权限：评论作者本人或项目管理员
        $can_delete = false;
        if ($comment['uid'] == $uid) {
            $can_delete = true;
        } elseif ($this->checkItemManage($uid, $item_id)) {
            $can_delete = true;
        }

        if (!$can_delete) {
            $this->sendError(10303, '无权限删除此评论');
            return;
        }

        // 软删除
        $result = D("PageComment")->where("comment_id = %d", array($comment_id))->save(array('is_deleted' => 1));

        if ($result) {
            $this->sendResult(array('message' => '删除成功'));
        } else {
            $this->sendError(10101, '删除失败');
        }
    }

    //格式化评论数据
    private function _formatComment($comment, $current_uid, $item_id)
    {
        $is_owner = ($comment['uid'] == $current_uid);
        $can_delete = $is_owner || $this->checkItemManage($current_uid, $item_id);

        return array(
            'comment_id' => (int)$comment['comment_id'],
            'parent_id' => (int)$comment['parent_id'],
            'uid' => (int)$comment['uid'],
            'username' => $comment['username'],
            'content' => $comment['content'],
            'addtime' => (int)$comment['addtime'],
            'addtime_text' => date('Y-m-d H:i', $comment['addtime']),
            'is_owner' => $is_owner,
            'can_delete' => $can_delete
        );
    }

    //发送评论通知
    private function _sendCommentNotification($comment_id, $page_id, $item_id, $parent_id, $from_uid, $from_name, $page_title)
    {
        if ($parent_id == 0) {
            // 场景1：新评论，通知项目管理员
            $item_info = D("Item")->where("item_id = %d", array($item_id))->find();
            $creator_uid = $item_info['uid'];

            // 获取项目管理员（member_group_id = 2）
            $managers = D("ItemMember")->where("item_id = %d AND member_group_id = 2", array($item_id))->select();
            $team_managers = D("TeamItemMember")->where("item_id = %d AND member_group_id = 2", array($item_id))->select();

            $notified_uids = array();
            $notify_content = $from_name . ' 发表了新评论';

            // 通知创建者
            if ($creator_uid != $from_uid && !in_array($creator_uid, $notified_uids)) {
                D("Message")->addMsg($from_uid, $from_name, $creator_uid, 'remind', $notify_content, 'comment', 'page', $page_id);
                $notified_uids[] = $creator_uid;
            }

            // 通知项目管理员
            if ($managers) {
                foreach ($managers as $manager) {
                    $manager_uid = $manager['uid'];
                    if ($manager_uid != $from_uid && !in_array($manager_uid, $notified_uids)) {
                        D("Message")->addMsg($from_uid, $from_name, $manager_uid, 'remind', $notify_content, 'comment', 'page', $page_id);
                        $notified_uids[] = $manager_uid;
                    }
                }
            }

            // 通知团队项目管理员
            if ($team_managers) {
                foreach ($team_managers as $team_manager) {
                    $team_manager_uid = $team_manager['member_uid'];
                    if ($team_manager_uid != $from_uid && !in_array($team_manager_uid, $notified_uids)) {
                        D("Message")->addMsg($from_uid, $from_name, $team_manager_uid, 'remind', $notify_content, 'comment', 'page', $page_id);
                        $notified_uids[] = $team_manager_uid;
                    }
                }
            }
        } else {
            // 场景2：回复评论，通知被回复的作者（统一使用comment作为action_type）
            $parent_comment = D("PageComment")->where("comment_id = %d", array($parent_id))->find();
            $parent_uid = $parent_comment['uid'];

            // 只通知原作者（不是自己）
            if ($parent_uid != $from_uid) {
                $notify_content = $from_name . ' 有新回复';
                D("Message")->addMsg($from_uid, $from_name, $parent_uid, 'remind', $notify_content, 'comment', 'page', $page_id);
            }
        }
    }
}

