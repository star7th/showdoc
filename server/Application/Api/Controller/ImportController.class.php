<?php
namespace Api\Controller;
use Think\Controller;
class ImportController extends BaseController {


    //自动检测导入的文件类型从而选择不同的控制器方法
    public function auto(){
        set_time_limit(100);
        ini_set('memory_limit','200M');
        $login_user = $this->checkLogin();
        $filename = $_FILES["file"]["name"] ;
        $file = $_FILES["file"]["tmp_name"] ;
        //文件后缀
        $tail = substr(strrchr($filename, '.'), 1);

        if ($tail == 'zip') {
            $zipArc = new \ZipArchive();
            $ret = $zipArc->open($file, \ZipArchive::CREATE);
            $info = $zipArc->getFromName("prefix_info.json") ;
            if ($info) {
                $info_array = json_decode($info ,1 );
                if ($info_array) {
                    $this->markdown($info_array);
                    return ;
                }
            }
        }

        if ($tail == 'json') {
            $json = file_get_contents($file) ;
            $json_array = json_decode($json ,1 );
            unset($json);
            if (( $json_array['swagger'] || $json_array['openapi'] ) && $json_array['info']) {
                R("ImportSwagger/import");
                return ;
            }
            if ($json_array['id']) {
                R("ImportPostman/import");
                return ;
            }
            if ($json_array['info']) {
                R("ImportPostman/import");
                return ;
            }
        }

        $this->sendError(10101);


    }

    //导入markdown压缩包
    public function markdown($info_array){
        set_time_limit(100);
        ini_set('memory_limit','200M');

        $login_user = $this->checkLogin();

        $file = $_FILES["file"]["tmp_name"] ;
        //$file = "../Public/markdown.zip" ; //test

        if (!$info_array) {
            $zipArc = new \ZipArchive();
            $ret = $zipArc->open($file, \ZipArchive::CREATE);
            $info = $zipArc->getFromName("prefix_info.json") ;
            $info_array = json_decode($info ,1 );
            unset($info);
        }

        if ($info_array) {

            //$info_array = $this->_fileToMarkdown($info_array,  $zipArc );
            //echo json_encode($info_array);return ;
            D("Item")->import( json_encode($info_array) , $login_user['uid'] );
            $this->sendResult(array());
            return ;
        }

        $this->sendError(10101);
    }

    //废弃
    private function _fileToMarkdown( $catalogData ,  $zipArc ){
        if ($catalogData['pages']) {
            foreach ($catalogData['pages'] as $key => $value) {
                if ($value['page_content']) {
                    $catalogData['pages'][$key]['page_content'] = $zipArc->getFromName( $value['page_content']) ;//原来的内容由文件名变为文件内容
                }
            }
        }

        if ($catalogData['catalogs']) {
            foreach ($catalogData['catalogs'] as $key => $value) {
                if ($value) {
                    $catalogData['catalogs'][$key] = $this->_markdownTofile($value ,  $zipArc);
                }
                
            }
            
        }
        return $catalogData ;

    }

}