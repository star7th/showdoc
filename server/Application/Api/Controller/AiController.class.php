<?php

namespace Api\Controller;

use Think\Controller;
use EasyWeChat\Factory;

class AiController extends BaseController
{

    private $open_api_key = '';
    private $ai_service_url = '';
    private $ai_service_token = '';

    public function __construct()
    {
        parent::__construct();
        $this->open_api_key = D("Options")->get("open_api_key");
        $this->ai_service_url = D("Options")->get("ai_service_url");
        $this->ai_service_token = D("Options")->get("ai_service_token");
    }

    /**
     * 检查 AI 知识库功能是否可用
     * @param int $item_id 项目ID
     * @return array 返回 ['enabled' => bool, 'message' => string, 'config' => array]
     */
    private function checkAiKnowledgeBaseEnabled($item_id)
    {
        // 检查系统级配置：是否配置了 AI 服务地址和 Token
        if (!$this->ai_service_url || !$this->ai_service_token) {
            return array(
                'enabled' => false,
                'message' => 'AI 服务未配置，请联系管理员'
            );
        }

        // 检查项目是否存在
        $item = D("Item")->where(array('item_id' => $item_id))->find();
        if (!$item) {
            return array(
                'enabled' => false,
                'message' => '项目不存在'
            );
        }

        // 检查项目级配置（使用新表）
        $config = D("ItemAiConfig")->getConfig($item_id);
        if (empty($config['enabled'])) {
            return array(
                'enabled' => false,
                'message' => '当前项目未启用 AI 知识库功能'
            );
        }

        return array(
            'enabled' => true,
            'message' => '',
            'config' => $config  // 返回完整配置
        );
    }

    public function create()
    {
        $content = I("post.content");
        $login_user = $this->checkLogin();
        if (!$this->open_api_key) {
            $this->sendError(10101, '管理员没有在管理后台配置AI助手认证KEY,因此你无法使用AI功能。请联系管理员');
            return;
        }
        $res = $this->send($content);
    }

    public function send($content)
    {
        header("Content-Type: text/event-stream");
        header("X-Accel-Buffering: no");

        // 检查连接是否已断开
        if (connection_aborted()) {
            return false;
        }

        $ai_model_name = D("Options")->get("ai_model_name");
        $ai_model_name = $ai_model_name ? $ai_model_name : 'gpt-4o';
        $postData = json_encode(array(
            "model" => $ai_model_name,
            "messages" => array(
                array(
                    "role" => 'system',
                    "content" => "你是文档工具 showdoc 的AI助手，主要是职责是协助用户生成各种各样的文档/代码/文案，提高用户的写作效率。用户提出要求，你应该帮助用户生成文字。如果用户不懂，你可以引导用户怎么使用怎么提问，或者让用户点击当前网页左下角的帮助说明链接查看一些使用例子",
                ),
                array(
                    "role" => 'user',
                    "content" => $content,
                ),
            ),
            "stream" => true,
        ));
        $open_api_host = D("Options")->get("open_api_host");
        if (!$open_api_host) {
            $open_api_host = 'https://api.openai.com';
        }
        if (!strstr($open_api_host, 'http')) {
            $open_api_host = 'https://' . $open_api_host;
        }
        if (substr($open_api_host, -1) === '/') { // 如果字符串以 / 符号结尾:
            $open_api_host = substr($open_api_host, 0, -1); // 将字符串的最后一个字符剪切掉
        }

        // 参考 https://github.com/dirk1983/chatgpt/blob/main/stream.php
        $callback = function ($ch, $data) {
            // 检查连接是否已断开
            if (connection_aborted()) {
                return -1; // 返回-1会中断curl执行
            }

            $complete = json_decode($data);
            if (isset($complete->error)) {
                setcookie("errcode", $complete->error->code);
                setcookie("errmsg", $data);
                if (strpos($complete->error->message, "Rate limit reached") === 0) { //访问频率超限错误返回的code为空，特殊处理一下
                    setcookie("errcode", "rate_limit_reached");
                }
                if (strpos($complete->error->message, "Your access was terminated") === 0) { //违规使用，被封禁，特殊处理一下
                    setcookie("errcode", "access_terminated");
                }
                if (strpos($complete->error->message, "You didn't provide an API key") === 0) { //未提供API-KEY
                    setcookie("errcode", "no_api_key");
                }
                if (strpos($complete->error->message, "You exceeded your current quota") === 0) { //API-KEY余额不足
                    setcookie("errcode", "insufficient_quota");
                }
                if (strpos($complete->error->message, "That model is currently overloaded") === 0) { //OpenAI模型超负荷
                    setcookie("errcode", "model_overloaded");
                }
            } else {
                echo $data;
                flush();
            }
            return strlen($data);
        };

        $curl = curl_init();  //初始化
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, $open_api_host . '/v1/chat/completions');  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法

        // 设置合理的超时时间
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);  // 连接超时30秒
        curl_setopt($curl, CURLOPT_TIMEOUT, 480);        // 总超时8分钟

        // 关键：防止连接复用，确保每次请求都是独立的
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);  // 强制使用新的连接
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);   // 禁止复用连接


        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);  //设置post的数据
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, $callback);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                "Authorization: Bearer {$this->open_api_key}",
                'Content-Length: ' . strlen($postData)
            )
        );
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3); // 设置最大重定向次数为3次
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // 允许自动重定向
        curl_setopt($curl, CURLOPT_AUTOREFERER, true); // 自动设置Referer

        $result = curl_exec($curl);
        // 检查curl执行结果
        if ($result === false) {
            $error = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);

            // 返回错误信息
            echo "data: " . json_encode(array("error" => "网络请求失败: " . $error)) . "\n\n";
            flush();
            return false;
        }

        curl_close($curl);
        return $result;
    }

    /**
     * 知识库对话接口
     */
    public function chat()
    {
        $item_id = I("item_id/d");
        $question = I("question");
        $conversation_id = I("conversation_id");
        $stream = I("stream/d", 1); // 默认流式返回

        // 对于流式输出，需要特殊处理错误，不能使用 sendError
        if ($stream == 1) {
            // 清除所有输出缓冲区，确保直接输出
            while (ob_get_level()) {
                ob_end_clean();
            }

            // 提前设置流式输出的 headers
            // 确保 UTF-8 编码，移除可能存在的其他 Content-Type 设置
            header_remove('Content-Type');
            header("Content-Type: text/event-stream; charset=utf-8");
            header("Cache-Control: no-cache");
            header("X-Accel-Buffering: no");

            // 确保内部编码和输出编码都是 UTF-8
            if (function_exists('mb_internal_encoding')) {
                mb_internal_encoding('UTF-8');
            }
            if (function_exists('mb_http_output')) {
                mb_http_output('UTF-8');
            }

            // 禁用 ThinkPHP 的模板渲染
            if (class_exists('Think\\View')) {
                $this->view = null;
            }

            if (!$item_id) {
                echo "data: " . json_encode(array('type' => 'error', 'message' => '项目ID不能为空'), JSON_UNESCAPED_UNICODE) . "\n\n";
                flush();
                exit;
            }

            if (!$question) {
                echo "data: " . json_encode(array('type' => 'error', 'message' => '问题不能为空'), JSON_UNESCAPED_UNICODE) . "\n\n";
                flush();
                exit;
            }

            // 检查登录状态（允许游客访问公开项目）
            $login_user = $this->checkLogin(false);
            $uid = $login_user ? $login_user['uid'] : 0;

            // 检查项目访问权限
            if (!$this->checkItemVisit($uid, $item_id)) {
                echo "data: " . json_encode(array('type' => 'error', 'message' => '您没有访问该项目的权限'), JSON_UNESCAPED_UNICODE) . "\n\n";
                flush();
                exit;
            }

            // 检查 AI 知识库功能是否可用
            $ai_check = $this->checkAiKnowledgeBaseEnabled($item_id);
            if (!$ai_check['enabled']) {
                echo "data: " . json_encode(array('type' => 'error', 'message' => $ai_check['message']), JSON_UNESCAPED_UNICODE) . "\n\n";
                flush();
                exit;
            }
        } else {
            // 非流式输出使用正常的错误处理
            if (!$item_id) {
                $this->sendError(10101, '项目ID不能为空');
                return;
            }

            if (!$question) {
                $this->sendError(10101, '问题不能为空');
                return;
            }

            // 检查登录状态（允许游客访问公开项目）
            $login_user = $this->checkLogin(false);
            $uid = $login_user ? $login_user['uid'] : 0;

            // 检查项目访问权限
            if (!$this->checkItemVisit($uid, $item_id)) {
                $this->sendError(10303, '您没有访问该项目的权限');
                return;
            }

            // 检查 AI 知识库功能是否可用
            $ai_check = $this->checkAiKnowledgeBaseEnabled($item_id);
            if (!$ai_check['enabled']) {
                $this->sendError(10101, $ai_check['message']);
                return;
            }
        }

        // 调用 AI 服务
        if ($stream == 1) {
            // 流式返回
            $url = rtrim($this->ai_service_url, '/') . '/api/chat/stream';
        } else {
            // 非流式返回
            $url = rtrim($this->ai_service_url, '/') . '/api/chat';
        }
        $postData = array(
            'item_id' => $item_id,
            'user_id' => $uid,
            'question' => $question
        );
        if ($conversation_id) {
            $postData['conversation_id'] = $conversation_id;
        }

        if ($stream == 1) {
            // 流式返回（headers 已在前面设置）
            \Api\Helper\AiHelper::callServiceStream($url, $postData, $this->ai_service_token);
            // 流式输出完毕后必须退出，防止框架继续渲染模板
            exit;
        } else {
            // 非流式返回
            $result = \Api\Helper\AiHelper::callService($url, $postData, $this->ai_service_token);
            if ($result === false) {
                $this->sendError(10101, 'AI 服务调用失败');
                return;
            }
            $this->sendResult($result);
        }
    }

    /**
     * 获取索引状态
     */
    public function getIndexStatus()
    {
        $item_id = I("item_id/d");
        if (!$item_id) {
            $this->sendError(10101, '项目ID不能为空');
            return;
        }

        $login_user = $this->checkLogin(false);
        $uid = $login_user ? $login_user['uid'] : 0;

        // 检查项目访问权限
        if (!$this->checkItemVisit($uid, $item_id)) {
            $this->sendError(10303, '您没有访问该项目的权限');
            return;
        }

        // 检查 AI 知识库功能是否可用
        $ai_check = $this->checkAiKnowledgeBaseEnabled($item_id);
        if (!$ai_check['enabled']) {
            $this->sendResult(array(
                'status' => 'not_configured',
                'message' => $ai_check['message']
            ));
            return;
        }

        // 调用 AI 服务获取索引状态
        $url = rtrim($this->ai_service_url, '/') . '/api/index/status?item_id=' . $item_id;
        $result = \Api\Helper\AiHelper::callService($url, null, $this->ai_service_token, 'GET');

        if ($result === false) {
            // 返回错误状态，但不使用 sendError，而是返回状态信息
            $this->sendResult(array(
                'status' => 'error',
                'message' => '无法连接到 AI 服务，请检查服务地址和网络连接'
            ));
            return;
        }

        // 如果 AI 服务返回了错误信息，也要正确处理
        if (isset($result['error_code']) && $result['error_code'] != 0) {
            $this->sendResult(array(
                'status' => 'error',
                'message' => isset($result['error_message']) ? $result['error_message'] : '获取索引状态失败'
            ));
            return;
        }

        // 转换 AI 服务返回的数据格式为前端期望的格式
        // AI 服务返回: indexed (bool), document_count (int)
        // 前端期望: status (string: 'indexed'/'indexing'/'not_configured'/'error'), document_count (int)
        $response = array(
            'status' => isset($result['indexed']) && $result['indexed'] ? 'indexed' : 'unknown',
            'document_count' => isset($result['document_count']) ? intval($result['document_count']) : 0,
            'last_update_time' => isset($result['last_update_time']) ? $result['last_update_time'] : null
        );

        $this->sendResult($response);
    }

    /**
     * 重新索引项目
     */
    public function rebuildIndex()
    {
        $item_id = I("item_id/d");
        if (!$item_id) {
            $this->sendError(10101, '项目ID不能为空');
            return;
        }

        $login_user = $this->checkLogin();
        $uid = $login_user['uid'];

        // 检查项目管理权限
        if (!$this->checkItemManage($uid, $item_id)) {
            $this->sendError(10101, '您没有管理该项目的权限');
            return;
        }

        // 检查 AI 知识库功能是否可用
        $ai_check = $this->checkAiKnowledgeBaseEnabled($item_id);
        if (!$ai_check['enabled']) {
            $this->sendError(10101, $ai_check['message']);
            return;
        }

        $result = \Api\Helper\AiHelper::rebuild($item_id, $this->ai_service_url, $this->ai_service_token);
        if ($result === false) {
            $this->sendError(10101, '重新索引失败');
            return;
        }

        $this->sendResult($result);
    }
}
