<?php
namespace Api\Controller;
use Think\Controller;
class TemplateController extends BaseController {


    //保存
    public function save(){
        $login_user = $this->checkLogin();

        $template_title = I("template_title");
        $template_content = I("template_content");

        $data['username'] = $login_user['username'] ;
        $data['uid'] = $login_user['uid'] ;
        $data['template_title'] = $template_title ;
        $data['template_content'] = $template_content ;
        $data['addtime'] = time() ;
        

        $id = D("Template")->add($data);
        $return = D("Template")->where(" id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

    //获取我的模板列表
    public function getList(){
        $login_user = $this->checkLogin();
        if ($login_user['uid'] > 0 ) {
            $ret = D("Template")->where(" uid = '$login_user[uid]' ")->order(" addtime desc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
                $value['template_content'] = htmlspecialchars_decode($value['template_content']);
            }
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }

    //删除目录
    public function delete(){
        $id = I("id/d")? I("id/d") : 0;
        $login_user = $this->checkLogin();
        if ($id && $login_user['uid']) {
            $ret = D("Template")->where(" id = '$id' and uid = '$login_user[uid]'")->delete();
        }
        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }




}