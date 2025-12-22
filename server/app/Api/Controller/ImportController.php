<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Model\Page;
use App\Common\Helper\FileHelper;
use Slim\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use const UPLOAD_ERR_OK;

class ImportController extends BaseController
{
    /**
     * 自动检测导入的文件类型从而选择不同的控制器方法
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function auto(Request $request, Response $response): Response
    {
        set_time_limit(100);
        ini_set('memory_limit', '600M');

        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        // 权限检查
        if ($itemId > 0) {
            if (!$this->checkItemEdit($uid, $itemId)) {
                return $this->error($response, 10302, '没有权限');
            }
            Item::deleteCache($itemId); // 清除项目缓存
        }


        // 获取上传的文件
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['file'])) {
            return $this->error($response, 10101, '请上传文件');
        }

        $file = $uploadedFiles['file'];
        $filename = $file->getClientFilename();

        // 检查文件扩展名（只允许 .zip 或 .json 文件）
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['zip', 'json'], true)) {
            return $this->error($response, 10101, '只支持上传 ZIP 或 JSON 格式的文件');
        }

        $tail = $ext;

        if ($tail == 'zip') {
            // ZIP 文件处理
            $tempFile = sys_get_temp_dir() . '/' . FileHelper::getRandStr() . '.zip';
            $file->moveTo($tempFile);

            $zipArc = new \ZipArchive();
            $ret = $zipArc->open($tempFile, \ZipArchive::CREATE);
            if ($ret !== true) {
                @unlink($tempFile);
                return $this->error($response, 10101, 'ZIP 文件打开失败');
            }

            // 先尝试新的格式 info.json，如果不存在则尝试旧的格式 prefix_info.json
            $info = $zipArc->getFromName("info.json");
            if (!$info) {
                $info = $zipArc->getFromName("prefix_info.json");
            }

            // 如有 info.json 或 prefix_info.json 文件，则导入
            if ($info) {
                $infoArray = json_decode($info, true);
                if ($infoArray) {
                    $infoArray['item_id'] = $itemId;
                    $zipArc->close();
                    @unlink($tempFile);
                    return $this->importMarkdownInfo($request, $response, $infoArray, $itemId);
                }
            } else {
                // 如果没有，则尝试解压压缩包后，遍历 markdown 文件导入
                $zipArc->close();
                return $this->importFromReadingMDFile($request, $response, $tempFile, $filename, $itemId);
            }
        }

        if ($tail == 'json') {
            // JSON 文件处理
            $tempFile = sys_get_temp_dir() . '/' . FileHelper::getRandStr() . '.json';
            $file->moveTo($tempFile);
            $json = file_get_contents($tempFile);
            $jsonArray = json_decode($json, true);
            @unlink($tempFile);
            unset($json);

            // 检测 Swagger/OpenAPI 格式
            if (($jsonArray['swagger'] ?? false) || (($jsonArray['openapi'] ?? false) && ($jsonArray['info'] ?? false))) {
                // Swagger 格式，调用 ImportSwaggerController
                $swaggerController = new \App\Api\Controller\ImportSwaggerController();
                // 需要重新构建请求，将文件内容传递给 ImportSwaggerController
                // 由于 ImportSwaggerController 需要从上传的文件中读取，我们需要创建一个临时文件
                $tempFile = sys_get_temp_dir() . '/' . FileHelper::getRandStr() . '.json';
                file_put_contents($tempFile, json_encode($jsonArray, JSON_UNESCAPED_UNICODE));

                // 创建一个新的上传文件对象（模拟上传）
                $streamFactory = new \Slim\Psr7\Factory\StreamFactory();
                $uploadedFile = new UploadedFile(
                    $tempFile,
                    'swagger.json',
                    'application/json',
                    filesize($tempFile),
                    UPLOAD_ERR_OK,
                    false
                );

                // 创建新的请求对象，包含上传的文件
                $newRequest = $request->withUploadedFiles(['file' => $uploadedFile]);

                $result = $swaggerController->import($newRequest, $response);
                @unlink($tempFile);
                return $result;
            }

            // 检测 Postman 格式
            if (($jsonArray['id'] ?? false) || ($jsonArray['info'] ?? false)) {
                // Postman 格式，调用 ImportPostmanController
                $postmanController = new \App\Api\Controller\ImportPostmanController();
                // 需要重新构建请求，将文件内容传递给 ImportPostmanController
                $tempFile = sys_get_temp_dir() . '/' . FileHelper::getRandStr() . '.json';
                file_put_contents($tempFile, json_encode($jsonArray, JSON_UNESCAPED_UNICODE));

                // 创建一个新的上传文件对象（模拟上传）
                $uploadedFile = new UploadedFile(
                    $tempFile,
                    'postman.json',
                    'application/json',
                    filesize($tempFile),
                    UPLOAD_ERR_OK,
                    false
                );

                // 创建新的请求对象，包含上传的文件
                $newRequest = $request->withUploadedFiles(['file' => $uploadedFile]);

                $result = $postmanController->import($newRequest, $response);
                @unlink($tempFile);
                return $result;
            }
        }

        return $this->error($response, 10101, '不支持的文件格式');
    }

    /**
     * 导入 Markdown 压缩包（根据压缩包内的 info 文件导入）
     *
     * @param Request $request
     * @param Response $response
     * @param array|null $infoArray 信息数组（可选）
     * @param int $itemId 项目 ID
     * @return Response
     */
    public function importMarkdownInfo(Request $request, Response $response, ?array $infoArray = null, int $itemId = 0): Response
    {
        set_time_limit(100);
        ini_set('memory_limit', '200M');

        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);

        // 如果没有传入 infoArray，则从上传的文件中读取
        if (!$infoArray) {
            $uploadedFiles = $request->getUploadedFiles();
            if (empty($uploadedFiles['file'])) {
                return $this->error($response, 10101, '请上传文件');
            }

            $file = $uploadedFiles['file'];
            $tempFile = sys_get_temp_dir() . '/' . FileHelper::getRandStr() . '.zip';
            $file->moveTo($tempFile);

            $zipArc = new \ZipArchive();
            $ret = $zipArc->open($tempFile, \ZipArchive::CREATE);
            if ($ret !== true) {
                @unlink($tempFile);
                return $this->error($response, 10101, 'ZIP 文件打开失败');
            }

            // 先尝试新的格式 info.json，如果不存在则尝试旧的格式 prefix_info.json
            $info = $zipArc->getFromName("info.json");
            if (!$info) {
                $info = $zipArc->getFromName("prefix_info.json");
            }
            $zipArc->close();
            @unlink($tempFile);

            if (!$info) {
                return $this->error($response, 10101, '未找到 info.json 文件');
            }

            $infoArray = json_decode($info, true);
            unset($info);
        }

        if ($infoArray) {
            $infoArray['item_id'] = $itemId;
            $json = json_encode($infoArray, JSON_UNESCAPED_UNICODE);
            $result = Item::import($json, $uid, $itemId);
            if ($result) {
                return $this->success($response, ['item_id' => $result]);
            }
        }

        return $this->error($response, 10101, '导入失败');
    }

    /**
     * 从 Markdown 文件导入（遍历目录）
     *
     * @param Request $request
     * @param Response $response
     * @param string $file ZIP 文件路径
     * @param string $filename 文件名
     * @param int $itemId 项目 ID
     * @return Response
     */
    public function importFromReadingMDFile(Request $request, Response $response, string $file, string $filename, int $itemId): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);

        // 如果项目 id 不存在，则新建一个项目
        if ($itemId <= 0) {
            $itemData = [
                'item_name'        => str_replace('.zip', '', $filename),
                'item_domain'      => '',
                'item_type'        => 1,
                'item_description' => '',
                'password'         => FileHelper::getRandStr(),
                'uid'              => $uid,
                'username'          => $user['username'] ?? '',
                'addtime'           => time(),
            ];
            $itemId = Item::add($itemData);
            if ($itemId <= 0) {
                return $this->error($response, 10500, '创建项目失败');
            }
        }

        $zipArc = new \ZipArchive();
        $zipArc->open($file, \ZipArchive::CREATE);

        // 在系统目录创建一个临时目录路径
        $tmpDir = sys_get_temp_dir() . '/' . FileHelper::getRandStr();
        mkdir($tmpDir);

        // 处理中文名乱码问题
        $fileNum = $zipArc->numFiles;
        for ($i = 0; $i < $fileNum; $i++) {
            $statInfo = $zipArc->statIndex($i, \ZipArchive::FL_ENC_RAW);
            $currentEncode = mb_detect_encoding($statInfo['name'], ['ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8']);
            $statInfo['name'] = mb_convert_encoding($statInfo['name'], 'UTF-8', $currentEncode);
            $zipArc->renameIndex($i, $statInfo['name']);
        }
        $zipArc->close();
        $zipArc->open($file, \ZipArchive::CREATE);

        $zipArc->extractTo($tmpDir);

        // 遍历解压后的目录
        $traverseFiles = function ($dir) use (&$traverseFiles, $itemId, $tmpDir, $uid, $user) {
            $handle = opendir($dir);
            while (($file = readdir($handle)) !== false) {
                if ($file !== '..' && $file !== '.') {
                    $f = $dir . '/' . $file;
                    if (is_file($f)) {
                        // 跳过 info.json 和 prefix_info.json 文件
                        if ($file === 'info.json' || $file === 'prefix_info.json') {
                            continue;
                        }
                        // 只处理 .md 文件
                        if (substr($file, -3) !== '.md') {
                            continue;
                        }
                        $pageTitle = str_replace('.md', '', $file);
                        $pageContent = file_get_contents($f);
                        // 获取目录路径，去掉临时目录前缀，并规范化路径分隔符
                        $catName = str_replace($tmpDir, '', $dir);
                        // 统一路径分隔符为 /
                        $catName = str_replace('\\', '/', $catName);
                        // 去掉开头的斜杠和空格
                        $catName = trim($catName, '/ ');
                        // 如果目录名为空，说明是根目录
                        if (empty($catName)) {
                            $catName = '';
                        }
                        Page::updateByTitle(
                            $itemId,
                            $pageTitle,
                            $pageContent,
                            $catName,
                            99,
                            $uid,
                            $user['username'] ?? ''
                        );
                    } else {
                        // 这里目录，则继续递归遍历
                        $traverseFiles($f);
                    }
                }
            }
            closedir($handle);
        };

        $traverseFiles($tmpDir);
        FileHelper::clearRuntime($tmpDir);
        @rmdir($tmpDir);
        $zipArc->close();
        @unlink($file);

        return $this->success($response, []);
    }
}
