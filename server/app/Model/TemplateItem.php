<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class TemplateItem
{
    /**
     * 根据模板 ID 获取项目列表
     *
     * @param int $templateId 模板 ID
     * @return array 项目列表
     */
    public static function getListByTemplateId(int $templateId): array
    {
        if ($templateId <= 0) {
            return [];
        }

        $rows = DB::table('template_item')
            ->where('template_id', $templateId)
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 根据项目 ID 获取模板列表（关联 template 表）
     *
     * @param int $itemId 项目 ID
     * @return array 模板列表
     */
    public static function getListByItemId(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        $rows = DB::table('template_item')
            ->leftJoin('template', 'template.id', '=', 'template_item.template_id')
            ->where('template_item.item_id', $itemId)
            ->select('template.*', 'template_item.*')
            ->get()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[] = (array) $row;
        }

        return $result;
    }

    /**
     * 添加模板项目关联
     *
     * @param array $data 关联数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('template_item')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 删除模板项目关联（根据模板 ID）
     *
     * @param int $templateId 模板 ID
     * @return bool 是否成功
     */
    public static function deleteByTemplateId(int $templateId): bool
    {
        if ($templateId <= 0) {
            return false;
        }

        try {
            $affected = DB::table('template_item')
                ->where('template_id', $templateId)
                ->delete();
            return $affected >= 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 检查模板是否已关联到项目
     *
     * @param int $templateId 模板 ID
     * @param int $itemId 项目 ID
     * @return bool 是否已关联
     */
    public static function exists(int $templateId, int $itemId): bool
    {
        if ($templateId <= 0 || $itemId <= 0) {
            return false;
        }

        $row = DB::table('template_item')
            ->where('template_id', $templateId)
            ->where('item_id', $itemId)
            ->first();

        return $row !== null;
    }
}
