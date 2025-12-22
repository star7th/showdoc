<?php

namespace App\Common\Helper;

/**
 * 负责与旧数据兼容的内容压缩/解压逻辑。
 *
 * 注意：算法需与旧版 compress_string/uncompress_string 完全一致，
 * 以便读取历史已压缩的 page_content。
 */
class ContentCodec
{
    public static function compress(string $string): string
    {
        return base64_encode(gzcompress($string, 9));
    }

    public static function decompress(?string $string): string
    {
        if ($string === null || $string === '') {
            return '';
        }

        $decoded = base64_decode($string, true);
        if ($decoded === false) {
            return '';
        }

        $uncompressed = @gzuncompress($decoded);
        if ($uncompressed === false) {
            // 不是压缩过的内容，则原样返回
            return $string;
        }

        return $uncompressed;
    }
}
