<?php

namespace Home\Controller;

use Think\Controller;

class PageController extends BaseController
{

    //展示某个项目的单个页面
    public function index()
    {
        import("Vendor.Parsedown.Parsedown");
        $page_id = I("page_id/d");
        $this->assign("page_id", $page_id);
        $this->display();
    }

    //展示单个页面
    public function single()
    {
        $page_id = I("page_id/d");

        //跳转到web目录
        header("location:./web/#/page/" . $page_id);
        exit();
    }
}
