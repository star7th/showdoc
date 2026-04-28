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

        // 生成唯一key
        $uniqueKey = md5(time() . rand() . "gbgdhbdgtfgfK3@bv45342asfsdfjhyfgkj54fofgfbv45342asfsdg");

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
}
