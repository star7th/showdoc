<?php
namespace Api\Controller;
use Think\Controller;
class PageController extends BaseController {

    //页面详情
    public function info(){
        $page_id = I("page_id/d");
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'] , $page['item_id'])) {
            $this->sendError(10303);
            return;
        }
        $page = $page ? $page : array();
        if ($page) {
           unset($page['page_content']);
           $page['addtime'] = date("Y-m-d H:i:s",$page['addtime']);
        }
        $this->sendResult($page);
    }

}