<?php
namespace Api\Controller;
use Think\Controller;
class ExportController extends BaseController {

    //导出整个项目为word
    public function word(){
        set_time_limit(100);
        ini_set('memory_limit','800M');
        import("Vendor.Parsedown.Parsedown");
        $Parsedown = new \Parsedown();
        $item_id =  I("item_id/d");
        $cat_id =  I("cat_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();


        $menu = D("Item")->getContent($item_id,"*","*",1);
        if ($cat_id) {
            foreach ($menu['catalogs'] as $key => $value) {
                if ($cat_id == $value['cat_id']) {
                    $pages = $value['pages'] ;
                    $catalogs = $value['catalogs'] ;
                }else{
                    if ($value['catalogs']) {
                        foreach ($value['catalogs'] as $key2 => $value2) {
                            if ($cat_id == $value2['cat_id']) {
                                $pages = $value2['pages'] ;
                                $catalogs = $value2['catalogs'] ;
                            }
                        }
                        if ($value2['catalogs']) {
                            foreach ($value2['catalogs'] as $key3 => $value3) {
                                if ($cat_id == $value3['cat_id']) {
                                    $pages = $value3['pages'] ;
                                    $catalogs = $value3['catalogs'] ;
                                }
                            }
                        }
                    }
                }
            }
        }else{
            $pages = $menu['pages'] ;
            $catalogs = $menu['catalogs'] ;
        }


        $data = '';
        $parent = 1;

        if ($pages) {
            foreach ($pages as $key => $value) {
                $data .= "<h1>{$parent}、{$value['page_title']}</h1>";
                $data .= '<div style="margin-left:20px;">';
                    $data .= htmlspecialchars_decode($Parsedown->text($value['page_content']));
                $data .= '</div>';
                $parent ++;
            }
        }
        //var_export($catalogs);
        if ($catalogs) {
            foreach ($catalogs as $key => $value) {
                $data .= "<h1>{$parent}、{$value['cat_name']}</h1>";
                $data .= '<div style="margin-left:0px;">';
                    $child = 1 ;
                    if ($value['pages']) {
                        foreach ($value['pages'] as $page) {
                            $data .= "<h2>{$parent}.{$child}、{$page['page_title']}</h2>";
                            $data .= '<div style="margin-left:0px;">';
                                $data .= htmlspecialchars_decode($Parsedown->text($page['page_content']));
                            $data .= '</div>';
                            $child ++;
                        }
                    }
                    if ($value['catalogs']) {
                        $parent2 = 1 ;
                        foreach ($value['catalogs'] as $key3 => $value3) {
                            $data .= "<h2>{$parent}.{$parent2}、{$value3['cat_name']}</h2>";
                            $data .= '<div style="margin-left:20px;">';
                                $child2 = 1 ;
                                if ($value3['pages']) {
                                    foreach ($value3['pages'] as $page3) {
                                        $data .= "<h3>{$parent}.{$parent2}.{$child2}、{$page3['page_title']}</h3>";
                                        $data .= '<div style="margin-left:0px;">';
                                            $data .= htmlspecialchars_decode($Parsedown->text($page3['page_content']));
                                        $data .= '</div>';
                                        $child2 ++;
                                    }
                                }

                                if ($value3['catalogs']) {
                                    $parent3 = 1 ;
                                    foreach ($value3['catalogs'] as $key4 => $value4) {
                                        $data .= "<h2>{$parent}.{$parent2}.{$parent3}、{$value4['cat_name']}</h2>";
                                        $data .= '<div style="margin-left:0px;">';
                                            $child3 = 1 ;
                                            if ($value4['pages']) {
                                                foreach ($value4['pages'] as $page4) {
                                                    $data .= "<h3>{$parent}.{$parent2}.{$parent3}.{$child3}、{$page4['page_title']}</h3>";
                                                    $data .= '<div style="margin-left:30px;">';
                                                        $data .= htmlspecialchars_decode($Parsedown->text($page4['page_content']));
                                                    $data .= '</div>';
                                                    $child3 ++;
                                                }
                                            }
                                        $data .= '</div>';
                                        $parent3 ++;
                                    }
                                }
                            $data .= '</div>';
                            $parent2 ++;
                        }
                    }
                $data .= '</div>';
                $parent ++;
            }
        }

        output_word($data,$item['item_name']);
    }

    //导出整个项目为markdown压缩包
    public function markdown(){
        set_time_limit(100);
        ini_set('memory_limit','800M');
        $item_id =  I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();

        $exportJson = D("Item")->export($item_id , true);
        $exportData = json_decode($exportJson , 1 ) ;
        $zipArc = new \ZipArchive();
        $temp_file = tempnam(sys_get_temp_dir(), 'Tux')."_showdoc_.zip";
        $temp_dir = sys_get_temp_dir()."/showdoc_".time().rand();
        mkdir($temp_dir) ;

        file_put_contents($temp_dir.'/'.'info.json', json_encode($exportData));
        file_put_contents($temp_dir.'/'.'readme.md', "由于页面标题可能含有特殊字符导致异常，所以markdown文件的命令均为英文（base64编码），以下是页面标题和文件的对应关系：".PHP_EOL.PHP_EOL );

        $exportData['pages'] = $this->_markdownTofile( $exportData['pages'] , $temp_dir);
        $ret = $this->_zip( $temp_dir ,$temp_file );

        clear_runtime($temp_dir);

        header("Cache-Control: max-age=0");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=showdoc.zip'); // 文件名
        header("Content-Type: application/zip"); // zip格式的
        header("Content-Transfer-Encoding: binary"); // 告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($temp_file)); // 告诉浏览器，文件大小
        @readfile($temp_file);//输出文件;
        unlink($temp_file);

    }

    private function _markdownTofile( $catalogData ,  $temp_dir ){
        if ($catalogData['pages']) {
            foreach ($catalogData['pages'] as $key => $value) {
                $t = rand(1000,100000) ;
                //把页面内容保存为md文件并且追加到压缩包里
                $filename = base64_encode($value['page_title'].'_'.$t).".md" ;
                file_put_contents($temp_dir.'/'.$filename, $value['page_content']);

                file_put_contents($temp_dir.'/'.'readme.md',$value['page_title']. " —— ".  $filename  .PHP_EOL, FILE_APPEND );

                $catalogData['pages'][$key]['page_content'] = $filename ; //原来的内容就变成文件名
            }
        }

        if ($catalogData['catalogs']) {
            foreach ($catalogData['catalogs'] as $key => $value) {
                $catalogData['catalogs'][$key] = $this->_markdownTofile($value ,  $temp_dir);
            }
            
        }
        return $catalogData ;

    }

    /**
     * 使用ZIP压缩文件或目录
     * @param  [string] $fromName 被压缩的文件或目录名
     * @param  [string] $toName   压缩后的文件名
     * @return [bool]             成功返回TRUE, 失败返回FALSE
     */
    private function _zip($fromName, $toName)
    {
        if(!file_exists($fromName) && !is_dir($fromName)){
            return FALSE;
        }
        $zipArc = new \ZipArchive();
        if(!$zipArc->open($toName, \ZipArchive::CREATE)){
            return FALSE;
        }
        $res = is_dir($fromName) ? $zipArc->addGlob("{$fromName}/*"  , 0 , array('add_path' => DIRECTORY_SEPARATOR, 'remove_all_path' => TRUE) ) : $zipArc->addFile($fromName);
        if(!$res){
            $zipArc->close();
            return FALSE;
        }
        return $zipArc->close();
    }

}
