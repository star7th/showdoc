<?php
namespace Api\Controller;
use Think\Controller;
/*
    团队和项目的绑定关系
 */
class TeamItemController extends BaseController {

    //添加和编辑
    public function save(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;

        $item_id = I("item_id/d");
        $team_id = I("team_id/d");

        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        }

        $teamInfo = D("Team")->where(" id = '$team_id' and uid = '$login_user[uid]' ")->find();
        if (!$teamInfo) {
            $this->sendError(10209,"无此团队或者你无管理此团队的权限");
            return ;
        } 


        $data = array() ;
        $data['item_id'] = $item_id ;
        $data['team_id'] = $team_id ;
        $data['addtime'] = time() ;
        $id = D("TeamItem")->add($data);

        //获取该团队的所有成员并加入项目
        $teamMembers = D("TeamMember")->where("  team_id = '$team_id' ")->select() ;
        if ($teamMembers) {
            foreach ($teamMembers as $key => $value) {
                $data= array(
                    "team_id"=>$team_id,
                    "member_uid"=>$value['member_uid'],
                    "member_username"=>$value['member_username'],
                    "item_id"=>$item_id,
                    "member_group_id"=>1, //默认添加的权限为1，即编辑权限
                    "addtime"=>time()
                );
                D("TeamItemMember")->add($data);
            }
        }


        $return = D("TeamItem")->where(" id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

    //根据项目来获取其绑定的团队列表
    public function getList(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;

        $item_id = I("item_id/d");

        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        }

        $sql  = "select team.*,team_item.team_id , team_item.id as id from team left join team_item on team.id = team_item.team_id where team_item.item_id = '$item_id' ";
        $ret = D("TeamItem")->query($sql);

        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
            }
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }
    
    //根据团队来获取项目列表
    public function getListByTeam(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;

        $team_id = I("team_id/d");

        $teamInfo = D("Team")->where(" id = '$team_id' and uid = '$login_user[uid]' ")->find();
        if (!$teamInfo) {
            $this->sendError(10209,"无此团队或者你无管理此团队的权限");
            return ;
        } 

        $sql  = "select item.*,team_item.team_id , team_item.id as id from item left join team_item on item.item_id = team_item.item_id where team_item.team_id = '$team_id' and item.is_del = 0 ";
        $ret = D("Item")->query($sql);

        if ($ret) {
            foreach ($ret as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s" , $value['addtime']);
            }
           $this->sendResult($ret);
        }else{
            $this->sendResult(array());
        }
    }


    //删除
    public function delete(){
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'] ;

        $id = I("id/d")? I("id/d") : 0;
        $teamItemInfo = D("TeamItem")->where(" id = '$id'  ")->find();
        $item_id = $teamItemInfo['item_id'] ;
        $team_id = $teamItemInfo['team_id'] ;

        if(!$this->checkItemCreator($uid , $item_id)){
            $this->sendError(10303);
            return ;
        }

        $ret = D("TeamItemMember")->where(" item_id = '$item_id' and team_id = '$team_id' ")->delete();
        $ret = D("TeamItem")->where(" id = '$id' ")->delete();

        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }




}