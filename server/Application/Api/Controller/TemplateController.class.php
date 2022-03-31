<?php

namespace Api\Controller;

use Think\Controller;

class TemplateController extends BaseController
{


    //保存
    public function save()
    {
        $login_user = $this->checkLogin();

        $template_title = I("template_title");
        $template_content = I("template_content");

        $data['username'] = $login_user['username'];
        $data['uid'] = $login_user['uid'];
        $data['template_title'] = $template_title;
        $data['template_content'] = $template_content;
        $data['addtime'] = time();


        $id = D("Template")->add($data);
        $return = D("Template")->where(" id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
        }

        $this->sendResult($return);
    }

    //获取我的模板列表
    public function getList()
    {
        $this->getMyList();  // 因为有些客户端已经使用了getList方法，所以要向后兼容
    }

    //获取我的模板列表
    public function getMyList()
    {
        $login_user = $this->checkLogin();
        if ($login_user['uid'] > 0) {
            $ret = D("Template")->where(" uid = '$login_user[uid]' ")->order(" addtime desc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['template_content'] = htmlspecialchars_decode($value['template_content']);
                // 获取当前模板被共享到哪些项目中
                $res = D("TemplateItem")->where("  template_id = '$value[id]' ")->select();
                $value['share_item'] = $res ? $res : array();
                $value['share_item_count'] = count($value['share_item']);
            }
            $this->sendResult($ret);
        } else {
            $this->sendResult(array());
        }
    }

    //获取当前项目的模板列表
    public function getItemList()
    {
        $login_user = $this->checkLogin();
        $item_id = I("post.item_id/d") ? I("post.item_id/d") : 0;
        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->sendError(10103);
            return;
        }

        $res = D("TemplateItem")->where("  item_id = '$item_id' ")->join(" left join template on template.id = template_item.template_id ")->select();
        if ($res) {
            foreach ($res as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $value['template_content'] = htmlspecialchars_decode($value['template_content']);
            }
            $this->sendResult($res);
        } else {
            $this->sendResult(array());
        }
    }

    //删除
    public function delete()
    {
        $id = I("post.id/d") ? I("post.id/d") : 0;
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        // 这里验证一下。传进来的模板id需要是他自己本人的
        if (!D("Template")->where("  id = '$id' and uid = '$uid' ")->find()) {
            $this->sendError(10103);
            return;
        }

        if ($id) {
            $ret = D("Template")->where(" id = '$id'  ")->delete();
            D("TemplateItem")->where(" template_id = '$id'")->delete();
            $this->sendResult(array());
            return;
        }
        $this->sendError(10101);
    }

    // 把模板分享给项目
    public function shareToItem()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $item_id = I("item_id"); // 这里的item_id可能是逗号分隔的字符串
        $template_id = I("template_id/d");
        $item_id =  \SQLite3::escapeString($item_id);
        $item_id_array = explode(",", $item_id);

        // 这里验证一下。传进来的模板id需要是他自己本人的
        if (!D("Template")->where("  id = '$template_id' and uid = '$uid' ")->find()) {
            $this->sendError(10103);
            return;
        }
        $res = '';
        D("TemplateItem")->where("  template_id = '$template_id'  ")->delete();
        foreach ($item_id_array as $key => $value) {
            $item_id = intval($value);
            if (!$item_id) continue;
            if (!$this->checkItemEdit($uid, $item_id)) {
                $this->sendError(10103);
                return;
            }

            if (D("TemplateItem")->where("  template_id = '$template_id' and item_id = '$item_id' ")->find()) {
                continue; //如果该模板已经分享到该项目中了，则结束当前一次循环。
            }

            $res = D("TemplateItem")->add(array(
                "template_id" => $template_id,
                "item_id" => $item_id,
                "uid" => $uid,
                "username" => $login_user['username'],
                "created_at" => date("Y-m-d H:i:s")
            ));
        }
        $this->sendResult(array());
    }

    // 获取“某个模板已经被共享到什么项目中了”的列表
    public function getShareItemList()
    {
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $template_id = I("template_id/d");
        // 这里验证一下。传进来的模板id需要是他自己本人的
        if (!D("Template")->where("  id = '$template_id' and uid = '$uid' ")->find()) {
            $this->sendError(10103);
            return;
        }

        $res = D("TemplateItem")->where("  template_id = '$template_id' ")->select();
        if ($res) {
            $this->sendResult($res);
        } else {
            $this->sendResult(array());
        }
    }
}
