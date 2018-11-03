<?php
namespace Api\Model;
use Api\Model\BaseModel;

class ItemModel extends BaseModel {

    public function export($item_id){
        $item = D("Item")->where("item_id = '$item_id' ")->field(" item_type, item_name ,item_description,password ")->find();
        $page_field = "page_title ,cat_id,page_content,s_number,page_comments";
        $catalog_field = "cat_id,cat_name ,parent_cat_id,level,s_number";
        $item['pages'] = $this->getContent($item_id , $page_field , $catalog_field ); 
        $item['members'] = D("ItemMember")->where("item_id = '$item_id' ")->field(" member_group_id ,uid,username ")->select();
        return  json_encode($item);
        
    }
    public function import($json,$uid,$item_name= '',$item_description= '',$item_password = '',$item_domain = ''){
        $userInfo = D("User")->userInfo($uid);
        $item = json_decode($json ,1 );
        unset($json);
        if ($item) {
            if ($item['item_domain']) {
                $item2 = D("Item")->where("item_domain = '%s'  ".array($item['item_domain']))->find();
                if ($item2) {
                    //个性域名已经存在
                    return false;
                }
                if(!ctype_alnum($item_domain) ||  is_numeric($item_domain) ){
                    //echo '个性域名只能是字母或数字的组合';exit;
                    return false;
                }
            }else{
                $item['item_domain'] = '';
            }
            $item_data = array(
                "item_name"=>$item_name ? $item_name :$item['item_name'],
                "item_domain"=>$item_domain ? $item_domain :$item['item_domain'],
                "item_type"=>$item['item_type'],
                "item_description"=>$item_description ? $item_description :$item['item_description'],
                "password"=>$item_password ? $item_password :$item['password'],
                "uid"=>$userInfo['uid'],
                "username"=>$userInfo['username'],
                "addtime"=>time(),
                );
            $item_id = D("Item")->add($item_data);
        }
        if ($item['pages']) {
            //父页面们（一级目录）
            if ($item['pages']['pages']) {
                foreach ($item['pages']['pages'] as $key => &$value) {
                    $page_data = array(
                        "author_uid"=>$userInfo['uid'],
                        "author_username"=>$userInfo['username'],
                        "page_title" =>$value['page_title'],
                        "page_content" =>$value['page_content'],
                        "s_number" =>$value['s_number'],
                        "page_comments" =>$value['page_comments'],
                        "item_id" => $item_id,
                        "cat_id" => 0 ,
                        "addtime" =>time(),
                        );
                    D("Page")->add($page_data);
                    unset($page_data);
                }
                unset($item['pages']['pages']);
            }
            //二级目录
            if ($item['pages']['catalogs']) {
                foreach ($item['pages']['catalogs'] as $key => &$value) {
                    $catalog_data = array(
                        "cat_name" => $value['cat_name'],
                        "level" => $value['level'],
                        "s_number" => $value['s_number'],
                        "item_id" => $item_id,
                        "addtime" =>time(),
                        );
                    $cat_id = D("Catalog")->add($catalog_data);
                    //二级目录的页面们
                    if ($value['pages']) {
                        foreach ($value['pages'] as $key2 => &$value2) {
                            $page_data = array(
                                "author_uid"=>$userInfo['uid'],
                                "author_username"=>$userInfo['username'],
                                "page_title" =>$value2['page_title'],
                                "page_content" =>$value2['page_content'],
                                "s_number" =>$value2['s_number'],
                                "page_comments" =>$value2['page_comments'],
                                "item_id" => $item_id,
                                "cat_id" => $cat_id ,
                                "addtime" =>time(),
                                );
                            D("Page")->add($page_data);
                            unset($page_data);
                            unset($value2);
                        }
                    }
                    //判断是否存在三级目录
                    if ($value['catalogs']) {
                            foreach ($value['catalogs'] as $key3 => &$value3) {
                                $catalog_data = array(
                                    "cat_name" => $value3['cat_name'],
                                    "level" => $value3['level'],
                                    "s_number" => $value3['s_number'],
                                    "parent_cat_id" => $cat_id,
                                    "item_id" => $item_id,
                                    "addtime" =>time(),
                                    );
                                $cat_id2 = D("Catalog")->add($catalog_data);
                                //三级目录的页面们
                                if ($value3['pages']) {
                                    foreach ($value3['pages'] as $key4 => &$value4) {
                                        $page_data = array(
                                            "author_uid"=>$userInfo['uid'],
                                            "author_username"=>$userInfo['username'],
                                            "page_title" =>$value4['page_title'],
                                            "page_content" =>$value4['page_content'],
                                            "s_number" =>$value4['s_number'],
                                            "page_comments" =>$value4['page_comments'],
                                            "item_id" => $item_id,
                                            "cat_id" => $cat_id2 ,
                                            "addtime" =>time(),
                                            );
                                        D("Page")->add($page_data);
                                        unset($page_data);
                                        unset($value4);
                                    }
                                }

                                //判断是否存在四级目录
                                if ($value3['catalogs']) {
                                        foreach ($value3['catalogs'] as $key5 => &$value5) {
                                            $catalog_data = array(
                                                "cat_name" => $value5['cat_name'],
                                                "level" => $value5['level'],
                                                "s_number" => $value5['s_number'],
                                                "parent_cat_id" => $cat_id2,
                                                "item_id" => $item_id,
                                                "addtime" =>time(),
                                                );
                                            $cat_id3 = D("Catalog")->add($catalog_data);
                                            //四级目录的页面们
                                            if ($value5['pages']) {
                                                foreach ($value5['pages'] as $key6 => &$value6) {
                                                    $page_data = array(
                                                        "author_uid"=>$userInfo['uid'],
                                                        "author_username"=>$userInfo['username'],
                                                        "page_title" =>$value6['page_title'],
                                                        "page_content" =>$value6['page_content'],
                                                        "s_number" =>$value6['s_number'],
                                                        "page_comments" =>$value6['page_comments'],
                                                        "item_id" => $item_id,
                                                        "cat_id" => $cat_id3 ,
                                                        "addtime" =>time(),
                                                        );
                                                    D("Page")->add($page_data);
                                                    unset($page_data);
                                                    unset($value6);
                                                }
                                            }
                                         unset($value3);
                                        }
                                }
                             unset($value3);
                            }
                    }
                    unset($value);
                }
                 
            }
        }

        if ($item['members']) {
            foreach ($item['members'] as $key => $value) {
                $member_data = array(
                    "member_group_id"=>$value['member_group_id'],
                    "uid"=>$value['uid'],
                    "username"=>$value['username'],
                    "item_id"=>$item_id,
                    "addtime"=>time(),
                    );
                D("ItemMember")->add($member_data);
            }
        }
        return $item_id;
    }

    public function copy($item_id,$uid,$item_name= '',$item_description= '',$item_password = '',$item_domain){
        return $this->import($this->export($item_id),$uid,$item_name,$item_description,$item_password,$item_domain);
    }

    //获取菜单结构
    public function getMemu($item_id){
            $page_field = "page_id,author_uid,cat_id,page_title,addtime";
            $catalog_field = '*';
            $data = $this->getContent($item_id , $page_field , $catalog_field) ;
            return $data ;
    }

    public function getContent($item_id , $page_field ="*" , $catalog_field ="*" , $uncompress = 0 ){
            //获取所有父目录id为0的页面
            $all_pages = D("Page")->where("item_id = '$item_id' and is_del = 0 ")->order(" `s_number` asc  ")->field($page_field)->select();
            $pages = array() ;
            if ($all_pages) {
                foreach ($all_pages as $key => $value) {
                    if ($value['cat_id']) {
                        # code...
                    }else{
                        $pages[] = $value ;
                    }
                }
            }
            
            //获取该项目下的所有目录
            $all_catalogs = D("Catalog")->field($catalog_field)->where("item_id = '$item_id' ")->order(" `s_number` asc  ")->select();

            //获取所有二级目录
            $catalogs = array() ;
            if ($all_catalogs) {
                foreach ($all_catalogs as $key => $value) {
                    if ($value['level'] == 2 ) {
                        $catalogs[] = $value;
                    }
                }
            }
            if ($catalogs) {
                foreach ($catalogs as $key => &$catalog2) {
                    //该二级目录下的所有子页面
                    $catalog2['pages'] = $this->_getPageByCatId($catalog2['cat_id'],$all_pages);

                    //该二级目录下的所有子目录
                    $catalog2['catalogs'] =  $this->_getCatByCatId($catalog2['cat_id'],$all_catalogs);
                    if($catalog2['catalogs']){
                        //获取所有三级目录的子页面
                        foreach ($catalog2['catalogs'] as $key3 => &$catalog3) {
                            //该三级目录下的所有子页面
                            $catalog3['pages'] = $this->_getPageByCatId($catalog3['cat_id'],$all_pages);

                            //该三级目录下的所有子目录
                            $catalog3['catalogs'] =  $this->_getCatByCatId($catalog3['cat_id'],$all_catalogs);
                            if($catalog3['catalogs']){
                                //获取所有三级目录的子页面
                                foreach ($catalog3['catalogs'] as $key4 => &$catalog4) {
                                    //该三级目录下的所有子页面
                                    $catalog4['pages'] = $this->_getPageByCatId($catalog4['cat_id'],$all_pages);
                                }                        
                            }

                        }                        
                    }             
                }
            }
            $menu = array(
                "pages" =>$pages,
                "catalogs" =>$catalogs,
                );
            unset($pages);
            unset($catalogs);
            return $menu;
    }
    
    //获取某个目录下的所有页面
    private function _getPageByCatId($cat_id ,$all_pages){
        $pages = array() ;
        if ($all_pages) {
            foreach ($all_pages as $key => $value) {
                if ($value['cat_id'] == $cat_id) {
                    $pages[] = $value ;
                }
            }
        }
        return $pages;
    }

    //获取某个目录下的所有子目录
    private function _getCatByCatId($cat_id ,$all_catalogs){
        $cats = array() ;
        if ($all_catalogs) {
            foreach ($all_catalogs as $key => $value) {
                if ($value['parent_cat_id'] == $cat_id) {
                    $cats[] = $value ;
                }
            }
        }
        return $cats;
    }


    //删除项目
    public function delete_item($item_id){
        D("Page")->where("item_id = '$item_id' ")->delete();
        D("Page")->where("item_id = '$item_id' ")->delete();
        D("Catalog")->where("item_id = '$item_id' ")->delete();
        D("PageHistory")->where("item_id = '$item_id' ")->delete();
        D("ItemMember")->where("item_id = '$item_id' ")->delete();
        D("TeamItem")->where("item_id = '$item_id' ")->delete();
        D("TeamItemMember")->where("item_id = '$item_id' ")->delete();
        return D("Item")->where("item_id = '$item_id' ")->delete();

    }
    
    //软删除项目
    public function soft_delete_item($item_id){
        return $this->where("item_id = '$item_id' ")->save(array("is_del"=>1 ,"last_update_time"=>time()));
    }

}