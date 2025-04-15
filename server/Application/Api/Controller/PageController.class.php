<?php

namespace Api\Controller;

use Think\Controller;
use Api\Helper\Convert;

class PageController extends BaseController
{

    //页面详情
    public function info()
    {
        $page_id = I("page_id/d");
        $with_path = I("with_path/d"); // 是否需要返回完整的路径信息
        $page = D("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page  || $page['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $page = $page ? $page : array();
        if ($page) {
            //unset($page['page_content']);
            $page['addtime'] = date("Y-m-d H:i:s", $page['addtime']);
            if ($page['page_addtime'] > 0) {
                $page['page_addtime'] = date("Y-m-d H:i:s", $page['page_addtime']);
            } else {
                $page['page_addtime'] = $page['addtime'];
            }
            //判断是否包含附件信息
            $page['attachment_count'] = D("FilePage")->where("page_id = '$page_id' ")->count();

            $singlePage = M("SinglePage")->where(" page_id = '%d' ", array($page_id))->limit(1)->find();
            if ($singlePage) {
                // 检查单页链接是否已过期
                if ($singlePage['expire_time'] > 0 && $singlePage['expire_time'] < time()) {
                    // 链接已过期，从数据库中删除记录
                    M("SinglePage")->where(" page_id = '%d' ", array($page_id))->delete();
                    $page['unique_key'] = '';
                } else {
                    $page['unique_key'] = $singlePage['unique_key'];
                }
            } else {
                $page['unique_key'] = '';
            }

            // 如果请求了完整路径信息，获取该页面的所有上级目录
            if ($with_path && $page['cat_id']) {
                $full_path = $this->getFullPath($page['cat_id'], $page['item_id']);
                // 添加当前页面作为路径的最后一个元素
                $full_path[] = array(
                    'page_id' => $page['page_id'],
                    'page_title' => $page['page_title']
                );
                $page['full_path'] = $full_path;
            }
        }
        $this->sendResult($page);
    }

    /**
     * 获取目录的完整路径
     * @param int $cat_id 当前目录ID
     * @param int $item_id 项目ID
     * @return array 完整路径数组
     */
    private function getFullPath($cat_id, $item_id)
    {
        if (!$cat_id || !$item_id) {
            return array();
        }

        // 获取项目的目录结构
        $item = D("Item")->where("item_id = '%d'", array($item_id))->find();
        if (!$item) {
            return array();
        }

        // 递归查找目录路径
        $path = array();
        $this->findCatPath($cat_id, $item_id, $path);

        // 返回路径（从上到下排序）
        return array_reverse($path);
    }

    /**
     * 递归查找目录路径
     * @param int $cat_id 当前目录ID
     * @param int $item_id 项目ID
     * @param array &$path 路径数组（引用传递）
     * @return boolean 是否找到路径
     */
    private function findCatPath($cat_id, $item_id, &$path)
    {
        // 查找当前目录信息
        $catalog = D("Catalog")->where("cat_id = '%d' AND item_id = '%d'", array($cat_id, $item_id))->find();
        if (!$catalog) {
            return false;
        }

        // 添加当前目录到路径
        $path[] = array(
            'cat_id' => $catalog['cat_id'],
            'cat_name' => $catalog['cat_name']
        );

        // 如果有父目录，继续递归查找
        if ($catalog['parent_cat_id'] > 0) {
            return $this->findCatPath($catalog['parent_cat_id'], $item_id, $path);
        }

        return true;
    }

    //删除页面
    public function delete()
    {
        $page_id = I("post.page_id/d") ? I("post.page_id/d") : 0;
        $page = D("Page")->where(" page_id = '$page_id' ")->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemManage($login_user['uid'], $page['item_id']) && $login_user['uid'] != $page['author_uid']) {
            $this->sendError(10303);
            return;
        }

        if ($page) {

            $ret = D("Page")->softDeletePage($page_id);
            //更新项目时间
            D("Item")->where(" item_id = '$page[item_id]' ")->save(array("last_update_time" => time()));
        }
        if ($ret) {
            D("ItemChangeLog")->addLog($login_user['uid'],  $page['item_id'], 'delete', 'page', $page['page_id'], $page['page_title']);
            $this->sendResult(array());
        } else {
            $this->sendError(10101);
        }
    }

    //保存
    public function save()
    {
        $login_user = $this->checkLogin();
        $page_id = I("page_id/d") ? I("page_id/d") : 0;
        $is_urlencode = I("is_urlencode/d") ? I("is_urlencode/d") : 0; //页面内容是否经过了转义
        $page_title = I("page_title") ? I("page_title") : L("default_title");
        $page_comments = I("page_comments") ? I("page_comments") : '';
        $page_content = I("post.page_content", "", ""); // 不进行htmlspecialchars过滤，后面再手工过滤
        $cat_id = I("cat_id/d") ? I("cat_id/d") : 0;
        $item_id = I("item_id/d") ? I("item_id/d") : 0;
        $s_number = I("s_number/d") ? I("s_number/d") : '';
        $is_notify = I("is_notify/d") ? I("is_notify/d") : 0;
        $notify_content = I("notify_content") ? I("notify_content") : '';
        $ext_info = I("ext_info") ? I("ext_info") : '';


        $login_user = $this->checkLogin();

        if (!$page_content) {
            $this->sendError(10103, "不允许保存空内容，请随便写点什么");
            return;
        }
        if ($is_urlencode) {
            $page_content = urldecode($page_content);
        }
        // htmlspecialchars过滤
        $page_content = htmlspecialchars($page_content);

        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->sendError(10103);
            return;
        }
        $data = array();

        $data['page_title'] = $page_title;
        $data['page_content'] = $page_content;
        $data['page_comments'] = $page_comments;
        if ($s_number) $data['s_number'] = $s_number;
        $data['item_id'] = $item_id;
        $data['cat_id'] = $cat_id;
        $data['addtime'] = time();
        $data['page_addtime'] = time();
        $data['author_uid'] = $login_user['uid'];
        $data['author_username'] = $login_user['username'];
        $data['ext_info'] = $ext_info;

        $item_array = D("Item")->where(" item_id = '$item_id' ")->find();
        
        // 这里插入一段逻辑，对于runapi项目类型，填充ext_info字段
        if(!$data['ext_info'] && $item_array['item_type'] == 3){
            $content_json = htmlspecialchars_decode($page_content);
            $content = json_decode($content_json, true);
            if ($content && $content['info'] && $content['info']['url']) {
                $ext_info_array = array(
                    "page_type"=>"api",
                    "api_info"=>array(
                        "method"=>"post",
                    )
                    );
                $data['ext_info'] = json_encode($ext_info_array);
            }
        }

        if ($page_id > 0) {

            // 设置里的历史版本数量
            $history_version_count = D("Options")->get("history_version_count");
            if (!$history_version_count) {
                $history_version_count = 20;
                D("Options")->set("history_version_count", $history_version_count);
            }

            //在保存前先把当前页面的版本存档
            $page = D("Page")->where(" page_id = '$page_id' ")->find();
            if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
                $this->sendError(10103);
                return;
            }
            $insert_history = array(
                'page_id' => $page['page_id'],
                'item_id' => $page['item_id'],
                'cat_id' => $page['cat_id'],
                'page_title' => $page['page_title'],
                'page_comments' => $page['page_comments'],
                'page_content' => base64_encode(gzcompress($page['page_content'], 9)),
                's_number' => $page['s_number'],
                'addtime' => $page['addtime'],
                'author_uid' => $page['author_uid'],
                'author_username' => $page['author_username'],
                'ext_info' => $page['ext_info'],
            );
            D("PageHistory")->add($insert_history);

            if ($page['page_addtime'] > 0) {
                $data['page_addtime'] = $page['page_addtime'];
            }
            $ret = D("Page")->where(" page_id = '$page_id' ")->save($data);

            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'update', 'page', $page_id, $page_title);

            //统计该page_id有多少历史版本了
            $Count = D("PageHistory")->where(" page_id = '$page_id' ")->Count();
            if ($Count > $history_version_count) {
                //每个单页面只保留最多$history_version_count个历史版本
                $ret = D("PageHistory")->where(" page_id = '$page_id' ")->limit($history_version_count)->order("page_history_id desc")->select();
                D("PageHistory")->where(" page_id = '$page_id' and page_history_id < " . $ret[$history_version_count - 1]['page_history_id'])->delete();
            }

            //如果是单页项目，则将页面标题设置为项目名
            $item_array = D("Item")->where(" item_id = '$item_id' ")->find();
            if ($item_array['item_type'] == 2) {
                D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time" => time(), "item_name" => $page_title));
            } else {
                D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time" => time()));
            }

            if ($is_notify) {
                // 检测订阅事件，根据订阅情况，将页面的更新消息发给通知用户
                $subscription_array = D("Subscription")->getListByObjectId($page_id, 'page', 'update');
                if ($subscription_array) {
                    foreach ($subscription_array as $skey => $svalue) {
                        D("Message")->addMsg($login_user['uid'], $login_user['username'], $svalue['uid'], 'remind', $notify_content, 'update', 'page', $page_id);
                    }
                }
            }

            $return = D("Page")->where(" page_id = '$page_id' ")->find();
        } else {

            $page_id = D("Page")->add($data);

            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'create', 'page', $page_id, $page_title);

            //更新项目时间
            D("Item")->where(" item_id = '$item_id' ")->save(array("last_update_time" => time()));

            // 添加页面的时候把最初的创建者加入消息订阅
            D("Subscription")->addSub($login_user['uid'], $page_id, 'page', 'update');

            $return = D("Page")->where(" page_id = '$page_id' ")->find();
        }
        if (!$return) {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
        }
        $this->sendResult($return);
    }


    //历史版本列表
    public function history()
    {
        $login_user = $this->checkLogin(false);
        $page_id = I("page_id/d") ? I("page_id/d") : 0;
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$this->checkItemVisit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $PageHistory = D("PageHistory")->where("page_id = '$page_id' ")->order(" addtime desc")->limit(20)->select();

        if ($PageHistory) {
            foreach ($PageHistory as $key => &$value) {
                $value['addtime'] = date("Y-m-d H:i:s", $value['addtime']);
                $page_content = uncompress_string($value['page_content']);
                if (!empty($page_content)) {
                    $value['page_content'] = htmlspecialchars_decode($page_content);
                    $value['page_content'] = htmlspecialchars($value['page_content'], ENT_NOQUOTES); // 不编码任何引号,以兼容json.同时转义其他字符串，以免xss
                }
            }

            $this->sendResult($PageHistory);
        } else {
            $this->sendResult(array());
        }
    }


    // 更新历史备注信息
    public function updateHistoryComments()
    {
        $login_user = $this->checkLogin(false);
        $page_id = I("page_id/d") ? I("page_id/d") : 0;
        $page_comments = I("page_comments");
        $page_history_id = I("page_history_id/d") ? I("page_history_id/d") : 0;
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $res = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->save(array(
            "page_comments" => $page_comments
        ));
        $this->sendResult($res);
    }


    //返回当前页面和历史某个版本的页面以供比较
    public function diff()
    {
        $page_id = I("page_id/d");
        $page_history_id = I("page_history_id/d");
        if (!$page_id) {
            return false;
        }
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemVisit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $history_page = D("PageHistory")->where(" page_history_id = '$page_history_id' ")->find();
        $page_content = uncompress_string($history_page['page_content']);
        $history_page['page_content'] = $page_content ? $page_content : $history_page['page_content'];

        $this->sendResult(array("page" => $page, "history_page" => $history_page));
    }


    //上传图片
    public function uploadImg()
    {
        //重定向控制器和方法
        R("Attachment/uploadImg");
    }

    //上传附件
    public function upload()
    {
        //重定向控制器和方法
        R("Attachment/attachmentUpload");
    }

    public function uploadList()
    {
        //重定向控制器和方法
        R("Attachment/pageAttachmentUploadList");
    }

    //删除已上传文件
    public function deleteUploadFile()
    {
        //重定向控制器和方法
        R("Attachment/deletePageUploadFile");
    }


    //创建单页
    public function createSinglePage()
    {
        $page_id = I("page_id/d");
        $isCreateSiglePage = I("isCreateSiglePage");
        $expire_days = I("expire_days/d", 0); // 获取有效期天数，默认为0表示永久有效
        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page || $page['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        D("SinglePage")->where(" page_id = '$page_id' ")->delete();
        $unique_key = md5(time() . rand() . "gbgdhbdgtfgfK3@bv45342regdhbdgtfgftghsdg");

        // 计算过期时间
        $expire_time = 0; // 默认为0表示永久有效
        if ($expire_days > 0) {
            $expire_time = time() + ($expire_days * 24 * 60 * 60); // 当前时间加上天数换算成的秒数
        }

        $add = array(
            "unique_key" => $unique_key,
            "page_id" => $page_id,
            "expire_time" => $expire_time
        );
        if ($isCreateSiglePage == 'true') { //这里的布尔值被转成字符串了
            D("SinglePage")->add($add);
            $this->sendResult($add);
        } else {
            $this->sendResult(array());
        }
    }

    //页面详情
    public function infoByKey()
    {
        $unique_key = I("unique_key");
        if (!$unique_key) {
            return false;
        }
        $singlePage = M("SinglePage")->where(" unique_key = '%s' ", array($unique_key))->find();
        $page_id = $singlePage['page_id'];

        // 检查链接是否已过期
        if ($singlePage && $singlePage['expire_time'] > 0 && $singlePage['expire_time'] < time()) {
            // 链接已过期，从数据库中删除记录
            M("SinglePage")->where(" unique_key = '%s' ", array($unique_key))->delete();
            $this->sendError(10101, "该分享链接已过期");
            return false;
        }

        $page = M("Page")->where(" page_id = '$page_id' ")->find();
        if (!$page || $page['is_del'] == 1) {
            sleep(1);
            $this->sendError(10101);
            return false;
        }
        $login_user = $this->checkLogin(false);
        $page = $page ? $page : array();
        if ($page) {
            unset($page['item_id']);
            unset($page['cat_id']);
            $page['addtime'] = date("Y-m-d H:i:s", $page['addtime']);
            //判断是否包含附件信息
            $page['attachment_count'] = D("FilePage")->where("page_id = '$page_id' ")->count();
            // 添加单页链接过期时间字段
            if ($singlePage) {
                $page['expire_time'] = $singlePage['expire_time'];
            }
        }
        $this->sendResult($page);
    }

    //同一个目录下的页面排序
    public function sort()
    {
        $pages = I("pages");
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->sendError(10103);
            return;
        }
        $ret = '';
        $data_array = json_decode(htmlspecialchars_decode($pages), true);
        if ($data_array) {
            foreach ($data_array as $key => $value) {
                $ret = D("Page")->where(" page_id = '%d' and item_id = '%d' ", array($key, $item_id))->save(array(
                    "s_number" => $value,
                ));
            }
        }

        $this->sendResult(array());
    }


    //判断页面是否加了编辑锁
    public function  isLock()
    {
        $page_id = I("page_id/d");
        $lock = 0;
        $now = time();
        $login_user = $this->checkLogin(false);
        $res = D("PageLock")->where(" page_id = '$page_id' and page_id > 0 and lock_to > '{$now}' ")->find();
        if ($res) {
            $lock = 1;
        }
        $this->sendResult(array(
            "lock" => $lock,
            "lock_uid" => $res['lock_uid'] ?  $res['lock_uid'] : '',
            "lock_username" => $res['lock_username'] ? $res['lock_username'] : '',
            "is_cur_user" => $res['lock_uid'] == $login_user['uid'] ? 1 : 0,
        ));
    }

    //设置页面加锁时间
    public function setLock()
    {
        $page_id = I("page_id/d");
        $lock_to = I("lock_to/d") ? I("lock_to/d") : (time() + 5 * 60 * 60);
        $item_id = I("item_id/d");
        $login_user = $this->checkLogin();
        if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
            $this->sendError(10103);
            return;
        }
        D("PageLock")->where("page_id = '{$page_id}' ")->delete();
        $id = D("PageLock")->add(array(
            "page_id" => $page_id,
            "lock_uid" => $login_user['uid'],
            "lock_username" => $login_user['username'],
            "lock_to" => $lock_to,
            "addtime" => time(),
        ));
        $now = time();
        D("PageLock")->where("lock_to < '{$now}' ")->delete();
        $this->sendResult(array("id" => $id));
    }

    // 转换 SQL 为 Markdown 表格
    public function sqlToMarkdownTable()
    {
        $sql = I("sql","","");
        $object = new Convert();
        $res = $object->convertSqlToMarkdownTable($sql);
        $this->sendResult(array("markdown" => $res));
    }
}
