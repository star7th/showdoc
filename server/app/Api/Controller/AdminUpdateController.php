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

        $url = "https://www.showdoc.cc/server/api/open/checkUpdate";
        $ch = curl_init();
        $timeout = 2;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "version={$version}");
        curl_setopt($ch, CURLOPT_URL, $url);
        $sContent = curl_exec($ch);
        curl_close($ch);

        // 转换字段名：将 file_url 改为 url（兼容前端）
        $resArray = json_decode($sContent, true);
        if ($resArray && isset($resArray['data']) && isset($resArray['data']['file_url'])) {
            $resArray['data']['url'] = $resArray['data']['file_url'];
        }

        // 输出转换后的响应
        $response->getBody()->write(json_encode($resArray));
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

        $url = "https://www.showdoc.cc/server/api/open/checkUpdate";
        $res = HttpHelper::post($url, ["version" => $version]);
        $resArray = json_decode($res, true);
        
        if (!$resArray || !isset($resArray['data'])) {
            return $this->error($response, 10101, '检测更新时异常');
        }

        $newVersion = $resArray['data']['new_version'] ?? '';
        $fileUrl = $resArray['data']['file_url'] ?? '';

        if (empty($fileUrl)) {
            return $this->error($response, 10101, '检测更新时异常');
        }

        $versionNum = str_replace("v", "", $newVersion);

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
            return $this->error($response, 10101, '请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件');
        }

        $tempDir = sys_get_temp_dir() . "/showdoc_update/";
        $zipFile = $tempDir . 'showdoc-' . $versionNum . '.zip';

        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0755, true)) {
                return $this->error($response, 10101, '创建临时目录失败，请检查目录权限');
            }
        }
        
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }

        $file = file_get_contents($fileUrl);
        if ($file === false) {
            return $this->error($response, 10101, '下载更新文件失败');
        }

        file_put_contents($zipFile, $file);

        $zip = new \ZipArchive();
        $flag = $zip->open($zipFile);
        if ($flag !== true) {
            return $this->error($response, 10101, '下载更新压缩包失败');
        }
        $zip->extractTo($tempDir);
        $zip->close();

        $zipFileSubpath = $tempDir . 'showdoc-' . $versionNum . "/";

        if (file_exists($zipFileSubpath . 'composer.json') && file_exists($zipFileSubpath . 'web/index.php') && file_exists($zipFileSubpath . 'server/vendor/autoload.php')) {
            // 移动目录到upload/update
            $this->copyDir($zipFileSubpath, $showdocPath . 'Public/Uploads/update/');
            $this->delDir($tempDir);
            return $this->success($response, []);
        } else {
            return $this->error($response, 10101, '下载更新压缩包后，解压的文件缺失');
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
            return $this->error($response, 10101, '请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件');
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
                return $this->error($response, 10101, '版本号显示不需要升级');
            }
        } else {
            return $this->error($response, 10101, '升级文件不存在');
        }
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

