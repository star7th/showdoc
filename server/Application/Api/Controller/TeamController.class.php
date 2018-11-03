<?php
namespace Api\Controller;
use Think\Controller;
/*
    团队管理
 */
class TeamController extends BaseController {

    //添加和编辑
    public function save(){
        $login_user = $this->checkLogin();

        $team_name = I("team_name");
        $id = I("id/d");

        if ($id) {
            
            D("Team")->where(" id = '$id' ")->save(array("team_name"=>$team_name));

        }else{
            $data['username'] = $login_user['username'] ;
            $data['uid'] = $login_user['uid'] ;
            $data['team_name'] = $team_name ;
            $data['addtime'] = time() ;
            $id = D("Team")->add($data);  
        }

        $return = D("Team")->where(" id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

    //获取列表
    public function getList(){
        $login_user = $this->checkLogin();
        if ($login_user['uid'] > 0 ) {
            $ret = D("Team")->where(" uid = '$login_user[uid]' ")->order(" addtime desc  ")->select();
        }
        if ($ret) {
            foreach ($ret as $key => &$value) {
                //获取该团队成员数
                $value['memberCount'] = D("TeamMember")->where(" team_id = '$value[id]' ")->count();

                //获取该团队涉及项目数
                $value['itemCount'] = D("TeamItem")->where(" team_id = '$value[id]' ")->count();

                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
            }
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }

    //删除
    public function delete(){
        $id = I("id/d")? I("id/d") : 0;
        $login_user = $this->checkLogin();
        if ($id && $login_user['uid']) {
            $ret = D("Team")->where(" id = '$id' and uid = '$login_user[uid]'")->delete();
        }
        if ($ret) {
            D("TeamItem")->where(" team_id = '$id' ")->delete();
            D("TeamItemMember")->where(" team_id = '$id' ")->delete();
            D("TeamMember")->where(" team_id = '$id' ")->delete();
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }




}