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
        $page = D("Page")->where(array('page_id' => $page_id))->find();
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
            $page['attachment_count'] = D("FilePage")->where(array('page_id' => $page_id))->count();

            $singlePage = M("SinglePage")->where(" page_id = '%d' ", array($page_id))->limit(1)->find();
            if ($singlePage) {
                // 检查单页链接是否已过期
                if ($singlePage['expire_time'] > 0 && $singlePage['expire_time'] < time()) {
                    // 链接已过期，从数据库中删除记录
                    M("SinglePage")->where(array('page_id' => $page_id))->delete();
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
        $page = D("Page")->where(array('page_id' => $page_id))->find();

        $login_user = $this->checkLogin();
        if (!$this->checkItemManage($login_user['uid'], $page['item_id']) && $login_user['uid'] != $page['author_uid']) {
            $this->sendError(10303);
            return;
        }

        if ($page) {

            $ret = D("Page")->softDeletePage($page_id);
            //更新项目时间
            D("Item")->where(array('item_id' => $page['item_id']))->save(array("last_update_time" => time()));
            
            // 删除页面评论和反馈
            D("PageComment")->where("page_id = %d", array($page_id))->delete();
            D("PageFeedback")->where("page_id = %d", array($page_id))->delete();
        }
        if ($ret) {
            D("ItemChangeLog")->addLog($login_user['uid'],  $page['item_id'], 'delete', 'page', $page['page_id'], $page['page_title']);

            // 先发送响应
            $this->sendResult(array());

            // 响应发送后，触发 AI 索引删除（异步，不阻塞删除流程）
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            $this->triggerAiIndex($page['item_id'], $page['page_id'], 'delete');
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

        $item_array = D("Item")->where(array('item_id' => $item_id))->find();
        
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
            $page = D("Page")->where(array('page_id' => $page_id))->find();
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
            $ret = D("Page")->where(array('page_id' => $page_id))->save($data);

            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'update', 'page', $page_id, $page_title);

            //统计该page_id有多少历史版本了
            $Count = D("PageHistory")->where(array('page_id' => $page_id))->Count();
            if ($Count > $history_version_count) {
                //每个单页面只保留最多$history_version_count个历史版本
                $ret = D("PageHistory")->where(array('page_id' => $page_id))->limit($history_version_count)->order("page_history_id desc")->select();
                D("PageHistory")->where(" page_id = '%d' and page_history_id < %d ", array($page_id, $ret[$history_version_count - 1]['page_history_id']))->delete();
            }

            //如果是单页项目，则将页面标题设置为项目名
            $item_array = D("Item")->where(array('item_id' => $item_id))->find();
            if ($item_array['item_type'] == 2) {
                D("Item")->where(array('item_id' => $item_id))->save(array("last_update_time" => time(), "item_name" => $page_title));
            } else {
                D("Item")->where(array('item_id' => $item_id))->save(array("last_update_time" => time()));
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

            $return = D("Page")->where(array('page_id' => $page_id))->find();
        } else {

            $page_id = D("Page")->add($data);

            D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'create', 'page', $page_id, $page_title);

            //更新项目时间
            D("Item")->where(array('item_id' => $item_id))->save(array("last_update_time" => time()));

            // 添加页面的时候把最初的创建者加入消息订阅
            D("Subscription")->addSub($login_user['uid'], $page_id, 'page', 'update');

            $return = D("Page")->where(array('page_id' => $page_id))->find();
        }
        if (!$return) {
            $return['error_code'] = 10103;
            $return['error_message'] = 'request  fail';
        }

        // 先发送响应，确保用户能收到结果
        $this->sendResult($return);

        // 响应发送后，触发 AI 索引更新（异步，不阻塞保存流程）
        // 使用 fastcgi_finish_request 确保在响应发送后执行（如果支持）
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // 触发索引更新（此时响应已发送，不会阻塞用户）
        $this->triggerAiIndex($item_id, $page_id, $page_id > 0 ? 'update' : 'create');
    }


    //历史版本列表
    public function history()
    {
        $login_user = $this->checkLogin(false);
        $page_id = I("page_id/d") ? I("page_id/d") : 0;
        $page = M("Page")->where(array('page_id' => $page_id))->find();
        if (!$this->checkItemVisit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }

        $PageHistory = D("PageHistory")->where(array('page_id' => $page_id))->order(" addtime desc")->limit(20)->select();

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
        $page = M("Page")->where(array('page_id' => $page_id))->find();
        if (!$this->checkItemEdit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $res = D("PageHistory")->where(array('page_history_id' => $page_history_id))->save(array(
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
        $page = M("Page")->where(array('page_id' => $page_id))->find();
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

        $history_page = D("PageHistory")->where(array('page_history_id' => $page_history_id))->find();
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
        $page = M("Page")->where(array('page_id' => $page_id))->find();
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
        D("SinglePage")->where(array('page_id' => $page_id))->delete();
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
        $singlePage = M("SinglePage")->where(array('unique_key' => $unique_key))->find();
        $page_id = $singlePage['page_id'];

        // 检查链接是否已过期
        if ($singlePage && $singlePage['expire_time'] > 0 && $singlePage['expire_time'] < time()) {
            // 链接已过期，从数据库中删除记录
            M("SinglePage")->where(array('unique_key' => $unique_key))->delete();
            $this->sendError(10101, "该分享链接已过期");
            return false;
        }

        $page = M("Page")->where(array('page_id' => $page_id))->find();
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
            $page['attachment_count'] = D("FilePage")->where(array('page_id' => $page_id))->count();
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
        $res = D("PageLock")->where(" page_id = '%d' and page_id > 0 and lock_to > '%d' ", array($page_id, $now))->find();
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
        D("PageLock")->where(array('page_id' => $page_id))->delete();
        $id = D("PageLock")->add(array(
            "page_id" => $page_id,
            "lock_uid" => $login_user['uid'],
            "lock_username" => $login_user['username'],
            "lock_to" => $lock_to,
            "addtime" => time(),
        ));
        $now = time();
        D("PageLock")->where(array('lock_to' => array('lt', $now)))->delete();
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

    /**
     * 触发 AI 索引更新（异步）
     */
    private function triggerAiIndex($item_id, $page_id, $action = 'update')
    {
        try {
            // 检查 AI 服务是否配置（系统级）
            $ai_service_url = D("Options")->get("ai_service_url");
            $ai_service_token = D("Options")->get("ai_service_token");

            if (!$ai_service_url || !$ai_service_token) {
                return; // AI 服务未配置，不触发索引
            }

            // 检查项目级开关
            // 检查项目是否存在且启用了 AI 知识库功能（使用新表）
            $config = D("ItemAiConfig")->getConfig($item_id);
            if (empty($config['enabled'])) {
                return; // 项目未启用 AI 知识库功能，不触发索引
            }

            // 异步触发索引更新（使用简单的 HTTP 请求，不等待响应）
            $url = rtrim($ai_service_url, '/') . '/api/index/upsert';

            // 获取页面信息（开源版没有分表，直接使用 D("Page")->where 查询）
            $page = D("Page")->where(array('page_id' => $page_id))->find();
            if (!$page || $page['is_del'] == 1) {
                // 如果是删除操作，直接调用删除接口
                if ($action == 'delete') {
                    $this->callAiServiceAsync($url, array(
                        'item_id' => $item_id,
                        'page_id' => $page_id
                    ), 'DELETE');
                }
                return;
            }

            $content = $page['page_content'];
            $pageType = isset($page['page_type']) ? $page['page_type'] : 'regular';

            // HTML 反转义（因为存储的内容是 HTML 转义的）
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // 使用 Convert 类转换为 Markdown（如果是 API 文档会自动转换，否则返回 false）
            $convert = new Convert();
            $md_content = $convert->runapiToMd($content);
            if ($md_content !== false) {
                $content = $md_content;
            }

            // 获取目录名称
            $cat_name = '';
            if ($page['cat_id'] > 0) {
                $catalog = D("Catalog")->where(array('cat_id' => $page['cat_id'], 'item_id' => $item_id))->find();
                if ($catalog) {
                    $cat_name = $catalog['cat_name'];
                }
            }

            $postData = array(
                'item_id' => $item_id,
                'page_id' => $page_id,
                'page_title' => $page['page_title'],
                'page_content' => $content,
                'page_type' => $pageType,
                'cat_name' => $cat_name,
                'update_time' => isset($page['update_time']) ? $page['update_time'] : time()
            );

            $this->callAiServiceAsync($url, $postData);
        } catch (\Exception $e) {
            // 记录错误日志，但不影响主流程
            \Think\Log::record("AI索引触发失败: " . $e->getMessage());
        }
    }

    /**
     * 异步调用 AI 服务（不等待响应）
     */
    private function callAiServiceAsync($url, $postData = null, $method = 'POST')
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 短超时，不阻塞
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // 避免信号量问题

            $ai_service_token = D("Options")->get("ai_service_token");
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $ai_service_token
            );

            if ($method == 'POST' && $postData) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            } elseif ($method == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($postData) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                }
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            // 不等待响应，立即返回
            curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            // 如果有错误，记录日志但不抛出异常
            if ($error) {
                \Think\Log::record("AI服务异步调用失败: " . $error);
            }
        } catch (\Exception $e) {
            // 记录错误日志，但不影响主流程
            \Think\Log::record("AI服务异步调用异常: " . $e->getMessage());
        }
    }
}
