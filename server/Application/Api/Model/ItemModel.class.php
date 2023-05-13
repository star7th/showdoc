<?php

namespace Api\Model;

use Api\Model\BaseModel;

class ItemModel extends BaseModel
{

    public function export($item_id, $uncompress = 0)
    {
        $item_id = intval($item_id);
        $item = D("Item")->where("item_id = '$item_id' ")->field(" item_type, item_name ,item_description,password ")->find();
        $page_field = "page_title ,cat_id,page_content,s_number,page_comments";
        $catalog_field = "cat_id,cat_name ,parent_cat_id,level,s_number";
        $item['pages'] = $this->getContent($item_id, $page_field, $catalog_field, $uncompress);
        $item['members'] = D("ItemMember")->where("item_id = '$item_id' ")->field(" member_group_id ,uid,username ")->select();
        return  json_encode($item);
    }
    public function import($json, $uid, $item_id = 0, $item_name = '', $item_description = '', $item_password = '', $item_domain = '')
    {
        $userInfo = D("User")->userInfo($uid);
        $item = json_decode($json, 1);
        unset($json);
        if ($item) {

            // 如果存在$item_id，那就是项目内导入。
            if ($item_id) {
                //

            } else {
                if ($item['item_domain']) {
                    $item2 = D("Item")->where("item_domain = '%s'  " . array($item['item_domain']))->find();
                    if ($item2) {
                        //个性域名已经存在
                        return false;
                    }
                    if (!ctype_alnum($item_domain) ||  is_numeric($item_domain)) {
                        //echo '个性域名只能是字母或数字的组合';exit;
                        return false;
                    }
                } else {
                    $item['item_domain'] = '';
                }
                $item_data = array(
                    "item_name" => $item_name ? $this->_htmlspecialchars($item_name)  : $this->_htmlspecialchars($item['item_name']),
                    "item_domain" => $item_domain ? $this->_htmlspecialchars($item_domain)  : $this->_htmlspecialchars($item['item_domain']),
                    "item_type" => $this->_htmlspecialchars($item['item_type']),
                    "item_description" => $item_description ? $this->_htmlspecialchars($item_description) : $this->_htmlspecialchars($item['item_description']),
                    "password" => $item_password ? $this->_htmlspecialchars($item_password)  : $this->_htmlspecialchars($item['password']),
                    "uid" => $userInfo['uid'],
                    "username" => $userInfo['username'],
                    "addtime" => time(),
                );
                $item_id = D("Item")->add($item_data);
            }
        }
        if ($item['pages']) {
            //父页面们（一级目录）
            if ($item['pages']['pages']) {
                foreach ($item['pages']['pages'] as $key => &$value) {
                    $page_data = array(
                        "author_uid" => $userInfo['uid'],
                        "author_username" => $userInfo['username'],
                        "page_title" => $this->_htmlspecialchars($value['page_title']),
                        "page_content" => $this->_htmlspecialchars($value['page_content']),
                        "s_number" => $this->_htmlspecialchars($value['s_number']),
                        "page_comments" => $this->_htmlspecialchars($value['page_comments']),
                        "item_id" => $item_id,
                        "cat_id" => 0,
                        "addtime" => time(),
                    );
                    D("Page")->add($page_data);
                    unset($page_data);
                }
                unset($item['pages']['pages']);
            }
            //二级目录
            if ($item['pages']['catalogs']) {
                $cat_path_pages = $this->toItemPageCatPath($item['pages']['catalogs']);
                foreach ($cat_path_pages as $key => $value) {
                    $page_id = D("Page")->update_by_title($item_id, $value['page_title'], $value['page_content'], $value['cat_path'], $value['s_number'], $userInfo['uid'], $userInfo['username']);
                }
            }
        }

        return $item_id;
    }

    public function copy($item_id, $uid, $item_name = '', $item_description = '', $item_password = '', $item_domain = '')
    {
        return $this->import($this->export($item_id), $uid, 0, $item_name, $item_description, $item_password, $item_domain);
    }

    //获取菜单结构
    public function getMemu($item_id)
    {
        $page_field = "page_id,author_uid,cat_id,page_title,addtime,ext_info";
        $catalog_field = '*';
        $data = $this->getContent($item_id, $page_field, $catalog_field);
        return $data;
    }

    public function getContent($item_id, $page_field = "*", $catalog_field = "*", $uncompress = 0)
    {
        $item_id = intval($item_id);
        //获取该项目下的所有页面
        $all_pages = D("Page")->where("item_id = '$item_id' and is_del = 0 ")->order(" s_number asc , page_id asc  ")->field($page_field)->select();
        $pages = array();
        if ($all_pages) {
            foreach ($all_pages as $key => $value) {
                if ($value['cat_id']) {
                    # code...
                } else {
                    $pages[] = $value;
                }
            }
        }

        //获取该项目下的所有目录
        $all_catalogs = D("Catalog")->field($catalog_field)->where("item_id = '$item_id' ")->order(" s_number asc , cat_id asc ")->select();

        //获取所有二级目录
        $catalogs = array();
        if ($all_catalogs) {
            foreach ($all_catalogs as $key => $value) {
                if ($value['level'] == 2) {
                    $catalogs[] = $value;
                }
            }
        }
        if ($catalogs) {
            foreach ($catalogs as $key => &$catalog2) {
                $catalog2 = $this->_getCat($catalog2, $all_pages, $all_catalogs);
            }
        }
        $menu = array(
            "pages" => $pages,
            "catalogs" => $catalogs,
        );
        unset($pages);
        unset($catalogs);
        return $menu;
    }

    //获取某个目录下的页面和子目录
    private function _getCat($catalog_data, &$all_pages, &$all_catalogs)
    {
        $catalog_data['pages'] = $this->_getPageByCatId($catalog_data['cat_id'], $all_pages);
        //该目录下的所有子目录
        $sub_catalogs =  $this->_getCatByCatId($catalog_data['cat_id'], $all_catalogs);
        if ($sub_catalogs) {
            foreach ($sub_catalogs as $key => $value) {
                $catalog_data['catalogs'][] = $this->_getCat($value, $all_pages, $all_catalogs);
            }
        }
        if (!$catalog_data['catalogs']) {
            $catalog_data['catalogs'] = array();
        }
        return $catalog_data;
    }


    //获取某个目录下的所有页面
    private function _getPageByCatId($cat_id, $all_pages)
    {
        $pages = array();
        if ($all_pages) {
            foreach ($all_pages as $key => $value) {
                if ($value['cat_id'] == $cat_id) {
                    $pages[] = $value;
                }
            }
        }
        return $pages;
    }

    //获取某个目录下的页面和子目录
    public function getCat($catalog_data,  &$all_pages, &$all_catalogs)
    {
        return $this->_getCat($catalog_data,  $all_pages, $all_catalogs);
    }

    //获取某个目录下的所有子目录
    private function _getCatByCatId($cat_id, $all_catalogs)
    {
        $cats = array();
        if ($all_catalogs) {
            foreach ($all_catalogs as $key => $value) {
                if ($value['parent_cat_id'] == $cat_id) {
                    $cats[] = $value;
                }
            }
        }
        return $cats;
    }


    //删除项目
    public function delete_item($item_id)
    {
        $item_id = intval($item_id);
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
    public function soft_delete_item($item_id)
    {
        $item_id = intval($item_id);
        return $this->where("item_id = '$item_id' ")->save(array("is_del" => 1, "last_update_time" => time()));
    }

    private function _htmlspecialchars($str)
    {
        if (!$str) {
            return '';
        }
        //之所以先htmlspecialchars_decode是为了防止被htmlspecialchars转义了两次
        return htmlspecialchars(htmlspecialchars_decode($str));
    }


    //根据用户目录权限来过滤项目数据
    public function filteMemberItem($uid, $item_id, $menuData)
    {
        if (!$menuData || !$menuData['catalogs']) {
            return $menuData;
        }
        $uid = intval($uid);
        $item_id = intval($item_id);
        $cat_id = 0;
        //首先看是否被添加为项目成员
        $itemMember = D("ItemMember")->where("uid = '$uid' and item_id = '$item_id' ")->find();
        if ($itemMember && $itemMember['cat_id'] > 0) {
            $cat_id = $itemMember['cat_id'];
        }
        //再看是否添加为团队-项目成员
        $teamItemMember = D("TeamItemMember")->where("member_uid = '$uid' and item_id = '$item_id' ")->find();
        if ($teamItemMember && $teamItemMember['cat_id'] > 0) {
            $cat_id = $teamItemMember['cat_id'];
        }
        //开始根据cat_id过滤
        if ($cat_id > 0) {
            foreach ($menuData['catalogs'] as $key => $value) {
                if ($value['cat_id'] != $cat_id) {
                    unset($menuData['catalogs'][$key]);
                }
            }
            $menuData['catalogs'] = array_values($menuData['catalogs']);
        }

        return $menuData;
    }


    // 把目录嵌套的项目页面数据平摊为目录路径（比如说‘目录/子目录1/子目录2’）的形式
    public function toItemPageCatPath($catalogs, $parent_cat_name = '')
    {
        if (!$catalogs) return false;
        $return_array = array();

        if (!$catalogs) {
            return;
        }
        $cat_id = 0;
        foreach ($catalogs as $key => $value) {
            $cat_name = $value['cat_name'];
            if ($parent_cat_name) {
                $cat_path =  $parent_cat_name . '/' . $cat_name;
            } else {
                $cat_path =  $cat_name;
            }


            //该目录下的页面们
            if ($value['pages']) {
                foreach ($value['pages'] as $key2 => $value2) {
                    $value2['cat_path'] = $cat_path;
                    unset($value2['cat_name']);
                    unset($value2['level']);
                    // unset($value2['page_content']);
                    $return_array[] = $value2;
                }
            }

            //该目录的子目录
            if ($value['catalogs']) {
                $sub_array = $this->toItemPageCatPath($value['catalogs'], $cat_path);
                $return_array = array_merge($return_array, $sub_array);
            }
        }

        return $return_array;
    }
}
