<?php

namespace Api\Controller;

use Think\Controller;

class MockController extends BaseController
{

    //添加
    public function add()
    {
        $page_id = I("page_id/d");
        $template = I("template");
        $path = I("path") ? I("path") : '/';
        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];
        $page = M("Page")->where(array('page_id' => $page_id))->find();
        if (!$this->checkItemEdit($uid, $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        $item_id = $page['item_id'];
        $tpl_decoded = htmlspecialchars_decode($template);
        $json = json_decode($tpl_decoded);
        if (!$json) {
            $this->sendError(10101, '为了服务器安全，只允许写符合json语法的字符串');
            return;
        }
        // mockjs 占位符参数存在 eval 求值面，入库前做白名单校验（与 mock/index.js 的 JS 侧等价）
        if (!$this->mockTemplateIsSafe($tpl_decoded)) {
            $this->sendError(10101, '为了服务器安全，模板含不支持的mockjs语法，仅允许标准占位符与字面量参数');
            return;
        }
        $unique_key = bin2hex(random_bytes(32)); // 64位随机hex，替代可预测的 md5(time().rand())
        //假如已经该页面存在mock
        $mock_page = D("Mock")->where(array('page_id' => $page_id))->find();
        if ($mock_page) {
            $unique_key = $mock_page['unique_key'];
            D("Mock")->where("page_id = '%d' ", array($page_id))->save(array(
                "uid" => $uid,
                "template" => $template,
                "path" => $path,
                "last_update_time" => date("Y-m-d H:i:s"),
            ));
        } else {
            $id = D("Mock")->add(array(
                "unique_key" => $unique_key,
                "uid" => $uid,
                "page_id" => $page_id,
                "item_id" => $item_id,
                "template" => $template,
                "path" => $path,
                "addtime" => date("Y-m-d H:i:s"),
                "last_update_time" => date("Y-m-d H:i:s"),
                "view_times" => 0
            ));
        }

        $this->sendResult(array(
            "page_id" => $page_id,
            "path" => $path,
            "unique_key" => $unique_key
        ));
    }

    // 根据页面id获取mock信息
    public function infoByPageId()
    {
        $login_user = $this->checkLogin(false);
        $page_id = I("page_id/d");
        $uid = $login_user['uid'];
        $page = D("Mock")->where(array('page_id' => $page_id))->find();
        if (!$this->checkItemVisit($login_user['uid'], $page['item_id'])) {
            $this->sendError(10103);
            return;
        }
        $this->sendResult($page);
    }

    // 根据唯一key获取mock的响应数据
    public function infoByKey()
    {
        $unique_key = I("unique_key");
        $page = D("Mock")->where(array('unique_key' => $unique_key))->find();
        $template = $page['template'];
        $tpl_decoded = htmlspecialchars_decode($page['template']);
        // 转发前兜底校验（覆盖历史库存/旁路写入的数据）
        if (!$this->mockTemplateIsSafe($tpl_decoded)) {
            $this->sendError(10101, '为了服务器安全，模板含不支持的mockjs语法，仅允许标准占位符与字面量参数');
            return;
        }
        $res = http_post("http://127.0.0.1:7123/mock", array(
            "template" => $tpl_decoded
        ));
        if ($res) {
            $json = json_decode($res);
            if (!$json) {
                $this->sendError(10101, '为了服务器安全，只允许写符合json语法的字符串');
                return;
            }
            $res = htmlspecialchars($res, ENT_NOQUOTES); // 不编码任何引号,以兼容json.同时转义其他字符串，以免xss
            echo $res;
        } else {
            echo "mock服务暂时不可用。网站管理员需要另行安装mock服务，详情请打开https://www.showdoc.com.cn/p/1ee8a176dd0ccc65609005f3a36c2cc7";
        }
    }
    // 根据item_id和path获取
    public function infoByPath()
    {
        // 这里如果用I("path")方法来获取参数，那么接受post请求的时候有问题。原因暂时不明。应该跟路由里使用正则有关。
        $item_id = $_REQUEST["item_id"];
        $path = $_REQUEST['path'] ? $_REQUEST['path'] : '/';
        $page = D("Mock")->where(" item_id = '%s' and path = '%s'  ", array($item_id, $path))->find();
        if (!$page) {
            echo 'no such path';
            return;
        }
        $template = $page['template'];
        $tpl_decoded = htmlspecialchars_decode($page['template']);
        // 转发前兜底校验（覆盖历史库存/旁路写入的数据）
        if (!$this->mockTemplateIsSafe($tpl_decoded)) {
            $this->sendError(10101, '为了服务器安全，模板含不支持的mockjs语法，仅允许标准占位符与字面量参数');
            return;
        }
        $mock_host = env("MOCK_HOST", '127.0.0.1');
        $mock_port = env('MOCK_PORT', '7123');
        $res = http_post("http://{$mock_host}:{$mock_port}/mock", array(
            "template" => $tpl_decoded
        ));
        if ($res) {
            D("Mock")->where(" id = '%d' ", array($page['id']))->setInc("view_times");
            $json = json_decode($res);
            if (!$json) {
                $this->sendError(10101, '为了服务器安全，只允许写符合json语法的字符串');
                return;
            }
            $res = htmlspecialchars($res, ENT_NOQUOTES); // 不编码任何引号,以兼容json.同时转义其他字符串，以免xss
            echo $res;
        } else {
            echo "mock服务暂时不可用。网站管理员安装完showdoc后需要另行安装mock服务，详情请打开https://www.showdoc.com.cn/p/1ee8a176dd0ccc65609005f3a36c2cc7";
        }
    }

    /**
     * mock 模板白名单校验（与 mock/index.js 的 JS 侧逻辑等价）
     * 背景：mockjs 对占位符 @xxx(参数) 的参数会拼接进 eval 求值，
     * 白名单要求所有带参数占位符的参数只能是：数字 / 单双引号字符串 /
     * 裸标识符 token（纯变量引用，无调用面）/ true / false / null，逗号分隔。
     * 拒绝：反引号（tagged template 无括号调用面）、\u 转义、数组字面量 [、
     * 占位符名后跟 . 或 [（属性链，如 constructor）。
     * 在 json_decode 后的解码形态上递归校验所有字符串值；
     * 属性名修饰符（如 "list|1-10"）不参与占位符求值，不影响。
     */
    private function mockTemplateIsSafe($tpl)
    {
        $decoded = json_decode($tpl, true);
        if ($decoded === null && trim((string)$tpl) !== 'null') {
            return false;
        }
        return $this->mockValueIsSafe($decoded);
    }

    private function mockValueIsSafe($value)
    {
        if (is_string($value)) {
            return $this->mockPlaceholderStringIsSafe($value);
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!$this->mockValueIsSafe($v)) {
                    return false;
                }
            }
        }
        return true; // 数字/布尔/null 无占位符求值面
    }

    private function mockPlaceholderStringIsSafe($str)
    {
        if (strpos($str, '`') !== false) {
            return false; // 反引号：tagged template 可无括号调用函数
        }
        if (preg_match('/\\\\u/i', $str)) {
            return false; // 字面反斜杠u序列（含 \u0028 括号转义 payload）
        }
        // mockjs RE_PLACEHOLDER 的真实名字字符集（[^@#%&()?\s]+，允许数字开头、$、.、非 ASCII 等），
        // 探测必须覆盖同一字符集，否则 @0(...)、@nàme(...) 等形态会绕过校验直达 eval
        $anyName = '@[^@#%&()\?\s]+';
        if (preg_match('/' . $anyName . '(?:\.|\[)/', $str)) {
            return false; // 占位符名后跟 . 或 [：属性链访问
        }
        $strLit = '\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"';
        $num = '-?\\d+(?:\\.\\d+)?(?:[eE][+-]?\\d+)?';
        // 裸 token：字母/_/$ 开头，另放行官方写法 \d+x\d+（如 200x100）与 #RRGGBB 颜色字面量；
        // 字符集排除 . [ ] ( ) = ` ' " \\，无调用/属性链/赋值面
        $bare = '[A-Za-z_$][A-Za-z0-9_$\\-]*|\\d+[xX]\\d+|#[0-9A-Fa-f]{3,8}';
        // 数组字面量参数（@pick 官方用法）：元素仅限 数字/字符串/true/false/null
        $arrElem = '(?:' . $strLit . '|' . $num . '|true|false|null)';
        $arrLit = '\\[(?:' . $arrElem . '(?:\\s*,\\s*' . $arrElem . ')*)?\\]';
        $arg = '(?:' . $strLit . '|' . $num . '|' . $arrLit . '|' . $bare . ')';
        $args = $arg . '(?:\\s*,\\s*' . $arg . ')*';
        $name = '@[A-Za-z][A-Za-z0-9_]*(?:\\/[A-Za-z][A-Za-z0-9_]*)?';
        $call = '/^(?:' . $name . '\\((?:' . $args . ')?\\))/';
        if (!preg_match_all('/' . $anyName . '\\(/', $str, $m, PREG_OFFSET_CAPTURE)) {
            return true; // 没有带参数的占位符，无 eval 面
        }
        foreach ($m[0] as $hit) {
            if (!preg_match($call, substr($str, $hit[1]))) {
                return false;
            }
        }
        return true;
    }
}
