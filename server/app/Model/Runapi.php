<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Runapi
{
    /**
     * 获取项目的全局参数
     * 与旧代码 RunapiModel::getGlobalParam 逻辑一致
     *
     * @param int $itemId 项目 ID
     * @return array 全局参数数组，包含 query、body、header、cookies、preScript、postScript
     */
    public static function getGlobalParam(int $itemId): array
    {
        if ($itemId <= 0) {
            return [
                'query' => [],
                'body' => [],
                'header' => [],
                'cookies' => [],
                'preScript' => '',
                'postScript' => '',
            ];
        }

        $return = [
            'query' => [],
            'body' => [],
            'header' => [],
            'cookies' => [],
            'preScript' => '',
            'postScript' => '',
        ];

        // 获取 query 参数
        $res = RunapiGlobalParam::findByItemIdAndType($itemId, 'query');
        if ($res) {
            $content = htmlspecialchars_decode($res['content_json_str'] ?? '[]');
            $return['query'] = json_decode($content, true) ?: [];
        } else {
            // 如果不存在，创建默认记录
            DB::table('runapi_global_param')->insert([
                'param_type' => 'query',
                'item_id' => $itemId,
                'content_json_str' => '[]',
                'addtime' => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 获取 body 参数
        $res = RunapiGlobalParam::findByItemIdAndType($itemId, 'body');
        if ($res) {
            $content = htmlspecialchars_decode($res['content_json_str'] ?? '[]');
            $return['body'] = json_decode($content, true) ?: [];
        } else {
            DB::table('runapi_global_param')->insert([
                'param_type' => 'body',
                'item_id' => $itemId,
                'content_json_str' => '[]',
                'addtime' => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 获取 header 参数
        $res = RunapiGlobalParam::findByItemIdAndType($itemId, 'header');
        if ($res) {
            $content = htmlspecialchars_decode($res['content_json_str'] ?? '[]');
            $return['header'] = json_decode($content, true) ?: [];
        } else {
            DB::table('runapi_global_param')->insert([
                'param_type' => 'header',
                'item_id' => $itemId,
                'content_json_str' => '[]',
                'addtime' => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 获取 cookies 参数
        $res = RunapiGlobalParam::findByItemIdAndType($itemId, 'cookies');
        if ($res) {
            $content = htmlspecialchars_decode($res['content_json_str'] ?? '[]');
            $return['cookies'] = json_decode($content, true) ?: [];
        } else {
            DB::table('runapi_global_param')->insert([
                'param_type' => 'cookies',
                'item_id' => $itemId,
                'content_json_str' => '[]',
                'addtime' => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 获取 preScript
        $res = RunapiGlobalParam::findByItemIdAndType($itemId, 'preScript');
        if ($res) {
            $return['preScript'] = htmlspecialchars_decode($res['content_json_str'] ?? '');
        } else {
            DB::table('runapi_global_param')->insert([
                'param_type' => 'preScript',
                'item_id' => $itemId,
                'content_json_str' => '',
                'addtime' => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        // 获取 postScript
        $res = RunapiGlobalParam::findByItemIdAndType($itemId, 'postScript');
        if ($res) {
            $return['postScript'] = htmlspecialchars_decode($res['content_json_str'] ?? '');
        } else {
            DB::table('runapi_global_param')->insert([
                'param_type' => 'postScript',
                'item_id' => $itemId,
                'content_json_str' => '',
                'addtime' => date('Y-m-d H:i:s'),
                'last_update_time' => date('Y-m-d H:i:s'),
            ]);
        }

        return $return;
    }
}
