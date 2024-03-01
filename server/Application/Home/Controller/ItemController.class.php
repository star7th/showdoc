<?php

namespace Home\Controller;

use Think\Controller;

class ItemController extends BaseController
{
    //项目列表页
    public function index()
    {
        $login_user = $this->checkLogin();

        //跳转到web目录
        header("location:./web/#/item/index");
        exit();

        $share_url = get_domain() . __APP__ . '/uid/' . $login_user['uid'];

        $this->assign("login_user", $login_user);
        $this->assign("share_url", $share_url);
        $this->display();
    }

    //根据项目类型展示项目
    //这些参数都不需要用到，只是为了兼容父类的方法。php8需要compatible with父类的同名方法
    public function show($content = '', $charset = '', $contentType = '', $prefix = '')
    {
        $this->checkLogin(false);
        $item_id = I("item_id/d");
        $item_domain = I("item_domain/s");
        $current_page_id = I("page_id/d");

        //判断个性域名
        if ($item_domain) {
            $item = D("Item")->where("item_domain = '%s'", array($item_domain))->find();
            if ($item['item_id']) {
                $item_id = $item['item_id'];
            }
        }

        //跳转到web目录
        header("location:./web/#/" . $item_id . "?page_id=" . $current_page_id);
        exit();
    }

}
