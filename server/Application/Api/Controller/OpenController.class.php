<?php

namespace Api\Controller;

use Think\Controller;

class OpenController extends BaseController
{

    /**
     * 根据内容更新页面（创建或更新）
     * 通过api_key和api_token鉴权
     */
    public function updatePage()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $cat_name = I("cat_name") ? I("cat_name") : '';
        $cat_name_sub = I("cat_name_sub");
        $page_title = I("page_title");
        $page_content = I("page_content");
        $s_number = I("s_number") ? I("s_number") : 99;
        //兼容之前的cat_name_sub参数
        if ($cat_name_sub) {
            $cat_name = $cat_name . '/' . $cat_name_sub;
        }

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if (!$res) {
            //没验证通过
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        $pageCount = M("Page")->where(array('item_id' => $item_id))->count();
        if ($pageCount > 5000) {
            $this->sendError(10100, "你创建太多页面啦！如有需求请联系网站管理员");
            return false;
        }
        $page_id = D("Page")->update_by_title($item_id, $page_title, $page_content, $cat_name, $s_number);

        if ($page_id) {
            $ret = D("Page")->where(array('page_id' => $page_id))->find();
            $this->sendResult($ret);
        } else {
            $this->sendError(10101);
        }
    }

    /**
     * 根据内容更新项目（兼容旧接口，转向 updatePage）
     * @deprecated 建议使用 updatePage 接口
     */
    public function updateItem()
    {
        // 兼容旧接口，直接调用新方法
        $this->updatePage();
    }

    //根据shell上报的数据库结构信息生成数据字典
    public function updateDbItem()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $table_info = I("table_info");
        $table_detail = I("table_detail");
        $s_number = I("s_number") ? I("s_number") : 99;
        $cat_name = I("cat_name") ? I("cat_name") : '';
        header('Content-Type:text/html;charset=utf-8 ');
        $cat_name = str_replace(PHP_EOL, '', $cat_name);
        $item_id = D("ItemToken")->check($api_key, $api_token);
        if (!$item_id) {
            //没验证通过
            echo "api_key或者api_token不匹配\n";
            return false;
        }
        $table_info = str_replace("_this_and_change_", "&", $table_info);
        $table_detail = str_replace("_this_and_change_", "&", $table_detail);
        $tables = $this->_analyze_db_structure_to_array($table_info, $table_detail);
        if (!empty($tables)) {
            foreach ($tables as $key => $value) {
                $page_title = $value['table_name'];
                $page_content = $value['markdown'];
                $result = D("Page")->update_by_title($item_id, $page_title, $page_content, $cat_name, $s_number);
            }
        }

        if (!empty($result)) {
            echo "成功\n";
        } else {
            echo "失败\n";
        }

        //$this->_record_log();

    }

    //通过注释生成api文档
    public function fromComments()
    {
        R("FromComments/generate");
    }


    private function _analyze_db_structure_to_array($table_info, $table_detail)
    {
        $tables = array();

        //解析table_info
        $array = explode("\n", $table_info);
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if ($key == 0) {
                    continue;
                }
                $array2 = explode("\t", $value);
                $table_name = str_replace(PHP_EOL, '', $array2[0]);
                $tables[$array2[0]] = array(
                    "table_name" => $table_name,
                    "table_comment" => $array2[1],
                );
            }
        }



        //解析table_detail
        $array = explode("\n", $table_detail);
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                if ($key == 0) {
                    continue;
                }
                $array2 = explode("\t", $value);

                $tables[$array2[0]]['columns'][$array2[1]] = array(
                    "column_name" => $array2[1],
                    "default" => $array2[2],
                    "is_nullable" => $array2[3],
                    "column_type" => $array2[4],
                    "column_comment" => $array2[5] ? $array2[5] : '无',
                );
            }
        }


        //生成markdown内容放在数组里
        if (!empty($tables)) {
            foreach ($tables as $key => $value) {
                $markdown = '';
                $markdown .= "- {$value['table_comment']} \n \n";
                $markdown .= "|字段|类型|允许空|默认|注释| \n ";
                $markdown .= "|:----    |:-------    |:--- |----|------      | \n ";
                foreach ($value['columns'] as $key2 => $value2) {
                    $markdown .= "|{$value2['column_name']} |{$value2['column_type']} |{$value2['is_nullable']} | {$value2['default']} | {$value2['column_comment']}  | \n ";
                }

                $tables[$key]['markdown'] = $markdown;
            }
        }
        return $tables;
    }

    /**
     * 获取页面详情
     * 通过api_key和api_token鉴权
     */
    public function getPage()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $page_id = I("page_id/d");
        $page_title = I("page_title");

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        // 如果提供了 page_title，则通过标题查找页面
        if ($page_title && !$page_id) {
            $page = D("Page")->where(array(
                'item_id' => $item_id,
                'page_title' => $page_title,
                'is_del' => 0
            ))->find();
            if ($page) {
                $page_id = $page['page_id'];
            }
        }

        if (!$page_id) {
            $this->sendError(10101, "page_id或page_title参数必填");
            return;
        }

        // 获取页面详情
        $page = M("Page")->where(array('page_id' => $page_id))->find();
        if (!$page || $page['is_del'] == 1 || $page['item_id'] != $item_id) {
            $this->sendError(10101, "页面不存在或已删除");
            return;
        }

        if ($page) {
            $page['addtime'] = date("Y-m-d H:i:s", $page['addtime']);
            $this->sendResult($page);
        } else {
            $this->sendError(10101, "获取页面失败");
        }
    }

    /**
     * 删除页面
     * 通过api_key和api_token鉴权
     */
    public function deletePage()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $page_id = I("page_id/d");

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        if (!$page_id) {
            $this->sendError(10101, "page_id参数必填");
            return;
        }

        // 检查页面是否属于该项目
        $page = M("Page")->where(array('page_id' => $page_id))->find();
        if (!$page || $page['item_id'] != $item_id) {
            $this->sendError(10101, "页面不存在或不属于该项目");
            return;
        }

        // 获取项目创建者信息，临时设置 session 以复用原有方法
        $item = D("Item")->where(array('item_id' => $item_id))->find();
        $del_by_uid = $item['uid'] ? $item['uid'] : 0;
        $del_by_username = $item['uid'] ? D("User")->where(array('uid' => $item['uid']))->getField('username') : 'API';

        // 临时设置 session，以便复用 softDeletePage 方法
        $old_login_user = session('login_user');
        session('login_user', array('uid' => $del_by_uid, 'username' => $del_by_username));

        // 复用原有方法
        $ret = D("Page")->softDeletePage($page_id);

        // 恢复原有 session
        if ($old_login_user) {
            session('login_user', $old_login_user);
        } else {
            session('login_user', null);
        }

        if ($ret !== false) {
            $this->sendResult(array("page_id" => $page_id));
        } else {
            $this->sendError(10101, "删除页面失败");
        }
    }

    /**
     * 获取项目的目录树结构（包含页面标题）
     * 通过api_key和api_token鉴权
     */
    public function getCatalogTree()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        // 复用 ItemModel 的 getContent 方法获取完整的目录树结构
        // page_field: 只获取必要的字段，不获取 page_content 节省资源
        $page_field = "page_id, page_title, cat_id, s_number, author_username, addtime";
        $catalog_field = "*";
        $menu = D("Item")->getContent($item_id, $page_field, $catalog_field);

        $this->sendResult($menu);
    }

    /**
     * 创建目录
     * 通过api_key和api_token鉴权
     */
    public function createCatalog()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $cat_name = I("cat_name");
        $parent_cat_id = I("parent_cat_id/d") ? I("parent_cat_id/d") : 0;
        $s_number = I("s_number/d") ? I("s_number/d") : 99;

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        if (!$cat_name) {
            $this->sendError(10101, "cat_name参数必填");
            return;
        }

        // 确定目录层级
        $level = 2;
        if ($parent_cat_id > 0) {
            $parent_cat = D("Catalog")->where(array('cat_id' => $parent_cat_id, 'item_id' => $item_id))->find();
            if (!$parent_cat) {
                $this->sendError(10101, "父目录不存在");
                return;
            }
            $level = $parent_cat['level'] + 1;
        }

        // 创建目录
        $catalog_data = array(
            "cat_name" => htmlspecialchars($cat_name),
            "level" => $level,
            "s_number" => $s_number,
            "item_id" => $item_id,
            "parent_cat_id" => $parent_cat_id,
            "addtime" => time(),
        );
        $cat_id = D("Catalog")->add($catalog_data);

        if ($cat_id) {
            $catalog_data['cat_id'] = $cat_id;
            $this->sendResult($catalog_data);
        } else {
            $this->sendError(10101, "创建目录失败");
        }
    }

    /**
     * 修改目录
     * 通过api_key和api_token鉴权
     */
    public function updateCatalog()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $cat_id = I("cat_id/d");
        $cat_name = I("cat_name");
        $s_number = I("s_number/d");

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        if (!$cat_id) {
            $this->sendError(10101, "cat_id参数必填");
            return;
        }

        // 检查目录是否属于该项目
        $catalog = D("Catalog")->where(array('cat_id' => $cat_id))->find();
        if (!$catalog || $catalog['item_id'] != $item_id) {
            $this->sendError(10101, "目录不存在或不属于该项目");
            return;
        }

        // 准备更新数据
        $update_data = array();
        if ($cat_name) {
            $update_data['cat_name'] = htmlspecialchars($cat_name);
        }
        if ($s_number !== null && $s_number !== '') {
            $update_data['s_number'] = $s_number;
        }

        if (empty($update_data)) {
            $this->sendError(10101, "请至少提供cat_name或s_number参数");
            return;
        }

        // 更新目录
        $ret = D("Catalog")->where(array('cat_id' => $cat_id))->save($update_data);
        if ($ret !== false) {
            $catalog = D("Catalog")->where(array('cat_id' => $cat_id))->find();
            $this->sendResult($catalog);
        } else {
            $this->sendError(10101, "更新目录失败");
        }
    }

    /**
     * 删除目录
     * 通过api_key和api_token鉴权
     */
    public function deleteCatalog()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $cat_id = I("cat_id/d");

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        if (!$cat_id) {
            $this->sendError(10101, "cat_id参数必填");
            return;
        }

        // 检查目录是否属于该项目
        $catalog = D("Catalog")->where(array('cat_id' => $cat_id))->find();
        if (!$catalog || $catalog['item_id'] != $item_id) {
            $this->sendError(10101, "目录不存在或不属于该项目");
            return;
        }

        // 获取项目创建者信息，临时设置 session 以复用原有方法
        $item = D("Item")->where(array('item_id' => $item_id))->find();
        $del_by_uid = $item['uid'] ? $item['uid'] : 0;
        $del_by_username = $item['uid'] ? D("User")->where(array('uid' => $item['uid']))->getField('username') : 'API';

        // 临时设置 session，以便复用 deleteCat 方法
        $old_login_user = session('login_user');
        session('login_user', array('uid' => $del_by_uid, 'username' => $del_by_username));

        // 复用原有方法
        $ret = D("Catalog")->deleteCat($cat_id);

        // 恢复原有 session
        if ($old_login_user) {
            session('login_user', $old_login_user);
        } else {
            session('login_user', null);
        }

        if ($ret) {
            $this->sendResult(array("cat_id" => $cat_id));
        } else {
            $this->sendError(10101, "删除目录失败");
        }
    }

    /**
     * 上传附件
     * 通过api_key和api_token鉴权
     */
    public function uploadAttachment()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $page_id = I("page_id/d") ? I("page_id/d") : 0;

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        // 获取项目所有者
        $item = D("Item")->where(array('item_id' => $item_id))->find();
        if (!$item) {
            $this->sendError(10101, "项目不存在");
            return;
        }
        $uid = $item['uid'];

        // 检查文件是否上传
        if (empty($_FILES['file'])) {
            $this->sendError(10101, "请上传文件");
            return;
        }

        $uploadFile = $_FILES['file'];

        // 检查文件扩展名
        if (!D("Attachment")->isAllowedFilename($uploadFile['name'])) {
            $this->sendError(10101, '不支持上传该文件类型');
            return;
        }

        // 上传文件
        $url = D("Attachment")->upload($_FILES, 'file', $uid, $item_id, $page_id, true);
        if ($url) {
            // 从 URL 中提取 sign 参数，用于后续删除
            $parsed_url = parse_url($url);
            $sign = '';
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $params);
                if (isset($params['sign'])) {
                    $sign = $params['sign'];
                }
            }

            // 通过 sign 查找 file_id
            $file_id = 0;
            if ($sign) {
                $file = D("UploadFile")->where(array('sign' => $sign))->find();
                if ($file) {
                    $file_id = $file['file_id'];
                }
            }

            $result = array("url" => $url);
            if ($file_id) {
                $result["file_id"] = $file_id;
            }
            if ($sign) {
                $result["sign"] = $sign;
            }
            $this->sendResult($result);
        } else {
            $this->sendError(10101, "上传失败");
        }
    }

    /**
     * 删除附件
     * 通过api_key和api_token鉴权
     */
    public function deleteAttachment()
    {
        $api_key = I("api_key");
        $api_token = I("api_token");
        $file_id = I("file_id/d");
        $file_url = I("file_url");
        $sign = I("sign");

        // 鉴权
        $res = D("ItemToken")->check($api_key, $api_token);
        if ($res === -2) {
            $this->sendError(10306, "api_key或者api_token不匹配");
            return;
        }
        $item_id = $res;

        // 通过 file_id, file_url 或 sign 查找文件
        $file = null;
        if ($file_id) {
            $file = D("UploadFile")->where(array('file_id' => $file_id))->find();
        } elseif ($sign) {
            $file = D("UploadFile")->where(array('sign' => $sign))->find();
            if ($file) {
                $file_id = $file['file_id'];
            }
        } elseif ($file_url) {
            // 从 URL 中提取 sign 参数
            $parsed_url = parse_url($file_url);
            if (isset($parsed_url['query'])) {
                parse_str($parsed_url['query'], $params);
                if (isset($params['sign'])) {
                    $sign = $params['sign'];
                    $file = D("UploadFile")->where(array('sign' => $sign))->find();
                    if ($file) {
                        $file_id = $file['file_id'];
                    }
                }
            }
            // 如果没有 sign，尝试从 URL 路径中提取（兼容直接的文件路径）
            if (!$file && isset($parsed_url['path'])) {
                $path = $parsed_url['path'];
                // 检查是否是 visitFile URL，提取 sign
                if (strpos($path, 'visitFile') !== false && isset($params['sign'])) {
                    $file = D("UploadFile")->where(array('sign' => $params['sign']))->find();
                    if ($file) {
                        $file_id = $file['file_id'];
                    }
                }
            }
        }

        if (!$file_id || !$file) {
            $this->sendError(10101, "请提供 file_id、file_url 或 sign 参数，且文件必须存在");
            return;
        }

        // 检查文件是否关联到该项目
        // 开源版可能没有 FilePage 表，检查 UploadFile.item_id
        $file_page = null;
        try {
            $file_page = D("FilePage")->where(array('file_id' => $file_id, 'item_id' => $item_id))->find();
        } catch (\Exception $e) {
            // FilePage 表不存在，跳过
        }
        // 如果 FilePage 表中没有记录，则检查 UploadFile.item_id
        if (!$file_page && isset($file['item_id']) && $file['item_id'] != $item_id) {
            $this->sendError(10101, "文件不属于该项目");
            return;
        }

        // 删除文件
        $ret = D("Attachment")->deleteFile($file_id);
        if ($ret) {
            $this->sendResult(array("file_id" => $file_id));
        } else {
            $this->sendError(10101, "删除失败");
        }
    }
}
