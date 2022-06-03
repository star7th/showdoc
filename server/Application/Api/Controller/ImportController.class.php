<?php

namespace Api\Controller;

use Think\Controller;

class ImportController extends BaseController
{


    //自动检测导入的文件类型从而选择不同的控制器方法
    public function auto()
    {
        set_time_limit(100);
        ini_set('memory_limit', '600M');
        $login_user = $this->checkLogin();
        $filename = $_FILES["file"]["name"];
        $file = $_FILES["file"]["tmp_name"];
        //文件后缀
        $tail = substr(strrchr($filename, '.'), 1);
        $item_id = I("item_id") ? I("item_id") : '0';
        if ($item_id) {
            if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
                $this->sendError(10302);
                return;
            }
        }

        if ($tail == 'zip') {
            $zipArc = new \ZipArchive();
            $ret = $zipArc->open($file, \ZipArchive::CREATE);
            $info = $zipArc->getFromName("prefix_info.json");
            // 如有prefix_info.json文件，则导入prefix_info.json文件
            if ($info) {
                $info_array = json_decode($info, 1);
                if ($info_array) {
                    $info_array['item_id'] = $item_id;
                    $this->importMarkdownInfo($info_array, $item_id);
                    return;
                }
            } else {
                // 如果没有，则尝试解压压缩包后，遍历markdown文件导入
                $this->importFromReadingMDFile($file, $filename, $item_id);
                return;
            }
        }

        if ($tail == 'json') {
            $json = file_get_contents($file);
            $json_array = json_decode($json, 1);
            unset($json);
            if (($json_array['swagger'] || $json_array['openapi']) && $json_array['info']) {
                R("ImportSwagger/import");
                return;
            }
            if ($json_array['id']) {
                R("ImportPostman/import");
                return;
            }
            if ($json_array['info']) {
                R("ImportPostman/import");
                return;
            }
        }

        $this->sendError(10101);
    }

    //导入markdown压缩包(根据压缩包内的info文件导入）)
    public function importMarkdownInfo($info_array, $item_id)
    {
        set_time_limit(100);
        ini_set('memory_limit', '200M');

        $login_user = $this->checkLogin();

        $file = $_FILES["file"]["tmp_name"];
        //$file = "../Public/markdown.zip" ; //test

        if (!$info_array) {
            $zipArc = new \ZipArchive();
            $ret = $zipArc->open($file, \ZipArchive::CREATE);
            $info = $zipArc->getFromName("prefix_info.json");
            $info_array = json_decode($info, 1);
            unset($info);
        }

        if ($info_array) {
            // $info_array['item_id'] = '2'; //debug
            D("Item")->import(json_encode($info_array), $login_user['uid'], $item_id);
            $this->sendResult(array());
            return;
        }

        $this->sendError(10101);
    }

    public function importFromReadingMDFile($file, $filename, $item_id)
    {
        // 如果项目id不存在，则新建一个项目
        if ($item_id <= 0) {
            $login_user = $this->checkLogin();
            $item_data = array(
                "item_name" => str_replace('.zip', '', $filename),
                "item_domain" => '',
                "item_type" => 1,
                "item_description" => '',
                "password" => get_rand_str(),
                "uid" => $login_user['uid'],
                "username" => $login_user['username'],
                "addtime" => time(),
            );
            $item_id = D("Item")->add($item_data);
        }
        $zipArc = new \ZipArchive();
        $zipArc->open($file, \ZipArchive::CREATE);
        // 在系统目录创建一个临时目录路径
        $tmp_dir = sys_get_temp_dir() . '/' . get_rand_str();
        mkdir($tmp_dir);

        // 加入此段是为了某些情况下中文名乱码问题，要先转码
        $fileNum = $zipArc->numFiles;
        for ($i = 0; $i < $fileNum; $i++) {
            $statInfo = $zipArc->statIndex($i, \ZipArchive::FL_ENC_RAW);
            $current_encode = mb_detect_encoding($statInfo['name'], array("ASCII", "GB2312", "GBK", 'BIG5', 'UTF-8'));
            $statInfo['name'] = mb_convert_encoding($statInfo['name'], 'UTF-8', $current_encode);
            $zipArc->renameIndex($i, $statInfo['name']);
        }
        $zipArc->close();
        $zipArc->open($file, \ZipArchive::CREATE);
        // 截至↑

        $zipArc->extractTo($tmp_dir);

        // 遍历解压后的目录
        // 定义一个匿名函数，以便使用名字来递归
        $traverseFiles = function ($dir) use (&$traverseFiles, $item_id, $tmp_dir) {
            $handle = opendir($dir);
            while ($file = readdir($handle)) {
                if ($file !== '..' && $file !== '.') {
                    $f = $dir . '/' . $file;
                    if (is_file($f)) {
                        // echo '|--' . $file . '<br>';          //代表文件
                        $page_title = str_replace('.md', '', $file);
                        $page_content = file_get_contents($f);
                        $cat_name = str_replace($tmp_dir, '', $dir);
                        // echo $cat_name . '<br>';
                        D("Page")->update_by_title($item_id, $page_title, $page_content, $cat_name);
                    } else {
                        // echo  '--' . $file . '<br>';          //代表文件夹
                        // 这里目录，则继续递归遍历
                        $traverseFiles($f);
                    }
                }
            }
        };
        $traverseFiles($tmp_dir, $item_id);
        clear_runtime($tmp_dir); // clear_runtime 函数是可以删除任意目录的。当初写的时候只是用来清除缓存目录。所以命名并不友好
        $this->sendResult(array());
    }
}
