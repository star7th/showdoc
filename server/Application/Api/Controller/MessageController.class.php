<?php
namespace Api\Controller;
use Think\Controller;
class MessageController extends BaseController {


    //获取公告类型消息列表
    public function getList(){
        $login_user = $this->checkLogin();
        $uid =  $login_user['uid'];
        $page = I("page/d") ? I("page/d") : 0 ;
        $count = I("count/d") ? I("count/d") : 1000 ;
        $notice_type = I("notice_type/s");
        $reg_time = date("Y-m-d H:i:s" , $login_user['reg_time']);
        // 获取用户未读公告
        // 特别说明一下时间的判断。只有在用户注册时间之后发的公告才会读取。尚未注册的时间点前的公告都不需要展示给他
        $message_announce_unread = D("MessageContent")->where(" message_type = 'announce' and addtime > '$reg_time'  and id not in ( select message_content_id from message where to_uid = '$uid'  ) ")->order(" id desc ")->select();
        // 获取用户已读公告
        $message_announce_read = D("MessageContent")->where(" message_type = 'announce' and addtime > '$reg_time' and id in ( select message_content_id from message where to_uid = '$uid' and status = 1  ) ")->order(" id desc ")->select();
        
        // 对于 message_type = 'private'  的私人一对一消息，showdoc尚未开启。留着以后扩展。


        //由于公告不会很多，所以不分页了。全部返回给前端

        $this->sendResult(array(
            'message_announce_unread' =>(array)$message_announce_unread ,
            'message_announce_read' =>(array)$message_announce_read ,
        )); 
    }



    //设置消息已读
    public function setRead(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        $message_content_id = I("message_content_id/d") ? I("message_content_id/d") : 0 ;
        $array = D("Message")->where("to_uid = '$uid' and message_content_id = '$message_content_id' ")->find();
        if($array){
            D("Message")->where("to_uid = '$uid' and message_content_id = '$message_content_id' ")->save(array("status"=>1));
        }else{
            if($message_content_id){
                // 如果不存在，则可能是公告类型。
                D("Message")->add(array(
                    "from_uid"=>0,
                    "to_uid" => $uid ,
                    "message_type" => 'announce' ,
                    "message_content_id" => $message_content_id ,
                    "status" => 1 ,
                    "addtime" => date("Y-m-d H:i:s") ,
                    "readtime" => date("Y-m-d H:i:s")
        
                ));
            }

        }

        $this->sendResult(array()); 
    }

    public function delete(){
        $login_user = $this->checkLogin();
        $uid =  $login_user['uid'];
        $message_content_id = I("message_content_id/d") ? I("message_content_id/d") : 0 ;
        D("Message")->where(" to_uid = '$uid' and message_content_id = '$message_content_id' ")->save(array(
            "status" => -1 ,
        ));
        $this->sendResult(array()); 
    }

    //获取提醒型消息列表
    public function getRemindList(){
        $page = I("page/d")?I("page/d") : 1;
        $count = I("count/d")?I("count/d") : 15;
        $login_user = $this->checkLogin();

        $list = D("Message")->getRemindList($login_user['uid'] , $page , $count );
        $list = $list ? $list: array() ;
        $this->sendResult($list);
    }

    // 快速获取未读的提醒类消息
    public function getUnreadRemind(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;
        $return = array() ;
        $array = D("Message")->where(" to_uid = '{$uid}' and status = 0 ")->find();
        if($array){
            // 如果有未读的，再组装更多信息
            $list = D("Message")->getRemindList($uid , 1,1 , 0);
            if($list && $list['list']){
                $return = $list['list'][0] ;
            }
        }
        $this->sendResult($return);
    }

    

}