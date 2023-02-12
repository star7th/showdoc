<?php

namespace Api\Controller;

use Think\Controller;

class ItemController extends BaseController
{


    //单个项目信息
    public function info()
    {
        $this->checkLogin(false);
        $item_id = I("item_id/s");
        $item_domain = I("item_domain/s");
        $current_page_id = I("page_id/d");
        if (!is_numeric($item_id)) {
            $item_domain = $item_id;
        }
        //判断个性域名
        if ($item_domain) {
            $item = D("Item")->where("item_domain = '%s'", array($item_domain))->find();
            if ($item['item_id']) {
                $item_id = $item['item_id'];
            }
        }
        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0;

        if (!$this->checkItemVisit($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }

        $item = D("Item")->where("item_id = '%d' ", array($item_id))->find();
        if (!$item || $item['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101, '项目不存在或者已删除');
            return false;
        }
        //从2020.7.5开始，常规项目和单页项目合并在一起返回
        $this->_show_regular_item($item);
    }

    //展示常规项目
    private function _show_regular_item($item)
    {
        $item_id = $item['item_id'];

        $default_page_id = I("default_page_id/d");
        $current_page_id = I("page_id/d");
        $keyword = I("keyword");
        $default_cat_id2 = $default_cat_id3 = 0;

        $login_user = session("login_user");
        $uid = $login_user['uid'] ? $login_user['uid'] : 0;
        $is_login =   $uid > 0 ? true : false;
        $menu = array(
            "pages" => array(),
            "catalogs" => array(),
        );
        //是否有搜索词
        if ($keyword) {
            $keyword = strtolower($keyword);
            $keyword = \SQLite3::escapeString($keyword);
            $pages = D("Page")->where("item_id = '$item_id' and is_del = 0  and ( lower(page_title) like '%{$keyword}%' or lower(page_content) like '%{$keyword}%' ) ")->order(" s_number asc  ")->field("page_id,author_uid,cat_id,page_title,addtime")->select();
            $menu['pages'] = $pages ? $pages : array();
            $menu['catalogs'] = array();
        } else {
            $menu = D("Item")->getMemu($item_id);
            if ($uid > 0) {
                $menu = D("Item")->filteMemberItem($uid, $item_id, $menu);
            }
        }

        $domain = $item['item_domain'] ? $item['item_domain'] : $item['item_id'];

        $item_edit = $this->checkItemEdit($uid, $item_id);

        $item_manage = $this->checkItemManage($uid, $item_id);

        //如果带了默认展开的页面id，则获取该页面所在的二级目录/三级目录/四级目录
        if ($default_page_id) {
            $page = D("Page")->where(" page_id = '$default_page_id' ")->find();
            if ($page) {
                $default_cat_id4 = $page['cat_id'];
                $cat1 = D("Catalog")->where(" cat_id = '$default_cat_id4' and parent_cat_id > 0  ")->find();
                if ($cat1) {
                    $default_cat_id3 = $cat1['parent_cat_id'];
                } else {
                    $default_cat_id3 = $default_cat_id4;
                    $default_cat_id4 = 0;
                }

                $cat2 = D("Catalog")->where(" cat_id = '$default_cat_id3' and parent_cat_id > 0  ")->find();
                if ($cat2) {
                    $default_cat_id2 = $cat2['parent_cat_id'];
                } else {
                    $default_cat_id2 = $default_cat_id3;
                    $default_cat_id3 = 0;
                }
            }
        }

        if (LANG_SET == 'en-us') {
            $help_url = "https://www.showdoc.cc/help-en";
        } else {
            $help_url = "https://www.showdoc.cc/help";
        }

        //当已经归档了，则去掉编辑权限
        if ($item['is_archived']) {
            $item_edit = $item_manage = false;
        }

        //如果项目类型为runapi，则获取看看有没有全局参数
        $global_param = array();
        if ($item['item_type'] == 3) {
            $global_param = D("Runapi")->getGlobalParam($item_id);
        }

        // 登录的状态下，才去检查下是否开启了水印
        if ($is_login) { //少了个$ if(is_login)
            $show_watermark = D("Options")->get("show_watermark");
            $show_watermark = $show_watermark ? '1' : '0';
        }


        $return = array(
            "item_id" => $item_id,
            "item_domain" => $item['item_domain'],
            "is_archived" => $item['is_archived'],
            "item_name" => $item['item_name'],
            "default_page_id" => (string)$default_page_id,
            "default_cat_id2" => $default_cat_id2,
            "default_cat_id3" => $default_cat_id3,
            "default_cat_id4" => $default_cat_id4,
            "unread_count" => $unread_count,
            "item_type" => $item['item_type'],
            "menu" => $menu,
            "is_login" => $is_login,
            "item_edit" => $item_edit,
            "item_manage" => $item_manage,
            "ItemPermn" => $item_edit, // ItemPermn 和 ItemCreator这两个字段是为了兼容历史。确保各大客户端(web/手机/runapi)改用字段后可以去掉
            "ItemCreator" => $item_manage,
            "current_page_id" => $current_page_id,
            "global_param" => $global_param,
            "show_watermark" => $show_watermark,

        );
        $this->sendResult($return);
    }


    //我的项目列表
    public function myList()
    {
        $login_user = $this->checkLogin();
        $original = I("original/d") ? I("original/d") : 0; //1：只返回自己原创的项目;默认是0 
        $item_group_id = I("item_group_id/d") ? I("item_group_id/d") : 0; //项目分组id。默认是0,即所有项目。当为-1的时候，表示返回标星项目
        $where = "uid = '$login_user[uid]' ";
        $member_item_ids = array(-1); // 所有 只读和编辑成员 的项目
        $manage_member_item_ids = array(-1); // 所有拥有项目管理权限的成员的项目

        $item_members = D("ItemMember")->where("uid = '$login_user[uid]' and  member_group_id != '2' ")->select();
        if ($item_members) {
            foreach ($item_members as $key => $value) {
                $member_item_ids[] = $value['item_id'];
            }
        }
        $team_item_members = D("TeamItemMember")->where("member_uid = '$login_user[uid]' and  member_group_id != '2' ")->select();
        if ($team_item_members) {
            foreach ($team_item_members as $key => $value) {
                $member_item_ids[] = $value['item_id'];
            }
        }

        $item_members = D("ItemMember")->where("uid = '$login_user[uid]' and  member_group_id = '2' ")->select();
        if ($item_members) {
            foreach ($item_members as $key => $value) {
                $manage_member_item_ids[] = $value['item_id'];
            }
        }
        $team_item_members = D("TeamItemMember")->where("member_uid = '$login_user[uid]' and  member_group_id = '2' ")->select();
        if ($team_item_members) {
            foreach ($team_item_members as $key => $value) {
                $manage_member_item_ids[] = $value['item_id'];
            }
        }


        $where .= " or item_id in ( " . implode(",", $member_item_ids) . " ) ";
        $where .= " or item_id in ( " . implode(",", $manage_member_item_ids) . " ) ";
        if ($item_group_id > 0) {
            $res = D("ItemGroup")->where(" id = '$item_group_id' ")->find();
            if ($res) {
                $where = " ({$where}) and item_id in ({$res['item_ids']}) ";
            }
        }

        $star_item_id_array = array();
        // 将star的项目都先读取出来，因为后面有两处需要用到：返回项目是否已经被标星字段，根据标星返回所有标星项目
        $res = D("ItemStar")->where(" uid = '$login_user[uid]' ")->select();

        if ($res) {
            foreach ($res as $key => $value) {
                $star_item_id_array[] = intval($value['item_id']);
            }
        }

        // 当强等于-1的时候。表示筛选出星标项目
        if ($item_group_id === -1) {
            if ($star_item_id_array) {
                $star_item_ids = implode(",", $star_item_id_array);
                $where = " ({$where}) and item_id in ({$star_item_ids}) ";
            } else {
                $where = " ({$where}) and item_id in (0) ";
            }
        }

        $items  = D("Item")->field("item_id,uid,item_name,item_domain,item_type,last_update_time,item_description,is_del,password")->where($where)->order("item_id asc")->select();


        foreach ($items as $key => $value) {
            $items[$key]['s_number'] = 0;
            if ($value['uid'] == $login_user['uid']) {
                $items[$key]['creator'] = 1;
                $items[$key]['manage'] = 1;
            } else if (in_array($value['item_id'], $manage_member_item_ids)) {
                $items[$key]['creator'] = 0;
                $items[$key]['manage'] = 1;
            } else {
                $items[$key]['creator'] = 0;
                $items[$key]['manage'] = 0;
                unset($items[$key]['password']);
            }
            //判断是否为私密项目
            if ($value['password']) {
                $items[$key]['is_private'] = 1;
            } else {
                $items[$key]['is_private'] = 0;
            }

            //如果项目已标识为删除
            if ($value['is_del'] == 1) {
                unset($items[$key]);
                continue;
            }

            //如果有参数指定了只返回原创项目
            if ($original > 0 && $value['uid'] != $login_user['uid']) {
                unset($items[$key]);
                continue;
            }
            // 判断项目是否被标星
            if (in_array(intval($value['item_id']), $star_item_id_array)) {
                $items[$key]['is_star'] = 1;
            } else {
                $items[$key]['is_star'] = 0;
            }
        }
        $items = array_values($items);
        //读取需要置顶的项目
        $top_items = D("ItemTop")->where("uid = '$login_user[uid]'")->select();
        if ($top_items) {
            $top_item_ids = array();
            foreach ($top_items as $key => $value) {
                $top_item_ids[] = $value['item_id'];
            }
            foreach ($items as $key => $value) {
                $items[$key]['top'] = 0;
                if (in_array($value['item_id'], $top_item_ids)) {
                    $items[$key]['top'] = 1;
                    $tmp = $items[$key];
                    unset($items[$key]);
                    array_unshift($items, $tmp);
                }
            }
        }

        //读取项目顺序
        $item_sort = D("ItemSort")->where("uid = '$login_user[uid]'  and item_group_id = '$item_group_id' ")->find();
        if ($item_sort) {
            $item_sort_data = json_decode(htmlspecialchars_decode($item_sort['item_sort_data']), true);
            //var_dump($item_sort_data);
            foreach ($items as $key => &$value) {
                //如果item_id有设置了序号，则赋值序号。没有则默认填上项目id
                if ($item_sort_data[$value['item_id']]) {
                    $value['s_number'] = $item_sort_data[$value['item_id']];
                } else {
                    $value['s_number'] = $value['item_id'];
                }
            }
            $items = $this->_sort_by_key($items, 's_number');
        }


        $items = $items ? array_values($items) : array();
        $this->sendResult($items);
    }

    private function _sort_by_key($array, $mykey)
    {
        for ($i = 0; $i < count($array); $i++) {
            for ($j = $i + 1; $j < count($array); $j++) {
                if ($array[$i][$mykey] > $array[$j][$mykey]) {
                    $tmp = $array[$i];
                    $array[$i] = $array[$j];
                    $array[$j] = $tmp;
                }
            }
        }
        return $array;
    }

    //项目详情
    public function detail()
    {
        $login_user = $this->checkLogin();
        $item_id = I("item_id/d");
        $uid = $login_user['uid'];
        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }
        $items  = D("Item")->where("item_id = '$item_id' ")->find();
        $items = $items ? $items : array();
        $this->sendResult($items);
    }

    //更新项目信息
    public function update()
    {
        $login_user = $this->checkLogin();
        $item_id = I("post.item_id/d");
        $item_name = I("item_name");
        $item_description = I("item_description");
        $item_domain = I("item_domain");
        $password = I("password");
        $uid = $login_user['uid'];
        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10303);
            return;
        }

        if ($item_domain) {

            if (!ctype_alnum($item_domain) ||  is_numeric($item_domain)) {
                //echo '个性域名只能是字母或数字的组合';exit;
                $this->sendError(10305);
                return false;
            }

            $item = D("Item")->where("item_domain = '%s' and item_id !='%s' ", array($item_domain, $item_id))->find();
            if ($item) {
                //个性域名已经存在
                $this->sendError(10304);
                return false;
            }
        }
        $save_data = array(
            "item_name" => $item_name,
            "item_description" => $item_description,
            "item_domain" => $item_domain,
            "password" => $password,
        );
        $items  = D("Item")->where("item_id = '$item_id' ")->save($save_data);
        $items = $items ? $items : array();
        $this->sendResult($items);
    }

    //转让项目
    public function attorn()
    {
        $login_user = $this->checkLogin();

        $username = I("username");
        $item_id = I("post.item_id/d");
        $password = I("post.password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$this->checkItemManage($login_user['uid'], $item['item_id'])) {
            $this->sendError(10303);
            return;
        }

        if (!D("User")->checkLogin($item['username'], $password)) {
            $this->sendError(10208);
            return;
        }

        $member = D("User")->where(" username = '%s' ", array($username))->find();

        if (!$member) {
            $this->sendError(10209);
            return;
        }

        $data['username'] = $member['username'];
        $data['uid'] = $member['uid'];


        $id = D("Item")->where(" item_id = '$item_id' ")->save($data);

        $return = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$return) {
            $this->sendError(10101);
        }

        $this->sendResult($return);
    }

    //删除项目
    public function delete()
    {
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$this->checkItemManage($login_user['uid'], $item['item_id'])) {
            $this->sendError(10303);
            return;
        }

        if (!D("User")->checkLogin($item['username'], $password)) {
            $this->sendError(10208);
            return;
        }


        $return = D("Item")->soft_delete_item($item_id);

        if (!$return) {
            $this->sendError(10101);
        } else {
        }

        $this->sendResult($return);
    }
    //归档项目
    public function archive()
    {
        $login_user = $this->checkLogin();

        $item_id = I("post.item_id/d");
        $password = I("password");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$this->checkItemManage($login_user['uid'], $item['item_id'])) {
            $this->sendError(10303);
            return;
        }

        if (!D("User")->checkLogin($item['username'], $password)) {
            $this->sendError(10208);
            return;
        }

        $return = D("Item")->where("item_id = '$item_id' ")->save(array("is_archived" => 1));

        if (!$return) {
            $this->sendError(10101);
        } else {
            $this->sendResult($return);
        }
    }
    public function getKey()
    {
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$this->checkItemManage($login_user['uid'], $item['item_id'])) {
            $this->sendError(10303);
            return;
        }

        $item_token  = D("ItemToken")->getTokenByItemId($item_id);
        if (!$item_token) {
            $this->sendError(10101);
        }
        $this->sendResult($item_token);
    }

    public function resetKey()
    {

        $login_user = $this->checkLogin();

        $item_id = I("post.item_id/d");

        $item  = D("Item")->where("item_id = '$item_id' ")->find();

        if (!$this->checkItemManage($login_user['uid'], $item['item_id'])) {
            $this->sendError(10303);
            return;
        }

        $item_token = D("ItemToken")->resetToken($item_id);

        if ($item_token) {
            $this->sendResult($item_token);
        } else {
            $this->sendError(10101);
        }
    }

    public function updateByApi()
    {
        //转到Open控制器的updateItem方法
        R('Open/updateItem');
    }

    //置顶项目
    public function top()
    {
        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $action = I("action");

        if ($action == 'top') {
            $ret = D("ItemTop")->add(array("item_id" => $item_id, "uid" => $login_user['uid'], "addtime" => time()));
        } elseif ($action == 'cancel') {
            $ret = D("ItemTop")->where(" uid = '$login_user[uid]' and item_id = '$item_id' ")->delete();
        }
        if ($ret) {
            $this->sendResult(array());
        } else {
            $this->sendError(10101);
        }
    }

    //验证访问密码
    public function pwd()
    {
        $item_id = I("item_id");
        $page_id = I("page_id/d");
        $password = I("password");
        $refer_url = I('refer_url');
        $captcha_id = I("captcha_id");
        $captcha = I("captcha");

        if (!D("Captcha")->check($captcha_id, $captcha)) {
            $this->sendError(10206, L('verification_code_are_incorrect'));
            return;
        }

        if (!is_numeric($item_id)) {
            $item_domain = $item_id;
        }
        //判断个性域名
        if ($item_domain) {
            $item = D("Item")->where("item_domain = '%s'", array($item_domain))->find();
            if ($item['item_id']) {
                $item_id = $item['item_id'];
            }
        }

        if ($page_id > 0) {
            $page = M("Page")->where(" page_id = '$page_id' ")->find();
            if ($page) {
                $item_id = $page['item_id'];
            }
        }
        $item = D("Item")->where("item_id = '$item_id' ")->find();
        if ($password && $item['password'] == $password) {
            session("visit_item_" . $item_id, 1);
            $this->sendResult(array("refer_url" => base64_decode($refer_url)));
        } else {
            $this->sendError(10010, L('access_password_are_incorrect'));
        }
    }


    public function itemList()
    {
        $login_user = $this->checkLogin();
        $items  = D("Item")->where("uid = '$login_user[uid]' ")->select();
        $items = $items ? $items : array();
        $this->sendResult($items);
    }

    //新建项目
    public function add()
    {
        $login_user = $this->checkLogin();
        $item_name = I("post.item_name");
        $item_domain = I("item_domain") ? I("item_domain") : '';
        $copy_item_id = I("copy_item_id");
        $password = I("password");
        $item_description = I("item_description");
        $item_type = I("item_type") ? I("item_type") : 1;
        if (!$item_name) {
            $this->sendError(10100, '项目名不能为空');
            return false;
        }
        if ($item_domain) {

            if (!ctype_alnum($item_domain) ||  is_numeric($item_domain)) {
                //echo '个性域名只能是字母或数字的组合';exit;
                $this->sendError(10305);
                return false;
            }

            $item = D("Item")->where("item_domain = '%s'  ", array($item_domain))->find();
            if ($item) {
                //个性域名已经存在
                $this->sendError(10304);
                return false;
            }
        }

        //如果是复制项目
        if ($copy_item_id > 0) {
            if (!$this->checkItemEdit($login_user['uid'], $copy_item_id)) {
                $this->sendError(10103);
                return;
            }
            $item_id = D("Item")->copy($copy_item_id, $login_user['uid'], $item_name, $item_description, $password, $item_domain);
            if ($item_id) {
                $this->sendResult(array("item_id" => $item_id));
            } else {
                $this->sendError(10101);
            }
            return;
        }

        $insert = array(
            "uid" => $login_user['uid'],
            "username" => $login_user['username'],
            "item_name" => $item_name,
            "password" => $password,
            "item_description" => $item_description,
            "item_domain" => $item_domain,
            "item_type" => $item_type,
            "addtime" => time()
        );
        $item_id = D("Item")->add($insert);

        if ($item_id) {
            //如果是单页应用，则新建一个默认页
            if ($item_type == 2) {
                $insert = array(
                    'author_uid' => $login_user['uid'],
                    'author_username' => $login_user['username'],
                    "page_title" => $item_name,
                    "item_id" => $item_id,
                    "cat_id" => 0,
                    "page_content" => '欢迎使用showdoc。点击右上方的编辑按钮进行编辑吧！',
                    "addtime" => time(),
                    "page_addtime" => time(),
                );
                $page_id = D("Page")->add($insert);
            }
            //如果是表格应用，则新建一个默认页
            if ($item_type == 4) {
                $insert = array(
                    'author_uid' => $login_user['uid'],
                    'author_username' => $login_user['username'],
                    "page_title" => $item_name,
                    "item_id" => $item_id,
                    "cat_id" => 0,
                    "page_content" => '',
                    "addtime" => time(),
                    "page_addtime" => time(),
                );
                $page_id = D("Page")->add($insert);
            }
            $this->sendResult(array("item_id" => $item_id));
        } else {
            $this->sendError(10101);
        }
    }

    //保存项目排序
    public function sort()
    {
        $login_user = $this->checkLogin();

        $data = I("data");
        $item_group_id = I("item_group_id/d");

        $res = D("ItemSort")->where("  uid ='$login_user[uid]' and item_group_id = $item_group_id ")->find();
        if ($res) {
            $ret = D("ItemSort")->where("  uid ='$login_user[uid]' and item_group_id = $item_group_id ")->save(array("item_sort_data" => $data, "addtime" => time()));
        } else {
            $ret = D("ItemSort")->add(array("item_sort_data" => $data, "item_group_id" => $item_group_id, "uid" => $login_user['uid'], "addtime" => time()));
        }


        if ($ret) {
            $this->sendResult(array());
        } else {
            $this->sendError(10101);
        }
    }

    public function exitItem()
    {

        $login_user = $this->checkLogin();

        $item_id = I("item_id/d");
        $ret = D("ItemMember")->where("item_id = '$item_id' and uid ='$login_user[uid]' ")->delete();

        $row = D("TeamItemMember")->join(" left join team on team.id = team_item_member.team_id ")->where("item_id = '$item_id' and member_uid ='$login_user[uid]' ")->find();
        if ($row) {
            $ret = D("TeamItemMember")->where(" member_uid = '$login_user[uid]' and  team_id = '$row[team_id]' ")->delete();
            $ret = D("TeamMember")->where(" member_uid = '$login_user[uid]' and  team_id = '$row[team_id]' ")->delete();
        }


        if ($ret) {
            $this->sendResult(array());
        } else {
            $this->sendError(10101);
        }
    }

    // 在某个项目中根据内容搜索
    public function search()
    {
        $keyword = I("keyword");
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        if (!$this->checkItemVisit($uid, $item_id)) {
            $this->sendError(10303, "没有权限");
            return;
        }
        $item = D("Item")->where("item_id = '%d' and is_del = 0 ", array($item_id))->find();
        $keyword =  \SQLite3::escapeString($keyword);
        $pages = D("Page")->search($item_id, $keyword);
        if ($pages) {
            foreach ($pages as $key => $value) {
                $page_content = htmlspecialchars_decode($value['page_content']);
                $pos = mb_strpos($page_content, $keyword);
                $len = mb_strlen($keyword);
                $start = ($pos - 100) > 0 ? ($pos - 100) : 0;
                $pages[$key]['search_content'] = '...' . mb_substr($page_content, $start, ($len +  200)) . '...';
                unset($pages[$key]['page_content']);
                $pages[$key]['item_id'] = $item['item_id'];
                $pages[$key]['item_name'] = $item['item_name'];
            }
        }
        $return = array(
            "item_id" => $item_id,
            "item_name" => $item['item_name'],
            "pages" => $pages
        );
        $this->sendResult($return);
    }

    //获取项目变更日志
    public function getChangeLog()
    {
        $page = I("page/d") ? I("page/d") : 1;
        $count = I("count/d") ? I("count/d") : 15;
        $item_id = I("post.item_id/d");
        $login_user = $this->checkLogin();

        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->sendError(10103);
            return;
        }

        $list = D("ItemChangeLog")->getLog($item_id, $page, $count);
        $list = $list ? $list : array();
        $this->sendResult($list);
    }

    //标星一个项目
    public function star()
    {
        $item_id = I("post.item_id/d");
        $login_user = $this->checkLogin();

        if (!$this->checkItemVisit($login_user['uid'], $item_id)) {
            $this->sendError(10103);
            return;
        }

        $data = array();
        $data['uid'] = $login_user['uid'];
        $data['item_id'] = $item_id;
        $data['created_at'] = date("Y-m-d H:i:s");
        $data['updated_at'] = date("Y-m-d H:i:s");
        $id = D("ItemStar")->add($data);
        $this->sendResult($id);
    }

    //取消标星一个项目
    public function unstar()
    {
        $item_id = I("post.item_id/d");
        $login_user = $this->checkLogin();
        D("ItemStar")->where(" uid = '$login_user[uid]' and item_id = '$item_id' ")->delete();
        $this->sendResult(array());
    }
}
