<?php
namespace Api\Controller;
use Think\Controller;
class AdminUpdateController extends BaseController {

    //检测showdoc版本更新
    public function checkUpdate(){
        //获取当前版本
        $text = file_get_contents("../composer.json");
        $composer = json_decode($text, true);
        $version = $composer['version'] ;
        $url = "https://www.showdoc.cc/server/api/open/checkUpdate";
        $ch = curl_init();
        $timeout = 2;
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt ( $ch, CURLOPT_POST, true );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, "version={$version}" );
        curl_setopt($ch,CURLOPT_URL,$url);
        $sContent = curl_exec($ch);
        curl_close($ch);
        echo $sContent  ;
    }

    // 下载更新代码包
    public function download(){
        $this->checkLogin();
        $this->checkAdmin();
        set_time_limit(1000);
        ini_set('memory_limit','500M');
        $new_version = I("new_version") ;
        $file_url = I("file_url") ;
        $version_num = str_replace("v","",$new_version) ;

        $showdoc_path = "../" ;

        // 进行文件读写权限检查
        if(!$this->new_is_writeable($showdoc_path)
            || !$this->new_is_writeable($showdoc_path."Sqlite/" )
            || !$this->new_is_writeable($showdoc_path."web/" )
            || !$this->new_is_writeable($showdoc_path."web/index.php" )
            || !$this->new_is_writeable($showdoc_path."server/" )
            || !$this->new_is_writeable($showdoc_path."server/vendor/autoload.php" )
            || !$this->new_is_writeable($showdoc_path."server/Application/Api" )
        ){
            $this->sendError(10101,'请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件');
            return ;
        }

        $temp_dir = sys_get_temp_dir()."/showdoc_update/";
        $zip_file = $temp_dir.'showdoc-'.$version_num.'.zip' ;
        mkdir($temp_dir) ;
        unlink($zip_file);
        $file = file_get_contents($file_url);
        file_put_contents($zip_file,$file);
        
        $zip = new \ZipArchive();
        $flag = $zip->open($zip_file);
        if($flag!==true){
            $this->sendError(10101,'下载更新压缩包失败');
            return ;
        }
        $zip->extractTo($temp_dir);
        $flag = $zip->close();
        
        $zip_file_subpath = $temp_dir.'showdoc-'.$version_num."/" ;
        
        if(file_exists($zip_file_subpath.'composer.json') && file_exists($zip_file_subpath.'web/index.php') && file_exists($zip_file_subpath.'server/vendor/autoload.php') ){
            //echo $zip_file_subpath.'存在';
            // 移动目录到upload/update
            $this->copydir($zip_file_subpath ,$showdoc_path.'Public/Uploads/update/' );
            $this->deldir($temp_dir);
            $this->sendResult(array());
        
        }else{
            $this->sendError(10101,'下载更新压缩包后，解压的文件缺失');
            return ;
        }
    }

    // 执行升级操作，升级覆盖文件
    public function updateFiles(){
        $this->checkLogin();
        $this->checkAdmin();
        set_time_limit(1000);
        ini_set('memory_limit','500M');
        
        $showdoc_path = "../" ;
        
        // 进行文件读写权限检查
        if(!$this->new_is_writeable($showdoc_path)
            || !$this->new_is_writeable($showdoc_path."Sqlite/" )
            || !$this->new_is_writeable($showdoc_path."web/" )
            || !$this->new_is_writeable($showdoc_path."web/index.php" )
            || !$this->new_is_writeable($showdoc_path."server/" )
            || !$this->new_is_writeable($showdoc_path."server/vendor/autoload.php" )
            || !$this->new_is_writeable($showdoc_path."server/Application/Api" )
        ){
            $this->sendError(10101,'请手动给showdoc安装目录下的所有文件可写权限，否则程序无法覆盖旧文件');
            return ;
        }

        if(file_exists($showdoc_path.'Public/Uploads/update/composer.json') && file_exists($showdoc_path.'Public/Uploads/update/server/vendor/autoload.php') ){
        
                $text = file_get_contents($showdoc_path."composer.json");
                $composer = json_decode($text, true);
                $cur_version = $composer['version'] ;
                $cur_version = str_replace("v","",$cur_version) ;
        
                $text = file_get_contents($showdoc_path."Public/Uploads/update/composer.json");
                $composer = json_decode($text, true);
                $update_version = $composer['version'] ;
                $update_version = str_replace("v","",$update_version) ;
        
                if(version_compare($update_version,$cur_version) > 0 ){
                    //复制数据库文件备份
                    $bak_name = $showdoc_path.'Sqlite/showdoc.db.bak.'.date("Y-m-d-H-i-s").'.php';
                    copy($showdoc_path.'Sqlite/showdoc.db.php', $bak_name);
        
                    // 目录覆盖
                    $this->copydir($showdoc_path.'Public/Uploads/update/',$showdoc_path);
                    // 用备份的数据库还原
                    copy($bak_name, $showdoc_path.'Sqlite/showdoc.db.php' );
        
                    $this->deldir($showdoc_path.'Public/Uploads/update/') ;
        
                    // echo '升级成功！' ;
                    $this->sendResult(array());
        
                }else{
                    // echo '不需要升级';
                    $this->sendError(10101,'版本号显示不需要升级');
                }
        }else{
            $this->sendError(10101,'升级文件不存在');
        }
    }

    /**
     * 复制到目录
    * $dirsrc  原目录
    * $dirto  目标目录
    *
    */
    private function copydir($dirsrc, $dirto) {
        //如果原来的文件存在， 是不是一个目录

        if(file_exists($dirto)) {
            if(!is_dir($dirto)) {
                echo "目标不是一个目录， 不能copy进去<br>";
                exit;   
            }
        }else{
            mkdir($dirto);
        }


        $dir = opendir($dirsrc);

        while($filename = readdir($dir)) {
            if($filename != "." && $filename !="..") {
                $srcfile = $dirsrc."/".$filename;  //原文件
                $tofile = $dirto."/".$filename;    //目标文件

                if(is_dir($srcfile)) {
                    $this->copydir($srcfile, $tofile);  //递归处理所有子目录
                }else{
                    copy($srcfile, $tofile);
                }

            }
        }
    }

    // 删除文件夹及文件夹下所有的文件
    private function deldir($dir) {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if($file != "." && $file!="..") {
            $fullpath = $dir."/".$file;
            if(!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                $this->deldir($fullpath);
            }
            }
        }
        closedir($dh);

        //删除当前文件夹：
        if(rmdir($dir)) {
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
    private function new_is_writeable($file) {
        if (is_dir($file)){
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

}
