<?php

namespace App\Model;

use Illuminate\Database\Capsule\Manager as DB;
use App\Common\Helper\OssHelper;
use App\Common\Helper\UrlHelper;
use App\Model\Options;
use App\Model\UploadFile;
use App\Model\FilePage;

/**
 * 附件相关模型（兼容旧 AttachmentModel）。
 */
class Attachment
{
    /**
     * 获取用户的本月已使用流量
     *
     * @param int $uid 用户 ID
     * @return int 已使用流量（字节）
     */
    public static function getUserFlow(int $uid): int
    {
        return FileFlow::getUserFlow($uid);
    }

    /**
     * 记录用户流量
     *
     * @param int $uid 用户 ID
     * @param int $fileSize 文件大小（字节）
     * @return bool 是否成功
     */
    public static function recordUserFlow(int $uid, int $fileSize): bool
    {
        return FileFlow::recordUserFlow($uid, $fileSize);
    }

    /**
     * 获取用户的已使用空间
     *
     * @param int $uid 用户 ID
     * @return int 已使用空间（字节）
     */
    public static function getUsedSpace(int $uid): int
    {
        if ($uid <= 0) {
            return 0;
        }

        $used = DB::table('upload_file')
            ->where('uid', $uid)
            ->sum('file_size');

        return (int) ($used ?? 0);
    }

    /**
     * 判断上传的文件扩展名是否处于白名单内
     *
     * @param string $filename 文件名
     * @return bool 是否允许
     */
    public static function isAllowedFilename(string $filename): bool
    {
        $allowArray = [
            '.jpg',
            '.jpeg',
            '.png',
            '.bmp',
            '.gif',
            '.ico',
            '.webp',
            '.mp3',
            '.wav',
            '.mp4',
            '.mov',
            '.flac',
            '.mkv',
            '.zip',
            '.tar',
            '.gz',
            '.tgz',
            '.ipa',
            '.apk',
            '.rar',
            '.iso',
            '.pdf',
            '.epub',
            '.xps',
            '.doc',
            '.docx',
            '.wps',
            '.ppt',
            '.pptx',
            '.xls',
            '.xlsx',
            '.txt',
            '.psd',
            '.csv',
            '.cer',
            '.pub',
            '.json',
            '.css',
        ];

        $ext = strtolower(substr($filename, strripos($filename, '.')));
        return in_array($ext, $allowArray, true);
    }

    /**
     * 上传文件（兼容旧开源版 AttachmentModel::upload 逻辑）。
     *
     * - 当 Options::get('oss_open') == 1 时，走 OSS 上传（uploadByOptions），real_url 为真实 OSS URL；
     * - 否则走本地上传，写入 ../Public/Uploads/，real_url 为 site_url()/Public/Uploads/...；
     * - 始终在 upload_file/file_page 写入记录，并返回 serverUrl('api/attachment/visitFile', ['sign' => $sign])。
     */
    public static function upload(array $_files, string $fileKey, int $uid, int $itemId = 0, int $pageId = 0, bool $checkFilename = true)
    {
        if (!isset($_files[$fileKey])) {
            return false;
        }

        $uploadFile = $_files[$fileKey];

        // 检查文件名白名单（与旧版一致）
        if ($checkFilename && !self::isAllowedFilename($uploadFile['name'])) {
            return false;
        }

        // 根据 oss_open 决定走本地还是 OSS
        $ossOpen = (int) Options::get('oss_open', 0);
        if ($ossOpen === 1) {
            $url = OssHelper::uploadByOptions($uploadFile);
        } else {
            $url = self::uploadLocal($uploadFile);
        }

        if (!$url) {
            return false;
        }

        // 与旧版保持一致的 sign 生成规则
        $sign = md5($url . time() . rand());

        // 写入 upload_file 表
        $fileId = DB::table('upload_file')->insertGetId([
            'sign'         => $sign,
            'uid'          => $uid,
            'item_id'      => $itemId,
            'page_id'      => $pageId,
            'display_name' => $uploadFile['name'],
            'file_type'    => $uploadFile['type'] ?? '',
            'file_size'    => $uploadFile['size'] ?? 0,
            'real_url'     => $url,
            'addtime'      => time(),
        ]);

        // 写入 file_page 表
        DB::table('file_page')->insert([
            'file_id' => $fileId,
            'item_id' => $itemId,
            'page_id' => $pageId,
            'addtime' => time(),
        ]);

        // 返回 visitFile 的访问 URL（兼容旧版）
        return UrlHelper::serverUrl('api/attachment/visitFile', ['sign' => $sign]);
    }

    /**
     * 删除文件（兼容旧开源版 AttachmentModel::deleteFile 逻辑）。
     *
     * - 优先尝试根据 real_url 删除本地 ../Public/Uploads/ 下的文件；
     * - 如果本地删不到，再尝试通过 OssHelper::deleteByOptions 删除远程 OSS；
     * - 最后删除 upload_file 和 file_page 记录。
     */
    public static function deleteFile(int $fileId): bool
    {
        if ($fileId <= 0) {
            return false;
        }

        $file = DB::table('upload_file')->where('file_id', $fileId)->first();
        if (!$file) {
            return false;
        }

        $realUrl = (string) ($file->real_url ?? '');

        // 优先删除本地 Public/Uploads 文件
        $deletedLocal = false;
        if (!empty($realUrl)) {
            $deletedLocal = self::deleteLocalByUrl($realUrl);
        }

        // 如果本地删不到，再尝试删除 OSS
        if (!$deletedLocal && !empty($realUrl)) {
            OssHelper::deleteByOptions($realUrl);
        }

        // 删除数据库记录
        DB::table('upload_file')->where('file_id', $fileId)->delete();
        DB::table('file_page')->where('file_id', $fileId)->delete();

        return true;
    }

    /**
     * 本地上传到 ../Public/Uploads/，返回完整 URL（与旧版行为等价）。
     *
     * @param array $uploadFile 单个 $_FILES 元素
     * @return string|false
     */
    private static function uploadLocal(array $uploadFile)
    {
        if (empty($uploadFile['tmp_name']) || !is_file($uploadFile['tmp_name'])) {
            return false;
        }

        // 计算 Public/Uploads 真实路径：项目根目录下的 Public/Uploads
        $projectRoot = dirname(__DIR__, 3); // .../showdoc
        $uploadRoot = $projectRoot . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'Uploads' . DIRECTORY_SEPARATOR;

        // 按日期分子目录（与 ThinkPHP Upload 默认行为类似）
        $subDir = date('Y-m-d') . DIRECTORY_SEPARATOR;
        $targetDir = $uploadRoot . $subDir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return false;
        }

        $ext = '';
        if (isset($uploadFile['name']) && strrpos($uploadFile['name'], '.') !== false) {
            $ext = substr($uploadFile['name'], strrpos($uploadFile['name'], '.'));
        }

        // 使用三个 uniqid 拼接的字母数字串作为文件名（不包含点号等特殊符号）
        $randomName = uniqid('', false) . uniqid('', false) . uniqid('', false);
        $saveName = $randomName . $ext;
        $targetPath = $targetDir . $saveName;

        // 优先使用 move_uploaded_file，失败则回退到 rename
        if (!@move_uploaded_file($uploadFile['tmp_name'], $targetPath)) {
            if (!@rename($uploadFile['tmp_name'], $targetPath)) {
                return false;
            }
        }

        // 构造对外可访问的 URL（site_url()/Public/Uploads/xxx）
        $relative = 'Public/Uploads/' . str_replace(DIRECTORY_SEPARATOR, '/', $subDir . $saveName);
        return UrlHelper::siteUrl() . '/' . $relative;
    }

    /**
     * 根据 real_url 尝试删除本地 Public/Uploads 下的文件。
     */
    private static function deleteLocalByUrl(string $realUrl): bool
    {
        $parts = explode('/Public/Uploads/', $realUrl);
        if (count($parts) < 2 || empty($parts[1])) {
            return false;
        }

        $relativePath = str_replace(['\\', '//'], '/', $parts[1]);

        $projectRoot = dirname(__DIR__, 3); // .../showdoc
        $filePath = $projectRoot . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'Uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (is_file($filePath)) {
            @unlink($filePath);
            return true;
        }

        return false;
    }
}
