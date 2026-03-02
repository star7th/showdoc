<?php

namespace App\Common;

use App\Model\User;
use App\Model\UserToken;
use App\Common\Helper\IpHelper;
use App\Common\Helper\Security;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController
{
    /**
     * 获取请求参数（兼容旧版 ThinkPHP I 方法的行为）。
     * 
     * 参数获取优先级：路径参数 > POST Body（表单/JSON）> GET 参数
     * 
     * 兼容性说明：
     * - 支持表单提交（application/x-www-form-urlencoded）：通过 Slim BodyParsingMiddleware 自动解析到 getParsedBody()
     * - 支持 JSON 提交（application/json）：通过 Slim BodyParsingMiddleware 自动解析到 getParsedBody()
     * - 支持 GET 参数：通过 getQueryParams() 获取
     * - 支持路径参数：通过 getAttribute() 获取（如 /api/user/{id} 中的 id）
     * 
     * 与旧版 I 方法的对应关系：
     * - I("post.xxx") 或 I("xxx")（POST 请求）-> getParam($request, 'xxx')（从 getParsedBody 获取）
     * - I("get.xxx") 或 I("xxx")（GET 请求）-> getParam($request, 'xxx')（从 getQueryParams 获取）
     * - I("xxx")（JSON 请求）-> getParam($request, 'xxx')（从 getParsedBody 获取，BodyParsingMiddleware 已解析）
     * 
     * 根据默认值的类型做简单类型转换，减少调用处的显式 (string)/(int)。
     * 
     * 注意：此方法返回原始值，不进行 HTML 转义。遵循"在输出时转义"的最佳实践。
     * 如需输出到 HTML，请在输出时使用 htmlspecialchars() 进行转义。
     * 
     * @param Request $request 请求对象
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed 参数值（原始值，未转义）
     */
    protected function getParam(Request $request, string $key, $default = null)
    {
        // 1. 优先从路径参数获取（如 /api/user/{id} 中的 id）
        $value = $request->getAttribute($key);

        // 2. 如果路径参数不存在，从 POST Body 获取（包含表单和 JSON）
        // Slim 的 BodyParsingMiddleware 会自动解析：
        // - application/x-www-form-urlencoded -> 解析为数组
        // - application/json -> 解析为数组
        if ($value === null) {
            $parsedBody = $request->getParsedBody() ?: [];
            if (is_array($parsedBody) && array_key_exists($key, $parsedBody)) {
                $value = $parsedBody[$key];
            }
        }

        // 3. 如果 POST Body 中也没有，从 GET 参数获取
        if ($value === null) {
            $query = $request->getQueryParams();
            if (array_key_exists($key, $query)) {
                $value = $query[$key];
            }
        }

        if ($value === null) {
            return $default;
        }

        // 没有提供默认值时，不做强制类型转换
        if ($default === null) {
            return $value;
        }

        // 根据默认值类型做轻量级转换
        switch (gettype($default)) {
            case 'string':
                return (string) $value;
            case 'integer':
                // 如果是布尔值或者字符串 true ，则应该转为1 
                if (is_bool($value)) {
                    return $value ? 1 : 0;
                }
                return is_numeric($value) ? (int) $value : (int) $default;
            case 'double':
                return is_numeric($value) ? (float) $value : (float) $default;
            case 'boolean':
                // 常见布尔字符串处理
                if (is_bool($value)) {
                    return $value;
                }
                $lower = strtolower((string) $value);
                if (in_array($lower, ['1', 'true', 'on', 'yes'], true)) {
                    return true;
                }
                if (in_array($lower, ['0', 'false', 'off', 'no'], true)) {
                    return false;
                }
                return (bool) $default;
            case 'array':
                return is_array($value) ? $value : $default;
            default:
                return $value;
        }
    }

    /**
     * 检查是否是管理员
     *
     * @param Request $request 请求对象
     * @param Response|null $response 响应对象（用于返回错误）
     * @param bool $redirect 是否在未授权时返回错误响应
     * @return bool|Response 如果是管理员返回 true，否则返回 false 或错误响应
     */
    protected function checkAdmin(Request $request, ?Response $response = null, bool $redirect = true)
    {
        // 获取登录用户
        $loginUser = [];
        if ($response && ($error = $this->requireLoginUser($request, $response, $loginUser))) {
            if ($redirect) {
                return $this->error($response, 10103, '需要管理员权限');
            }
            return false;
        } elseif (!$response) {
            // 如果没有提供 response，使用 requireUserFromToken
            $uid = 0;
            $dummyResponse = new \Slim\Psr7\Response();
            $this->requireUserFromToken($request, $dummyResponse, $uid, false);
            if ($uid <= 0) {
                return false;
            }
            $user = \App\Model\User::findById($uid);
            if (!$user) {
                return false;
            }
            $loginUser = (array) $user;
        }

        // 检查 groupid 是否为 1（管理员，与旧代码逻辑一致：$login_user['groupid'] == 1）
        $groupid = $loginUser['groupid'] ?? 0;
        if ($groupid == 1) {
            return true;
        }

        if ($redirect && $response) {
            return $this->error($response, 10103, '需要管理员权限');
        }

        return false;
    }

    /**
     * 将大整数转换为字符串，避免 JavaScript 精度丢失问题。
     * 
     * JavaScript 的 Number.MAX_SAFE_INTEGER 是 9007199254740991 (2^53 - 1)，
     * 超过这个值的整数在 JavaScript 中会丢失精度。
     * 因此，将所有大于该值的整数转换为字符串，确保前端能正确处理。
     * 
     * 注意：只检查 JSON 的前三级（顶层、第一级和第二级嵌套），达到深度限制后退出递归，避免性能影响。
     * 
     * @param mixed $data 要处理的数据（可以是数组、对象或标量值）
     * @param int $depth 当前递归深度，默认为 0
     * @return mixed 处理后的数据
     */
    private function convertLargeIntegersToString($data, int $depth = 0)
    {
        // JavaScript 的最大安全整数：2^53 - 1 = 9007199254740991
        $maxSafeInteger = 9007199254740991;

        // 先检查是否是大整数（无论深度如何，都要转换大整数）
        if (is_int($data) && $data > $maxSafeInteger) {
            return (string) $data;
        }

        // 只处理前三级，达到深度限制后直接返回
        if ($depth >= 3) {
            return $data;
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = $this->convertLargeIntegersToString($value, $depth + 1);
            }
            return $result;
        } elseif (is_object($data)) {
            // 处理对象（stdClass 等）
            $result = new \stdClass();
            foreach ($data as $key => $value) {
                $result->$key = $this->convertLargeIntegersToString($value, $depth + 1);
            }
            return $result;
        } else {
            // 其他类型（字符串、浮点数、布尔值、null 等）保持不变
            return $data;
        }
    }

    /**
     * 返回 JSON 响应（底层通用封装）。
     * 
     * 注意：json_encode() 会自动转义特殊字符，所以 JSON 响应本身是安全的。
     * 但如果前端将 JSON 数据直接插入到 HTML DOM 中（如 innerHTML），
     * 前端需要自行进行 HTML 转义。
     * 
     * 在输出前会对 JSON 字符串进行敏感词替换（与旧代码 BaseController::sendResult 逻辑一致）。
     * 使用 JSON_UNESCAPED_UNICODE 确保中文字符不会被转义，保证替换的准确性。
     * 
     * 自动处理大整数：将所有大于 JavaScript MAX_SAFE_INTEGER (9007199254740991) 的整数
     * 转换为字符串，避免 JavaScript 精度丢失问题。
     */
    protected function json(Response $response, $data, int $status = 200): Response
    {
        // 自动将大整数转换为字符串，避免 JavaScript 精度丢失
        $data = $this->convertLargeIntegersToString($data);

        // JSON_UNESCAPED_UNICODE 参数是为了防止中文乱码（与旧代码一致）
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 开源版不需要敏感词过滤功能

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * 转义字符串用于 HTML 输出（防止 XSS 攻击）。
     * 
     * 用于需要输出到 HTML 内容的场景。
     * 
     * @param string $string 要转义的字符串
     * @return string 转义后的字符串
     */
    protected function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 转义字符串用于 HTML 属性（防止 XSS 攻击）。
     * 
     * 用于 HTML 属性值的场景，如 <input value="...">
     * 
     * @param string $string 要转义的字符串
     * @return string 转义后的字符串
     */
    protected function escapeHtmlAttr(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 转义字符串用于 URL 参数。
     * 
     * @param string $string 要转义的字符串
     * @return string 转义后的字符串
     */
    protected function escapeUrl(string $string): string
    {
        return urlencode($string);
    }

    /**
     * 转义字符串用于 JavaScript 代码。
     * 
     * @param string $string 要转义的字符串
     * @return string 转义后的字符串（JSON 编码）
     */
    protected function escapeJs(string $string): string
    {
        return json_encode($string, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 业务成功统一返回结构：
     * { "error_code": 0, "data": {...} }
     * 
     * @param Response $response 响应对象
     * @param mixed $data 返回数据，可以是数组、字符串或其他可序列化的类型
     * @param int $status HTTP 状态码
     * @return Response
     */
    protected function success(Response $response, $data = [], int $status = 200): Response
    {
        return $this->json($response, [
            'error_code' => 0,
            'data' => $data,
        ], $status);
    }

    /**
     * 业务错误统一返回结构：
     * { "error_code": <int>, "error_message": "<string>", ...extra }
     *
     * @param Response $response 响应对象
     * @param int $code 错误码
     * @param string|null $message 错误消息，如果为 null 或空字符串则从 ErrorCodes 配置中获取
     * @param int $status HTTP 状态码
     * @param array $extra 额外的响应数据
     * @return Response
     */
    protected function error(
        Response $response,
        int $code,
        ?string $message = null,
        int $status = 200,
        array $extra = []
    ): Response {
        // 如果未提供消息或消息为空，从配置中获取
        if ($message === null || $message === '') {
            $message = \App\Common\Config\ErrorCodes::getMessage($code);
        }

        $body = array_merge([
            'error_code' => $code,
            'error_message' => $message,
        ], $extra);

        return $this->json($response, $body, $status);
    }

    /**
     * 从请求中提取用户 Token（Header 优先，兼容 GET/POST 参数）。
     */
    protected function getToken(Request $request): ?string
    {
        // 1. X-API-Token 头
        $token = trim($request->getHeaderLine('X-API-Token'));
        if ($token !== '') {
            return $token;
        }

        // 2. Authorization: Bearer xxx
        $auth = $request->getHeaderLine('Authorization');
        if ($auth !== '') {
            if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
                return trim($m[1]);
            }
        }

        // 3. user_token 参数（POST/GET）
        $token = (string) $this->getParam($request, 'user_token', '');
        $token = trim($token);
        return $token !== '' ? $token : null;
    }

    /**
     * 从 user_token 中解析出当前用户 uid 的公共逻辑。
     *
     * @param int  $uid            解析出的用户 ID（引用传递）
     * @param bool $strictOnError  是否严格返回错误（登出等场景可用 false）
     *
     * @return Response|null  出错时返回 Response；成功时返回 null 并填充 $uid
     */
    protected function requireUserFromToken(
        Request $request,
        Response $response,
        int &$uid,
        bool $strictOnError = true
    ): ?Response {
        $token = $this->getToken($request);
        if ($token === null) {
            if ($strictOnError) {
                // 业务级错误：HTTP 始终 200，仅通过 error_code 表达未登录
                return $this->error($response, 10102, '未提供用户 Token');
            }
            $uid = 0;
            return null;
        }

        $tokenRow = UserToken::getToken($token);
        if (!$tokenRow || empty($tokenRow['uid'])) {
            if ($strictOnError) {
                return $this->error($response, 10102, 'Token 无效或已过期');
            }
            $uid = 0;
            return null;
        }

        $parsedUid = (int) ($tokenRow['uid'] ?? 0);
        if ($parsedUid <= 0) {
            if ($strictOnError) {
                return $this->error($response, 10102, 'Token 无效');
            }
            $uid = 0;
            return null;
        }

        $uid = $parsedUid;
        return null;
    }

    /**
     * 从 token 中获取当前登录用户的完整信息（等价于新世界的 checkLogin）。
     *
     * @param array $user          解析出的用户信息（引用传递，已去除密码/盐）
     * @param bool  $strictOnError 是否严格返回错误；为 false 时，失败仅使 $user 为空数组
     *
     * @return Response|null 出错时返回 Response；成功时返回 null 并填充 $user
     */
    protected function requireLoginUser(
        Request $request,
        Response $response,
        array &$user,
        bool $strictOnError = true
    ): ?Response {
        $uid = 0;
        if ($error = $this->requireUserFromToken($request, $response, $uid, $strictOnError)) {
            return $error;
        }

        if ($uid <= 0) {
            // 非严格模式，调用方自行判断
            $user = [];
            return null;
        }

        $userObj = User::findById($uid);
        if (!$userObj) {
            if ($strictOnError) {
                return $this->error($response, 10102, '用户不存在');
            }
            $user = [];
            return null;
        }

        $arr = (array) $userObj;
        unset($arr['password'], $arr['salt']);

        $user = $arr;
        return null;
    }

    /**
     * 判断某用户是否有项目管理权限（项目创建者、项目单独管理员、团队中的项目管理员，系统管理员）。
     *
     * @param int $uid     用户 ID
     * @param int $itemId  项目 ID
     *
     * @return bool
     */
    protected function checkItemManage(int $uid, int $itemId): bool
    {
        // 与旧代码逻辑一致：先检查 uid 是否存在
        if (!$uid) {
            return false;
        }
        $uid = (int) $uid;
        $itemId = (int) $itemId;

        // 检查是否是项目创建者（与旧代码逻辑一致：$item['uid'] && $item['uid'] == $uid）
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return false;
        }
        if ($item->uid && (int) $item->uid == $uid) {
            return true;
        }

        // 检查是否是项目单独管理员（member_group_id = 2）
        $member = DB::table('item_member')
            ->where('item_id', $itemId)
            ->where('uid', $uid)
            ->where('member_group_id', 2)
            ->first();
        if ($member) {
            return true;
        }

        // 检查是否是团队中的项目管理员
        $teamMember = DB::table('team_item_member')
            ->where('item_id', $itemId)
            ->where('member_uid', $uid)
            ->where('member_group_id', 2)
            ->first();
        if ($teamMember) {
            return true;
        }

        // 检查是否是系统管理员（与旧代码逻辑一致：checkAdmin(false)，直接检查用户 groupid）
        $user = \App\Model\User::findById($uid);
        if ($user && ($user->groupid ?? 0) == 1) {
            return true;
        }

        return false;
    }

    /**
     * 判断某用户是否有项目编辑权限（项目成员 member_group_id 为 1，是项目所在团队的成员并且成员权限为 1，
     * 以及项目管理者、创建者和系统管理员）。
     *
     * @param int $uid     用户 ID
     * @param int $itemId  项目 ID
     *
     * @return bool
     */
    protected function checkItemEdit(int $uid, int $itemId): bool
    {
        // 与旧代码逻辑一致：先检查 uid 是否存在
        if (!$uid) {
            return false;
        }

        // 检查是否是项目创建者（与旧代码逻辑一致：$item['uid'] && $item['uid'] == $uid）
        $item = \App\Model\Item::findById($itemId);
        if ($item && $item->uid && (int) $item->uid == $uid) {
            return true;
        }

        // 检查是否是项目编辑成员（member_group_id = 1）
        $ItemMember = DB::table('item_member')
            ->where('item_id', $itemId)
            ->where('uid', $uid)
            ->where('member_group_id', 1)
            ->first();
        if ($ItemMember) {
            return true;
        }

        // 检查是否是团队中的编辑成员
        $ItemMember = DB::table('team_item_member')
            ->where('item_id', $itemId)
            ->where('member_uid', $uid)
            ->where('member_group_id', 1)
            ->first();
        if ($ItemMember) {
            return true;
        }

        // 如果有管理权限，也自动拥有编辑权限
        if ($this->checkItemManage($uid, $itemId)) {
            return true;
        }

        return false;
    }

    /**
     * 检查用户是否有项目访问权限（包括公开项目、白名单、成员等）。
     *
     * @param int    $uid      用户 ID（0 表示游客）
     * @param int    $itemId   项目 ID
     * @param string $referUrl 来源 URL（可选）
     *
     * @return bool
     */
    protected function checkItemVisit(int $uid, int $itemId, string $referUrl = ''): bool
    {
        // 检查管理权限（包括项目创建者）
        $hasManage = $this->checkItemManage($uid, $itemId);
        if ($hasManage) {
            return true;
        }

        // 检查是否是项目成员（任何权限级别）
        if ($uid > 0) {
            $ItemMember = DB::table('item_member')
                ->where('item_id', $itemId)
                ->where('uid', $uid)
                ->first();
            if ($ItemMember) {
                return true;
            }

            $TeamItemMember = DB::table('team_item_member')
                ->where('item_id', $itemId)
                ->where('member_uid', $uid)
                ->first();
            if ($TeamItemMember) {
                return true;
            }
        }

        // 检查项目密码（与旧代码逻辑一致：如果有密码且密码不匹配，返回 false；否则返回 true）
        $item = \App\Model\Item::findById($itemId);
        if (!$item) {
            return false;
        }

        // 与旧代码逻辑一致：如果有密码，需要验证密码
        // 旧代码：if ($item['password'] && $item['password'] != I('_item_pwd')) { return false; } else { return true; }
        // _item_pwd 参数的作用：跨域请求时无法带 cookies，自然无法记住 session。用这个参数使记住用户输入过项目密码。
        if ($item->password && $item->password != ($_REQUEST['_item_pwd'] ?? '')) {
            // 有密码且密码不匹配，返回 false
            return false;
        } else {
            // 无密码，或者有密码且密码匹配，返回 true
            return true;
        }
    }

    /**
     * 判断某用户是否有团队管理权限（团队创建者、团队管理员、系统管理员）。
     *
     * @param int $uid    用户 ID
     * @param int $teamId 团队 ID
     *
     * @return bool
     */
    protected function checkTeamManage(int $uid, int $teamId): bool
    {
        // 与旧代码逻辑一致：先检查 uid 是否存在
        if (!$uid) {
            return false;
        }

        // 检查是否是团队创建者（与旧代码逻辑一致：$team['uid'] && $team['uid'] == $uid）
        $team = DB::table('team')
            ->where('id', $teamId)
            ->first();
        if ($team && $team->uid && (int) $team->uid == $uid) {
            return true;
        }

        // 检查是否是团队管理员（team_member_group_id = 2）
        $team_member = DB::table('team_member')
            ->where('team_id', $teamId)
            ->where('member_uid', $uid)
            ->where('team_member_group_id', 2)
            ->first();
        if ($team_member) {
            return true;
        }

        // 检查是否是系统管理员（与旧代码逻辑一致：checkAdmin(false)，直接检查用户 groupid）
        $user = \App\Model\User::findById($uid);
        if ($user && ($user->groupid ?? 0) == 1) {
            return true;
        }

        return false;
    }

    /**
     * 获取客户端 IP 地址（兼容旧接口 getIPaddress）
     *
     * @return string IP 地址
     */
    protected function getIPaddress(): string
    {
        return IpHelper::getClientIp();
    }


    /**
     * 安全处理 LIKE 查询关键字（兼容旧接口 safe_like）
     *
     * @param string $keyword 关键字
     * @param bool $strict 是否严格模式
     * @return string 处理后的关键字
     */
    protected function safeLike(string $keyword, bool $strict = true): string
    {
        return Security::safeLike($keyword, $strict);
    }
}
