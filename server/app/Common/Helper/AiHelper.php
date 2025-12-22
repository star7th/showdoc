<?php

namespace App\Common\Helper;

use App\Model\Item;
use App\Model\Options;
use App\Common\Helper\Convert;

/**
 * AI 辅助类
 * 提供 AI 相关的公共方法
 */
class AiHelper
{
    /**
     * 重建项目索引
     *
     * @param int $itemId 项目ID
     * @param string $aiServiceUrl AI服务地址
     * @param string $aiServiceToken AI服务Token
     * @return array|false 成功返回结果数组，失败返回false
     */
    public static function rebuild(int $itemId, string $aiServiceUrl, string $aiServiceToken)
    {
        try {
            // 设置执行时长和内存限制（重建索引可能需要较长时间和较多内存）
            set_time_limit(3600);  // 60分钟超时（大项目需要更长时间）
            ini_set('memory_limit', '2G');  // 2GB 内存限制

            // 使用 Item 模型的 getContent 方法获取所有页面（会自动处理分表和内容解压缩）
            $menu = Item::getContent($itemId, true);
            if (!$menu || (empty($menu['pages']) && empty($menu['catalogs']))) {
                error_log("自动重建索引失败: item_id={$itemId}, 项目中没有可索引的文档");
                return false;
            }

            // 使用 Convert 类转换 API 文档
            $convert = new Convert();

            // 构建页面数据（递归处理所有页面）
            $pageData = [];
            self::collectPages($menu, $pageData, $convert);

            if (empty($pageData)) {
                error_log("自动重建索引失败: item_id={$itemId}, 没有可索引的文档（所有页面内容都为空）");
                return false;
            }

            $totalPages = count($pageData);
            error_log("自动触发重建索引: item_id={$itemId}, 页面总数={$totalPages}");

            // 重建索引前，先清空整个项目的旧索引，避免分批处理时重复删除操作
            $deleteUrl = rtrim($aiServiceUrl, '/') . '/api/index/delete-item';
            $deleteResult = self::callService($deleteUrl, ['item_id' => $itemId], $aiServiceToken, 'DELETE', 30);
            if ($deleteResult !== false) {
                error_log("已清空项目旧索引: item_id={$itemId}");
            } else {
                error_log("清空项目旧索引失败（可能不存在）: item_id={$itemId}");
            }

            // 分批处理，避免一次性发送所有页面数据导致超时或内存问题
            // 每批处理 200 个页面（可根据实际情况调整）
            $batchSize = 200;
            $url = rtrim($aiServiceUrl, '/') . '/api/index/rebuild';

            // 如果页面数量较少，一次性提交
            if ($totalPages <= 100) {
                $postData = [
                    'item_id' => $itemId,
                    'pages' => $pageData
                ];
                $result = self::callService($url, $postData, $aiServiceToken, 'POST', 30);  // 30秒超时（只是提交任务）
                if ($result !== false && isset($result['status']) && $result['status'] == 'success') {
                    error_log("重建索引任务已提交: item_id={$itemId}, 页面总数={$totalPages}, task_id=" . (isset($result['task_id']) ? $result['task_id'] : ''));
                    return [
                        'status' => 'success',
                        'message' => '重建索引任务已提交，正在后台处理',
                        'total' => $totalPages,
                        'task_id' => $result['task_id'] ?? null
                    ];
                }
            }

            // 如果页面数量太多，分批提交（每批调用一次 rebuild 接口）
            $totalBatches = ceil($totalPages / $batchSize);
            $successBatches = 0;
            $errorBatches = 0;
            $taskIds = [];

            error_log("使用分批方式重建索引: item_id={$itemId}, 总批次数={$totalBatches}, 每批={$batchSize}个页面");

            for ($i = 0; $i < $totalPages; $i += $batchSize) {
                $batch = array_slice($pageData, $i, $batchSize);
                $batchNum = floor($i / $batchSize) + 1;

                $postData = [
                    'item_id' => $itemId,
                    'pages' => $batch
                ];

                $result = self::callService($url, $postData, $aiServiceToken, 'POST', 30);
                if ($result !== false && isset($result['status']) && $result['status'] == 'success') {
                    $successBatches++;
                    if (isset($result['task_id'])) {
                        $taskIds[] = $result['task_id'];
                    }
                    error_log("重建索引进度: item_id={$itemId}, 批次 {$batchNum}/{$totalBatches} 已提交, task_id=" . (isset($result['task_id']) ? $result['task_id'] : ''));
                } else {
                    $errorBatches++;
                    error_log("重建索引进度: item_id={$itemId}, 批次 {$batchNum}/{$totalBatches} 提交失败");
                }

                // 每批之间稍作延迟，避免请求过于频繁
                if ($i + $batchSize < $totalPages) {
                    usleep(200000);  // 延迟 0.2 秒
                }
            }

            error_log("重建索引任务提交完成: item_id={$itemId}, 成功批次={$successBatches}, 失败批次={$errorBatches}, 总计={$totalBatches}");

            return [
                'status' => $errorBatches == 0 ? 'success' : 'partial_success',
                'message' => $errorBatches == 0 ? '重建索引任务已全部提交，正在后台处理' : "重建索引任务已提交，但有 {$errorBatches} 个批次失败",
                'total' => $totalPages,
                'total_batches' => $totalBatches,
                'success_batches' => $successBatches,
                'error_batches' => $errorBatches,
                'task_ids' => $taskIds
            ];
        } catch (\Exception $e) {
            error_log("自动重建索引异常: item_id={$itemId}, 错误: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 调用 AI 服务（非流式）
     *
     * @param string $url 请求URL
     * @param array|null $postData POST数据
     * @param string $aiServiceToken AI服务Token
     * @param string $method 请求方法
     * @param int $timeout 超时时间（秒）
     * @return array|false
     */
    public static function callService(string $url, ?array $postData, string $aiServiceToken, string $method = 'POST', int $timeout = 30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_ENCODING, '');

        $headers = [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer ' . $aiServiceToken,
            'Accept: application/json; charset=utf-8'
        ];

        if ($method == 'POST' && $postData) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($postData) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
            }
        } elseif ($method == 'GET') {
            curl_setopt($curl, CURLOPT_HTTPGET, 1);
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($result === false || $error) {
            $errorMsg = $error ?: '连接失败';
            error_log("AI服务调用失败: " . $errorMsg . " (URL: " . $url . ")");
            return false;
        }

        if ($httpCode != 200) {
            error_log("AI服务返回错误: HTTP " . $httpCode . ", Response: " . substr($result, 0, 500));
            return false;
        }

        // 确保响应是 UTF-8 编码
        if (!mb_check_encoding($result, 'UTF-8')) {
            $result = mb_convert_encoding($result, 'UTF-8', 'auto');
        }

        $data = json_decode($result, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            error_log("AI服务返回数据解析失败: " . json_last_error_msg() . ", Response: " . substr($result, 0, 500));
            return false;
        }

        return $data;
    }

    /**
     * 调用 AI 服务（流式）
     *
     * @param string $url 请求URL
     * @param array $postData POST数据
     * @param string $aiServiceToken AI服务Token
     * @return void
     */
    public static function callServiceStream(string $url, array $postData, string $aiServiceToken): void
    {
        $callback = function ($ch, $data) {
            if (connection_aborted()) {
                return -1;
            }

            // 确保数据是 UTF-8 编码
            if (!mb_check_encoding($data, 'UTF-8')) {
                $detected = mb_detect_encoding($data, ['UTF-8', 'GBK', 'GB2312', 'ISO-8859-1'], true);
                $data = mb_convert_encoding($data, 'UTF-8', $detected ?: 'auto');
            }

            echo $data;
            flush();
            return strlen($data);
        };

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, $callback);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 5分钟超时
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Authorization: Bearer ' . $aiServiceToken,
            'Accept: text/event-stream; charset=utf-8'
        ]);

        curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // 如果请求失败，输出错误信息
        if ($error || $httpCode != 200) {
            $errorMsg = $error ?: "HTTP {$httpCode}";
            echo "data: " . json_encode(['type' => 'error', 'message' => 'AI 服务调用失败: ' . $errorMsg], JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
        }
    }

    /**
     * 递归收集所有页面数据
     *
     * @param array $menu 菜单数据
     * @param array $pageData 页面数据数组（引用传递）
     * @param Convert $convert Convert 对象
     * @return void
     */
    private static function collectPages(array $menu, array &$pageData, Convert $convert): void
    {
        // 处理根目录下的页面
        if (isset($menu['pages']) && is_array($menu['pages'])) {
            foreach ($menu['pages'] as $page) {
                self::processPage($page, $pageData, $convert);
            }
        }

        // 递归处理子目录
        if (isset($menu['catalogs']) && is_array($menu['catalogs'])) {
            foreach ($menu['catalogs'] as $catalog) {
                self::collectPages($catalog, $pageData, $convert);
            }
        }
    }

    /**
     * 处理单个页面
     *
     * @param array $page 页面数据
     * @param array $pageData 页面数据数组（引用传递）
     * @param Convert $convert Convert 对象
     * @return void
     */
    private static function processPage(array $page, array &$pageData, Convert $convert): void
    {
        $content = $page['page_content'] ?? '';
        $pageType = $page['page_type'] ?? 'regular';

        // HTML 反转义（因为存储的内容是 HTML 转义的）
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 尝试使用 Convert 类转换为 Markdown（如果是 API 文档会自动转换，否则返回 false）
        $mdContent = $convert->runapiToMd($content);
        if ($mdContent !== false) {
            $content = $mdContent;
        }

        // 跳过空内容的页面
        if (empty($content) || !is_string($content) || trim($content) === '') {
            error_log("跳过空内容页面: page_id=" . ($page['page_id'] ?? '') . ", title=" . ($page['page_title'] ?? ''));
            return;
        }

        $pageData[] = [
            'page_id' => $page['page_id'] ?? 0,
            'page_title' => $page['page_title'] ?? '',
            'page_content' => $content,
            'page_type' => $pageType,
            'cat_name' => $page['cat_name'] ?? '',
            'update_time' => $page['update_time'] ?? time()
        ];
    }

    /**
     * 调用 OpenAI API
     *
     * @param array $messages 消息数组，格式：[['role' => 'system', 'content' => '...'], ['role' => 'user', 'content' => '...']]
     * @param int $timeout 超时时间（秒），默认120秒
     * @return string|false 成功返回结果字符串，失败返回false
     */
    public static function callOpenAI(array $messages, int $timeout = 120)
    {
        $aiModelName = Options::get('ai_model_name', 'gpt-4o-mini');
        $openApiKey = Options::get('open_api_key');

        $postData = json_encode([
            'model' => $aiModelName,
            'messages' => $messages,
        ]);

        $openApiHost = Options::get('open_api_host', 'https://api.openai.com');
        if (!strstr($openApiHost, 'http')) {
            $openApiHost = 'https://' . $openApiHost;
        }
        if (substr($openApiHost, -1) === '/') {
            $openApiHost = substr($openApiHost, 0, -1);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, $openApiHost . '/v1/chat/completions');
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, true);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                "Authorization: Bearer {$openApiKey}",
                'Content-Length: ' . strlen($postData)
            ]
        );

        $result = curl_exec($curl);

        if ($result === false) {
            $error = curl_error($curl);
            $errno = curl_errno($curl);
            curl_close($curl);
            error_log("OpenAI API curl error: " . $error . " (errno: " . $errno . ")");
            return false;
        }

        curl_close($curl);

        return $result;
    }

    /**
     * AI生成脚本
     *
     * @param string $scriptType 脚本类型：pre 或 post
     * @param string $description 用户描述的需求
     * @param string $apiInfo 接口信息（JSON字符串，可选）
     * @param string $originalScript 原脚本内容（可选）
     * @return string|false AI返回的结果
     */
    public static function generateScript(string $scriptType, string $description, string $apiInfo = '', string $originalScript = '')
    {
        $aiModelName = Options::get('ai_model_name', 'gpt-4o-mini');
        $openApiKey = Options::get('open_api_key');

        // 解析接口信息
        $apiInfoObj = null;
        if ($apiInfo) {
            $apiInfoObj = json_decode($apiInfo, true);
        }

        // 构建系统提示词
        $systemPrompt = '你是一个经验丰富的API测试脚本编写专家。用户会描述一个需求，你需要根据需求生成对应的JavaScript脚本代码。';

        if ($scriptType === 'pre') {
            $systemPrompt .= '这是"前执行脚本"（请求前执行），用于在发送HTTP请求之前修改请求参数、请求头、URL等。';
        } else {
            $systemPrompt .= '这是"后执行脚本"（请求后执行），用于处理响应结果、提取数据、进行断言测试等。';
        }

        $systemPrompt .= '

脚本运行环境说明：
- 脚本运行在安全的沙箱环境中，无法访问window、localStorage、document等浏览器对象
- 通过runapi对象可以访问以下功能：

内置库：
- runapi.CryptoJS: 加密库（支持MD5、SHA256、AES加密/解密、Base64编码/解码、HMAC签名等）
- runapi.moment: 时间处理库（支持时间格式化、计算、时间戳转换等）
- runapi.JSEncrypt: RSA非对称加密库
- runapi.ajax({ ... }): 同步AJAX请求

变量操作：
- runapi.getVar(name): 获取环境变量
- runapi.setVar(name, value): 设置环境变量
- runapi.clearVar(name): 清除环境变量
- runapi.getLocalVar(name): 获取本地变量
- runapi.setLocalVar(name, value): 设置本地变量
- runapi.clearLocalVar(name): 清除本地变量

数据库操作（仅 Electron 环境可用）：
- runapi.db.query(sql, params?, configName?): 执行数据库查询
  * sql: SQL 查询语句（必填）
  * params: SQL 参数数组（可选），用于防止 SQL 注入，使用 ? 占位符
  * configName: 数据库配置名称（可选），不指定则使用默认配置
  * 返回值: 返回查询结果数组，每个元素为一行数据对象
  * 注意: 查询是同步阻塞的，会等待查询完成后再继续执行
  * 示例: 
    const user = runapi.db.query("SELECT * FROM users WHERE id = ?", [123])[0];
    const users = runapi.db.query("SELECT id, name FROM users WHERE status = 1", [], "主库");

';

        if ($scriptType === 'pre') {
            $systemPrompt .= '前执行脚本专用API：
- runapi.getParam(name): 获取请求参数（通用方法，GET请求获取query参数，POST/PUT/DELETE请求获取body参数）
- runapi.setParam(name, value): 设置请求参数（通用方法，GET请求设置query参数，POST/PUT/DELETE请求设置body参数）
- runapi.deleteParam(name): 删除请求参数（通用方法，GET请求删除query参数，POST/PUT/DELETE请求删除body参数）
- runapi.getAllParam(): 获取所有参数列表（通用方法，GET请求返回query参数列表，POST/PUT/DELETE请求返回body参数列表）
- runapi.getParamJson(): 获取JSON格式的请求参数对象
- runapi.setParamJson(objOrStr): 设置JSON格式的请求参数
- runapi.getHeader(name): 获取请求头
- runapi.setHeader(name, value): 设置请求头
- runapi.getMethod(): 获取请求方法
- runapi.getUrl(): 获取请求URL
- runapi.setUrl(url): 设置请求URL（可用于动态修改请求地址，如根据环境切换API地址）
- runapi.alert(v): 显示信息弹窗
';
        } else {
            $systemPrompt .= '后执行脚本专用API：
- runapi.responseBody: 响应体对象
- runapi.responseHeader: 响应头对象
- runapi.status: HTTP状态码
- runapi.responseTime: 响应耗时（毫秒）
- runapi.responseSize: 响应大小
- runapi.bodyIsJson(): 判断响应体是否为JSON
- runapi.bodyHas(keyword): 判断响应体是否包含关键字
- runapi.assert(textOrFn): 断言测试
- runapi.alert(v): 显示信息弹窗
';
        }

        $systemPrompt .= '
要求：
1. 只返回JavaScript代码，不要包含额外不符合语法的解释文字。请写好完善的注释，在注释里讲清楚，不用额外文字或段落。
2. 不要使用代码块标记（如```javascript）
3. 代码应该简洁、实用、可直接运行
4. 如果需求描述不够清晰，可以生成一个通用的示例脚本
';

        // 构建用户消息
        $userMessage = "需求描述：{$description}";

        if ($originalScript && trim($originalScript)) {
            $userMessage .= "\n\n现有脚本内容：\n```javascript\n{$originalScript}\n```\n\n请根据需求描述，在现有脚本基础上进行修改或补充。如果需求是全新的功能，可以直接添加新代码；如果需求是修改现有功能，请修改对应的代码部分。";
        }

        if ($apiInfoObj) {
            $userMessage .= "\n\n接口信息：\n";
            if (isset($apiInfoObj['info'])) {
                $info = $apiInfoObj['info'];
                $userMessage .= "- 接口URL: " . ($info['url'] ?? '') . "\n";
                $userMessage .= "- 请求方法: " . ($info['method'] ?? '') . "\n";
                if (isset($info['description'])) {
                    $userMessage .= "- 接口描述: " . $info['description'] . "\n";
                }
            }
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
        ];

        return self::callOpenAI($messages, 120);
    }

    /**
     * AI生成测试数据（示例值）
     *
     * @param array $params 参数数组，每个参数包含：name（参数名）、type（参数类型）、remark（参数描述，可选）
     * @param string $apiInfo 接口信息（JSON字符串，可选）
     * @param int $count 生成数据组数，默认1组
     * @return string|false AI返回的结果
     */
    public static function generateTestData(array $params, string $apiInfo = '', int $count = 1)
    {
        $aiModelName = Options::get('ai_model_name', 'gpt-4o-mini');
        $openApiKey = Options::get('open_api_key');

        // 解析接口信息
        $apiInfoObj = null;
        if ($apiInfo) {
            $apiInfoObj = json_decode($apiInfo, true);
        }

        // 构建系统提示词
        $systemPrompt = '你是一个经验丰富的API测试数据生成专家。用户会提供一组参数信息，你需要根据参数名、参数类型和参数描述，智能推断并生成合理的测试数据（示例值）。

要求：
1. 根据参数名智能推断参数含义（如 email → test@example.com，phone → 13800138000，user_id → 12345）
2. 根据参数类型生成对应格式的数据（string、number、boolean、array、object等）
3. 生成的数据应该符合业务逻辑，多个参数之间应该有关联性（如 age: 25，status: 1）
4. 如果参数有描述信息，优先根据描述生成更准确的数据
5. 生成的数据应该是固定的示例值，不是动态值或模板

输出格式：
- 必须返回JSON格式，格式为：{"data": [{"param1": "value1", "param2": "value2", ...}, ...]}
- 如果count=1，返回一个对象数组，包含1组数据
- 如果count>1，返回一个对象数组，包含多组不同的数据（正常数据、边界数据、异常数据等）
- 只返回JSON，不要包含任何其他文字说明或代码块标记';

        // 构建用户消息
        $userMessage = "请为以下参数生成测试数据：\n\n";

        foreach ($params as $index => $param) {
            $userMessage .= ($index + 1) . ". 参数名: " . ($param['name'] ?? '') . "\n";
            $userMessage .= "   类型: " . ($param['type'] ?? 'string') . "\n";
            if (!empty($param['remark'])) {
                $userMessage .= "   描述: " . $param['remark'] . "\n";
            }
            $userMessage .= "\n";
        }

        if ($count > 1) {
            $userMessage .= "请生成 {$count} 组不同的测试数据，包括正常数据、边界数据和异常数据。\n";
        } else {
            $userMessage .= "请生成 1 组合理的测试数据。\n";
        }

        if ($apiInfoObj) {
            $userMessage .= "\n接口信息：\n";
            if (isset($apiInfoObj['info'])) {
                $info = $apiInfoObj['info'];
                $userMessage .= "- 接口URL: " . ($info['url'] ?? '') . "\n";
                $userMessage .= "- 请求方法: " . ($info['method'] ?? '') . "\n";
                if (isset($info['description'])) {
                    $userMessage .= "- 接口描述: " . $info['description'] . "\n";
                }
            }
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $userMessage,
            ],
        ];

        return self::callOpenAI($messages, 120);
    }
}
