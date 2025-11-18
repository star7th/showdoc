<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 项目AI知识库配置模型
 * @author 
 */
class ItemAiConfigModel extends BaseModel
{
    /**
     * 获取项目的 AI 配置（如果不存在则返回默认配置）
     * @param int $item_id 项目ID
     * @return array 配置数组
     */
    public function getConfig($item_id)
    {
        $item_id = intval($item_id);
        $config = $this->where(array('item_id' => $item_id))->find();
        
        if (!$config) {
            // 返回默认配置
            return array(
                'item_id' => $item_id,
                'enabled' => 0,
                'dialog_collapsed' => 1,
                'welcome_message' => '',
                'addtime' => 0,
                'updatetime' => 0
            );
        }
        
        return $config;
    }
    
    /**
     * 保存或更新配置
     * @param int $item_id 项目ID
     * @param array $data 配置数据
     * @return boolean|integer
     */
    public function saveConfig($item_id, $data)
    {
        $item_id = intval($item_id);
        $config = $this->where(array('item_id' => $item_id))->find();
        
        $save_data = array(
            'item_id' => $item_id,
            'updatetime' => time()
        );
        
        // 合并传入的数据
        if (isset($data['enabled'])) {
            $save_data['enabled'] = $data['enabled'] ? 1 : 0;
        }
        if (isset($data['dialog_collapsed'])) {
            $save_data['dialog_collapsed'] = $data['dialog_collapsed'] ? 1 : 0;
        }
        if (isset($data['welcome_message'])) {
            $save_data['welcome_message'] = $data['welcome_message'];
        }
        
        if ($config) {
            // 更新
            return $this->where(array('item_id' => $item_id))->save($save_data);
        } else {
            // 新增
            $save_data['addtime'] = time();
            return $this->add($save_data);
        }
    }
}

