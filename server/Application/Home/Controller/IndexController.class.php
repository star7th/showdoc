<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends BaseController {
    public function index(){

				//不存在安装文件夹的，表示已经安装过
				if(!file_exists("./install")){
					//跳转到web目录
					header("location:./web/#/");
					exit();
				}

				if(file_exists("./install") && file_exists("./install/install.lock") && $this->new_is_writeable("./install") && $this->new_is_writeable("./install/install.lock") ){
					//跳转到web目录
					header("location:./web/#/");
					exit();
				}
				//其他情况都跳转到安装页面
				header("location:./install/index.php");
    }

		/**
		 * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
		 *
		 * @param string $file 文件/目录
		 * @return boolean
		 */
		public function new_is_writeable($file) {
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
