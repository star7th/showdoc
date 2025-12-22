<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Model\ItemAiConfig;
use App\Model\AiContentLog;
use App\Model\Options;
use App\Common\Helper\AiHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AiController extends BaseController
{
    private $openApiKey = '';
    private $aiServiceUrl = '';
    private $aiServiceToken = '';

    public function __construct()
    {
        $this->openApiKey = Options::get('open_api_key', '');
        $this->aiServiceUrl = Options::get('ai_service_url', '');
        $this->aiServiceToken = Options::get('ai_service_token', '');
    }

    /**
     * 检查 AI 知识库功能是否可用
     *
     * @param int $itemId 项目ID
     * @return array 返回 ['enabled' => bool, 'message' => string, 'config' => array]
     */
    private function checkAiKnowledgeBaseEnabled(int $itemId): array
    {
        // 检查系统级配置：是否配置了 AI 服务地址和 Token
        if (!$this->aiServiceUrl || !$this->aiServiceToken) {
            return [
                'enabled' => false,
                'message' => 'AI 服务未配置，请联系管理员'
            ];
        }

        // 检查项目是否存在
        $item = Item::findById($itemId);
        if (!$item) {
            return [
                'enabled' => false,
                'message' => '项目不存在'
            ];
        }

        // 检查项目级配置（使用新表）
        $config = ItemAiConfig::getConfig($itemId);
        if (empty($config['enabled'])) {
            return [
                'enabled' => false,
                'message' => '当前项目未启用 AI 知识库功能'
            ];
        }

        return [
            'enabled' => true,
            'message' => '',
            'config' => $config  // 返回完整配置
        ];
    }

    /**
     * AI 助手（流式输出）
     */
    public function create(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $content = $this->getParam($request, 'content', '');

        // 对于流式接口，错误也应该以流式格式返回（SSE 格式），而不是 JSON
        // 这样前端才能正确识别错误
        if (!$this->openApiKey) {
            $this->sendStreamError('管理员没有在管理后台配置AI助手认证KEY,因此你无法使用AI功能。请联系管理员');
            return $response;
        }

        // 开源版无会员限制，AI 功能对所有用户开放

        // 写日志记录
        AiContentLog::add([
            'uid' => $uid,
            'content' => $content,
            'reply_content' => '',
        ]);

        // 流式输出：清除所有输出缓冲区，设置 headers，然后直接输出
        $this->sendStream($content);
        exit;
    }

    /**
     * 兼容旧接口：Api/Ai/send
     *
     * 行为：读取参数 content，复用内部流式输出实现。
     */
    public function send(Request $request, Response $response): Response
    {
        $content = $this->getParam($request, 'content', '');
        if ($content === '') {
            return $this->error($response, 10101, '内容不能为空');
        }

        // 流式输出（直接输出，不返回 Response）
        $this->sendStream($content);
        exit;
    }

    /**
     * 以流式格式（SSE）返回错误信息
     * 
     * 用于流式接口的错误返回，确保前端能正确识别错误
     * 注意：此方法会直接输出，调用者需要负责 exit
     * 采用与 sendStream 完全相同的设置方式，确保行为一致
     * 
     * @param string $message 错误消息
     * @param int $code 错误码，默认 10101
     * @return void
     */
    private function sendStreamError(string $message, int $code = 10101): void
    {
        // 清除所有输出缓冲区，确保直接输出（必须在设置 headers 之前）
        // 与 sendStream 方法保持一致
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 设置流式输出 headers（必须在任何输出之前）
        // 与 sendStream 方法保持一致
        if (!headers_sent()) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        // 禁用输出缓冲（确保数据实时传输）
        // 与 sendStream 方法保持一致
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);

        // 以 SSE 格式输出错误（格式与前端期望的 JSON 格式一致）
        // 与 sendStream 中的错误输出格式保持一致
        $errorData = json_encode([
            'error_code'    => $code,
            'error_message' => $message,
        ], JSON_UNESCAPED_UNICODE);

        echo "data: {$errorData}\n\n";

        // 确保所有输出都被立即发送
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }

    /**
     * 发送 AI 请求（流式输出，内部实现）
     *
     * @param string $content 用户输入内容
     * @return void
     */
    private function sendStream(string $content): void
    {
        // 清除所有输出缓冲区，确保直接输出（必须在设置 headers 之前）
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // 设置流式输出 headers（必须在任何输出之前）
        if (!headers_sent()) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        // 禁用输出缓冲（确保数据实时传输）
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);

        // 检查连接是否已断开
        if (connection_aborted()) {
            return;
        }

        $aiModelName = Options::get('ai_model_name', 'gpt-4o');
        $postData = json_encode([
            'model' => $aiModelName,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是文档工具 showdoc 的AI助手，主要是职责是协助用户生成各种各样的文档/代码/文案，提高用户的写作效率。用户提出要求，你应该帮助用户生成文字。如果用户不懂，你可以引导用户怎么使用怎么提问，或者让用户点击当前网页左下角的帮助说明链接查看一些使用例子',
                ],
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
            'stream' => true,
        ]);

        $openApiHost = Options::get('open_api_host', 'https://api.openai.com');
        if (!strstr($openApiHost, 'http')) {
            $openApiHost = 'https://' . $openApiHost;
        }
        if (substr($openApiHost, -1) === '/') {
            $openApiHost = substr($openApiHost, 0, -1);
        }

        // 参考 https://github.com/dirk1983/chatgpt/blob/main/stream.php
        $callback = function ($ch, $data) {
            // 检查连接是否已断开
            if (connection_aborted()) {
                return -1; // 返回-1会中断curl执行
            }

            // 如果数据为空，直接返回
            if (empty($data)) {
                return strlen($data);
            }

            // 尝试解析 JSON 以检测错误（但不阻塞正常流式数据）
            // 注意：流式数据可能是分块的，所以不能完全依赖 JSON 解析
            $complete = @json_decode($data, true);
            if ($complete !== null && isset($complete['error'])) {
                // 检测到错误信息，统一格式输出
                $errorCode = $complete['error']['code'] ?? '';
                $errorMessage = $complete['error']['message'] ?? '';

                // 特殊错误码处理
                if (strpos($errorMessage, 'Rate limit reached') === 0) {
                    $errorCode = 'rate_limit_reached';
                }
                if (strpos($errorMessage, 'Your access was terminated') === 0) {
                    $errorCode = 'access_terminated';
                }
                if (strpos($errorMessage, "You didn't provide an API key") === 0) {
                    $errorCode = 'no_api_key';
                }
                if (strpos($errorMessage, 'You exceeded your current quota') === 0) {
                    $errorCode = 'insufficient_quota';
                }
                if (strpos($errorMessage, 'That model is currently overloaded') === 0) {
                    $errorCode = 'model_overloaded';
                }

                // 输出错误信息（SSE 格式）
                $errorData = json_encode(['error' => ['code' => $errorCode, 'message' => $errorMessage]], JSON_UNESCAPED_UNICODE);
                echo "data: {$errorData}\n\n";
            } else {
                // 正常数据直接输出（保持原始格式，不做任何处理）
                // 这样确保流式数据能实时传输
                echo $data;
            }

            // 立即刷新输出，确保数据实时传输
            flush();

            return strlen($data);
        };

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, $openApiHost . '/v1/chat/completions');
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        // 设置合理的超时时间
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 480);

        // 关键：防止连接复用，确保每次请求都是独立的
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);

        // 注意：使用 CURLOPT_WRITEFUNCTION 时，不能设置 CURLOPT_RETURNTRANSFER
        // 因为 CURLOPT_RETURNTRANSFER 会让 curl 返回数据而不是通过回调输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, $callback);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                "Authorization: Bearer {$this->openApiKey}",
                'Content-Length: ' . strlen($postData)
            ]
        );
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);

        $result = curl_exec($curl);

        // 检查curl执行结果
        if ($result === false) {
            $error = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);

            // 返回错误信息（直接输出）
            $errorData = json_encode(['error' => '网络请求失败: ' . $error], JSON_UNESCAPED_UNICODE);
            echo "data: {$errorData}\n\n";
            flush();
            return;
        }

        curl_close($curl);
    }

    /**
     * 知识库对话接口
     */
    public function chat(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);
        $question = $this->getParam($request, 'question', '');
        $conversationId = $this->getParam($request, 'conversation_id', '');
        $stream = $this->getParam($request, 'stream', 1);

        // 对于流式输出，需要特殊处理错误，不能使用 sendError
        if ($stream == 1) {
            if (!$itemId) {
                $this->sendStreamError('项目ID不能为空');
                return $response;
            }

            if (!$question) {
                $this->sendStreamError('问题不能为空');
                return $response;
            }

            // 检查登录状态（允许游客访问公开项目）
            $loginUser = [];
            $uid = 0;
            $this->requireUserFromToken($request, $response, $uid, false);
            if ($uid > 0) {
                $user = \App\Model\User::findById($uid);
                if ($user) {
                    $loginUser = (array) $user;
                }
            }

            // 检查项目访问权限
            if (!$this->checkItemVisit($uid, $itemId)) {
                $this->sendStreamError('您没有访问该项目的权限');
                return $response;
            }

            // 检查 AI 知识库功能是否可用
            $aiCheck = $this->checkAiKnowledgeBaseEnabled($itemId);
            if (!$aiCheck['enabled']) {
                $this->sendStreamError($aiCheck['message']);
                return $response;
            }

            // 调用 AI 服务（流式返回）
            // 注意：必须在所有验证通过后再进行流式输出
            $url = rtrim($this->aiServiceUrl, '/') . '/api/chat/stream';
            $postData = [
                'item_id' => $itemId,
                'user_id' => $uid,
                'question' => $question
            ];
            if ($conversationId) {
                $postData['conversation_id'] = $conversationId;
            }

            // 清除所有输出缓冲区，设置 headers，然后直接输出
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (!headers_sent()) {
                header('Content-Type: text/event-stream; charset=utf-8');
                header('Cache-Control: no-cache');
                header('X-Accel-Buffering: no');
            }

            // 禁用输出缓冲（确保数据实时传输）
            if (function_exists('apache_setenv')) {
                @apache_setenv('no-gzip', 1);
            }
            @ini_set('zlib.output_compression', 0);
            @ini_set('implicit_flush', 1);

            // 调用 AI 服务并直接输出流式数据
            AiHelper::callServiceStream($url, $postData, $this->aiServiceToken);
            exit;
        } else {
            // 非流式输出使用正常的错误处理
            if (!$itemId) {
                return $this->error($response, 10101, '项目ID不能为空');
            }

            if (!$question) {
                return $this->error($response, 10101, '问题不能为空');
            }

            // 检查登录状态（允许游客访问公开项目）
            $loginUser = [];
            $uid = 0;
            $this->requireUserFromToken($request, $response, $uid, false);
            if ($uid > 0) {
                $user = \App\Model\User::findById($uid);
                if ($user) {
                    $loginUser = (array) $user;
                }
            }

            // 检查项目访问权限
            if (!$this->checkItemVisit($uid, $itemId)) {
                return $this->error($response, 10303, '您没有访问该项目的权限');
            }

            // 检查 AI 知识库功能是否可用
            $aiCheck = $this->checkAiKnowledgeBaseEnabled($itemId);
            if (!$aiCheck['enabled']) {
                return $this->error($response, 10101, $aiCheck['message']);
            }

            // 调用 AI 服务（非流式返回）
            $url = rtrim($this->aiServiceUrl, '/') . '/api/chat';
            $postData = [
                'item_id' => $itemId,
                'user_id' => $uid,
                'question' => $question
            ];
            if ($conversationId) {
                $postData['conversation_id'] = $conversationId;
            }

            $result = AiHelper::callService($url, $postData, $this->aiServiceToken, 'POST', 30);
            if ($result === false) {
                return $this->error($response, 10101, 'AI 服务调用失败');
            }

            return $this->success($response, $result);
        }
    }

    /**
     * 获取索引状态
     */
    public function getIndexStatus(Request $request, Response $response): Response
    {
        $itemId = $this->getParam($request, 'item_id', 0);
        if (!$itemId) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        // 检查登录状态（允许游客访问公开项目）
        $loginUser = [];
        $uid = 0;
        $this->requireUserFromToken($request, $response, $uid, false);
        if ($uid > 0) {
            $user = \App\Model\User::findById($uid);
            if ($user) {
                $loginUser = (array) $user;
            }
        }

        // 检查项目访问权限
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有访问该项目的权限');
        }

        // 检查 AI 知识库功能是否可用
        $aiCheck = $this->checkAiKnowledgeBaseEnabled($itemId);
        if (!$aiCheck['enabled']) {
            return $this->success($response, [
                'status' => 'not_configured',
                'message' => $aiCheck['message']
            ]);
        }

        // 调用 AI 服务获取索引状态
        $url = rtrim($this->aiServiceUrl, '/') . '/api/index/status?item_id=' . $itemId;
        $result = AiHelper::callService($url, null, $this->aiServiceToken, 'GET', 30);

        if ($result === false) {
            // 返回错误状态，但不使用 sendError，而是返回状态信息
            return $this->success($response, [
                'status' => 'error',
                'message' => '无法连接到 AI 服务，请检查服务地址和网络连接'
            ]);
        }

        // 如果 AI 服务返回了错误信息，也要正确处理
        if (isset($result['error_code']) && $result['error_code'] != 0) {
            return $this->success($response, [
                'status' => 'error',
                'message' => $result['error_message'] ?? '获取索引状态失败'
            ]);
        }

        // 转换 AI 服务返回的数据格式为前端期望的格式
        $responseData = [
            'status' => $result['status'] ?? (isset($result['indexed']) && $result['indexed'] ? 'indexed' : 'unknown'),
            'document_count' => isset($result['document_count']) ? (int) $result['document_count'] : 0,
            'last_update_time' => $result['last_update_time'] ?? null
        ];

        return $this->success($response, $responseData);
    }

    /**
     * 重新索引项目
     */
    public function rebuildIndex(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'item_id', 0);

        if (!$itemId) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        // 检查项目管理权限
        if (!$this->checkItemManage($uid, $itemId)) {
            return $this->error($response, 10101, '您没有管理该项目的权限');
        }

        // 检查 AI 知识库功能是否可用
        $aiCheck = $this->checkAiKnowledgeBaseEnabled($itemId);
        if (!$aiCheck['enabled']) {
            return $this->error($response, 10101, $aiCheck['message']);
        }

        $result = AiHelper::rebuild($itemId, $this->aiServiceUrl, $this->aiServiceToken);
        if ($result === false) {
            return $this->error($response, 10101, '重新索引失败');
        }

        return $this->success($response, $result);
    }
}
