<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * 在线升级控制器
 */
class UpdateController extends BaseController
{
    /**
     * 检测数据库并更新
     * 
     * 由于在 BaseController 的构造函数中已经执行过一次升级了，所以这里不需要动作
     * 保留着是为了兼容历史接口
     */
    public function checkDb(Request $request, Response $response): Response
    {
        return $this->success($response, []);
    }

    /**
     * 从最新 Docker 镜像中更新代码
     * 
     * 注意：此方法只能从命令行调用
     */
    public function dockerUpdateCode(Request $request, Response $response): Response
    {
        if (!preg_match("/cli/i", php_sapi_name())) {
            return $this->error($response, 10101, '只能从命令行中调用');
        }

        $showdocPath = "/var/www/html/";

        // 进行文件读写权限检查
        if (
            !$this->isWriteable($showdocPath)
            || !$this->isWriteable($showdocPath . "Sqlite/")
            || !$this->isWriteable($showdocPath . "web/")
            || !$this->isWriteable($showdocPath . "web/index.php")
            || !$this->isWriteable($showdocPath . "server/")
            || !$this->isWriteable($showdocPath . "server/vendor/autoload.php")
            || !$this->isWriteable($showdocPath . "server/app/Api")
        ) {
            return $this->error($response, 10101, '请手动给 showdoc 安装目录下的所有文件可写权限，否则程序无法覆盖旧文件');
        }

        // 获取当前版本号
        $composerPath = $showdocPath . "composer.json";
        if (!file_exists($composerPath)) {
            return $this->error($response, 10101, '无法读取 composer.json');
        }

        $text = file_get_contents($composerPath);
        $composer = json_decode($text, true);
        $curVersion = $composer['version'] ?? '';

        // 获取 Docker 中的版本号
        $dockerComposerPath = "/showdoc_data/html/composer.json";
        if (!file_exists($dockerComposerPath)) {
            return $this->error($response, 10101, '无法读取 Docker 中的 composer.json');
        }

        $text = file_get_contents($dockerComposerPath);
        $dockerComposer = json_decode($text, true);
        $versionInDocker = $dockerComposer['version'] ?? '';

        if ($curVersion && $versionInDocker && version_compare($versionInDocker, $curVersion) > 0) {
            // 获取原来的语言设置
            $lang = $this->getLang($showdocPath . "web/index.html");

            // 复制数据库文件备份
            $dbPath = $showdocPath . 'Sqlite/showdoc.db.php';
            if (file_exists($dbPath)) {
                $bakName = $showdocPath . 'Sqlite/showdoc.db.bak.' . date("Y-m-d-H-i-s") . '.php';
                copy($dbPath, $bakName);
            }

            // 目录覆盖
            $this->copyDir('/showdoc_data/html/', $showdocPath);

            // 用备份的数据库还原
            if (isset($bakName) && file_exists($bakName)) {
                copy($bakName, $dbPath);
            }

            // 恢复语言设置
            if ($lang == 'en') {
                $this->replaceFileContent($showdocPath . "web/index.html", "zh-cn", "en");
                $this->replaceFileContent($showdocPath . "web_src/index.html", "zh-cn", "en");
            }

            return $this->success($response, []);
        }

        return $this->success($response, ['message' => '已是最新版本']);
    }

    /**
     * 复制目录
     *
     * @param string $dirSrc 原目录
     * @param string $dirTo 目标目录
     * @return void
     */
    private function copyDir(string $dirSrc, string $dirTo): void
    {
        if (file_exists($dirTo)) {
            if (!is_dir($dirTo)) {
                return;
            }
        } else {
            mkdir($dirTo, 0755, true);
        }

        $dir = opendir($dirSrc);
        if (!$dir) {
            return;
        }

        while (($filename = readdir($dir)) !== false) {
            if ($filename != "." && $filename != "..") {
                $srcFile = $dirSrc . "/" . $filename;
                $toFile = $dirTo . "/" . $filename;

                if (is_dir($srcFile)) {
                    $this->copyDir($srcFile, $toFile);
                } else {
                    copy($srcFile, $toFile);
                }
            }
        }

        closedir($dir);
    }

    /**
     * 判断文件/目录是否可写
     *
     * @param string $file 文件/目录路径
     * @return bool
     */
    private function isWriteable(string $file): bool
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                return true;
            }
            return false;
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                return true;
            }
            return false;
        }
    }

    /**
     * 替换文件内容
     *
     * @param string $file 文件路径
     * @param string $from 要替换的字符串
     * @param string $to 替换为的字符串
     * @return void
     */
    private function replaceFileContent(string $file, string $from, string $to): void
    {
        if (!file_exists($file)) {
            return;
        }

        $content = file_get_contents($file);
        $content2 = str_replace($from, $to, $content);
        if ($content2 !== false) {
            file_put_contents($file, $content2);
        }
    }

    /**
     * 获取语言设置
     *
     * @param string $path 文件路径
     * @return string 语言代码（'zh-cn' 或 'en'）
     */
    private function getLang(string $path): string
    {
        if (!file_exists($path)) {
            return 'zh-cn';
        }

        $content = file_get_contents($path);
        if (strpos($content, 'lang="en"') !== false || strpos($content, "lang='en'") !== false) {
            return 'en';
        }

        return 'zh-cn';
    }
}

