<?php

namespace Api\Model;

use Api\Model\BaseModel;

/**
 * 
 * @author star7th      
 */
class PageModel extends BaseModel
{


  //搜索某个项目下的页面
  public function search($item_id, $keyword)
  {
    $return_pages = array();
    $item = D("Item")->where("item_id = '%d' and is_del = 0 ", array($item_id))->find();
    $pages = $this->where("item_id = '$item_id' and is_del = 0")->order(" s_number asc  ")->select();
    if (!empty($pages)) {
      foreach ($pages as $key => &$value) {
        $page_content = $value['page_content'];
        if (strpos(strtolower($item['item_name'] . "-" . $value['page_title'] . "  " . $page_content), strtolower($keyword)) !== false) {
          $value['page_content'] = $page_content;
          $return_pages[] = $value;
        }
      }
    }
    unset($pages);
    return $return_pages;
  }

  //根据标题更新页面
  //其中cat_name参数特别说明下,传递各格式如 '二级目录/三级目录/四级目录'
  public function update_by_title($item_id, $page_title, $page_content, $cat_name = '', $s_number = 99, $author_uid = 0, $author_username = 'update_by_title')
  {
    $item_id = intval($item_id);
    $s_number = intval($s_number);
    if (!$item_id) {
      return false;
    }

    // 用路径的形式（比如'二级目录/三级目录/四级目录'）来保存目录信息并返回最后一层目录的id
    $cat_id = D("Catalog")->saveCatPath($cat_name, $item_id);

    $this->cat_name_id[$cat_name] = $cat_id;

    if ($page_content) {
      $page_array = D("Page")->field("page_id")->where(" item_id = '$item_id' and is_del = 0  and cat_id = '$cat_id'  and page_title ='%s' ", array($page_title))->find();
      //如果不存在则新建
      if (!$page_array) {
        $add_data = array(
          "author_uid" => $author_uid,
          "author_username" => $author_username,
          "item_id" => $item_id,
          "cat_id" => $cat_id,
          "page_title" => $this->_htmlspecialchars($page_title),
          "page_content" => $this->_htmlspecialchars($page_content),
          "s_number" => $s_number,
          "addtime" => time(),
        );
        $page_id = D("Page")->add($add_data);
      } else {
        $page_id = $page_array['page_id'];
        $update_data = array(
          "author_uid" => $author_uid,
          "author_username" => $author_username,
          "item_id" => $item_id,
          "cat_id" => $cat_id,
          "page_title" => $this->_htmlspecialchars($page_title),
          "page_content" => $this->_htmlspecialchars($page_content),
          "s_number" => $s_number,
        );
        D("Page")->where(" page_id = '$page_id' ")->save($update_data);
      }
    }

    return $page_id;
  }

  //软删除页面
  public function softDeletePage($page_id)
  {
    $page_id = intval($page_id);
    //放入回收站
    $login_user = session('login_user');
    $page = D("Page")->field("item_id,page_title")->where(" page_id = '$page_id' ")->find();
    D("Recycle")->add(array(
      "item_id" => $page['item_id'],
      "page_id" => $page_id,
      "page_title" => $page['page_title'],
      "del_by_uid" => $login_user['uid'],
      "del_by_username" => $login_user['username'],
      "del_time" => time()
    ));
    $ret = M("Page")->where(" page_id = '$page_id' ")->save(array("is_del" => 1, "addtime" => time()));
    return $ret;
  }

  //删除页面
  public function deletePage($page_id)
  {
    $page_id = intval($page_id);
    $ret = M("Page")->where(" page_id = '$page_id' ")->delete();
    return $ret;
  }

  public function deleteFile($file_id)
  {
    $file_id = intval($file_id);
    return D("Attachment")->deleteFile($file_id);
  }

  private function _htmlspecialchars($str)
  {
    if (!$str) {
      return '';
    }
    //之所以先htmlspecialchars_decode是为了防止被htmlspecialchars转义了两次
    return htmlspecialchars(htmlspecialchars_decode($str));
  }
}
