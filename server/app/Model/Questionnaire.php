<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

class Questionnaire
{
    /**
     * 添加问卷
     *
     * @param array $data 问卷数据
     * @return int|false 返回插入的ID，失败返回false
     */
    public static function add(array $data)
    {
        try {
            $id = DB::table('questionnaire')->insertGetId($data);
            return $id ?: false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 检查用户是否参与过调查
     *
     * @param int $uid 用户 ID
     * @param string $title 调查标题
     * @return bool 是否参与过
     */
    public static function isParticipated(int $uid, string $title): bool
    {
        if ($uid <= 0 || empty($title)) {
            return false;
        }

        $row = DB::table('questionnaire')
            ->where('uid', $uid)
            ->where('title', $title)
            ->first();

        return $row !== null;
    }
}
