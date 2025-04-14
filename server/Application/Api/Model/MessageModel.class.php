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
		$ret = $this->add(array(
			"from_uid" => $from_uid,
			"to_uid" => $to_uid,
			"message_type" => $message_type,
			"message_content_id" => $message_content_id,
			"status" => '0',
			"addtime" => date("Y-m-d H:i:s"),
			"readtime" => date("Y-m-d H:i:s"),
		));

		// 检查用户是否设置了推送地址，如果有则发送微信推送
		$push_url = D("UserSetting")->getPushUrl($to_uid);
		if ($push_url) {
			// 构建标题和内容
			$title = $from_name . "给您发送了一条消息";
			$content = $message_content;
			if ($object_type == 'page') {
				$page_id = $object_id;
				$page = M("Page")->where("page_id = '$page_id'")->find();
				if ($page) {
					$title = "页面更新提醒";
					$content = $from_name . "修改了页面 《" . $page['page_title'] . "》, 修改备注：" . $message_content . "。详情请登录showdoc查看";
				}
			}

			// 发送HTTP请求到用户设置的推送地址
			$this->sendPushNotification($push_url, $title, $content);
		}

		return $ret;
	}

	/**
	 * 发送推送通知
	 * @param string $push_url 推送地址
	 * @param string $title 消息标题
	 * @param string $content 消息内容
	 * @return boolean
	 */
	private function sendPushNotification($push_url, $title, $content)
	{
		try {
			$params = array(
				'title' => $title,
				'content' => $content
			);
			
			// 使用cURL发送请求
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $push_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 设置超时时间为10秒
			$response = curl_exec($ch);
			curl_close($ch);
			
			return true;
		} catch (\Exception $e) {
			// 记录错误，但不影响正常业务流程
			return false;
		}
	}
}
