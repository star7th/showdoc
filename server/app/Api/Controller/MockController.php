<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Mock;
use App\Model\Page;
use App\Model\User;
use App\Common\Helper\HttpHelper;
use App\Common\Helper\Env;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class MockController extends BaseController
{
    /**
     * 添加/更新Mock数据
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function add(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $pageId = $this->getParam($request, 'page_id', 0);
        $template = $this->getParam($request, 'template', '');
        $path = $this->getParam($request, 'path', '/');

        // 获取页面信息
        $page = Page::findById($pageId);
        if (!$page) {
            return $this->error($response, 10101, '页面不存在');
        }

        // 权限检查
        if (!$this->checkItemEdit($uid, (int) ($page['item_id'] ?? 0))) {
            return $this->error($response, 10303, '没有权限');
        }

        // 路径处理
        if (strpos($path, "~") === 0) {
            // 正则路径：以 ~ 开头
            $regex = substr($path, 1);
            if (strlen($regex) > 500) {
                return $this->error($response, 10101, '正则表达式不能超过500个字符');
            }
            // 验证正则语法（将用户输入的正则包裹在定界符中测试）
            $delimiter = '/';
            $escapedPattern = str_replace($delimiter, '\\' . $delimiter, $regex);
            $testResult = @preg_match($delimiter . $escapedPattern . '/u', '');
            if ($testResult === false) {
                return $this->error($response, 10101, '正则表达式语法错误：' . preg_last_error_msg());
            }
            // 安全检查
            if (!Mock::isSafeRegex($regex)) {
                return $this->error($response, 10101, '正则表达式过于复杂或存在安全风险，请简化');
            }
        } else {
            // 普通路径：确保以 / 开头
            if (substr($path, 0, 1) !== '/') {
                $path = '/' . $path;
            }
        }

        $itemId = (int) $page['item_id'];

        // 验证JSON格式
        $json = json_decode(htmlspecialchars_decode($template));
        if (!$json) {
            return $this->error($response, 10101, '为了服务器安全，只允许写符合json语法的字符串');
        }

        // mockjs 占位符参数存在 eval 求值面，入库前做白名单校验（与 mock-server JS 侧等价）
        if (!self::mockTemplateIsSafe(htmlspecialchars_decode($template))) {
            return $this->error($response, 10101, '为了服务器安全，模板含不支持的mockjs语法，仅允许标准占位符与字面量参数');
        }

        // 生成唯一key
        $uniqueKey = bin2hex(random_bytes(32));

        // 检查是否已存在该页面的Mock
        $mockPage = Mock::findByPageId($pageId);
        if ($mockPage) {
            // 更新现有Mock
            $uniqueKey = $mockPage['unique_key'];
            Mock::saveByPageId($pageId, [
                'uid' => $uid,
                'template' => $template,
                'path' => $path,
                'last_update_time' => date("Y-m-d H:i:s"),
            ]);
        } else {
            // 新建Mock
            Mock::add([
                'unique_key' => $uniqueKey,
                'uid' => $uid,
                'page_id' => $pageId,
                'item_id' => $itemId,
                'template' => $template,
                'path' => $path,
                'addtime' => date("Y-m-d H:i:s"),
                'last_update_time' => date("Y-m-d H:i:s"),
                'view_times' => 0,
            ]);
        }

        return $this->success($response, [
            'page_id' => $pageId,
            'path' => $path,
            'unique_key' => $uniqueKey,
        ]);
    }

    /**
     * 根据页面ID获取mock信息
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function infoByPageId(Request $request, Response $response): Response
    {
        $user = [];
        $this->requireLoginUser($request, $response, $user, false); // 非严格模式，允许未登录

        $uid = (int) ($user['uid'] ?? 0);
        $pageId = $this->getParam($request, 'page_id', 0);

        $mock = Mock::findByPageId($pageId);
        // 如果没有数据，返回空数据而不是错误（避免前端显示错误弹窗）
        if (!$mock) {
            return $this->success($response, []);
        }

        // 权限检查
        if (!$this->checkItemVisit($uid, $mock['item_id'], '')) {
            return $this->error($response, 10103, '没有权限');
        }

        return $this->success($response, $mock);
    }

    /**
     * 根据唯一key获取mock的响应数据
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function infoByKey(Request $request, Response $response): Response
    {
        $uniqueKey = $this->getParam($request, 'unique_key', '');

        $mock = Mock::findByUniqueKey($uniqueKey);
        if (!$mock) {
            $response->getBody()->write('no such key');
            return $response->withStatus(404);
        }

        $template = $mock['template'];
        $mockHost = Env::get('MOCK_HOST', '127.0.0.1');
        $mockPort = Env::get('MOCK_PORT', '7123');

        // 转发前兜底校验（覆盖历史库存/旁路写入的数据）
        if (!self::mockTemplateIsSafe(htmlspecialchars_decode($template))) {
            $response->getBody()->write('为了服务器安全，模板含不支持的mockjs语法，仅允许标准占位符与字面量参数');
            return $response->withStatus(400);
        }

        // 调用Mock服务
        $res = HttpHelper::post("http://{$mockHost}:{$mockPort}/mock", [
            'template' => htmlspecialchars_decode($template),
        ]);

        if ($res) {
            $json = json_decode($res, true);
            if (!$json) {
                $response->getBody()->write('为了服务器安全，只允许写符合json语法的字符串');
                return $response->withStatus(400);
            }

            // 直接使用原始 JSON 数据

            // 使用 json_encode 重新编码，确保安全（会自动转义所有特殊字符）
            $response->getBody()->write(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write('mock服务暂时不可用。网站管理员安装完showdoc后需要另行安装mock服务，详情请打开https://www.showdoc.com.cn/help');
            return $response->withStatus(503);
        }
    }

    /**
     * 根据item_id和path获取mock数据
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function infoByPath(Request $request, Response $response): Response
    {
        // 使用 getParam 获取参数（更安全）
        $itemId = $this->getParam($request, 'item_id', 0);
        $path = $this->getParam($request, 'path', '/');

        if (strlen($path) > 2048) {
            $response->getBody()->write('path too long');
            return $response->withStatus(400);
        }

        $mock = Mock::findByItemIdAndPath($itemId, $path);
        if (!$mock) {
            $response->getBody()->write('no such path');
            return $response->withStatus(404);
        }

        $template = $mock['template'];
        $mockHost = Env::get('MOCK_HOST', '127.0.0.1');
        $mockPort = Env::get('MOCK_PORT', '7123');

        // 转发前兜底校验（覆盖历史库存/旁路写入的数据）
        if (!self::mockTemplateIsSafe(htmlspecialchars_decode($template))) {
            $response->getBody()->write('为了服务器安全，模板含不支持的mockjs语法，仅允许标准占位符与字面量参数');
            return $response->withStatus(400);
        }

        // 调用Mock服务
        $res = HttpHelper::post("http://{$mockHost}:{$mockPort}/mock", [
            'template' => htmlspecialchars_decode($template),
        ]);

        if ($res) {
            // 增加查看次数
            Mock::incrementViewTimes($mock['id']);

            $json = json_decode($res, true);
            if (!$json) {
                $response->getBody()->write('为了服务器安全，只允许写符合json语法的字符串');
                return $response->withStatus(400);
            }

            // 直接使用原始 JSON 数据

            // 使用 json_encode 重新编码，确保安全（会自动转义所有特殊字符）
            $response->getBody()->write(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write('mock服务暂时不可用。网站管理员安装完showdoc后需要另行安装mock服务，详情请打开https://www.showdoc.com.cn/help');
            return $response->withStatus(503);
        }
    }

    /**
     * mock 模板白名单校验（与 mock-server index.js 的 JS 侧逻辑等价）
     * 背景：mockjs 对占位符 @xxx(参数) 的参数会拼接进 eval 求值，
     * 白名单要求参数只能是：数字 / 单双引号字符串 / 裸标识符 token（纯变量引用，无调用面）/
     * true / false / null / 字面量数组（如 @pick([1,2,3])，元素仅限 数字/字符串/true/false/null），逗号分隔。
     * 拒绝：反引号（tagged template 无括号调用面）、\u 转义、
     * 占位符名后跟 . 或 [（属性链，如 constructor）、任何无法按字面量完整解析的参数。
     * 在 json_decode 后的解码形态上递归校验所有字符串值；
     * 属性名修饰符（如 "list|1-10"）不参与占位符求值，不影响。
     */
    private static function mockTemplateIsSafe(string $tpl): bool
    {
        $decoded = json_decode($tpl, true);
        if ($decoded === null && trim($tpl) !== 'null') {
            return false;
        }
        return self::mockValueIsSafe($decoded);
    }

    private static function mockValueIsSafe($value): bool
    {
        if (is_string($value)) {
            return self::mockPlaceholderStringIsSafe($value);
        }
        if (is_array($value)) {
            foreach ($value as $v) {
                if (!self::mockValueIsSafe($v)) {
                    return false;
                }
            }
        }
        return true; // 数字/布尔/null 无占位符求值面
    }

    private static function mockPlaceholderStringIsSafe(string $str): bool
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
        if (preg_match('/' . $anyName . '(?:\\.|\\[)/', $str)) {
            return false; // 占位符名后跟 . 或 [：属性链访问
        }
        $name = '@[A-Za-z][A-Za-z0-9_]*(?:\\/[A-Za-z][A-Za-z0-9_]*)?';
        $strLit = '\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"]|\\\\.)*"';
        $num = '-?\\d+(?:\\.\\d+)?(?:[eE][+-]?\\d+)?';
        // 裸 token：字母/_/$ 开头，另放行官方写法 \d+x\d+（如 200x100）与 #RRGGBB 颜色字面量；
        // 字符集排除 . [ ] ( ) = ` ' " \\，无调用/属性链/赋值面
        $bare = '[A-Za-z_$][A-Za-z0-9_$\\-]*|\\d+[xX]\\d+|#[0-9A-Fa-f]{3,8}';
        // 数组字面量参数（@pick 官方用法）：元素仅限 数字/字符串/true/false/null
        $arrElem = '(?:' . $strLit . '|' . $num . '|true|false|null)';
        $arrLit = '\\[(?:' . $arrElem . '(?:\\s*,\\s*' . $arrElem . ')*)?\\]';
        $arg = '(?:' . $strLit . '|' . $num . '|' . $arrLit . '|' . $bare . ')';
        $args = $arg . '(?:\\s*,\\s*' . $arg . ')*';
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
