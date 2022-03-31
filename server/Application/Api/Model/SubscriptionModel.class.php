<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 
 * @author star7th      
 */
class SubscriptionModel extends BaseModel
{

  public function addSub($uid, $object_id, $object_type, $action_type)
  {

    $uid - intval($uid);
    $object_id - intval($object_id);
    // 检测是否已经存在订阅了
    $res = $this->where(" uid = '$uid' and object_id ='$object_id' and object_type='$object_type' and action_type='$action_type' ")->find();
    if (!$res) {
      $res = $this->add(array(
        "uid" => $uid,
        "object_id" => $object_id,
        "object_type" => $object_type,
        "action_type" => $action_type,
        "sub_time" => date("Y-m-d H:i:s")
      ));
    }
    return $res;
  }

  public function deleteSub($uid, $object_id, $object_type, $action_type)
  {
    $uid - intval($uid);
    $object_id - intval($object_id);
    // 检测是否已经存在订阅了
    $res = $this->where(" uid = '$uid' and object_id ='$object_id' and object_type='$object_type' and action_type='$action_type' ")->delete();
    return $res;
  }

  public function getListByObjectId($object_id, $object_type, $action_type)
  {

    return $this->where(" object_id = '$object_id' and  object_type='$object_type'  and action_type='$action_type'  ")->select();
  }
}
