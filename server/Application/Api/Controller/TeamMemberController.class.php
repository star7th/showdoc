<?php
namespace Api\Controller;
use Think\Controller;
/*
    团队成员管理
 */
class TeamMemberController extends BaseController {

    //添加和编辑
    public function save(){
        $login_user = $this->checkLogin();

        $member_username = I("member_username");
        $team_id = I("team_id/d");

        $teamInfo = D("Team")->where(" id = '$team_id' and uid = '$login_user[uid]' ")->find();
        if (!$teamInfo) {
            $this->sendError(10209,"无此团队或者你无管理此团队的权限");
            return ;
        } 

        $memberInfo = D("User")->where(" username = '%s' ",array($member_username,$member_username))->find();
        if (!$memberInfo) {
            $this->sendError(10209);
            return ;
        }

        $data['team_id'] = $team_id ;
        $data['member_uid'] = $memberInfo['uid'] ;
        $data['member_username'] = $memberInfo['username'] ;
        $data['addtime'] = time() ;
        $id = D("TeamMember")->add($data);  

        //检查该团队已经加入了哪些项目
        $teamItems = D("TeamItem")->where("  team_id = '$team_id' ")->select() ;
        if ($teamItems) {
            foreach ($teamItems as $key => $value) {
                $data= array(
                    "team_id"=>$team_id,
                    "member_uid"=>$memberInfo['uid'],
                    "member_username"=>$memberInfo['username'],
                    "item_id"=>$value['item_id'],
                    "member_group_id"=>1, //默认添加的权限为1，即编辑权限
                    "addtime"=>time()
                );
                D("TeamItemMember")->add($data);
            }
        }
        $return = D("TeamMember")->where(" id = '$id' ")->find();

        if (!$return) {
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
        }

        $this->sendResult($return);
        
    }

    //获取列表
    public function getList(){
        $login_user = $this->checkLogin();
        $team_id = I("team_id/d");

        $teamInfo = D("Team")->where(" id = '$team_id' and uid = '$login_user[uid]' ")->find();
        if (!$teamInfo) {
            $this->sendError(102099,"无此团队或者你无管理此团队的权限");
            return ;
        }

        if ($login_user['uid'] > 0 ) {
            $ret = D("TeamMember")->where(" team_id = '$team_id' ")->join(" left join user on user.uid = team_member.member_uid")->field("team_member.* , user.name as name")->order(" addtime desc  ")->select();
        }
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
        $id = I("id/d")? I("id/d") : 0;
        $teamMemberInfo = D("TeamMember")->where(" id = '$id'  ")->find();
        $team_id = $teamMemberInfo['team_id'] ;
        $teamInfo = D("Team")->where(" id = '$team_id' and uid = '$login_user[uid]' ")->find();
        if (!$teamInfo) {
            $this->sendError(102099,"无此团队或者你无管理此团队的权限");
            return ;
        }
        $ret = D("TeamItemMember")->where(" member_uid = '$teamMemberInfo[member_uid]' and  team_id = '$team_id' ")->delete();
        $ret = D("TeamMember")->where(" id = '$id' ")->delete();
        

        if ($ret) {
           $this->sendResult($ret);
        }else{
            $return['error_code'] = 10103 ;
            $return['error_message'] = 'request  fail' ;
            $this->sendResult($return);
        }
    }




}