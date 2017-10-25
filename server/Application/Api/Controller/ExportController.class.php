<?php
namespace Api\Controller;
use Think\Controller;
class ExportController extends BaseController {

    //导出整个项目为word
    public function word(){
        import("Vendor.Parsedown.Parsedown");
        $Parsedown = new \Parsedown();
        $item_id =  I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }

        $item = D("Item")->where("item_id = '$item_id' ")->find();

        //获取所有父目录id为0的页面
        $pages = D("Page")->where("cat_id = '0' and item_id = '$item_id' ")->order(" `s_number` asc  ")->select();
        //获取所有二级目录
        $catalogs = D("Catalog")->where("item_id = '$item_id' and level = 2  ")->order(" `s_number` asc  ")->select();
        if ($catalogs) {
            foreach ($catalogs as $key => &$catalog) {
                //该二级目录下的所有子页面
                $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                $catalog['pages'] = $temp ? $temp: array();

                //该二级目录下的所有子目录
                $temp = D("catalog")->where("parent_cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                $catalog['catalogs'] = $temp ? $temp: array();
                if($catalog['catalogs']){
                    //获取所有三级目录的子页面
                    foreach ($catalog['catalogs'] as $key3 => &$catalog3) {
                        //该二级目录下的所有子页面
                        $temp = D("Page")->where("cat_id = '$catalog3[cat_id]' ")->order(" `s_number` asc  ")->select();
                        $catalog3['pages'] = $temp ? $temp: array();
                    }                        
                }               
            }
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
                $data .= '<div style="margin-left:20px;">';
                    $child = 1 ;
                    if ($value['pages']) {
                        foreach ($value['pages'] as $page) {
                            $data .= "<h2>{$parent}.{$child}、{$page['page_title']}</h2>";
                            $data .= '<div style="margin-left:20px;">';
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
                                        $data .= '<div style="margin-left:30px;">';
                                            $data .= htmlspecialchars_decode($Parsedown->text($page3['page_content']));
                                        $data .= '</div>';
                                        $child2 ++;
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

    //把指定目录导出为word
    public function word_cat(){
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
        //获取指定目录。先按照目录是二级目录的逻辑来处理
        $catalog = D("Catalog")->where("item_id = '$item_id' and cat_id = '$cat_id' and level =2 ")->order(" `s_number` asc  ")->find();
        if (!empty($catalog)) {
                //该二级目录下的所有子页面
                $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                $catalog['pages'] = $temp ? $temp: array();

                //该二级目录下的所有子目录
                $temp = D("catalog")->where("parent_cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                $catalog['catalogs'] = $temp ? $temp: array();
                if($catalog['catalogs']){
                    //获取所有三级目录的子页面
                    foreach ($catalog['catalogs'] as $key3 => &$catalog3) {
                        //该二级目录下的所有子页面
                        $temp = D("Page")->where("cat_id = '$catalog3[cat_id]' ")->order(" `s_number` asc  ")->select();
                        $catalog3['pages'] = $temp ? $temp: array();
                    }                        
                } 
        }else{
            //获取指定目录。按照目录是三级目录的逻辑来处理
            $catalog = D("Catalog")->where("item_id = '$item_id' and cat_id = '$cat_id' and level =3 ")->order(" `s_number` asc  ")->find();
            if (!empty($catalog)) {
                    //该三级目录下的所有子页面
                    $temp = D("Page")->where("cat_id = '$catalog[cat_id]' ")->order(" `s_number` asc  ")->select();
                    $catalog['pages'] = $temp ? $temp: array();
            }
        }


        $data = '';
        $parent = 1;
        //var_export($catalog);exit();
        $data .= "<h1>{$catalog['cat_name']}</h1>";
        $data .= '<div style="margin-left:20px;">';
            $child = 1 ;
            if ($catalog['pages']) {
                foreach ($catalog['pages'] as $page) {
                    $data .= "<h2>{$child}、{$page['page_title']}</h2>";
                    $data .= '<div style="margin-left:20px;">';
                        $data .= htmlspecialchars_decode($Parsedown->text($page['page_content']));
                    $data .= '</div>';
                    $child ++;
                }
            }
            if ($catalog['catalogs']) {
                $parent2 = 1 ;
                foreach ($catalog['catalogs'] as $key3 => $value3) {
                    $data .= "<h2>{$parent2}、{$value3['cat_name']}</h2>";
                    $data .= '<div style="margin-left:20px;">';
                        $child2 = 1 ;
                        if ($value3['pages']) {
                            foreach ($value3['pages'] as $page3) {
                                $data .= "<h3>{$parent2}.{$child2}、{$page3['page_title']}</h3>";
                                $data .= '<div style="margin-left:30px;">';
                                    $data .= htmlspecialchars_decode($Parsedown->text($page3['page_content']));
                                $data .= '</div>';
                                $child2 ++;
                            }
                        }
                    $data .= '</div>';
                    $parent2 ++;
                }
            }
        $data .= '</div>';

        output_word($data,$item['item_name']);

    }

    //把指定页面导出为word
    public function word_page(){
        import("Vendor.Parsedown.Parsedown");
        $Parsedown = new \Parsedown();
        $item_id =  I("item_id/d");
        $page_id =  I("page_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemPermn($login_user['uid'] , $item_id)) {
            $this->message(L('no_permissions'));
            return;
        }
        $temp = D("Page")->where("page_id = '$page_id' ")->order(" `s_number` asc  ")->find();
        $page= $temp ? $temp: array();
        $data = htmlspecialchars_decode($Parsedown->text($page['page_content']));
        output_word($data,$page['page_title']);
    }

}