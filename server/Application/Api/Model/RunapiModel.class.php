<?php
namespace Api\Model;
use Api\Model\BaseModel;
/**
 * 
 * @author star7th      
 */
class RunapiModel  {

    Protected $autoCheckFields = false;
    
    //获取全局参数
    public function getGlobalParam($item_id){
        $item_id = intval($item_id) ; 
        $return = array(
            'query'=>array(),
            'body'=>array(),
            'header'=>array(),
          );
    
          $res = D("RunapiGlobalParam")->where(" param_type = 'query' and item_id = {$item_id} ")->find();
          if($res){
            $return['query'] = json_decode( htmlspecialchars_decode($res['content_json_str']) ,true);
            $return['query'] = $return['query'] ? $return['query'] : array() ;
          }else{
            D("RunapiGlobalParam")->add(array(
              "param_type"=>"query",
              "item_id"=>$item_id,
              "content_json_str"=>'[]',
              "addtime" => date("Y-m-d H:i:s") ,
              "last_update_time" => date("Y-m-d H:i:s") ,
            ));
          }
          $res = D("RunapiGlobalParam")->where(" param_type = 'body' and item_id = {$item_id} ")->find();
          if($res){
            $return['body'] = json_decode( htmlspecialchars_decode($res['content_json_str']) ,true);
            $return['body'] = $return['body'] ? $return['body'] : array() ;
          }else{
            D("RunapiGlobalParam")->add(array(
              "param_type"=>"body",
              "item_id"=>$item_id,
              "content_json_str"=>'[]',
              "addtime" => date("Y-m-d H:i:s") ,
              "last_update_time" => date("Y-m-d H:i:s") ,
            ));
          }
          $res = D("RunapiGlobalParam")->where(" param_type = 'header' and item_id = {$item_id} ")->find();
          if($res){
            $return['header'] = json_decode( htmlspecialchars_decode($res['content_json_str']) ,true);
            $return['header'] = $return['header'] ? $return['header'] : array() ;
          }else{
            D("RunapiGlobalParam")->add(array(
              "param_type"=>"header",
              "item_id"=>$item_id,
              "content_json_str"=>'[]',
              "addtime" => date("Y-m-d H:i:s") ,
              "last_update_time" => date("Y-m-d H:i:s") ,
            ));
          }
        return $return ;
    }

}