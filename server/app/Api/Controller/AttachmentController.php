<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * 附件相关 Api（新架构）。
 *
 * 注意：文件上传功能需要依赖 OSS（七牛云）配置，部分功能可能需要进一步实现。
 */
class AttachmentController extends BaseController
{
    /**
     * 默认入口/兼容接口（兼容旧接口 Api/Attachment/index）。
     *
     * 说明：旧实现主要用于占位，这里简单返回成功，以便旧客户端探活。
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->success($response, ['status' => 'ok']);
    }

    /**
     * 设置大文件上传支持
     */
    private function setLargeFileUploadSupport(): void
    {
        // 设置上传文件大小限制为 500MB
        ini_set('upload_max_filesize', '500M');
        ini_set('post_max_size', '500M');

        // 设置内存限制为 512MB
        ini_set('memory_limit', '512M');

        // 设置最大执行时间为 300 秒（5分钟）
        ini_set('max_execution_time', 300);

        // 设置输入时间限制为 300 秒
        ini_set('max_input_time', 300);
    }

    /**
     * 浏览附件（兼容旧接口 Api/Attachment/visitFile）。
     *
     * 功能：
     * - 根据 sign 查找文件
     * - 微信浏览器 APK 文件特殊处理
     * - 更新访问次数和记录流量
     * - 访问/下载行为与旧版开源 AttachmentController::visitFile 完全一致：
     *   - 先选 cache_url，否则用 real_url；
     *   - 根据 oss_open 判断是否使用 OSS；
     *   - 若 oss_open = 0 且 URL 指向本地 Public/Uploads，且为“非图片/非常见文档”并且有 display_name，则做本地下载（Content-Disposition: attachment 输出文件内容）；
     *   - 其他情况直接 302 跳转到 URL。
     */
    public function visitFile(Request $request, Response $response): Response
    {
        $sign = $this->getParam($request, 'sign', '');
        $imageView2 = $this->getParam($request, 'imageView2', ''); // 旧版有该参数，但未实际使用，这里保留兼容

        if (empty($sign)) {
            return $response->withStatus(404)->withBody(new \Slim\Psr7\Stream(fopen('php://temp', 'r+')));
        }

        $file = \App\Model\UploadFile::findBySign($sign);
        if (!$file) {
            $response->getBody()->write('www.showdoc.com.cn');
            return $response;
        }

        $fileData = (array) $file;
        $uid = (int) ($fileData['uid'] ?? 0);

        // 如果是 APK 文件且在微信浏览器中打开
        $userAgent = $request->getHeaderLine('User-Agent');
        $realUrl = $fileData['real_url'] ?? '';
        if (strpos($userAgent, 'MicroMessenger') !== false && strpos($realUrl, '.apk') !== false) {
            $html = '<head><title>温馨提示</title></head><br><h1>微信不支持直接下载，请点击右上角"---"在外部浏览器中打开</h1>';
            $response->getBody()->write($html);
            return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        }

        // 更新访问次数
        DB::table('upload_file')
            ->where('file_id', (int) $fileData['file_id'])
            ->update([
                'visit_times'     => (int) ($fileData['visit_times'] ?? 0) + 1,
                'last_visit_time' => time(),
            ]);

        // 记录用户流量（开源版保留此功能）
        \App\Model\Attachment::recordUserFlow($uid, (int) ($fileData['file_size'] ?? 0));

        // 构建下载 URL：优先 cache_url，否则 real_url
        if (!empty($fileData['cache_url'])) {
            $url = $fileData['cache_url'];
        } else {
            $url = $realUrl;
        }

        // 本地文件路径解析（与旧版逻辑一致）
        $array = explode('/Public/Uploads/', $url);
        $filePath = '';
        if (count($array) > 1 && !empty($array[1])) {
            // 旧版使用 ../Public/Uploads/，这里根据项目根目录计算真实路径
            $projectRoot = dirname(__DIR__, 3); // .../showdoc
            $filePath = $projectRoot . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'Uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $array[1]);
        }

        // 根据 oss_open 决定是否走本地下载逻辑
        $ossOpen = (int) \App\Model\Options::get('oss_open', 0);
        if (
            $ossOpen === 0 &&
            $filePath &&
            is_file($filePath) &&
            !empty($fileData['display_name']) &&
            !strstr(strtolower($filePath), '.bmp') &&
            !strstr(strtolower($filePath), '.jpg') &&
            !strstr(strtolower($filePath), '.png') &&
            !strstr(strtolower($filePath), '.pdf') &&
            !strstr(strtolower($filePath), '.doc') &&
            !strstr(strtolower($filePath), '.xls') &&
            !strstr(strtolower($filePath), '.ppt')
        ) {
            // 本地下载逻辑：设置 Content-Disposition: attachment 输出文件内容
            return $this->downloadLocalFile($filePath, (string) $fileData['display_name'], $response);
        }

        // 其他情况直接 302 跳转到 URL（与旧版一致）
        return $response->withStatus(302)->withHeader('Location', $url);
    }

    /**
     * 上传图片（兼容旧接口 Api/Attachment/uploadImg）。
     *
     * 使用 Attachment::upload() 方法，通过 OssHelper 上传到七牛云，不依赖 ThinkPHP。
     */
    public function uploadImg(Request $request, Response $response): Response
    {
        $this->setLargeFileUploadSupport();

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        // 检查文件是否存在
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['editormd-image-file'])) {
            return $this->json($response, ['message' => '未找到上传文件', 'success' => 0]);
        }

        $uploadedFile = $uploadedFiles['editormd-image-file'];

        // 处理 blob 文件名
        $originalName = $uploadedFile->getClientFilename();
        if ($originalName === 'blob') {
            $originalName = 'blob.jpg';
        }

        // 检查文件扩展名
        $checkFilename = true;
        $user = \App\Model\User::findById($uid);
        if ($user && (int) ($user->groupid ?? 0) === 1) {
            $checkFilename = false; // 管理员不检查
        }

        if ($checkFilename && !\App\Model\Attachment::isAllowedFilename($originalName)) {
            return $this->json($response, ['message' => '不支持上传该文件类型。可将文件压缩成 zip/rar 等压缩包后上传，或联系网站管理员', 'success' => 0]);
        }

        // 创建临时文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
        $uploadedFile->moveTo($tmpFile);

        // 构建 $_FILES 数组格式（兼容旧接口）
        $_files = [
            'editormd-image-file' => [
                'name' => $originalName,
                'type' => $uploadedFile->getClientMediaType(),
                'tmp_name' => $tmpFile,
                'size' => $uploadedFile->getSize(),
            ],
        ];

        // 上传文件
        $url = \App\Model\Attachment::upload($_files, 'editormd-image-file', $uid, $itemId, $pageId, $checkFilename);

        // 清理临时文件
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }

        if ($url) {
            return $this->json($response, ['url' => $url, 'success' => 1]);
        } else {
            return $this->json($response, ['message' => '上传失败', 'success' => 0]);
        }
    }

    /**
     * 上传附件（兼容旧接口 Api/Attachment/attachmentUpload）。
     *
     * 使用 Attachment::upload() 方法，通过 OssHelper 上传到七牛云，不依赖 ThinkPHP。
     */
    public function attachmentUpload(Request $request, Response $response): Response
    {
        $this->setLargeFileUploadSupport();

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        // 如果附件是要上传绑定到某个页面，那么检验项目权限
        if ($pageId > 0 || $itemId > 0) {
            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10103, '您没有编辑权限');
            }
        }

        // 检查文件是否存在
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return $this->error($response, 10101, '未找到上传文件');
        }

        $uploadedFile = $uploadedFiles['file'];

        // 检查文件扩展名
        $checkFilename = true;
        $user = \App\Model\User::findById($uid);
        if ($user && (int) ($user->groupid ?? 0) === 1) {
            $checkFilename = false; // 管理员不检查
        }

        $originalName = $uploadedFile->getClientFilename();
        if ($checkFilename && !\App\Model\Attachment::isAllowedFilename($originalName)) {
            return $this->error($response, 10101, '不支持上传该文件类型。可将文件压缩成 zip/rar 等压缩包后上传，或联系网站管理员');
        }

        // 创建临时文件
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload_');
        $uploadedFile->moveTo($tmpFile);

        // 构建 $_FILES 数组格式（兼容旧接口）
        $_files = [
            'file' => [
                'name' => $originalName,
                'type' => $uploadedFile->getClientMediaType(),
                'tmp_name' => $tmpFile,
                'size' => $uploadedFile->getSize(),
            ],
        ];

        // 上传文件
        $url = \App\Model\Attachment::upload($_files, 'file', $uid, $itemId, $pageId, $checkFilename);

        // 清理临时文件
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }

        if ($url) {
            return $this->json($response, ['url' => $url, 'success' => 1]);
        } else {
            return $this->error($response, 10101, '上传失败');
        }
    }

    /**
     * 页面的上传附件列表（兼容旧接口 Api/Attachment/pageAttachmentUploadList）。
     */
    public function pageAttachmentUploadList(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($pageId <= 0) {
            return $this->error($response, 10103, '请至少先保存一次页面内容');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 获取页面的附件列表
        $files = \App\Model\FilePage::getPageAttachments($pageId);

        if (!empty($files)) {
            // 获取第一个文件的 item_id 用于权限检查
            $firstFile = DB::table('file_page')
                ->where('page_id', $pageId)
                ->first();
            if ($firstFile) {
                $itemId = (int) ($firstFile->item_id ?? 0);
                if (!$this->checkItemVisit($uid, $itemId)) {
                    return $this->error($response, 10103, '您没有访问权限');
                }
            }
        }

        // 构建返回数据
        $result = [];
        foreach ($files as $file) {
            $sign = DB::table('upload_file')
                ->where('file_id', $file['file_id'])
                ->value('sign');
            if ($sign) {
                $url = UrlHelper::serverUrl('api/attachment/visitFile', ['sign' => $sign]);
                $result[] = [
                    'file_id'     => $file['file_id'],
                    'display_name' => $file['display_name'],
                    'url'         => $url,
                    'addtime'     => $file['addtime'],
                ];
            }
        }

        return $this->success($response, $result);
    }

    /**
     * 删除页面中已上传文件（兼容旧接口 Api/Attachment/deletePageUploadFile）。
     */
    public function deletePageUploadFile(Request $request, Response $response): Response
    {
        $fileId = $this->getParam($request, 'file_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($fileId <= 0 || $pageId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查文件关联的页面数量
        $count = \App\Model\FilePage::getPageCount($fileId);
        if ($count <= 1) {
            // 如果只有一个页面关联，则删除整个文件
            return $this->deleteMyAttachment($request, $response);
        } else {
            // 如果有多个页面关联，只删除页面关联
            $page = DB::table('page')
                ->where('page_id', $pageId)
                ->first();
            if (!$page) {
                return $this->error($response, 10101, '页面不存在');
            }

            $itemId = (int) ($page->item_id ?? 0);
            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10103, '您没有编辑权限');
            }

            $deleted = \App\Model\FilePage::delete($fileId, $pageId);
            if ($deleted) {
                return $this->success($response, []);
            } else {
                return $this->error($response, 10101, '删除失败');
            }
        }
    }

    /**
     * 获取全站的附件列表（管理员用）（兼容旧接口 Api/Attachment/getAllList）。
     */
    public function getAllList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理员权限
        $user = \App\Model\User::findById($uid);
        if (!$user || (int) ($user->groupid ?? 0) !== 1) {
            return $this->error($response, 10103, '您没有管理员权限');
        }

        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 20);
        $attachmentType = $this->getParam($request, 'attachment_type', 0);
        $displayName = $this->getParam($request, 'display_name', '');
        $username = $this->getParam($request, 'username', '');

        $filters = [];
        if ($attachmentType > 0) {
            $filters['attachment_type'] = $attachmentType;
        }
        if (!empty($displayName)) {
            $filters['display_name'] = $displayName;
        }
        if (!empty($username)) {
            $filters['username'] = $username;
        }

        $result = \App\Model\UploadFile::getAllList($filters, $page, $count);

        // 构建返回数据
        $list = [];
        foreach ($result['list'] as $file) {
            $url = UrlHelper::serverUrl('api/attachment/visitFile', ['sign' => $file['sign'] ?? '']);
            $list[] = [
                'file_id'        => (int) $file['file_id'],
                'username'       => $file['username'] ?? '',
                'uid'            => (int) $file['uid'],
                'file_type'      => $file['file_type'] ?? '',
                'visit_times'    => (int) ($file['visit_times'] ?? 0),
                'file_size'      => (int) ($file['file_size'] ?? 0),
                'item_id'        => (int) ($file['item_id'] ?? 0),
                'page_id'        => (int) ($file['page_id'] ?? 0),
                'file_size_m'    => $file['file_size_m'] ?? 0,
                'display_name'   => $file['display_name'] ?? '',
                'url'            => $url,
                'addtime'        => $file['addtime'],
                'last_visit_time' => $file['last_visit_time'],
            ];
        }

        return $this->success($response, [
            'list'   => $list,
            'total'  => $result['total'],
            'used'   => $result['used'],
            'used_m' => $result['used_m'],
        ]);
    }

    /**
     * 删除附件（管理员用）（兼容旧接口 Api/Attachment/deleteAttachment）。
     */
    public function deleteAttachment(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理员权限
        $user = \App\Model\User::findById($uid);
        if (!$user || (int) ($user->groupid ?? 0) !== 1) {
            return $this->error($response, 10103, '您没有管理员权限');
        }

        $fileId = $this->getParam($request, 'file_id', 0);
        if ($fileId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        $deleted = \App\Model\Attachment::deleteFile($fileId);
        if ($deleted) {
            return $this->success($response, []);
        } else {
            return $this->error($response, 10101, '删除失败');
        }
    }

    /**
     * 获取我的附件列表（兼容旧接口 Api/Attachment/getMyList）。
     */
    public function getMyList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 20);
        $attachmentType = $this->getParam($request, 'attachment_type', 0);
        $displayName = $this->getParam($request, 'display_name', '');

        $filters = [];
        if ($attachmentType > 0) {
            $filters['attachment_type'] = $attachmentType;
        }
        if (!empty($displayName)) {
            $filters['display_name'] = $displayName;
        }

        $result = \App\Model\UploadFile::getMyList($uid, $filters, $page, $count);

        // 获取本月使用流量
        $usedFlow = \App\Model\Attachment::getUserFlow($uid);
        $result['used_flow_m'] = round($usedFlow / (1024 * 1024), 3);

        // 构建返回数据
        $list = [];
        foreach ($result['list'] as $file) {
            // 使用 UrlHelper 生成完整 URL
            $url = \App\Common\Helper\UrlHelper::serverUrl('api/attachment/visitFile', ['sign' => $file['sign'] ?? '']);
            $list[] = [
                'file_id'        => (int) $file['file_id'],
                'uid'            => (int) $file['uid'],
                'file_type'      => $file['file_type'] ?? '',
                'visit_times'    => (int) ($file['visit_times'] ?? 0),
                'file_size'      => (int) ($file['file_size'] ?? 0),
                'item_id'        => (int) ($file['item_id'] ?? 0),
                'page_id'        => (int) ($file['page_id'] ?? 0),
                'file_size_m'    => $file['file_size_m'] ?? 0,
                'display_name'   => $file['display_name'] ?? '',
                'url'            => $url,
                'addtime'        => $file['addtime'],
                'last_visit_time' => $file['last_visit_time'],
            ];
        }

        return $this->success($response, [
            'list'        => $list,
            'total'       => $result['total'],
            'used'        => $result['used'],
            'used_m'      => $result['used_m'],
            'used_flow_m' => $result['used_flow_m'],
        ]);
    }

    /**
     * 删除附件（我的附件）（兼容旧接口 Api/Attachment/deleteMyAttachment）。
     */
    public function deleteMyAttachment(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $fileId = $this->getParam($request, 'file_id', 0);

        if ($fileId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 检查文件是否属于当前用户
        $file = \App\Model\UploadFile::findById($fileId);
        if (!$file || (int) ($file->uid ?? 0) !== $uid) {
            return $this->error($response, 10101, '文件不存在或无权删除');
        }

        $deleted = \App\Model\Attachment::deleteFile($fileId);
        if ($deleted) {
            return $this->success($response, []);
        } else {
            return $this->error($response, 10101, '删除失败');
        }
    }

    /**
     * 将已上传文件绑定到页面中（兼容旧接口 Api/Attachment/bindingPage）。
     */
    public function bindingPage(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        $fileId = $this->getParam($request, 'file_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        if ($fileId <= 0 || $pageId <= 0) {
            return $this->error($response, 10101, '参数错误');
        }

        // 检查文件是否属于当前用户
        $file = \App\Model\UploadFile::findById($fileId);
        if (!$file || (int) ($file->uid ?? 0) !== $uid) {
            return $this->error($response, 10101, '文件不存在或无权操作');
        }

        // 检查页面权限
        $page = DB::table('page')
            ->where('page_id', $pageId)
            ->first();
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        $itemId = (int) ($page->item_id ?? 0);
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10103, '您没有编辑权限');
        }

        // 添加文件页面关联
        $id = \App\Model\FilePage::add($fileId, $itemId, $pageId);
        if ($id > 0) {
            return $this->success($response, []);
        } else {
            return $this->error($response, 10101, '绑定失败');
        }
    }

    /**
     * 管理员：获取未被使用的附件列表（分页）（兼容旧接口 Api/Attachment/getUnusedList）。
     *
     * 功能：获取所有附件，通过检查 page.page_content 来判断是否被使用。
     * 开源版无分表，直接检查 page.page_content。
     */
    public function getUnusedList(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理员权限
        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        $page = $this->getParam($request, 'page', 1);
        $count = $this->getParam($request, 'count', 10);
        $displayName = $this->getParam($request, 'display_name', '');
        $username = $this->getParam($request, 'username', '');

        // 构建查询条件
        $where = [];
        if (!empty($displayName)) {
            $where[] = ['display_name', 'like', '%' . $displayName . '%'];
        }
        if (!empty($username)) {
            $user = \App\Model\User::findByUsernameOrEmail($username);
            $targetUid = $user ? (int) $user->uid : -99;
            $where[] = ['uid', '=', $targetUid];
        }

        // 获取所有附件
        $query = DB::table('upload_file');
        if (!empty($where)) {
            foreach ($where as $condition) {
                if (count($condition) === 3) {
                    $query->where($condition[0], $condition[1], $condition[2]);
                }
            }
        }
        $candidates = $query->orderBy('addtime', 'desc')->get();

        // 检查哪些附件未被使用
        $unused = [];
        if ($candidates) {
            foreach ($candidates as $value) {
                $fileId = (int) ($value->file_id ?? 0);
                $sign = (string) ($value->sign ?? '');

                if (empty($sign)) {
                    continue;
                }

                // 在页面内容中进行 like 匹配：sign 是 MD5 加密串，重复率很小，直接搜索即可
                // 开源版无分表，直接检查 page.page_content
                $used = DB::table('page')
                    ->where('is_del', 0)
                    ->where('page_content', 'like', '%' . $sign . '%')
                    ->exists();

                if (!$used) {
                    $unused[] = [
                        'file_id'      => $fileId,
                        'display_name' => $value->display_name ?? '',
                        'file_size'    => (int) ($value->file_size ?? 0),
                        'file_size_m'  => round((int) ($value->file_size ?? 0) / (1024 * 1024), 3),
                        'addtime'      => $value->addtime ?? 0,
                        'username'     => $value->username ?? '',
                        'uid'          => (int) ($value->uid ?? 0),
                    ];
                }
            }
        }

        // 分页处理
        $total = count($unused);
        $offset = ($page - 1) * $count;
        $unused = array_slice($unused, $offset, $count);

        return $this->success($response, [
            'list'  => $unused,
            'total' => $total,
        ]);
    }

    /**
     * 输出本地文件到浏览器（下载），兼容旧版 _downloadFile 行为。
     */
    private function downloadLocalFile(string $filename, string $rename, Response $response): Response
    {
        if (!is_file($filename) || !is_readable($filename)) {
            $response->getBody()->write('File not found');
            return $response->withStatus(404);
        }

        // 设置脚本的最大执行时间，设置为0则无时间限制
        @set_time_limit(3000);
        @ini_set('max_execution_time', '0');

        $filesize = filesize($filename);
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            $response->getBody()->write('File not readable');
            return $response->withStatus(500);
        }

        $stream = new \Slim\Psr7\Stream($handle);

        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Accept-Length', (string) $filesize)
            ->withHeader('Content-Disposition', 'attachment;filename=' . basename($rename));
    }

    /**
     * 管理员：批量删除附件（用于清理未使用）（兼容旧接口 Api/Attachment/batchDeleteAttachments）。
     */
    public function batchDeleteAttachments(Request $request, Response $response): Response
    {
        // 获取登录用户
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $uid = (int) ($loginUser['uid'] ?? 0);

        // 检查管理员权限
        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        // 支持 file_ids 数组或逗号分隔字符串
        $fileIds = $this->getParam($request, 'file_ids', '');
        if (empty($fileIds)) {
            return $this->error($response, 10101, '缺少参数');
        }

        if (is_string($fileIds)) {
            $fileIds = explode(',', $fileIds);
        }
        if (!is_array($fileIds)) {
            $fileIds = [];
        }

        $success = 0;
        $failed = 0;
        foreach ($fileIds as $fid) {
            $fid = intval($fid);
            if ($fid <= 0) {
                continue;
            }
            $ret = \App\Model\Attachment::deleteFile($fid);
            if ($ret) {
                $success++;
            } else {
                $failed++;
            }
        }

        return $this->success($response, [
            'success' => $success,
            'failed'  => $failed,
        ]);
    }
}
