<?php
namespace Api\Controller;
use Think\Controller;
class AdminUpdateController extends BaseController {

    // 下载更新代码包
    // 未完成。此方式下载代码包不行
    public function dowmload(){
        return ;
        set_time_limit(1200);
        ignore_user_abort(1);
        $url = 'https://github.com/star7th/showdoc/archive/master.tar.gz';
        $file = './master.tar.gz';

        $file_content = file_get_contents($url);
        var_dump($file_content);
        curl_close($ch);
        $downloaded_file = fopen($file, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
    }

}
