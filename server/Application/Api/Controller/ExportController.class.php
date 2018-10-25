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

                                if ($value3['catalogs']) {
                                    $parent3 = 1 ;
                                    foreach ($value3['catalogs'] as $key4 => $value4) {
                                        $data .= "<h2>{$parent}.{$parent2}.{$parent3}、{$value4['cat_name']}</h2>";
                                        $data .= '<div style="margin-left:20px;">';
                                            $child3 = 1 ;
                                            if ($value4['pages']) {
                                                foreach ($value4['pages'] as $page4) {
                                                    $data .= "<h3>{$parent}.{$parent2}.{$child2}.{$child3}、{$page4['page_title']}</h3>";
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


}
