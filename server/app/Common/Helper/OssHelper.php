<?php

namespace App\Common\Helper;

use App\Model\Options;
use AsyncAws\S3\S3Client;

/**
 * 开源版 OSS Helper
 *
 * 行为严格对齐旧版开源 AttachmentModel::uploadOss/uploadS3/deleteOss/deleteS3/getQiuniuEndpointByKey，
 * 唯一差异：
 * - 旧版通过 D("Options")->get() 获取配置，这里改为 Options::get()；
 * - 旧版使用 new S3Client([...])，这里使用 AsyncAws\S3\S3Client 同样的参数写法。
 *
 * 所有配置仅从 options 表的 oss_setting（JSON）读取，不再使用任何环境变量。
 */
class OssHelper
{
    /**
     * 基于 Options::get('oss_setting') 上传文件，返回真实 URL。
     *
     * @param array $uploadFile 单个 $_FILES 元素（name/type/tmp_name/size）
     * @return string|false
     */
    public static function uploadByOptions(array $uploadFile)
    {
        $ossSettingJson = Options::get('oss_setting', '');
        $ossSetting = $ossSettingJson ? json_decode($ossSettingJson, true) : null;

        if (!$ossSetting || empty($ossSetting['oss_type'])) {
            return false;
        }

        // s3_storage / aliyun 直接走 S3
        if ($ossSetting['oss_type'] === 's3_storage' || $ossSetting['oss_type'] === 'aliyun') {
            return self::uploadS3WithConfig($uploadFile, $ossSetting);
        }

        // 七牛：需先查询 S3 endpoint
        if ($ossSetting['oss_type'] === 'qiniu') {
            $endpoint = self::getQiuniuEndpointByKey($ossSetting['key'], $ossSetting['bucket']);
            if (!$endpoint) {
                return false;
            }
            $ossSetting['endpoint'] = $endpoint;
            return self::uploadS3WithConfig($uploadFile, $ossSetting);
        }

        // 腾讯云：根据 region 拼 endpoint，并把 secretId/secretKey 映射为 key/secret
        if ($ossSetting['oss_type'] === 'qcloud') {
            if (empty($ossSetting['region']) || empty($ossSetting['secretId']) || empty($ossSetting['secretKey'])) {
                return false;
            }
            $ossSetting['endpoint'] = "https://cos.{$ossSetting['region']}.myqcloud.com";
            $ossSetting['key'] = $ossSetting['secretId'];
            $ossSetting['secret'] = $ossSetting['secretKey'];
            return self::uploadS3WithConfig($uploadFile, $ossSetting);
        }

        return false;
    }

    /**
     * 基于 Options::get('oss_setting') 删除文件。
     *
     * @param string $fileUrl 完整的文件 URL
     * @return bool
     */
    public static function deleteByOptions(string $fileUrl): bool
    {
        $ossSettingJson = Options::get('oss_setting', '');
        $ossSetting = $ossSettingJson ? json_decode($ossSettingJson, true) : null;

        if (!$ossSetting || empty($ossSetting['oss_type'])) {
            return false;
        }

        if ($ossSetting['oss_type'] === 's3_storage' || $ossSetting['oss_type'] === 'aliyun') {
            return self::deleteS3WithConfig($fileUrl, $ossSetting);
        }

        if ($ossSetting['oss_type'] === 'qiniu') {
            $endpoint = self::getQiuniuEndpointByKey($ossSetting['key'], $ossSetting['bucket']);
            if (!$endpoint) {
                return false;
            }
            $ossSetting['endpoint'] = $endpoint;
            return self::deleteS3WithConfig($fileUrl, $ossSetting);
        }

        if ($ossSetting['oss_type'] === 'qcloud') {
            if (empty($ossSetting['region']) || empty($ossSetting['secretId']) || empty($ossSetting['secretKey'])) {
                return false;
            }
            $ossSetting['endpoint'] = "https://cos.{$ossSetting['region']}.myqcloud.com";
            $ossSetting['key'] = $ossSetting['secretId'];
            $ossSetting['secret'] = $ossSetting['secretKey'];
            return self::deleteS3WithConfig($fileUrl, $ossSetting);
        }

        return false;
    }

    /**
     * 通过 S3 协议上传（内部使用），直接复刻旧 AttachmentModel::uploadS3。
     *
     * @param array $uploadFile
     * @param array $ossSetting
     * @return string|false
     */
    private static function uploadS3WithConfig(array $uploadFile, array $ossSetting)
    {
        if (empty($uploadFile['tmp_name']) || !is_file($uploadFile['tmp_name'])) {
            return false;
        }

        // 扩展名
        $ext = strrchr($uploadFile['name'], '.');

        // 构造 OSS 对象路径
        // 使用更长的随机串，降低命名碰撞概率
        $rand = self::getRandStr(32);
        if (!empty($ossSetting['subcat'])) {
            $ossPath = rtrim($ossSetting['subcat'], '/') . '/showdoc_' . $rand . $ext;
        } else {
            $ossPath = 'showdoc_' . $rand . $ext;
        }

        // endpoint 确保带协议
        if (!empty($ossSetting['endpoint']) && !strstr($ossSetting['endpoint'], '://')) {
            $ossSetting['endpoint'] = 'https://' . $ossSetting['endpoint'];
        }

        if (empty($ossSetting['endpoint']) || empty($ossSetting['key']) || empty($ossSetting['secret']) || empty($ossSetting['bucket'])) {
            return false;
        }

        // 使用 AsyncAws S3Client
        $s3 = new S3Client([
            'accessKeyId'     => $ossSetting['key'],
            'accessKeySecret' => $ossSetting['secret'],
            'endpoint'        => $ossSetting['endpoint'],
            'sendChunkedBody' => false,
        ]);

        // 发送 PutObject 请求
        $s3->putObject([
            'Bucket'       => $ossSetting['bucket'],
            'Key'          => $ossPath,
            'Body'         => fopen($uploadFile['tmp_name'], 'rb'),
            'CacheControl' => 'public, max-age=31536000, s-maxage=31536000, immutable',
            'ContentType'  => $uploadFile['type'] ?? '',
        ]);

        // 构造最终访问 URL
        if (!empty($ossSetting['domain'])) {
            $protocol = !empty($ossSetting['protocol']) ? $ossSetting['protocol'] : 'https';
            return $protocol . '://' . $ossSetting['domain'] . '/' . $ossPath;
        }

        $tmp = parse_url($ossSetting['endpoint']);
        $host = $tmp['host'] ?? '';
        if ($host) {
            return 'https://' . $ossSetting['bucket'] . '.' . $host . '/' . $ossPath;
        }

        return false;
    }

    /**
     * 通过 S3 协议删除（内部使用），复刻旧 AttachmentModel::deleteS3。
     *
     * @param string $fileUrl
     * @param array $ossSetting
     * @return bool
     */
    private static function deleteS3WithConfig(string $fileUrl, array $ossSetting): bool
    {
        $array = parse_url($fileUrl);
        if (!$array || empty($array['path'])) {
            return false;
        }

        $file = ltrim($array['path'], '/');

        if (!empty($ossSetting['endpoint']) && !strstr($ossSetting['endpoint'], '://')) {
            $ossSetting['endpoint'] = 'https://' . $ossSetting['endpoint'];
        }

        if (empty($ossSetting['endpoint']) || empty($ossSetting['key']) || empty($ossSetting['secret']) || empty($ossSetting['bucket'])) {
            return false;
        }

        $s3 = new S3Client([
            'accessKeyId'     => $ossSetting['key'],
            'accessKeySecret' => $ossSetting['secret'],
            'endpoint'        => $ossSetting['endpoint'],
        ]);

        $s3->deleteObject([
            'Bucket' => $ossSetting['bucket'],
            'Key'    => $file,
        ]);

        return true;
    }

    /**
     * 由于历史原因，当初没有让用户填写七牛云的 region，需要自己调接口查询 endpoint。
     *
     * @param string $key
     * @param string $bucket
     * @return string|null
     */
    private static function getQiuniuEndpointByKey(string $key, string $bucket): ?string
    {
        $queryUrl = "https://api.qiniu.com/v2/query?ak={$key}&bucket={$bucket}";
        $res = http_post($queryUrl, []);
        $array = $res ? json_decode($res, true) : null;

        if ($array && isset($array['s3']['src']['main'][0]) && $array['s3']['src']['main'][0]) {
            return 'https://' . $array['s3']['src']['main'][0];
        }

        return null;
    }

    /**
     * 生成随机字符串（用于 OSS 对象名），等价于旧版 get_rand_str。
     */
    private static function getRandStr(int $len = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[random_int(0, $max)];
        }
        return $str;
    }
}
