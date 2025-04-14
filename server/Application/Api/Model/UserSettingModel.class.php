<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 用户设置模型
 * @author 
 */
class UserSettingModel extends BaseModel
{
    /**
     * 获取用户设置
     * @param int $uid 用户ID
     * @param string $key 设置键名
     * @return string|null 设置值
     */
    public function getSetting($uid, $key)
    {
        $res = $this->where("uid = '%d' AND key_name = '%s'", array($uid, $key))->find();
        return $res ? $res['key_value'] : null;
    }

    /**
     * 保存用户设置
     * @param int $uid 用户ID
     * @param string $key 设置键名
     * @param string $value 设置值
     * @return boolean|integer
     */
    public function saveSetting($uid, $key, $value)
    {
        $res = $this->where("uid = '%d' AND key_name = '%s'", array($uid, $key))->find();
        if ($res) {
            return $this->where("id = '%d'", array($res['id']))->save(array('key_value' => $value));
        } else {
            return $this->add(array(
                'uid' => $uid,
                'key_name' => $key,
                'key_value' => $value,
                'addtime' => date("Y-m-d H:i:s")
            ));
        }
    }

    /**
     * 获取用户的推送地址
     * @param int $uid 用户ID
     * @return string|null 推送地址
     */
    public function getPushUrl($uid)
    {
        return $this->getSetting($uid, 'push_url');
    }

    /**
     * 保存用户的推送地址
     * @param int $uid 用户ID
     * @param string $url 推送地址
     * @return boolean|integer
     */
    public function savePushUrl($uid, $url)
    {
        return $this->saveSetting($uid, 'push_url', $url);
    }
} 