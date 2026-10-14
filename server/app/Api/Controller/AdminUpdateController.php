<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Common\Helper\HttpHelper;

/**
 * 管理后台更新相关 Api（开源版）
 */
class AdminUpdateController extends BaseController
{
    /**
     * 检测showdoc版本更新（兼容旧接口 Api/AdminUpdate/checkUpdate）
     */
    public function checkUpdate(Request $request, Response $response): Response
    {
        // 获取当前版本
        $rootPath = dirname(__DIR__, 4);
        $composerPath = $rootPath . '/composer.json';
        
        if (!file_exists($composerPath)) {
            return $this->error($response, 10101, 'composer.json 文件不存在');
        }

        $text = file_get_contents($composerPath);
        $composer = json_decode($text, true);
        $version = $composer['version'] ?? '';
        // ShipDesk 要求纯 semver，去掉 v 前缀
        $versionNum = ltrim($version, 'vV');

        // 上游切换为 ShipDesk 发版台检测更新接口
        $url = "https://shipdesk.showdoc.cc/api/v1/apps/showdoc/clients/source/latest?current_version=" . urlencode($versionNum);
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $sContent = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resArray = is_string($sContent) ? json_decode($sContent, true) : null;

        // 适配 ShipDesk 响应为前端（AboutSite.vue）期望的旧格式 {new_version, url, version}
        $data = [
            'new_version' => '',
            'url' => '',
            'version' => $version,
        ];
        if ($httpCode === 200 && is_array($resArray) && !empty($resArray['update']) && is_array($resArray['latest'])) {
            $latest = $resArray['latest'];
            $latestVersionNum = ltrim($latest['version'] ?? '', 'vV');
            // 防御性二次校验：最新版本号必须大于本地版本
            if ($latestVersionNum !== '' && version_compare($latestVersionNum, $versionNum) > 0) {
                // 从资产中挑选 type=file 的下载地址（slug 为 null 的资产优先，否则回退第一个 file 资产）
                $fileUrl = '';
                $fileAsset = $this->pickFileAsset($latest);
                if ($fileAsset !== null && !empty($fileAsset['url'])) {
                    $fileUrl = $fileAsset['url'];
                }
                if ($fileUrl !== '') {
                    $data['new_version'] = 'v' . $latestVersionNum;
                    $data['url'] = $fileUrl;
                }
            }
        }

        // 输出适配后的响应（前端 request.ts 依赖 error_code === 0 判定业务成功）
        $response->getBody()->write(json_encode(['error_code' => 0, 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * 下载更新代码包（兼容旧接口 Api/AdminUpdate/download）
     */
    public function download(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        set_time_limit(1000);
        ini_set('memory_limit', '500M');

        $rootPath = dirname(__DIR__, 4);
        $showdocPath = $rootPath . '/';

        // 从配置中获取数据库路径（与 Database.php 保持一致）
        $dbPath = \App\Common\Helper\Env::get('DB_NAME', $showdocPath . 'Sqlite/showdoc.db.php');
        
        // 检查数据库文件是否存在，不存在则使用默认路径
        if (!file_exists($dbPath)) {
            $dbPath = $showdocPath . 'Sqlite/showdoc.db.php';
        }

        // 获取当前版本
        $composerPath = $showdocPath . 'composer.json';
        if (!file_exists($composerPath)) {
            return $this->error($response, 10101, 'composer.json 文件不存在');
        }

        $text = file_get_contents($composerPath);
        $composer = json_decode($text, true);
        $version = $composer['version'] ?? '';
        $versionNum = ltrim($version, 'vV');

        // 上游切换为 ShipDesk 发版台检测更新接口
        $url = "https://shipdesk.showdoc.cc/api/v1/apps/showdoc/clients/source/latest?current_version=" . urlencode($versionNum);
        $res = HttpHelper::get($url);
        $resArray = is_string($res) ? json_decode($res, true) : null;

        if (!$resArray || empty($resArray['update']) || !is_array($resArray['latest'])) {
            return $this->error($response, 10101, '当前已是最新版本或检测更新时异常');
        }

        $latest = $resArray['latest'];
        $newVersion = $latest['version'] ?? '';
        $newVersionNum = ltrim($newVersion, 'vV');

        // 防御性二次校验（与 checkUpdate 一致）：最新版本号必须大于本地版本，否则不下载
        if ($newVersionNum === '' || version_compare($newVersionNum, $versionNum) <= 0) {
            return $this->error($response, 10101, '当前已是最新版本或检测更新时异常');
        }

        // 从资产中挑选 type=file 的下载地址与 sha256（slug 为 null 的资产优先，否则回退第一个 file 资产）
        $fileAsset = $this->pickFileAsset($latest);
        $fileUrl = $fileAsset['url'] ?? '';
        $sha256 = '';
        if ($fileAsset !== null) {
            $sha256 = is_string($fileAsset['sha256'] ?? null) ? $fileAsset['sha256'] : '';
        }

        if (empty($fileUrl) || !is_string($sha256) || $sha256 === '') {
            return $this->error($response, 10101, '检测更新时异常：更新包缺少下载地址或 sha256 校验值');
        }

        // 安全检查：fileUrl 必须来自 showdoc.cc 官方域名
        $parsedFileUrl = parse_url($fileUrl);
        $allowedHosts = ['showdoc.cc', 'showdoc.com.cn'];
        $fileUrlHost = strtolower($parsedFileUrl['host'] ?? '');
        $isAllowedHost = false;
        foreach ($allowedHosts as $allowedHost) {
            if ($fileUrlHost === $allowedHost || substr($fileUrlHost, -(strlen('.' . $allowedHost))) === '.' . $allowedHost) {
                $isAllowedHost = true;
                break;
            }
        }
        if (!$isAllowedHost) {
            return $this->error($response, 10101, '更新文件来源不受信任');
        }

        // 安全检查：白名单域名且仅允许 443/默认端口
        $fileUrlPort = $parsedFileUrl['port'] ?? null;
        if ($fileUrlPort !== null && (int)$fileUrlPort !== 443) {
            return $this->error($response, 10101, '更新文件来源不受信任');
        }

        // 安全检查：只允许 https 协议
        $fileUrlScheme = strtolower($parsedFileUrl['scheme'] ?? '');
        if ($fileUrlScheme !== 'https') {
            return $this->error($response, 10101, '更新文件必须使用 HTTPS 协议');
        }

        $versionNum = ltrim($newVersion, 'vV');

        // 进行文件读写权限检查
        if (
            !$this->newIsWriteable($showdocPath)
            || !$this->newIsWriteable($showdocPath . "Sqlite/")
            || !$this->newIsWriteable($showdocPath . "web/")
            || !$this->newIsWriteable($showdocPath . "web/index.php")
            || !$this->newIsWriteable($showdocPath . "server/")
            || !$this->newIsWriteable($showdocPath . "server/vendor/autoload.php")
            || !$this->newIsWriteable($showdocPath . "server/app/Api")
        ) {
            return $this->error($response, 10101, '请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }

        $tempDir = sys_get_temp_dir() . "/showdoc_update/";
        $zipFile = $tempDir . 'showdoc-' . $versionNum . '.zip';

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                return $this->error($response, 10101, '创建临时目录失败，请检查目录权限。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
            }
        }
        
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }

        $file = file_get_contents($fileUrl);
        if ($file === false) {
            return $this->error($response, 10101, '下载更新文件失败。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }

        file_put_contents($zipFile, $file);

        // 完整性校验：ShipDesk 返回的 sha256 与下载文件实际哈希比对
        $actualHash = hash_file('sha256', $zipFile);
        if ($actualHash === false || !hash_equals(strtolower($sha256), $actualHash)) {
            if (file_exists($zipFile)) {
                unlink($zipFile);
            }
            $this->delDir($tempDir);
            return $this->error($response, 10101, '更新包 sha256 校验失败，已放弃本次更新。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }

        $zip = new \ZipArchive();
        $flag = $zip->open($zipFile);
        if ($flag !== true) {
            return $this->error($response, 10101, '下载更新压缩包失败。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }

        // Zip Slip 保护：检查解压路径是否在临时目录内
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $realPath = realpath($tempDir) . DIRECTORY_SEPARATOR . $entryName;
            $realTempDir = realpath($tempDir);
            if ($realTempDir === false || strpos($realPath, $realTempDir . DIRECTORY_SEPARATOR) !== 0) {
                $zip->close();
                $this->delDir($tempDir);
                return $this->error($response, 10101, '更新包包含不安全的文件路径');
            }
        }

        $zip->extractTo($tempDir);
        $zip->close();

        $zipFileSubpath = $tempDir . 'showdoc-' . $versionNum . "/";

        if (file_exists($zipFileSubpath . 'composer.json') && file_exists($zipFileSubpath . 'web/index.html') && file_exists($zipFileSubpath . 'server/vendor/autoload.php')) {
            // 移动目录到upload/update
            $this->copyDir($zipFileSubpath, $showdocPath . 'Public/Uploads/update/');
            $this->delDir($tempDir);
            return $this->success($response, []);
        } else {
            return $this->error($response, 10101, '下载更新压缩包后，解压的文件缺失。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }
    }

    /**
     * 执行升级操作，升级覆盖文件（兼容旧接口 Api/AdminUpdate/updateFiles）
     */
    public function updateFiles(Request $request, Response $response): Response
    {
        // 获取登录用户并检查管理员权限
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $adminCheck = $this->checkAdmin($request, $response);
        if ($adminCheck !== true) {
            return $adminCheck;
        }

        set_time_limit(1000);
        ini_set('memory_limit', '500M');

        $rootPath = dirname(__DIR__, 4);
        $showdocPath = $rootPath . '/';

        // 从配置中获取数据库路径（与 Database.php 保持一致）
        $dbPath = \App\Common\Helper\Env::get('DB_NAME', $showdocPath . 'Sqlite/showdoc.db.php');
        
        // 检查数据库文件是否存在，不存在则使用默认路径
        if (!file_exists($dbPath)) {
            $dbPath = $showdocPath . 'Sqlite/showdoc.db.php';
        }

        // 进行文件读写权限检查
        if (
            !$this->newIsWriteable($showdocPath)
            || !$this->newIsWriteable($showdocPath . "Sqlite/")
            || !$this->newIsWriteable($showdocPath . "web/")
            || !$this->newIsWriteable($showdocPath . "web/index.php")
            || !$this->newIsWriteable($showdocPath . "server/")
            || !$this->newIsWriteable($showdocPath . "server/vendor/autoload.php")
            || !$this->newIsWriteable($showdocPath . "server/app/Api")
        ) {
            return $this->error($response, 10101, '请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }

        if (file_exists($showdocPath . 'Public/Uploads/update/composer.json') && file_exists($showdocPath . 'Public/Uploads/update/server/vendor/autoload.php')) {

            $text = file_get_contents($showdocPath . "composer.json");
            $composer = json_decode($text, true);
            $curVersion = $composer['version'] ?? '';
            $curVersion = str_replace("v", "", $curVersion);

            $text = file_get_contents($showdocPath . "Public/Uploads/update/composer.json");
            $composer = json_decode($text, true);
            $updateVersion = $composer['version'] ?? '';
            $updateVersion = str_replace("v", "", $updateVersion);

            if (version_compare($updateVersion, $curVersion) > 0) {
                // 复制数据库文件备份
                $bakName = $dbPath . '.bak.' . date("Y-m-d-H-i-s") . '.php';
                if (file_exists($dbPath)) {
                    copy($dbPath, $bakName);
                }

                // 目录覆盖（完整覆盖所有文件）
                $this->copyDir($showdocPath . 'Public/Uploads/update/', $showdocPath);

                // 用备份的数据库还原
                if (file_exists($bakName) && file_exists($dbPath)) {
                    copy($bakName, $dbPath);
                }

                $this->delDir($showdocPath . 'Public/Uploads/update/');

                return $this->success($response, []);
            } else {
                return $this->error($response, 10101, '版本号显示不需要升级。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
            }
        } else {
            return $this->error($response, 10101, '升级文件不存在。如自动更新失败，请参考 https://www.showdoc.com.cn/help/13732 手动升级');
        }
    }

    /**
     * 从 ShipDesk 资产列表中挑选 type=file 且带下载地址的资产
     * 多个 file 资产时：slug 为 null 的优先（source 平台约定），无匹配则回退第一个 file 资产
     *
     * @param array $latest ShipDesk latest 响应
     * @return array|null 选中的资产，无可用资产时返回 null
     */
    private function pickFileAsset(array $latest): ?array
    {
        $fallback = null;
        foreach (($latest['assets'] ?? []) as $asset) {
            if (!is_array($asset) || ($asset['type'] ?? '') !== 'file' || empty($asset['url'])) {
                continue;
            }
            if ($fallback === null) {
                $fallback = $asset;
            }
            if (($asset['slug'] ?? null) === null) {
                return $asset;
            }
        }
        return $fallback;
    }

    /**
     * 复制到目录
     *
     * @param string $dirsrc 原目录
     * @param string $dirto 目标目录
     */
    private function copyDir($dirsrc, $dirto)
    {
        if (file_exists($dirto)) {
            if (!is_dir($dirto)) {
                return false;
            }
        } else {
            mkdir($dirto, 0755, true);
        }

        $dir = opendir($dirsrc);
        if (!$dir) {
            return false;
        }

        while ($filename = readdir($dir)) {
            if ($filename != "." && $filename != "..") {
                $srcfile = $dirsrc . "/" . $filename;
                $tofile = $dirto . "/" . $filename;

                if (is_dir($srcfile)) {
                    $this->copyDir($srcfile, $tofile);
                } else {
                    copy($srcfile, $tofile);
                }
            }
        }
        closedir($dir);
    }

    /**
     * 删除文件夹及文件夹下所有的文件
     *
     * @param string $dir 目录路径
     * @return bool
     */
    private function delDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $dh = opendir($dir);
        if (!$dh) {
            return false;
        }

        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    $this->delDir($fullpath);
                }
            }
        }
        closedir($dh);

        // 删除当前文件夹
        if (rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
     *
     * @param string $file 文件/目录
     * @return boolean
     */
    private function newIsWriteable($file)
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                return true;
            } else {
                return false;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                return true;
            } else {
                return false;
            }
        }
    }

}

