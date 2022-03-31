<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 
 * @author star7th      
 */
class MessageModel extends BaseModel
{

	public function getRemindList($to_uid, $page = 1, $count = 15, $status = -2)
	{
		$item_id = intval($item_id);
		$where = " to_uid = '{$to_uid}'  and message_type='remind' ";
		if ($status > -2) {
			$where .= " and status = '$status' ";
		}
		$list = $this->where(" {$where}")->order(" id desc ")->page(" $page , $count ")->select();
		$total = $this->where(" {$where}")->count();
		if ($list) {
			foreach ($list as $key => $value) {
				$list[$key] = $this->renderOne($value);
			}
		}

		$return = array(
			"total" => (int)$total,
			"list" => (array)$list,
		);

		return $return;
	}

	// 一行一行来
	public function renderOne($one)
	{
		$message_content_id = $one['message_content_id'];
		$message_content_array = D("MessageContent")->where(" id = '$message_content_id'  ")->find();
		$one['object_type'] = $message_content_array['object_type'];
		$one['object_id'] = $message_content_array['object_id'];
		$one['action_type'] = $message_content_array['action_type'];
		$one['message_content'] = $message_content_array['message_content'];
		$one['from_name'] = $message_content_array['from_name'];
		if ($one['object_type'] == 'page') {
			$page_id = $one['object_id'];
			$array1 = M("Page")->where(" page_id = '$page_id' ")->find();
			unset($array1['page_content']);
			$one['page_data'] = $array1;
		}
		return $one;
	}

	// 添加消息
	public function addMsg($from_uid, $from_name, $to_uid,  $message_type, $message_content, $action_type, $object_type, $object_id)
	{

		$message_content_id = D("MessageContent")->add(array(
			"from_uid" => $from_uid,
			"from_name" => $from_name,
			"message_type" => $message_type,
			"message_content" => $message_content,
			"action_type" => $action_type,
			"object_type" => $object_type,
			"object_id" => $object_id,
			"addtime" => date("Y-m-d H:i:s"),
		));
		return $this->add(array(
			"from_uid" => $from_uid,
			"to_uid" => $to_uid,
			"message_type" => $message_type,
			"message_content_id" => $message_content_id,
			"status" => '0',
			"addtime" => date("Y-m-d H:i:s"),
			"readtime" => date("Y-m-d H:i:s"),
		));
	}
}
