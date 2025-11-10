<?php

namespace Api\Controller;

use Think\Controller;

class PageFeedbackController extends BaseController
{

    //获取反馈统计
    public function getStat()
    {
        $this->checkLogin(false);
        $page_id = I("page_id/d");
        $client_id = I("client_id");

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

        // 检查项目是否开启反馈功能
        $item_info = D("Item")->where("item_id = %d", array($item_id))->find();
        if (!$item_info || $item_info['item_type'] != 1) {
            // 非常规项目或未开启，返回空统计
            $this->sendResult(array(
                'helpful_count' => 0,
                'unhelpful_count' => 0,
                'user_feedback' => 0
            ));
            return;
        }

        if (!$item_info['allow_feedback']) {
            // 未开启反馈功能，返回空统计
            $this->sendResult(array(
                'helpful_count' => 0,
                'unhelpful_count' => 0,
                'user_feedback' => 0
            ));
            return;
        }

        // 统计数量
        $helpful_count = D("PageFeedback")->where(array(
            'page_id' => $page_id,
            'feedback_type' => 1
        ))->count();

        $unhelpful_count = D("PageFeedback")->where(array(
            'page_id' => $page_id,
            'feedback_type' => 2
        ))->count();

        // 获取当前用户/浏览器的反馈
        $user_feedback = 0;
        if ($uid > 0) {
            // 登录用户
            $feedback = D("PageFeedback")->where(array(
                'page_id' => $page_id,
                'uid' => $uid
            ))->find();
            if ($feedback) {
                $user_feedback = (int)$feedback['feedback_type'];
            }
        } elseif ($client_id) {
            // 游客
            $feedback = D("PageFeedback")->where(array(
                'page_id' => $page_id,
                'client_id' => $client_id
            ))->find();
            if ($feedback) {
                $user_feedback = (int)$feedback['feedback_type'];
            }
        }

        $this->sendResult(array(
            'helpful_count' => (int)$helpful_count,
            'unhelpful_count' => (int)$unhelpful_count,
            'user_feedback' => $user_feedback
        ));
    }

    //提交/修改反馈
    public function submit()
    {
        $this->checkLogin(false);
        $page_id = I("page_id/d");
        $feedback_type = I("feedback_type/d");
        $client_id = I("client_id");

        if (!$page_id) {
            $this->sendError(10100, '缺少page_id参数');
            return;
        }

        if ($feedback_type != 1 && $feedback_type != 2) {
            $this->sendError(10100, 'feedback_type参数错误，必须为1或2');
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

        // 检查项目是否开启反馈功能
        $item_info = D("Item")->where("item_id = %d", array($item_id))->find();
        if (!$item_info || $item_info['item_type'] != 1) {
            $this->sendError(10100, '此项目类型不支持反馈功能');
            return;
        }

        if (!$item_info['allow_feedback']) {
            $this->sendError(10100, '项目未开启反馈功能');
            return;
        }

        // 游客必须提供client_id
        if ($uid == 0 && empty($client_id)) {
            $this->sendError(10100, '游客必须提供client_id参数');
            return;
        }

        // 查询现有反馈
        $where = array('page_id' => $page_id);
        if ($uid > 0) {
            $where['uid'] = $uid;
        } else {
            $where['client_id'] = $client_id;
        }

        $existing_feedback = D("PageFeedback")->where($where)->find();

        if ($existing_feedback) {
            // 已有反馈记录
            if ($existing_feedback['feedback_type'] == $feedback_type) {
                // 相同类型：取消反馈（删除）
                D("PageFeedback")->where("feedback_id = %d", array($existing_feedback['feedback_id']))->delete();
            } else {
                // 不同类型：更新反馈
                D("PageFeedback")->where("feedback_id = %d", array($existing_feedback['feedback_id']))->save(array(
                    'feedback_type' => $feedback_type,
                    'addtime' => time()
                ));
            }
        } else {
            // 新增反馈
            $feedback_data = array(
                'page_id' => $page_id,
                'item_id' => $item_id,
                'uid' => $uid,
                'feedback_type' => $feedback_type,
                'addtime' => time()
            );
            // 登录用户设置client_id为NULL，游客才设置client_id
            // 注意：必须显式设置为NULL，不能省略，否则会使用默认值''导致唯一约束冲突
            if ($uid == 0) {
                $feedback_data['client_id'] = $client_id;
            } else {
                $feedback_data['client_id'] = null; // 登录用户设为NULL，避免与空字符串冲突
            }
            D("PageFeedback")->add($feedback_data);
        }

        // 重新统计并返回
        $helpful_count = D("PageFeedback")->where(array(
            'page_id' => $page_id,
            'feedback_type' => 1
        ))->count();

        $unhelpful_count = D("PageFeedback")->where(array(
            'page_id' => $page_id,
            'feedback_type' => 2
        ))->count();

        $this->sendResult(array(
            'message' => '感谢您的反馈',
            'helpful_count' => (int)$helpful_count,
            'unhelpful_count' => (int)$unhelpful_count,
            'user_feedback' => $feedback_type
        ));
    }
}

