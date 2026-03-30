<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Attachment;
use App\Model\UploadFile;
use App\Common\Helper\OssHelper;
use App\Common\Helper\UrlHelper;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * MCP 附件操作 Handler
 */
class AttachmentHandler extends McpHandler
{
  /**
   * 获取支持的操作列表
   *
   * @return array
   */
  public function getSupportedOperations(): array
  {
    return [
      'upload_attachment',
      'list_attachments',
      'delete_attachment',
    ];
  }

  /**
   * 执行操作
   *
   * @param string $operation 操作名称
   * @param array $params 参数
   * @return mixed
   * @throws McpException
   */
  public function execute(string $operation, array $params = [])
  {
    switch ($operation) {
      case 'upload_attachment':
        return $this->uploadAttachment($params);

      case 'list_attachments':
        return $this->listAttachments($params);

      case 'delete_attachment':
        return $this->deleteAttachment($params);

      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  /**
   * 上传附件（通过 URL 或 Base64）
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function uploadAttachment(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }

    // 检查写入权限
    $this->requireWritePermission($itemId);

    $pageId = (int) ($params['page_id'] ?? 0);
    $fileUrl = trim($params['file_url'] ?? '');
    $fileBase64 = trim($params['file_base64'] ?? '');
    $fileName = trim($params['file_name'] ?? '');

    // 必须提供 file_url 或 file_base64
    if ($fileUrl === '' && $fileBase64 === '') {
      McpError::throw(McpError::INVALID_PARAMS, '必须提供 file_url 或 file_base64');
    }

    $uid = $this->getUid();

    // 开源版：无需邮箱绑定和支付实名认证即可上传文件

    // 检查空间配额
    $quotaCheck = $this->checkSpaceQuota($uid);
    if (!$quotaCheck['allowed']) {
      McpError::throw(McpError::OPERATION_FAILED, $quotaCheck['message']);
    }

    // 检查单文件大小限制（开源版：固定 100MB）
    $fileSizeLimit = $this->getFileSizeLimit();

    // 通过 URL 上传
    if ($fileUrl !== '') {
      return $this->uploadFromUrl($itemId, $pageId, $fileUrl, $fileName, $uid, $fileSizeLimit);
    }

    // 通过 Base64 上传（此时 fileBase64 必定不为空）
    return $this->uploadFromBase64($itemId, $pageId, $fileBase64, $fileName, $uid, $fileSizeLimit);
  }

  /**
   * 检查用户空间配额
   *
   * @param int $uid 用户 ID
   * @param int $additionalSize 额外需要的空间（字节），默认为 0
   * @return array ['allowed' => bool, 'message' => string, 'usedSpace' => int, 'allowCount' => int]
   */
  private function checkSpaceQuota(int $uid, int $additionalSize = 0): array
  {
    // 开源版：无 VIP 功能，使用固定的空间配额
    $usedSpace = Attachment::getUsedSpace($uid);
    $allowCount = $this->getSpaceQuota();

    $allowed = ($usedSpace + $additionalSize) <= $allowCount;
    $message = $allowed ? '' : '你使用的空间超出限制。请在项目列表点击更多，进入"文件库"来清理不需要的空间。如有疑问请联系网站管理员';

    return [
      'allowed' => $allowed,
      'message' => $message,
      'usedSpace' => $usedSpace,
      'allowCount' => $allowCount,
    ];
  }

  /**
   * 获取用户的空间配额限制（字节）
   * 开源版：固定 1TB
   *
   * @return int 配额限制（字节）
   */
  private function getSpaceQuota(): int
  {
    return 1 * 1024 * 1024 * 1024 * 1024; // 开源版固定 1TB
  }

  /**
   * 获取用户的单文件大小限制（字节）
   * 开源版：固定 10GB
   *
   * @return int 文件大小限制（字节）
   */
  private function getFileSizeLimit(): int
  {
    return 10 * 1024 * 1024 * 1024; // 开源版固定 10GB
  }

  /**
   * 通过 URL 上传文件
   *
   * @param int $itemId 项目ID
   * @param int $pageId 页面ID
   * @param string $fileUrl 文件URL
   * @param string $fileName 文件名
   * @param int $uid 用户ID
   * @param int $fileSizeLimit 文件大小限制（字节）
   * @return array
   * @throws McpException
   */
  private function uploadFromUrl(int $itemId, int $pageId, string $fileUrl, string $fileName, int $uid, int $fileSizeLimit): array
  {
    // 下载文件
    $fileContent = @file_get_contents($fileUrl);
    if ($fileContent === false) {
      McpError::throw(McpError::OPERATION_FAILED, '无法下载文件: ' . $fileUrl);
    }

    // 检查文件大小
    $fileSize = strlen($fileContent);
    if ($fileSize > $fileSizeLimit) {
      $limitMB = round($fileSizeLimit / 1024 / 1024, 1);
      McpError::throw(McpError::OPERATION_FAILED, "文件大小超出限制（{$limitMB}MB）。可开通更高级版本获取更大限制");
    }

    // 获取文件名
    if ($fileName === '') {
      $urlPath = parse_url($fileUrl, PHP_URL_PATH);
      $fileName = basename($urlPath ?: 'file');
    }

    // 检查文件名是否允许
    if (!Attachment::isAllowedFilename($fileName)) {
      McpError::throw(McpError::OPERATION_FAILED, '不支持上传该文件类型。可将文件压缩成 zip/rar 等压缩包后上传');
    }

    // 获取文件类型
    $fileType = $this->getMimeType($fileName, $fileContent);

    // 保存到临时文件
    $tmpFile = sys_get_temp_dir() . '/mcp_upload_' . uniqid('', true);
    file_put_contents($tmpFile, $fileContent);

    try {
      // 构建 $_files 格式
      $_files = [
        'file' => [
          'name'     => $fileName,
          'type'     => $fileType,
          'tmp_name' => $tmpFile,
          'error'    => UPLOAD_ERR_OK,
          'size'     => $fileSize,
        ],
      ];

      // 调用 Attachment::upload
      $showdocUrl = Attachment::upload($_files, 'file', $uid, $itemId, $pageId, false);

      if (!$showdocUrl) {
        McpError::throw(McpError::OPERATION_FAILED, '上传失败');
      }

      // 从 URL 中提取 sign 参数
      $sign = '';
      $parsedUrl = parse_url($showdocUrl);
      if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
        $sign = $queryParams['sign'] ?? '';
      }

      // 通过 sign 查找 file_id
      $fileId = 0;
      if ($sign) {
        $file = UploadFile::findBySign($sign);
        if ($file) {
          $fileId = (int) ($file->file_id ?? 0);
        }
      }

      return [
        'file_id'   => $fileId,
        'file_name' => $fileName,
        'file_type' => $fileType,
        'file_size' => $fileSize,
        'url'       => $showdocUrl,
        'sign'      => $sign,
        'item_id'   => $itemId,
        'page_id'   => $pageId,
        'message'   => '上传成功',
      ];
    } finally {
      // 清理临时文件
      @unlink($tmpFile);
    }
  }

  /**
   * 通过 Base64 上传文件
   *
   * @param int $itemId 项目ID
   * @param int $pageId 页面ID
   * @param string $fileBase64 Base64 编码的文件内容
   * @param string $fileName 文件名
   * @param int $uid 用户ID
   * @param int $fileSizeLimit 文件大小限制（字节）
   * @return array
   * @throws McpException
   */
  private function uploadFromBase64(int $itemId, int $pageId, string $fileBase64, string $fileName, int $uid, int $fileSizeLimit): array
  {
    // 解码 Base64
    $fileContent = base64_decode($fileBase64, true);
    if ($fileContent === false) {
      McpError::throw(McpError::INVALID_PARAMS, 'Base64 解码失败');
    }

    // 检查文件大小
    $fileSize = strlen($fileContent);
    if ($fileSize > $fileSizeLimit) {
      $limitMB = round($fileSizeLimit / 1024 / 1024, 1);
      McpError::throw(McpError::OPERATION_FAILED, "文件大小超出限制（{$limitMB}MB）。可开通更高级版本获取更大限制");
    }

    // 生成文件名
    if ($fileName === '') {
      $fileName = 'file_' . time() . '.bin';
    }

    // 检查文件名是否允许
    if (!Attachment::isAllowedFilename($fileName)) {
      McpError::throw(McpError::OPERATION_FAILED, '不支持上传该文件类型。可将文件压缩成 zip/rar 等压缩包后上传');
    }

    // 获取文件类型
    $fileType = $this->getMimeType($fileName, $fileContent);

    // 保存到临时文件
    $tmpFile = sys_get_temp_dir() . '/mcp_upload_' . uniqid('', true);
    file_put_contents($tmpFile, $fileContent);

    try {
      // 构建 $_files 格式
      $_files = [
        'file' => [
          'name'     => $fileName,
          'type'     => $fileType,
          'tmp_name' => $tmpFile,
          'error'    => UPLOAD_ERR_OK,
          'size'     => $fileSize,
        ],
      ];

      // 调用 Attachment::upload
      $showdocUrl = Attachment::upload($_files, 'file', $uid, $itemId, $pageId, false);

      if (!$showdocUrl) {
        McpError::throw(McpError::OPERATION_FAILED, '上传失败');
      }

      // 从 URL 中提取 sign 参数
      $sign = '';
      $parsedUrl = parse_url($showdocUrl);
      if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
        $sign = $queryParams['sign'] ?? '';
      }

      // 通过 sign 查找 file_id
      $fileId = 0;
      if ($sign) {
        $file = UploadFile::findBySign($sign);
        if ($file) {
          $fileId = (int) ($file->file_id ?? 0);
        }
      }

      return [
        'file_id'   => $fileId,
        'file_name' => $fileName,
        'file_type' => $fileType,
        'file_size' => $fileSize,
        'url'       => $showdocUrl,
        'sign'      => $sign,
        'item_id'   => $itemId,
        'page_id'   => $pageId,
        'message'   => '上传成功',
      ];
    } finally {
      // 清理临时文件
      @unlink($tmpFile);
    }
  }

  /**
   * 获取附件列表
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function listAttachments(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    $pageId = (int) ($params['page_id'] ?? 0);

    if ($itemId <= 0 && $pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '必须提供 item_id 或 page_id');
    }

    // 如果只提供了 page_id，需要获取 item_id 来检查权限
    if ($itemId <= 0 && $pageId > 0) {
      // 通过 page 主表索引获取 item_id（替代遍历 100 个分表）
      $pageRow = DB::table('page')
        ->where('page_id', $pageId)
        ->where('is_del', 0)
        ->first();
      if (!$pageRow) {
        McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
      }
      $itemId = (int) $pageRow->item_id;
    }

    // 检查读取权限
    $this->requireReadPermission($itemId);

    // 构建查询 - 通过 file_page 表关联
    $query = DB::table('upload_file as uf')
      ->join('file_page as fp', 'uf.file_id', '=', 'fp.file_id')
      ->where('fp.item_id', $itemId);

    if ($pageId > 0) {
      $query->where('fp.page_id', $pageId);
    }

    $files = $query->select('uf.*')
      ->orderBy('uf.addtime', 'desc')
      ->limit(500)
      ->get()
      ->all();

    $result = [];
    foreach ($files as $row) {
      // 构建访问 URL
      $sign = $row->sign ?? '';
      $url = '';
      if ($sign) {
        $extension = '';
        $realUrl = $row->real_url ?? '';
        if ($realUrl) {
          $parsedUrl = parse_url($realUrl);
          if ($parsedUrl && isset($parsedUrl['path'])) {
            $extension = pathinfo($parsedUrl['path'], PATHINFO_EXTENSION);
          }
        }
        $params = ['sign' => $sign];
        if ($extension) {
          $params['file'] = 'file.' . $extension;
        }
        $url = UrlHelper::serverUrl('api/attachment/visitFile', $params);
      }

      $result[] = [
        'file_id'   => (int) $row->file_id,
        'file_name' => $row->display_name ?? '',
        'file_type' => $row->file_type ?? '',
        'file_size' => (int) ($row->file_size ?? 0),
        'url'       => $url,
        'sign'      => $sign,
        'item_id'   => $itemId,
        'page_id'   => $pageId,
        'addtime'   => date('Y-m-d H:i:s', (int) ($row->addtime ?? 0)),
      ];
    }

    return [
      'item_id'     => $itemId,
      'page_id'     => $pageId,
      'attachments' => $result,
      'total'       => count($result),
    ];
  }

  /**
   * 删除附件
   *
   * @param array $params 参数
   * @return array
   * @throws McpException
   */
  private function deleteAttachment(array $params): array
  {
    $fileId = (int) ($params['file_id'] ?? 0);
    $sign = trim($params['sign'] ?? '');

    if ($fileId <= 0 && $sign === '') {
      McpError::throw(McpError::INVALID_PARAMS, '必须提供 file_id 或 sign');
    }

    // 查找文件
    $file = null;
    if ($fileId > 0) {
      $file = UploadFile::findById($fileId);
    } else {
      $file = UploadFile::findBySign($sign);
    }

    if (!$file) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '附件不存在');
    }

    $fileId = (int) ($file->file_id ?? 0);
    $itemId = (int) ($file->item_id ?? 0);

    // 检查写入权限
    $this->requireWritePermission($itemId);

    // 删除文件
    $deleted = Attachment::deleteFile($fileId);
    if (!$deleted) {
      McpError::throw(McpError::OPERATION_FAILED, '删除失败');
    }

    return [
      'file_id'   => $fileId,
      'file_name' => $file->display_name ?? '',
      'message'   => '删除成功',
    ];
  }

  /**
   * 获取 MIME 类型
   *
   * @param string $fileName 文件名
   * @param string $content 文件内容
   * @return string
   */
  private function getMimeType(string $fileName, string $content): string
  {
    // 常见扩展名映射
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
      'jpg'  => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'png'  => 'image/png',
      'gif'  => 'image/gif',
      'webp' => 'image/webp',
      'svg'  => 'image/svg+xml',
      'pdf'  => 'application/pdf',
      'doc'  => 'application/msword',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'xls'  => 'application/vnd.ms-excel',
      'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'ppt'  => 'application/vnd.ms-powerpoint',
      'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      'txt'  => 'text/plain',
      'json' => 'application/json',
      'xml'  => 'application/xml',
      'zip'  => 'application/zip',
      'rar'  => 'application/x-rar-compressed',
      'tar'  => 'application/x-tar',
      'gz'   => 'application/gzip',
      'mp3'  => 'audio/mpeg',
      'wav'  => 'audio/wav',
      'mp4'  => 'video/mp4',
      'mov'  => 'video/quicktime',
      'css'  => 'text/css',
      'csv'  => 'text/csv',
    ];

    if (isset($mimeTypes[$extension])) {
      return $mimeTypes[$extension];
    }

    // 尝试通过内容检测
    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($content);
    return $mimeType ?: 'application/octet-stream';
  }
}
