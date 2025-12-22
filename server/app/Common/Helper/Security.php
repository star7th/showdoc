<?php

namespace App\Common\Helper;

class Security
{
    /**
     * 安全处理 LIKE 查询关键字（防止 SQL 注入）
     *
     * @param string $keyword 关键字
     * @param bool $strict 是否严格模式
     * @return string 处理后的关键字
     */
    public static function safeLike(string $keyword, bool $strict = true): string
    {
        $s = (string) $keyword;

        // 输入长度限制（防止过长的攻击载荷）
        if (strlen($s) > 200) {
            $s = substr($s, 0, 200);
        }

        // 优先使用 SQLite3 原生转义函数
        if (class_exists('\SQLite3')) {
            $s = \SQLite3::escapeString($s);
        } else {
            // 备用方案：手动转义
            // 先转义反斜杠，避免后续再次转义造成歧义
            $s = str_replace('\\', '\\\\', $s);
            // 转义单引号和双引号（防止 SQL 注入）
            $s = str_replace("'", "\\'", $s);
            $s = str_replace('"', '\\"', $s);
            // 转义百分号和下划线（LIKE 查询特殊字符）
            if ($strict) {
                $s = str_replace('%', '\\%', $s);
                $s = str_replace('_', '\\_', $s);
            }
        }

        return $s;
    }

    /**
     * 生成随机盐值
     *
     * @return string 盐值
     */
    public static function generateSalt(): string
    {
        return substr(md5(uniqid(rand(), true)), 0, 8);
    }

    /**
     * 加密密码（兼容旧版 encry_password 函数）
     *
     * @param string $password 原始密码
     * @param string $salt 盐值
     * @return string 加密后的密码
     */
    public static function hashPassword(string $password, string $salt = ''): string
    {
        // 兼容旧版加密算法：md5(base64_encode(md5($password)) . '576hbgh6' . $salt)
        return md5(base64_encode(md5($password)) . '576hbgh6' . $salt);
    }

    /**
     * 过滤 HTML 内容（防止 XSS，兼容旧版 filter_html 函数）
     *
     * @param string $content HTML 内容
     * @return string 过滤后的内容
     */
    public static function filterHtml(string $content): string
    {
        // 移除危险标签和属性，但保留安全的 HTML 标签
        $allowedTags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><tr><td><th><thead><tbody>';
        $content = strip_tags($content, $allowedTags);

        // 移除危险属性（如 onclick, onerror 等）
        $content = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/\s*on\w+\s*=\s*[^\s>]*/i', '', $content);

        // 保留换行为可见的 <br>
        $content = nl2br($content);

        return $content;
    }

    /**
     * 验证密码强度（兼容旧版 validate_strong_password 函数）
     * 要求：至少8位，包含大小写字母、数字和特殊字符
     * 
     * @param string $password 待验证的密码
     * @return array 返回数组，['valid' => bool, 'message' => string, 'errors' => array]
     */
    public static function validateStrongPassword(string $password): array
    {
        // 从 Options 表读取配置
        $strongPasswordEnabled = \App\Model\Options::get('strong_password_enabled', '0');

        // 如果未启用高强度密码，直接返回通过
        if (!$strongPasswordEnabled || $strongPasswordEnabled === '0' || $strongPasswordEnabled === false) {
            return ['valid' => true, 'message' => '', 'errors' => []];
        }

        $errors = [];

        // 检查密码长度
        if (strlen($password) < 8) {
            $errors[] = '密码长度至少需要8位';
        }

        // 检查是否包含小写字母
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = '密码必须包含至少一个小写字母';
        }

        // 检查是否包含大写字母
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = '密码必须包含至少一个大写字母';
        }

        // 检查是否包含数字
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = '密码必须包含至少一个数字';
        }

        // 检查是否包含特殊字符
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = '密码必须包含至少一个特殊字符';
        }

        // 如果有错误，返回所有错误信息
        if (count($errors) > 0) {
            $message = implode('；', $errors);
            return ['valid' => false, 'message' => $message, 'errors' => $errors];
        }

        return ['valid' => true, 'message' => '', 'errors' => []];
    }
}
