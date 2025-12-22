<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\ExportLog;
use App\Model\Item;
use App\Model\Page;
use App\Model\Member;
use App\Model\Runapi;
use App\Model\ItemChangeLog;
use App\Common\Helper\FileHelper;
use App\Common\Helper\Convert;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ExportController extends BaseController
{
    /**
     * 检查 Markdown 导出限制
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function checkMarkdownLimit(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $exportFormat = $this->getParam($request, 'export_format', '');

        if ($exportFormat == 'markdown') {
            // 开源版无导出次数限制
        }

        return $this->success($response, []);
    }

    /**
     * 导出整个项目为 Word
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function word(Request $request, Response $response): Response
    {
        set_time_limit(200);
        ini_set('memory_limit', '1500M');

        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);
        $catId = $this->getParam($request, 'cat_id', 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10302, '没有权限');
        }

        // 获取项目信息
        $item = Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 检查是否为 runapi 项目并获取全局 header
        $globalHeaders = [];
        if ((int) ($item->item_type ?? 0) == 3) {
            $globalParam = Runapi::getGlobalParam($itemId);
            if (!empty($globalParam['header'])) {
                $globalHeaders = $globalParam['header'];
            }
        }

        // 成员目录权限：获取该用户在此项目下允许的目录集合
        $allowedCatIds = Member::getCatIds($itemId, $uid);

        // 获取菜单结构
        $menu = Item::getContent($itemId, true); // 解压内容

        $pages = [];
        $catalogs = [];

        if ($pageId > 0) {
            // 导出单个页面
            $page = Page::findPageByCache($pageId, $itemId);
            if (!$page) {
                return $this->error($response, 10101, '页面不存在');
            }
            // 如果有限定目录，则校验页面所属目录是否在允许集合内
            if (!empty($allowedCatIds)) {
                $pageCatId = (int) ($page['cat_id'] ?? 0);
                $allowed = array_flip(array_map('intval', $allowedCatIds));
                if (!isset($allowed[$pageCatId])) {
                    return $this->error($response, 10302, '没有权限');
                }
            }
            $pages[] = $page;
        } elseif ($catId > 0) {
            // 导出指定目录
            if (!empty($allowedCatIds) && !in_array($catId, array_map('intval', $allowedCatIds))) {
                return $this->error($response, 10302, '没有权限');
            }
            // 从菜单中找到指定目录
            $found = $this->findCatalogInMenu($menu['catalogs'], $catId);
            if ($found) {
                $pages = $found['pages'] ?? [];
                $catalogs = [$found];
            }
        } else {
            // 导出整个项目
            if (!empty($allowedCatIds)) {
                // 仅导出被允许的二级目录
                $allowed = array_flip(array_map('intval', $allowedCatIds));
                $catalogs = [];
                foreach ($menu['catalogs'] ?? [] as $one) {
                    if (isset($allowed[(int) ($one['cat_id'] ?? 0)])) {
                        $catalogs[] = $one;
                    }
                }
                $pages = [];
            } else {
                $pages = $menu['pages'] ?? [];
                $catalogs = $menu['catalogs'] ?? [];
            }
        }

        // 生成 Word 内容
        // 引入 Parsedown 类（Markdown 解析器）
        if (!class_exists('Parsedown')) {
            require_once __DIR__ . '/../../Common/Vendor/Parsedown.php';
        }
        $parsedown = new \Parsedown();
        $convert = new Convert();
        $data = '';
        $parent = 1;

        // 如果是 runapi 项目且有全局 header，则先添加全局 header 信息
        if (!empty($globalHeaders)) {
            $data .= "<h1>全局Header参数</h1>";
            $data .= '<div style="margin-left:20px;">';
            $data .= "<table>";
            $data .= "<thead><tr><th>参数名</th><th>值</th><th>是否启用</th><th>备注</th></tr></thead>";
            $data .= "<tbody>";
            foreach ($globalHeaders as $header) {
                $enabled = isset($header['enabled']) && $header['enabled'] ? '是' : '否';
                $name = isset($header['name']) ? htmlspecialchars($header['name']) : '';
                $value = isset($header['value']) ? htmlspecialchars($header['value']) : '';
                $remark = isset($header['remark']) ? htmlspecialchars($header['remark']) : '';
                $data .= "<tr><td>{$name}</td><td>{$value}</td><td>{$enabled}</td><td>{$remark}</td></tr>";
            }
            $data .= "</tbody></table>";
            $data .= '</div>';
            $parent++;
        }

        // 处理页面
        if (!empty($pages)) {
            foreach ($pages as $value) {
                if (count($pages) > 1) {
                    $data .= "<h1>{$parent}、" . htmlspecialchars($value['page_title'] ?? '') . "</h1>";
                } else {
                    $data .= "<h1>" . htmlspecialchars($value['page_title'] ?? '') . "</h1>";
                }
                $data .= '<div style="margin-left:20px;">';
                $pageContent = $value['page_content'] ?? '';
                $tmpContent = $convert->runapiToMd($pageContent);
                $pageContent = $tmpContent ?: $pageContent;
                $data .= htmlspecialchars_decode($parsedown->text($pageContent));
                $data .= '</div>';
                $parent++;
            }
        }

        // 处理目录（递归处理多级目录）
        if (!empty($catalogs)) {
            foreach ($catalogs as $value) {
                $data .= "<h1>{$parent}、" . htmlspecialchars($value['cat_name'] ?? '') . "</h1>";
                $data .= '<div style="margin-left:0px;">';
                $child = 1;
                $data .= $this->renderCatalogPages($value, $parent, $child, $parsedown, $convert);
                $data .= '</div>';
                $parent++;
            }
        }

        // 写导出记录
        ExportLog::add([
            'uid'         => $uid,
            'export_type' => 'word',
            'item_id'     => $itemId,
            'addtime'     => date('y-m-d H:i:s'),
        ]);

        // 记录项目变更日志
        ItemChangeLog::addLog($uid, $itemId, 'export', 'item', $itemId, $item->item_name ?? '');

        // 输出 Word 文档
        FileHelper::outputWord($data, 'showdoc_export_' . date('YmdHis'));

        // 注意：outputWord 会直接输出并退出，所以这里不会执行
        return $response;
    }

    /**
     * 递归渲染目录下的页面
     */
    private function renderCatalogPages($catalog, $parentNum, &$childNum, $parsedown, $convert, $level = 1): string
    {
        $data = '';
        $hTag = 'h' . min($level + 1, 6);
        $indent = $level * 20;

        if (!empty($catalog['pages'])) {
            foreach ($catalog['pages'] as $page) {
                $pageTitle = $page['page_title'] ?? '';
                $data .= "<{$hTag}>{$parentNum}.{$childNum}、" . htmlspecialchars($pageTitle) . "</{$hTag}>";
                $data .= '<div style="margin-left:' . $indent . 'px;">';
                $pageContent = $page['page_content'] ?? '';
                $tmpContent = $convert->runapiToMd($pageContent);
                $pageContent = $tmpContent ?: $pageContent;
                $data .= htmlspecialchars_decode($parsedown->text($pageContent));
                $data .= '</div>';
                $childNum++;
            }
        }

        if (!empty($catalog['catalogs'])) {
            $subParent = 1;
            foreach ($catalog['catalogs'] as $subCatalog) {
                $subChild = 1;
                $data .= $this->renderCatalogPages($subCatalog, $parentNum . '.' . $subParent, $subChild, $parsedown, $convert, $level + 1);
                $subParent++;
            }
        }

        return $data;
    }

    /**
     * 在菜单中查找指定目录
     */
    private function findCatalogInMenu($catalogs, $catId): ?array
    {
        if (empty($catalogs)) {
            return null;
        }

        foreach ($catalogs as $catalog) {
            if ((int) ($catalog['cat_id'] ?? 0) === $catId) {
                return $catalog;
            }
            if (!empty($catalog['catalogs'])) {
                $found = $this->findCatalogInMenu($catalog['catalogs'], $catId);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * 导出整个项目为 Markdown 压缩包
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function markdown(Request $request, Response $response): Response
    {
        set_time_limit(100);
        ini_set('memory_limit', '800M');

        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        // 权限检查
        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10302, '没有权限');
        }

        // 开源版无导出次数限制

        // 获取项目信息
        $item = Item::findById($itemId);
        if (!$item) {
            return $this->error($response, 10101, '项目不存在');
        }

        // 成员目录权限：获取该用户在此项目下允许的目录集合
        $allowedCatIds = Member::getCatIds($itemId, $uid);

        // 导出项目数据
        $exportJson = Item::export($itemId, true);
        $exportData = json_decode($exportJson, true);

        // 如果有限定目录，则过滤数据
        if (!empty($allowedCatIds) && isset($exportData['pages']) && is_array($exportData['pages'])) {
            $allowed = array_flip(array_map('intval', $allowedCatIds));
            // 目录受限：去掉根目录下的页面，仅保留被允许的二级目录
            $exportData['pages']['pages'] = [];
            $filteredCatalogs = [];
            if (!empty($exportData['pages']['catalogs']) && is_array($exportData['pages']['catalogs'])) {
                foreach ($exportData['pages']['catalogs'] as $one) {
                    if (isset($allowed[(int) ($one['cat_id'] ?? 0)])) {
                        $filteredCatalogs[] = $one;
                    }
                }
            }
            $exportData['pages']['catalogs'] = $filteredCatalogs;
        }

        // 创建临时目录
        $tempDir = sys_get_temp_dir() . '/showdoc_' . time() . rand();
        if (!mkdir($tempDir, 0755, true)) {
            return $this->error($response, 10500, '创建临时目录失败');
        }

        // 保存 info.json
        unset($exportData['members']);
        file_put_contents($tempDir . '/info.json', json_encode($exportData, JSON_UNESCAPED_UNICODE));

        // 将 Markdown 内容写入文件
        $this->markdownToFile($exportData['pages'] ?? [], $tempDir);

        // 创建 ZIP 文件
        $tempFile = tempnam(sys_get_temp_dir(), 'Tux') . '_showdoc_.zip';
        $zip = new \ZipArchive();
        if ($zip->open($tempFile, \ZipArchive::CREATE) !== true) {
            FileHelper::clearRuntime($tempDir);
            return $this->error($response, 10500, '创建 ZIP 文件失败');
        }

        $this->addDirectoryToZip($tempDir, $zip, '');
        $zip->close();

        // 清理临时目录
        FileHelper::clearRuntime($tempDir);
        @rmdir($tempDir);

        // 写导出记录
        ExportLog::add([
            'uid'         => $uid,
            'export_type' => 'markdown',
            'item_id'     => $itemId,
            'addtime'     => date('y-m-d H:i:s'),
        ]);

        // 记录项目变更日志
        ItemChangeLog::addLog($uid, $itemId, 'export', 'item', $itemId, $item->item_name ?? '');

        // 输出 ZIP 文件
        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=showdoc.zip');
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($tempFile));
        @readfile($tempFile);
        @unlink($tempFile);

        // 注意：直接输出文件后会退出，所以这里不会执行
        return $response;
    }

    /**
     * 将目录数据转换为 Markdown 文件，保持目录结构
     */
    private function markdownToFile($catalogData, $tempDir, $basePath = ''): void
    {
        // 处理当前目录下的页面
        if (isset($catalogData['pages']) && !empty($catalogData['pages'])) {
            foreach ($catalogData['pages'] as $value) {
                $filename = FileHelper::sanitizeFilename($value['page_title'] ?? '未命名') . '.md';
                $filePath = $basePath ? $basePath . '/' . $filename : $filename;
                $fullPath = $tempDir . '/' . $filePath;

                // 如果文件已存在，添加序号避免冲突
                $counter = 1;
                while (file_exists($fullPath)) {
                    $nameWithoutExt = FileHelper::sanitizeFilename($value['page_title'] ?? '未命名');
                    $filename = $nameWithoutExt . '_' . $counter . '.md';
                    $filePath = $basePath ? $basePath . '/' . $filename : $filename;
                    $fullPath = $tempDir . '/' . $filePath;
                    $counter++;
                }

                // 确保目录存在
                $dir = dirname($fullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // 保存文件内容
                file_put_contents($fullPath, htmlspecialchars_decode($value['page_content'] ?? ''));
            }
        }

        // 递归处理子目录
        if (isset($catalogData['catalogs']) && !empty($catalogData['catalogs'])) {
            foreach ($catalogData['catalogs'] as $value) {
                $catName = $value['cat_name'] ?? '目录';
                $dirName = FileHelper::sanitizeFilename($catName);
                $newBasePath = $basePath ? $basePath . '/' . $dirName : $dirName;

                // 如果目录名已存在，添加序号避免冲突
                $dirFullPath = $tempDir . '/' . $newBasePath;
                $counter = 1;
                while (is_dir($dirFullPath)) {
                    $dirName = FileHelper::sanitizeFilename($catName) . '_' . $counter;
                    $newBasePath = $basePath ? $basePath . '/' . $dirName : $dirName;
                    $dirFullPath = $tempDir . '/' . $newBasePath;
                    $counter++;
                }

                // 递归处理子目录
                $this->markdownToFile($value, $tempDir, $newBasePath);
            }
        }
    }

    /**
     * 递归添加目录到 ZIP 文件
     */
    private function addDirectoryToZip($dir, $zip, $zipPath): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $filePath = $dir . '/' . $file;
            $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;

            if (is_dir($filePath)) {
                // 递归处理子目录
                $this->addDirectoryToZip($filePath, $zip, $zipFilePath);
            } else {
                // 添加文件
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }
}
