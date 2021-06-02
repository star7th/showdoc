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
        $page_id =  I("page_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();


        $menu = D("Item")->getContent($item_id,"*","*",1);
        if($page_id > 0 ){
            $pages[] = D("Page")->where(" page_id = '$page_id' ")->find();
        }
        else if ($cat_id) {
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
                if(count($pages) > 1){
                    $data .= "<h1>{$parent}、{$value['page_title']}</h1>";
                }else{
                    $data .= "<h1>{$value['page_title']}</h1>";
                }
                $data .= '<div style="margin-left:20px;">';
                $tmp_content = D("Page")->runapiToMd($value['page_content']) ;
                $value['page_content'] = $tmp_content ? $tmp_content : $value['page_content'] ;
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
                            $tmp_content = D("Page")->runapiToMd($page['page_content']) ;
                            $page['page_content'] = $tmp_content ? $tmp_content : $page['page_content'] ;
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
                                        $tmp_content = D("Page")->runapiToMd($page3['page_content']) ;
                                        $page3['page_content'] = $tmp_content ? $tmp_content : $page3['page_content'] ;
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
                                                    $tmp_content = D("Page")->runapiToMd($page4['page_content']) ;
                                                    $page4['page_content'] = $tmp_content ? $tmp_content : $page4['page_content'] ;
                                                    $data .= htmlspecialchars_decode($Parsedown->text($page4['page_content']));
                                                    $data .= '</div>';
                                                    $child3 ++;
                                                }
                                            }
                                            if ($value4['catalogs']) {
                                                $parent4 = 1 ;
                                                foreach ($value4['catalogs'] as $key5 => $value5) {
                                                    $data .= "<h2>{$parent}.{$parent2}.{$parent3}.{$parent4}、{$value5['cat_name']}</h2>";
                                                    $data .= '<div style="margin-left:0px;">';
                                                        $child4 = 1 ;
                                                        if ($value4['pages']) {
                                                            foreach ($value4['pages'] as $page5) {
                                                                $data .= "<h3>{$parent}.{$parent2}.{$parent3}.{$parent4}.{$child4}、{$page5['page_title']}</h3>";
                                                                $data .= '<div style="margin-left:30px;">';
                                                                $tmp_content = D("Page")->runapiToMd($page5['page_content']) ;
                                                                $page5['page_content'] = $tmp_content ? $tmp_content : $page5['page_content'] ;
                                                                $data .= htmlspecialchars_decode($Parsedown->text($page5['page_content']));
                                                                $data .= '</div>';
                                                                $child3 ++;
                                                            }
                                                        }
                                                    $data .= '</div>';
                                                    $parent3 ++;
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
        if (!$this->checkItemEdit($login_user['uid'] , $item_id)) {
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
        unset($exportData['members']);
        file_put_contents($temp_dir.'/'.'info.json', json_encode($exportData));
        file_put_contents($temp_dir.'/'.'readme.md', "由于页面标题可能含有特殊字符导致异常，所以markdown文件的命名均为英文（md5串），以下是页面标题和文件的对应关系：".PHP_EOL.PHP_EOL );

        $exportData['pages'] = $this->_markdownTofile( $exportData['pages'] , $temp_dir);
        $ret = $this->_zip( $temp_dir ,$temp_file );

        clear_runtime($temp_dir);
        rmdir($temp_dir);
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
                //把页面内容保存为md文件
                $filename = md5($value['page_title'].'_'.$t).".md" ;
                file_put_contents($temp_dir.'/'.$filename, htmlspecialchars_decode( $value['page_content']) ) ;

                file_put_contents($temp_dir.'/'.'readme.md',$value['page_title']. " —— prefix_".  $filename  .PHP_EOL, FILE_APPEND );

            }
        }

        if ($catalogData['catalogs']) {
            foreach ($catalogData['catalogs'] as $key => $value) {
                $catalogData['catalogs'][$key] = $this->_markdownTofile($value ,  $temp_dir);
            }
            
        }
        return $catalogData ;

    }

    private function _zip($temp_dir, $temp_file)
    {
        $zipArc = new \ZipArchive();
        if(!$zipArc->open($temp_file, \ZipArchive::CREATE)){
            return FALSE;
        }
         $dir = opendir( $temp_dir );
         while( false != ( $file = readdir( $dir ) ) )
         {
              if( ( $file != "." ) and ( $file != ".." ) )
              {
                 $res = $zipArc->addFromString ( "prefix_".$file , file_get_contents($temp_dir."/".$file) ) ;
              }
         }
        closedir( $dir );

        if(!$res){
            $zipArc->close();
            return FALSE;
        }
        return $zipArc->close();
    }

}
