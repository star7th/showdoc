<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Cache\CacheManager;
use App\Common\Helper\AgentHelper;
use App\Common\Helper\IpHelper;
use App\Model\AiChatSession;
use App\Model\AiChatMessage;
use App\Model\Item;
use App\Model\ItemAiConfig;
use App\Model\Options;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * AI Agent 统一控制器
 *
 * 会话管理 + 配置 + 反馈 API。
 * Agent 循环（agent / agentCancel）在 T4b 实现。
 */
class AgentController extends BaseController
{
    use AgentHelper; // from App\Common\Helper
    /** @var string AI 服务地址 */
    private $aiServiceUrl = '';

    /** @var string AI 模型名称 */
    private $aiModelName = '';

    /** @var string 全局系统提示词 */
    private $aiSystemPrompt = '';

    /** @var string 全局欢迎语 */
    private $aiWelcomeMessage = '';

    // S4 fix: Token 不再在构造函数中加载，改为按需读取

    public function __construct()
    {
        $this->aiServiceUrl    = Options::get('open_ai_host', '');
        $this->aiModelName     = Options::get('ai_model_name', 'gpt-5-mini');
        $this->aiSystemPrompt  = Options::get('ai_system_prompt', '');
        $this->aiWelcomeMessage = Options::get('ai_welcome_message', '');
    }

    /**
     * 按需获取 AI 服务 Token（S4 fix: 不再存储在属性中）
     */
    private function getAiServiceToken(): string
    {
        return (string) Options::get('open_ai_key', '');
    }

    // -------------------------------------------------------
    //  辅助方法
    // -------------------------------------------------------

    /**
     * 解析用户身份
     *
     * 已登录用户：通过 user_token 解析 uid
     * 游客：通过 guest_token 标识
     *
     * @return array ['uid' => int, 'guest_token' => ?string, 'is_guest' => bool]
     */
    private function resolveUserId(Request $request): array
    {
        $uid = 0;
        $this->requireUserFromToken($request, new \Slim\Psr7\Response(), $uid, false);

        if ($uid > 0) {
            return ['uid' => $uid, 'guest_token' => null, 'is_guest' => false];
        }

        $guestToken = trim((string) $this->getParam($request, 'guest_token', ''));
        return ['uid' => 0, 'guest_token' => $guestToken !== '' ? $guestToken : null, 'is_guest' => true];
    }

    /**
     * 检查游客是否被允许使用 AI（项目级配置）
     *
     * @param int $itemId 项目 ID
     * @return bool
     */
    private function isGuestAllowed(int $itemId): bool
    {
        if ($itemId <= 0) {
            return false; // 游客仅可在项目内使用
        }
        $config = ItemAiConfig::getConfig($itemId);
        return (bool) ($config['guest_enabled'] ?? 0);
    }

    /**
     * 获取客户端 IP（复用项目 IpHelper，获取失败兜底 0.0.0.0）
     *
     * @return string 客户端 IP
     */
    private function getClientIp(): string
    {
        $ip = trim(IpHelper::getClientIp());
        return $ip !== '' ? $ip : '0.0.0.0';
    }

    /**
     * 游客 IP 限流：滑动窗口内最多 100 次 / 60 秒
     *
     * 开源版无积分配额，用 IP 计数限流防 LLM 成本滥用。
     *
     * @param string $ip 客户端 IP
     * @return bool true=允许访问，false=已命中限流
     */
    private function checkGuestRateLimit(string $ip): bool
    {
        $cache = CacheManager::getInstance();
        $key   = 'agent_guest_rate:' . $ip;
        $count = (int) $cache->get($key);
        if ($count >= 100) {
            return false;
        }
        $cache->set($key, $count + 1, 60);
        return true;
    }

    /**
     * 检查 AI 功能是否启用
     *
     * @param int $itemId 项目 ID（0 = 全局会话）
     * @return array ['enabled' => bool, 'message' => string, 'welcome_message' => string, 'config' => array]
     */
    private function checkAiEnabled(int $itemId): array
    {
        // 系统级：必须配置了 AI 服务地址和 Token
        if (!$this->aiServiceUrl || !$this->getAiServiceToken()) {
            return [
                'enabled'         => false,
                'message'         => 'AI 服务未配置，请联系管理员',
                'welcome_message' => '',
                'config'          => [],
            ];
        }

        // 全局会话（item_id=0）：不检查项目级配置
        if ($itemId <= 0) {
            return [
                'enabled'         => true,
                'message'         => '',
                'welcome_message' => $this->aiWelcomeMessage ?: '欢迎使用 ShowDoc AI 助手，有什么可以帮你的？',
                'config'          => [],
            ];
        }

        // 项目级：检查项目是否存在
        $item = Item::findById($itemId);
        if (!$item) {
            return [
                'enabled'         => false,
                'message'         => '项目不存在',
                'welcome_message' => '',
                'config'          => [],
            ];
        }

        // 检查项目级 AI 配置
        // enabled 字段保留但不再用于阻止登录用户使用 AI（仅影响游客）
        $config = ItemAiConfig::getConfig($itemId);

        // 项目存在即视为 enabled（对登录用户）
        // enabled 字段不再作为 on/off 开关

        // 项目级欢迎语优先于全局
        $welcomeMessage = $config['welcome_message']
            ?: $this->aiWelcomeMessage
            ?: '欢迎使用 ShowDoc AI 助手，有什么可以帮你的？';

        return [
            'enabled'         => true,
            'message'         => '',
            'welcome_message' => $welcomeMessage,
            'config'          => $config,
        ];
    }

    /**
     * 获取项目类型字符串
     *
     * @param int $itemId
     * @return string 'regular' | 'single_page' | 'runapi' | 'table' | 'whiteboard' | 'kanban'
     */
    private function getItemType(int $itemId): string
    {
        if ($itemId <= 0) {
            return 'regular';
        }

        $item = Item::findById($itemId);
        if (!$item) {
            return 'regular';
        }

        // item_type: 1=常规, 2=单页, 3=RunApi, 4=表格, 5=白板, 6=看板
        $type = (int) ($item->item_type ?? 1);
        switch ($type) {
            case 2:
                return 'single_page';
            case 3:
                return 'runapi';
            case 4:
                return 'table';
            case 5:
                return 'whiteboard';
            case 6:
                return 'kanban';
            default:
                return 'regular';
        }
    }

    /**
     * 获取会话列表（支持已登录和游客）
     *
     * @param int    $uid
     * @param string $guestToken
     * @param int    $itemId
     * @return array
     */
    private function getSessionListForUser(int $uid, string $guestToken, int $itemId): array
    {
        if ($uid > 0) {
            return AiChatSession::getSessionList($uid, $itemId);
        }

        // 游客会话列表
        if ($guestToken === '' || $itemId <= 0) {
            return [];
        }

        $sessions = DB::table(AiChatSession::TABLE)
            ->where('guest_token', $guestToken)
            ->where('item_id', $itemId)
            ->where('is_del', 0)
            ->orderBy('last_message_at', 'desc')
            ->limit(50)
            ->get()
            ->all();

        if (empty($sessions)) {
            return [];
        }

        $sessionIds = array_map(function ($s) {
            return (int) $s->id;
        }, $sessions);

        $firstMessages = AiChatMessage::getFirstUserMessagesBatch($sessionIds);
        $messageCounts = AiChatMessage::getMessageCountsBatch($sessionIds);

        $result = [];
        foreach ($sessions as $session) {
            $sessionId = (int) $session->id;
            $title = '';
            $firstMsg = $firstMessages[$sessionId] ?? null;
            if ($firstMsg && !empty($firstMsg['content'])) {
                $title = mb_substr($firstMsg['content'], 0, 20);
                if (mb_strlen($firstMsg['content']) > 20) {
                    $title .= '...';
                }
            }

            $result[] = [
                'session_id'      => $sessionId,
                'title'           => $title,
                'last_message_at' => (int) $session->last_message_at,
                'created_at'      => (int) $session->created_at,
                'message_count'   => $messageCounts[$sessionId] ?? 0,
            ];
        }

        return $result;
    }

    // -------------------------------------------------------
    //  会话管理 API
    // -------------------------------------------------------

    /**
     * POST /api/ai/session/load
     *
     * 加载会话列表及当前会话历史消息。
     */
    public function sessionLoad(Request $request, Response $response): Response
    {
        $itemId    = (int) $this->getParam($request, 'item_id', 0);
        $sessionId = (int) $this->getParam($request, 'session_id', 0);

        // 解析用户身份
        $identity = $this->resolveUserId($request);
        $uid         = $identity['uid'];
        $guestToken  = $identity['guest_token'] ?? '';
        $isGuest     = $identity['is_guest'];

        // 全局会话仅已登录用户可用
        if ($itemId <= 0 && $isGuest) {
            return $this->error($response, 10102, '请先登录');
        }

        // 游客必须有 guest_token 且长度足够（防刷）
        if ($isGuest && ($guestToken === '' || $guestToken === null || strlen($guestToken) < 8)) {
            return $this->error($response, 10101, '缺少游客标识，请刷新页面重试');
        }

        // 项目级：检查项目访问权限
        if ($itemId > 0) {
            if (!$this->checkItemVisit($uid, $itemId)) {
                return $this->error($response, 10303, '您没有访问该项目的权限');
            }
        }

        // 游客项目级开关检查（必须在项目访问权限检查之后）
        if ($isGuest && !$this->isGuestAllowed($itemId)) {
            return $this->error($response, 10101, '游客暂不可使用 AI 助手');
        }

        // 检查 AI 是否启用
        $aiCheck = $this->checkAiEnabled($itemId);
        if (!$aiCheck['enabled']) {
            return $this->error($response, 10101, $aiCheck['message']);
        }

        // 确定加载的会话
        $session = null;

        if ($sessionId > 0) {
            // 指定会话 ID：验证归属
            $row = DB::table(AiChatSession::TABLE)
                ->where('id', $sessionId)
                ->where('is_del', 0)
                ->first();

            if (!$row) {
                return $this->error($response, 10101, '会话不存在');
            }

            // 归属校验：uid/guest_token
            if ($isGuest) {
                if ($row->guest_token !== $guestToken) {
                    return $this->error($response, 10101, '会话不存在');
                }
            } else {
                if ((int) $row->uid !== $uid) {
                    return $this->error($response, 10101, '会话不存在');
                }
            }

            // 归属校验：item_id（防止跨项目会话串用）
            if ($itemId > 0 && (int) $row->item_id !== $itemId) {
                return $this->error($response, 10101, '会话不存在');
            }

            $session = (array) $row;
        } else {
            // 不指定：获取最新活跃会话
            if ($isGuest) {
                $session = AiChatSession::getActiveSessionForGuest($guestToken, $itemId);
            } else {
                $session = AiChatSession::getActiveSession($uid, $itemId);
            }
        }

        // 获取会话列表
        $sessions = $this->getSessionListForUser($uid, $guestToken, $itemId);

        // 获取消息
        $messages = [];
        if ($session) {
            $rawMessages = AiChatMessage::getMessages((int) $session['id'], 40, $uid);
            foreach ($rawMessages as $msg) {
                $item = [
                    'id'         => (int) $msg['id'],
                    'role'       => $msg['role'],
                    'content'    => $msg['content'],
                    'feedback'   => (int) ($msg['feedback'] ?? 0),
                    'created_at' => (int) $msg['created_at'],
                ];
                $messages[] = $item;
            }
        }

        // 判断编辑权限
        $canEdit = false;
        if (!$isGuest && $itemId > 0) {
            $canEdit = $this->checkItemEdit($uid, $itemId);
        }

        return $this->success($response, [
            'session_id'      => $session ? (int) $session['id'] : 0,
            'sessions'        => $sessions,
            'messages'        => $messages,
            'welcome_message' => $aiCheck['welcome_message'],
            'can_edit'        => $canEdit,
        ]);
    }

    /**
     * POST /api/ai/session/new
     *
     * 创建新会话。
     */
    public function sessionNew(Request $request, Response $response): Response
    {
        $itemId = (int) $this->getParam($request, 'item_id', 0);

        // 解析用户身份
        $identity    = $this->resolveUserId($request);
        $uid         = $identity['uid'];
        $guestToken  = $identity['guest_token'] ?? '';
        $isGuest     = $identity['is_guest'];

        // 全局会话仅已登录用户
        if ($itemId <= 0 && $isGuest) {
            return $this->error($response, 10102, '请先登录');
        }

        // 游客必须有 guest_token 且长度足够（防刷）
        if ($isGuest && ($guestToken === '' || $guestToken === null || strlen($guestToken) < 8)) {
            return $this->error($response, 10101, '缺少游客标识，请刷新页面重试');
        }

        // 项目级：检查项目访问权限
        if ($itemId > 0) {
            if (!$this->checkItemVisit($uid, $itemId)) {
                return $this->error($response, 10303, '您没有访问该项目的权限');
            }
        }

        // 游客项目级开关检查（必须在项目访问权限检查之后）
        if ($isGuest && !$this->isGuestAllowed($itemId)) {
            return $this->error($response, 10101, '游客暂不可使用 AI 助手');
        }

        // 检查 AI 是否启用
        $aiCheck = $this->checkAiEnabled($itemId);
        if (!$aiCheck['enabled']) {
            return $this->error($response, 10101, $aiCheck['message']);
        }

        // 创建会话
        $newSessionId = AiChatSession::createSession($uid, $itemId, $isGuest ? $guestToken : null);

        if ($newSessionId <= 0) {
            return $this->error($response, 10101, '创建会话失败');
        }

        // 清理超出上限的旧会话
        AiChatSession::cleanOldSessions($uid, $itemId, 50, $isGuest ? $guestToken : null);

        return $this->success($response, [
            'session_id' => $newSessionId,
        ]);
    }

    /**
     * POST /api/ai/session/reset
     *
     * 清空会话消息，保留 session。
     */
    public function sessionReset(Request $request, Response $response): Response
    {
        $sessionId = (int) $this->getParam($request, 'session_id', 0);
        if ($sessionId <= 0) {
            return $this->error($response, 10101, '会话ID不能为空');
        }

        // 验证会话归属
        $identity = $this->resolveUserId($request);
        $session = DB::table(AiChatSession::TABLE)
            ->where('id', $sessionId)
            ->where('is_del', 0)
            ->first();

        if (!$session) {
            return $this->error($response, 10101, '会话不存在');
        }

        if ($identity['is_guest']) {
            if ($session->guest_token !== ($identity['guest_token'] ?? '')) {
                return $this->error($response, 10101, '会话不存在');
            }
        } else {
            if ((int) $session->uid !== $identity['uid']) {
                return $this->error($response, 10101, '会话不存在');
            }
        }

        $result = AiChatSession::resetSession(
            $sessionId,
            $identity['uid'],
            $identity['is_guest'] ? ($identity['guest_token'] ?? null) : null
        );
        if (!$result) {
            return $this->error($response, 10101, '重置会话失败');
        }

        return $this->success($response, null);
    }

    /**
     * POST /api/ai/session/list
     *
     * 获取当前作用域下的会话列表。
     */
    public function sessionList(Request $request, Response $response): Response
    {
        $itemId = (int) $this->getParam($request, 'item_id', 0);

        $identity    = $this->resolveUserId($request);
        $uid         = $identity['uid'];
        $guestToken  = $identity['guest_token'] ?? '';
        $isGuest     = $identity['is_guest'];

        // 全局会话仅已登录用户
        if ($itemId <= 0 && $isGuest) {
            return $this->error($response, 10102, '请先登录');
        }

        // 项目级：检查访问权限
        if ($itemId > 0) {
            if (!$this->checkItemVisit($uid, $itemId)) {
                return $this->error($response, 10303, '您没有访问该项目的权限');
            }
        }

        // 游客必须有 guest_token 且长度足够（防刷）
        if ($isGuest && ($guestToken === '' || $guestToken === null || strlen($guestToken) < 8)) {
            return $this->error($response, 10101, '缺少游客标识，请刷新页面重试');
        }

        // 游客项目级开关检查
        if ($isGuest && !$this->isGuestAllowed($itemId)) {
            return $this->error($response, 10101, '游客暂不可使用 AI 助手');
        }

        $sessions = $this->getSessionListForUser($uid, $guestToken, $itemId);

        return $this->success($response, [
            'sessions' => $sessions,
        ]);
    }

    /**
     * POST /api/ai/session/delete
     *
     * 删除指定会话。
     */
    public function sessionDelete(Request $request, Response $response): Response
    {
        $sessionId = (int) $this->getParam($request, 'session_id', 0);
        if ($sessionId <= 0) {
            return $this->error($response, 10101, '会话ID不能为空');
        }

        // 验证会话归属
        $identity = $this->resolveUserId($request);
        $session = DB::table(AiChatSession::TABLE)
            ->where('id', $sessionId)
            ->where('is_del', 0)
            ->first();

        if (!$session) {
            return $this->error($response, 10101, '会话不存在');
        }

        if ($identity['is_guest']) {
            if ($session->guest_token !== ($identity['guest_token'] ?? '')) {
                return $this->error($response, 10101, '会话不存在');
            }
        } else {
            if ((int) $session->uid !== $identity['uid']) {
                return $this->error($response, 10101, '会话不存在');
            }
        }

        $result = AiChatSession::deleteSession(
            $sessionId,
            $identity['uid'],
            $identity['is_guest'] ? ($identity['guest_token'] ?? null) : null
        );
        if (!$result) {
            return $this->error($response, 10101, '删除会话失败');
        }

        return $this->success($response, null);
    }

    // -------------------------------------------------------
    //  配置 / 反馈 API
    // -------------------------------------------------------

    /**
     * POST /api/ai/config
     *
     * 获取当前项目/全局的 AI 配置信息。
     */
    public function config(Request $request, Response $response): Response
    {
        $itemId = (int) $this->getParam($request, 'item_id', 0);

        $identity = $this->resolveUserId($request);
        $uid      = $identity['uid'];
        $isGuest  = $identity['is_guest'];

        // 全局会话仅已登录用户
        if ($itemId <= 0 && $isGuest) {
            return $this->error($response, 10102, '请先登录');
        }

        // 对话框默认折叠状态（项目级配置，全局默认折叠）
        $dialogCollapsed = $itemId > 0
            ? (int) (ItemAiConfig::getConfig($itemId)['dialog_collapsed'] ?? 1)
            : 1;

        // 项目级：检查访问权限
        if ($itemId > 0) {
            if (!$this->checkItemVisit($uid, $itemId)) {
                return $this->error($response, 10303, '您没有访问该项目的权限');
            }
        }

        // 游客项目级开关检查
        if ($isGuest && !$this->isGuestAllowed($itemId)) {
            return $this->success($response, [
                'enabled'          => false,
                'welcome_message'  => '',
                'can_edit'         => false,
                'item_type'        => $this->getItemType($itemId),
                'is_guest'         => true,
                'dialog_collapsed' => $dialogCollapsed,
            ]);
        }

        // 检查 AI 是否启用（全局：AI 服务是否配置；项目级：项目是否存在）
        $aiCheck = $this->checkAiEnabled($itemId);
        if (!$aiCheck['enabled']) {
            return $this->success($response, [
                'enabled'          => false,
                'welcome_message'  => '',
                'can_edit'         => false,
                'item_type'        => $this->getItemType($itemId),
                'is_guest'         => $isGuest,
                'dialog_collapsed' => $dialogCollapsed,
            ]);
        }

        // 判断编辑权限
        $canEdit = false;
        if (!$isGuest && $itemId > 0) {
            $canEdit = $this->checkItemEdit($uid, $itemId);
        }

        // 开源版无积分/配额：仅返回功能性字段（含游客开关 guest_enabled）
        $guestEnabled = false;
        if ($itemId > 0) {
            $guestEnabled = $this->isGuestAllowed($itemId);
        }

        $result = [
            'enabled'          => true,
            'welcome_message'  => $aiCheck['welcome_message'],
            'can_edit'         => $canEdit,
            'item_type'        => $this->getItemType($itemId),
            'is_guest'         => $isGuest,
            'guest_enabled'    => $guestEnabled,
            'dialog_collapsed' => $dialogCollapsed,
        ];

        return $this->success($response, $result);
    }

    /**
     * POST /api/ai/feedback
     *
     * 更新消息反馈（1=赞，2=踩，0=取消）。
     */
    public function feedback(Request $request, Response $response): Response
    {
        $msgId    = (int) $this->getParam($request, 'msg_id', 0);
        $feedback = (int) $this->getParam($request, 'feedback', 0);

        if ($msgId <= 0) {
            return $this->error($response, 10101, '消息ID不能为空');
        }

        if (!in_array($feedback, [0, 1, 2], true)) {
            return $this->error($response, 10101, '无效的反馈值');
        }

        // 验证消息归属：通过 session 归属到当前用户
        $identity = $this->resolveUserId($request);

        $message = DB::table(AiChatMessage::TABLE)
            ->where('id', $msgId)
            ->first();

        if (!$message) {
            return $this->error($response, 10101, '消息不存在');
        }

        // 查找对应的 session，验证归属
        $session = DB::table(AiChatSession::TABLE)
            ->where('id', (int) $message->session_id)
            ->where('is_del', 0)
            ->first();

        if (!$session) {
            return $this->error($response, 10101, '会话不存在');
        }

        if ($identity['is_guest']) {
            if ($session->guest_token !== ($identity['guest_token'] ?? '')) {
                return $this->error($response, 10101, '无权操作');
            }
        } else {
            if ((int) $session->uid !== $identity['uid']) {
                return $this->error($response, 10101, '无权操作');
            }
        }

        // Fix L4: 项目访问权限校验（与 sessionLoad/agent 一致）
        $sessionItemId = (int) $session->item_id;
        if ($sessionItemId > 0 && !$this->checkItemVisit($identity['uid'], $sessionItemId)) {
            return $this->error($response, 10303, '您没有访问该项目的权限');
        }

        $result = AiChatMessage::updateFeedback($msgId, $feedback, (int) $message->session_id);
        if (!$result) {
            return $this->error($response, 10101, '更新反馈失败');
        }

        return $this->success($response, null);
    }

    // -------------------------------------------------------
    //  Agent 循环（T4b 实现）
    // -------------------------------------------------------

    /**
     * POST /api/ai/agent
     *
     * 发送消息，SSE 流式响应。Agent 循环：LLM 调用 → 工具调用 → 循环。
     */
    public function agent(Request $request, Response $response): Response
    {

        // ── 1. 解析参数 ──────────────────────────────────────
        $message   = trim((string) $this->getParam($request, 'message', ''));
        $sessionId = (int) $this->getParam($request, 'session_id', 0);
        $itemId    = (int) $this->getParam($request, 'item_id', 0);
        $pageId    = (int) $this->getParam($request, 'page_id', 0);
        $editorContent   = (string) $this->getParam($request, 'editor_content', '');
        $currentPage     = (string) $this->getParam($request, 'current_page', '');
        $regenerateFrom  = (int) $this->getParam($request, 'regenerate_from_msg_id', 0);
        $userToken = trim((string) $this->getParam($request, 'user_token', ''));
        $guestToken = trim((string) $this->getParam($request, 'guest_token', ''));
        // 当前 turn 的取消令牌：将 cancel 标记隔离到单轮，避免停止后下一条消息被滞后的
        // cancelAgent 误杀。前端每轮生成新值传入；未传时为空串（按 session 维度，向后兼容）。
        $turnToken = trim((string) $this->getParam($request, 'turn_token', ''));

        // 从 current_page 路由解析 page_id / item_id（补充前端未传或传错的值）
        $pageContext = $this->parsePageContext($currentPage);
        if ($pageId <= 0 && !empty($pageContext['page_id'])) {
            $pageId = $pageContext['page_id'];
        }
        if ($itemId <= 0 && !empty($pageContext['item_id'])) {
            $itemId = $pageContext['item_id'];
        }

        // ── 2. 基本校验 ──────────────────────────────────────
        if ($message === '' && $regenerateFrom <= 0) {
            return $this->error($response, 10101, '消息不能为空');
        }
        if ($sessionId <= 0) {
            return $this->error($response, 10101, '会话ID不能为空');
        }

        // Fix 1.4: 用户消息长度限制，防止超长输入导致配额耗尽/DoS
        if ($message !== '') {
            $maxMessageLen = (int) Options::get('ai_max_message_length', 8000);
            if ($maxMessageLen > 0 && mb_strlen($message) > $maxMessageLen) {
                return $this->error($response, 10101, '消息过长，请精简后重试（上限 ' . $maxMessageLen . ' 字符）');
            }
        }

        // ── 3. 解析用户身份 ──────────────────────────────────
        $identity = $this->resolveUserId($request);
        $uid      = $identity['uid'];
        $isGuest  = $identity['is_guest'];

        // 全局会话仅已登录用户
        if ($itemId <= 0 && $isGuest) {
            return $this->error($response, 10102, '请先登录');
        }

        // 游客必须有 guest_token 且长度足够（防刷）
        if ($isGuest && ($guestToken === '' || strlen($guestToken) < 8)) {
            return $this->error($response, 10101, '缺少游客标识，请刷新页面重试');
        }

        // ── 4. 验证会话归属 ──────────────────────────────────
        $session = DB::table(AiChatSession::TABLE)
            ->where('id', $sessionId)
            ->where('is_del', 0)
            ->first();

        if (!$session) {
            return $this->error($response, 10101, '会话不存在');
        }

        if ($isGuest) {
            if ($session->guest_token !== $guestToken) {
                return $this->error($response, 10101, '会话不存在');
            }
        } else {
            if ((int) $session->uid !== $uid) {
                return $this->error($response, 10101, '会话不存在');
            }
        }

        $sessionItemId = (int) $session->item_id;

        // ── 5. 检查 AI 是否启用 ──────────────────────────────
        $aiCheck = $this->checkAiEnabled($sessionItemId);
        if (!$aiCheck['enabled']) {
            return $this->error($response, 10101, $aiCheck['message']);
        }

        // ── 6. 项目访问权限检查 ──────────────────────────────
        if ($sessionItemId > 0 && !$this->checkItemVisit($uid, $sessionItemId)) {
            return $this->error($response, 10303, '您没有访问该项目的权限');
        }

        // 游客项目级开关检查
        if ($isGuest && !$this->isGuestAllowed($sessionItemId)) {
            return $this->error($response, 10101, '游客暂不可使用 AI 助手');
        }

        // ── 游客 IP 限流（开源版无积分配额，用 IP 限流防 LLM 成本滥用）──
        if ($isGuest) {
            $ip = $this->getClientIp();
            if (!$this->checkGuestRateLimit($ip)) {
                return $this->error($response, 10429, '操作过于频繁，请稍后再试');
            }
        }

        // ── 7. 保存 user 消息（重新生成时跳过） ──────────────
        if ($regenerateFrom <= 0 && $message !== '') {
            AiChatMessage::addMessage($sessionId, 'user', $message);
        }

        // ── 8. 重新生成时删除旧消息（regenerateFrom 及之后，含旧 assistant 回复）
        if ($regenerateFrom > 0) {
            DB::table(AiChatMessage::TABLE)
                ->where('session_id', $sessionId)
                ->where('id', '>=', $regenerateFrom)
                ->delete();
        }

        // ── 9. 初始化 SSE 输出 ─────────────────────────────
        $this->initSseHeaders();
        $this->clearCancelled($sessionId, $turnToken);
        $this->consecutiveToolCounts = [];
        $this->searchedItemIds = [];

        // ── 10. 构建 LLM 上下文 ─────────────────────────────
        if ($regenerateFrom > 0) {
            $dbMessages = AiChatMessage::getMessagesBefore($sessionId, $regenerateFrom, 40, $uid);
        } else {
            $dbMessages = AiChatMessage::getMessages($sessionId, 40, $uid);
        }
        $llmMessages = $this->buildLlmMessages($dbMessages);

        // ── 12. 构建上下文参数 ──────────────────────────────
        $itemName = '';
        $pageTitle = '';
        $itemType = 'regular';
        if ($sessionItemId > 0) {
            $itemObj = Item::findById($sessionItemId);
            $itemName = $itemObj ? ((string) ($itemObj->item_name ?? '')) : '';
            $itemType = $this->getItemType($sessionItemId);
        }
        if ($pageId > 0) {
            $pageTitle = $this->getPageTitle($pageId, $sessionItemId);
        }

        $canEdit = !$isGuest && $sessionItemId > 0 && $this->checkItemEdit($uid, $sessionItemId);
        $role = $this->resolveRole($uid, $sessionItemId, $isGuest);

        $promptParams = [
            'item_id'         => $sessionItemId,
            'item_name'       => $itemName,
            'page_id'         => $pageId,
            'page_title'      => $pageTitle,
            'editor_content'  => $editorContent,
            'is_guest'        => $isGuest,
            'uid'             => $isGuest ? 0 : $uid,
            'can_edit'        => $canEdit,
            'role'            => $role,
            'item_type'       => $itemType,
            'current_page'    => $currentPage,
        ];

        // System prompt
        $systemPrompt = $this->buildSystemPrompt($promptParams);
        array_unshift($llmMessages, ['role' => 'system', 'content' => $systemPrompt]);

        // ── 13. 构建工具列表 ────────────────────────────────
        // 注：editor_content 已在 buildSystemPrompt 中通过 <user_context> 标签安全注入，
        // 不再作为独立 user message 注入（避免双重注入）
        $tools = $this->buildToolsList($role);

        // ── 14. MCP tokenInfo ────────────────────────────────
        $mcpTokenInfo = $this->buildMcpTokenInfo($uid, $userToken, $sessionItemId, $isGuest, $guestToken);

        // ── 15. Agent 循环 ──────────────────────────────────
        // 开源版：工具轮次上限取自 ai_tool_rounds 选项（绝对上限 30，防死循环）
        $maxToolRounds = min((int) Options::get('ai_tool_rounds', 20), 30);
        $toolRound     = 0;
        $fullContent   = ''; // 最终回答内容
        $mcpWriteOps   = []; // MCP 写操作追踪

        while ($toolRound < $maxToolRounds) {
            // 检查取消标记
            if ($this->isCancelled($sessionId, $turnToken)) {
                if ($fullContent === '') {
                    $fullContent = '（已停止生成）';
                    $this->sendSse('text', $fullContent);
                }
                break;
            }

            // 检查客户端是否已断开
            if (connection_aborted()) {
                break;
            }

            // 调用 LLM（流式打字机效果 + 引用延迟解析）
            [$content, $toolCalls, $finishReason] = $this->callLlmStream($llmMessages, $tools, $sessionItemId, $sessionId, $turnToken);

            // LLM 调用后再次检查客户端连接
            if (connection_aborted()) {
                break;
            }

            // LLM 级别错误已通过 SSE 发送，终止循环
            if ($finishReason === 'error') {
                break;
            }

            // 流式输出文本内容（解析引用标记）
            if ($content !== '') {
                // SSE 已在 callLlmStream 流式发送，此处仅做引用标记清理（不重复发 SSE）
                $cleanContent = $this->parseReferences($content, $sessionItemId, false);
                $fullContent .= $cleanContent;
            }

            // 无工具调用 → 结束循环
            if (empty($toolCalls) || $finishReason === 'stop') {
                break;
            }

            // 有工具调用 → 追加 assistant 消息到上下文
            $assistantMsg = ['role' => 'assistant', 'content' => $content ?: ''];
            $assistantMsg['tool_calls'] = [];
            foreach ($toolCalls as $tc) {
                $assistantMsg['tool_calls'][] = [
                    'id'       => $tc['id'],
                    'type'     => 'function',
                    'function' => [
                        'name'      => $tc['function']['name'],
                        'arguments' => is_array($tc['function']['arguments'])
                            ? json_encode($tc['function']['arguments'], JSON_UNESCAPED_UNICODE)
                            : (string) $tc['function']['arguments'],
                    ],
                ];
            }
            $llmMessages[] = $assistantMsg;

            // 执行每个工具调用
            foreach ($toolCalls as $tc) {
                $toolName = $tc['function']['name'];
                $toolArgs = is_array($tc['function']['arguments']) ? $tc['function']['arguments'] : [];
                $toolCallId = $tc['id'];

                // 🔴 安全校验：白名单
                if (!$this->isToolAllowed($toolName, $role)) {
                    $this->sendSse('status', "工具 {$toolName} 无权使用");
                    $llmMessages[] = [
                        'role'         => 'tool',
                        'content'     => json_encode(['error' => "工具 {$toolName} 无权使用"], JSON_UNESCAPED_UNICODE),
                        'tool_call_id' => $toolCallId,
                    ];
                    continue;
                }

                // 🔴 连续同类检查
                if ($this->isConsecutiveLimit($toolName)) {
                    $this->sendSse('status', '连续调用同一工具次数过多，已跳过');
                    $llmMessages[] = [
                        'role'         => 'tool',
                        'content'     => json_encode(['error' => '连续调用同一工具次数已达上限，请使用已有信息回答用户'], JSON_UNESCAPED_UNICODE),
                        'tool_call_id' => $toolCallId,
                    ];
                    continue;
                }

                // 🔴 项目内搜索优先校验（search_help_docs 兜底拦截，从主版同步）：
                // 会话处于项目内（item_id>0）且本轮尚未对当前项目执行过 search_pages 时，
                // 拦截 search_help_docs 并提示模型先搜当前项目（不依赖模型自觉）
                if ($toolName === 'search_help_docs'
                    && $sessionItemId > 0
                    && !isset($this->searchedItemIds[$sessionItemId])
                ) {
                    $this->sendSse('status', '请先在当前项目内搜索');
                    $llmMessages[] = [
                        'role'         => 'tool',
                        'content'     => json_encode([
                            'error' => "当前会话处于项目内（item_id={$sessionItemId}），请先使用 search_pages 并传入 item_id={$sessionItemId} 搜索当前项目文档；仅当项目内搜索无相关结果时，才可调用 search_help_docs。请现在先用 search_pages 搜索当前项目。",
                        ], JSON_UNESCAPED_UNICODE),
                        'tool_call_id' => $toolCallId,
                    ];
                    continue;
                }

                // 重复调用拦截（从主版同步，温和不阻断）：同一工具+等价参数且上次执行成功时，
                // 跳过实际执行，返回提示让 LLM 直接复用上下文中的已有结果（省 token）；
                // 上次为 error 时不拦截，允许重试
                $prevExec = $this->findExecutedToolCall($toolName, $toolArgs);
                if ($prevExec !== null && !$prevExec['is_error']) {
                    $this->sendSse('status', "工具 {$toolName} 参数未变，复用已获取的结果");
                    $llmMessages[] = [
                        'role'         => 'tool',
                        'content'     => json_encode([
                            'notice' => "该调用与之前的调用（{$prevExec['tool_call_id']}）使用完全相同的工具和参数，其结果已在上下文中。请直接使用已有结果回答，不要重复调用；如需不同结果请调整参数。",
                        ], JSON_UNESCAPED_UNICODE),
                        'tool_call_id' => $toolCallId,
                    ];
                    continue;
                }

                // 推送状态
                $statusText = $this->getToolStatusText($toolName);
                $this->sendSse('status', $statusText);

                // 调用 MCP 工具
                $result = $this->callMcpTool($toolName, $toolArgs, $mcpTokenInfo);

                // 记录本次调用结果（供重复调用检测：成功则后续同参调用拦截，失败允许重试）
                $this->rememberToolCallResult($toolName, $toolArgs, $toolCallId, $this->isToolResultError($result));

                // 记录项目内搜索已执行（供 search_help_docs 兜底校验：先搜过项目才允许搜帮助文档）
                if ($toolName === 'search_pages' && $sessionItemId > 0
                    && (int) ($toolArgs['item_id'] ?? 0) === $sessionItemId
                ) {
                    $this->searchedItemIds[$sessionItemId] = true;
                }

                // 追踪 MCP 写操作（用于循环结束后发送 changes 事件）
                $writeOp = $this->trackMcpWriteOp($toolName, $toolArgs, $result, $sessionItemId);
                if ($writeOp !== null) {
                    $mcpWriteOps[] = $writeOp;
                }

                // 结果裁剪（从主版同步）：≤5k 原样；>5k 前 3k + 尾 1k + 中间省略标记（省 token）
                $result = $this->trimToolResult($result);

                // 追加 tool 结果到上下文
                $llmMessages[] = [
                    'role'         => 'tool',
                    'content'     => $result,
                    'tool_call_id' => $toolCallId,
                ];
            }

            // ── 搜索结果引用格式提醒 ────────────────────────
            // 检测本轮是否有搜索工具调用，若有则在下一轮 LLM 上下文中
            // 追加一段提醒，要求用 [[page:ID|标题]] 引用格式而非表格
            $hasSearchTool = false;
            foreach ($toolCalls as $tc) {
                $tName = $tc['function']['name'] ?? '';
                if ($tName === 'search_pages' || $tName === 'search_all_pages') {
                    $hasSearchTool = true;
                    break;
                }
            }
            if ($hasSearchTool && !isset($this->injectedSystemReminders['search_format'])) {
                // 去重（从主版同步）：同一轮次内该提醒只追加一次，避免逐轮累积重复 system 消息浪费 token
                $this->injectedSystemReminders['search_format'] = true;
                $llmMessages[] = [
                    'role'    => 'system',
                    'content' => '【搜索结果回复格式提醒】你刚刚执行了文档搜索。请务必使用 [[page:页面ID|页面标题]] 引用格式提及搜索到的文档，不要使用表格、编号列表或其他格式罗列搜索结果。正确示例：相关文档请查看 [[page:123|接口认证说明]] 和 [[page:456|错误码大全]]。',
                ];
            }

            $toolRound++;
        }

        // ── 16. 循环结束：保存 assistant 消息 ────────────────
        // 客户端已断开时，跳过后续 DB 写入和 SSE 输出，直接退出
        $clientDisconnected = connection_aborted() !== 0;

        // ── 15.1 轮次耗尽兜底：基于已收集的工具结果让 LLM 总结回答 ──
        if ($fullContent === '' && !$clientDisconnected && $toolRound >= $maxToolRounds) {
            // 检查是否有工具调用记录（即循环确实因轮次耗尽而退出）
            $hasToolCalls = false;
            foreach ($llmMessages as $msg) {
                if (($msg['role'] === 'tool' && !empty($msg['content']))
                    || ($msg['role'] === 'assistant' && !empty($msg['tool_calls']))) {
                    $hasToolCalls = true;
                    break;
                }
            }
            if ($hasToolCalls) {
                $this->sendSse('text', '[AI 正在整理回答...]');
                // Prompt cache 友好化（从主版同步）：复用完整 $llmMessages（保留首条 system prompt 与历史，
                // 前缀与循环内最后一次请求逐字节一致，命中缓存），仅在尾部追加总结指令
                $fallbackMessages = $llmMessages;
                $fallbackMessages[] = ['role' => 'system', 'content' => "之前的对话因工具调用轮次耗尽而中断，但你已经收集了大量信息。请根据已有的工具调用结果，直接用中文总结回答用户的问题。不要调用任何工具，只给出最终回答。如果信息不足以完整回答，请基于已有信息尽量回答，并说明哪些部分信息不足。"];
                try {
                    [$fallbackContent, , ] = $this->callLlmStream($fallbackMessages, [], $sessionItemId, $sessionId, $turnToken);
                    if (!empty($fallbackContent)) {
                        $fullContent = $fallbackContent;
                    }
                } catch (\Throwable $e) {
                    // 兜底调用失败，继续走下面的原始 fallback
                }
            }
        }
        // 最终兜底：兜底总结也失败或无工具结果时
        if ($fullContent === '' && !$clientDisconnected) {
            $fullContent = '抱歉，处理较复杂，请尝试更具体的问题。';
            $this->sendSse('text', $fullContent);
        }

        // --- 编辑器内容处理 ---
        // 必须在保存到 DB 之前完成，以便从 $fullContent 中移除 EDIT 标记。
        // 即使客户端断开也要提取，否则 EDIT 标记会被写入 DB。
        $editContent = $this->extractEditContent($fullContent);
        if ($editContent !== null) {
            $fullContent = $editContent['cleaned'];
        } else {
            // Fix TC-478: 无匹配 EDIT 对时，仍需清除残留的孤立标记，避免泄露到 DB
            $fullContent = preg_replace('/[<\x{FF1C}]{3}\s*EDIT_START\s*[>\x{FF1E}]{3}/u', '', $fullContent);
            $fullContent = preg_replace('/[<\x{FF1C}]{3}\s*EDIT_END\s*[>\x{FF1E}]{3}/u', '', $fullContent);
            $fullContent = trim($fullContent);
        }

        // ── 保存助手消息到 DB ──
        $assistantMsgId = 0;
        if ($fullContent !== '') {
            $assistantMsgId = AiChatMessage::addMessage($sessionId, 'assistant', $fullContent);
        }

        // 更新 session 最后活跃时间（在发送 [DONE] 前）
        AiChatSession::updateLastMessageAt($sessionId);

        // 懒清理
        AiChatSession::maybeCleanup($sessionId);

        // ── 发送剩余 SSE 事件 + [DONE] ────────────────────────
        if (!$clientDisconnected) {
            // 编辑器内容（通过 SSE 发送 edit 事件）
            if ($editContent !== null) {
                $this->sendSseEdit($editContent['content'], $pageId);
            }

            // --- MCP 写操作变更通知 ---
            if (!empty($mcpWriteOps)) {
                $this->sendSseChanges($mcpWriteOps);
            }

            // ── 16. 发送 message_id（让前端更新本地消息 ID） ──
            if ($assistantMsgId > 0) {
                $this->sendSse('message_id', (string) $assistantMsgId);
            }

            // ── 17. 发送 done ───────────────────────────────────
            $this->sendSseDone();
        }

        return $response;
    }

    /**
     * POST /api/ai/agent/cancel
     *
     * 中断当前正在进行的 agent 响应。
     */
    public function agentCancel(Request $request, Response $response): Response
    {
        $sessionId = (int) $this->getParam($request, 'session_id', 0);
        if ($sessionId <= 0) {
            return $this->error($response, 10101, '会话ID不能为空');
        }
        $turnToken = trim((string) $this->getParam($request, 'turn_token', ''));

        // 验证会话归属
        $identity = $this->resolveUserId($request);

        $session = DB::table(AiChatSession::TABLE)
            ->where('id', $sessionId)
            ->where('is_del', 0)
            ->first();

        if (!$session) {
            return $this->error($response, 10101, '会话不存在');
        }

        if ($identity['is_guest']) {
            if ($session->guest_token !== ($identity['guest_token'] ?? '')) {
                return $this->error($response, 10101, '会话不存在');
            }
        } else {
            if ((int) $session->uid !== $identity['uid']) {
                return $this->error($response, 10101, '会话不存在');
            }
        }

        // 标记取消（agent() 主循环若仍在运行，会在下一轮检测到并提前终止生成）
        $this->markCancelled($sessionId, $turnToken);

        return $this->success($response, null);
    }

}
