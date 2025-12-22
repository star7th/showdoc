<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * 选项配置表封装，兼容旧版 OptionsModel::get/set 行为。
 *
 * 表结构沿用旧表 `options`：
 * - option_name
 * - option_value
 */
class Options
{
    /**
     * 获取配置项，不存在时返回提供的默认值（默认为 null）。
     * 直接返回数据库中的原始值，不进行类型转换。
     */
    public static function get(string $name, $default = null)
    {
        $row = DB::table('options')
            ->where('option_name', $name)
            ->first();

        if (!$row) {
            return $default;
        }

        return $row->option_value;
    }

    /**
     * 设置配置项（存在则更新，不存在则插入）。
     * 给它什么就保存什么，不做类型转换（数组除外，需要转 JSON）。
     * 
     * 兼容 ThinkPHP 的 M('options')->add(..., NULL, true) 行为：
     * 第三个参数 true 表示如果记录已存在则更新，不存在则插入。
     */
    public static function set(string $name, $value): bool
    {
        // 如果是数组，转换为 JSON 字符串（旧代码中 ldap_form 等配置是 JSON）
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        try {
            // 先检查记录是否存在
            $existing = DB::table('options')
                ->where('option_name', $name)
                ->first();

            if ($existing) {
                // 更新现有记录（写操作默认使用主库）
                $affected = DB::table('options')
                    ->where('option_name', $name)
                    ->update([
                        'option_value' => $value,
                    ]);
                // affected >= 0 表示执行成功（0 表示值没变化，但也是成功）
                return $affected >= 0;
            }

            // 插入新记录（写操作默认使用主库）
            return DB::table('options')->insert([
                'option_name'  => $name,
                'option_value' => $value,
            ]);
        } catch (\Throwable $e) {
            // 记录错误但不抛出异常，返回 false 表示失败
            error_log("Options::set() failed for '{$name}': " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
    }
}
