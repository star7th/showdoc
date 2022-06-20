<?php

namespace Api\Controller;

use Think\Controller;

class UpdateController extends BaseController
{

    //检测数据库并更新
    public function checkDb($showBack = true)
    {

        // 由于在BaseController的构造函数执行过一次升级了，所以这里不需要动作
        // 保留着是为了兼容历史

        if ($showBack) {
            $this->sendResult(array());
        }
    }
    // 从最新docker镜像中更新代码
    public function dockerUpdateCode()
    {
        if (!preg_match("/cli/i", php_sapi_name())) {
            echo '只能从命令行中调用';
            return;
        }
        $showdoc_path = "/var/www/html/";
        // 进行文件读写权限检查
        if (
            !$this->new_is_writeable($showdoc_path)
            || !$this->new_is_writeable($showdoc_path . "Sqlite/")
            || !$this->new_is_writeable($showdoc_path . "web/")
            || !$this->new_is_writeable($showdoc_path . "web/index.php")
            || !$this->new_is_writeable($showdoc_path . "server/")
            || !$this->new_is_writeable($showdoc_path . "server/vendor/autoload.php")
            || !$this->new_is_writeable($showdoc_path . "server/Application/Api")
        ) {
            $this->sendError(10101, '请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件');
            return;
        }

        //获取当前版本号
        $text = file_get_contents($showdoc_path . "composer.json");
        $composer = json_decode($text, true);
        $cur_version = $composer['version'];

        // 获取docker中的版本号
        $text = file_get_contents("/showdoc_data/html/composer.json");
        $composer = json_decode($text, true);
        $version_in_docker = $composer['version'];

        if ($cur_version && $version_in_docker && version_compare($version_in_docker, $cur_version) > 0) {
            // 比较版本后，应该更新

            // 获取原来的语言设置
            $lang = get_lang($showdoc_path . "web/index.html") ;

            //复制数据库文件备份
            $bak_name = $showdoc_path . 'Sqlite/showdoc.db.bak.' . date("Y-m-d-H-i-s") . '.php';
            copy($showdoc_path . 'Sqlite/showdoc.db.php', $bak_name);

            // 目录覆盖
            $this->copydir('/showdoc_data/html/', $showdoc_path);
            // 用备份的数据库还原
            copy($bak_name, $showdoc_path . 'Sqlite/showdoc.db.php');


            // 恢复语言设置
            if ($lang == 'en') {
                $this->replace_file_content($showdoc_path . "web/index.html","zh-cn","en") ;
                $this->replace_file_content($showdoc_path . "web_src/index.html","zh-cn","en") ;
            }

            // echo '升级成功！' ;
            $this->sendResult(array());
        }
    }

    /**
     * 复制到目录
     * $dirsrc  原目录
     * $dirto  目标目录
     *
     */
    private function copydir($dirsrc, $dirto)
    {
        //如果原来的文件存在， 是不是一个目录

        if (file_exists($dirto)) {
            if (!is_dir($dirto)) {
                echo "目标不是一个目录， 不能copy进去<br>";
                exit;
            }
        } else {
            mkdir($dirto);
        }


        $dir = opendir($dirsrc);

        while ($filename = readdir($dir)) {
            if ($filename != "." && $filename != "..") {
                $srcfile = $dirsrc . "/" . $filename;  //原文件
                $tofile = $dirto . "/" . $filename;    //目标文件

                if (is_dir($srcfile)) {
                    $this->copydir($srcfile, $tofile);  //递归处理所有子目录
                } else {
                    copy($srcfile, $tofile);
                }
            }
        }
    }

    /**
     * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
     *
     * @param string $file 文件/目录
     * @return boolean
     */
    private function new_is_writeable($file)
    {
        if (is_dir($file)) {
            $dir = $file;
            if ($fp = @fopen("$dir/test.txt", 'w')) {
                @fclose($fp);
                @unlink("$dir/test.txt");
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        } else {
            if ($fp = @fopen($file, 'a+')) {
                @fclose($fp);
                $writeable = 1;
            } else {
                $writeable = 0;
            }
        }

        return $writeable;
    }

    private function replace_file_content($file , $from ,$to )
    {
        $content = file_get_contents($file);
        $content2 = str_replace($from,$to,$content);
        if ($content2) {
            file_put_contents($file,$content2);
        }
    }
}
