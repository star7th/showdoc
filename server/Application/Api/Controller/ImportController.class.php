<?php
namespace Api\Controller;
use Think\Controller;
class ImportController extends BaseController {


    //导入markdown压缩包
    public function markdown(){
        set_time_limit(100);
        ini_set('memory_limit','200M');

        $login_user = $this->checkLogin();

        $file = $_FILES["file"]["tmp_name"] ;
        //$file = "../Public/markdown.zip" ; //test

        $zipArc = new \ZipArchive();
        $ret = $zipArc->open($file, \ZipArchive::CREATE);
        $info = $zipArc->getFromName(DIRECTORY_SEPARATOR."info.json") ;
        $info_array = json_decode($info ,1 );
        unset($info);

        if ($info_array) {

            $info_array = $this->_fileToMarkdown($info_array,  $zipArc );
            //echo json_encode($info_array);return ;
            D("Item")->import( json_encode($info_array) , $login_user['uid'] );
            $this->sendResult(array());
            return ;
        }

        $this->sendError(10101);
    }


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