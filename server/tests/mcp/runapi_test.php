<?php

/**
 * ShowDoc MCP RunApi 功能自动化测试脚本
 *
 * 使用方法：
 *   php server/tests/mcp/runapi_test.php
 *
 * 测试内容：
 *   1. 创建 RunApi 项目（item_type=3）
 *   2. create_runapi_page / get_runapi_page / update_runapi_page / upsert_runapi_page
 *   3. 参数校验、类型校验、乐观锁
 *   4. 读写往返一致性
 */

require __DIR__ . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;

define('MCP_URL', 'http://127.0.0.1/showdoc/mcp.php');

$debugMode = false;

// ============================================================================
// 自动获取测试用户
// ============================================================================

$testUser = TestUserManager::getTestUser('admin');
if (!$testUser) {
  $testUser = TestUserManager::getTestUser('normal');
}
if (!$testUser) {
  echo colorOutput("错误: 找不到可用的测试用户", 'red') . "\n";
  exit(1);
}
define('TEST_UID', $testUser['uid']);

// ============================================================================
// 工具函数
// ============================================================================

function extractData($result, $key = null)
{
  $text = $result['body']['result']['content'][0]['text'] ?? '';
  $data = json_decode($text, true);
  if ($key !== null) {
    return $data[$key] ?? null;
  }
  return $data;
}

function extractError($result)
{
  return $result['body']['error']['message'] ?? '';
}

function hasError($result)
{
  return isset($result['body']['error']);
}

// ============================================================================
// 测试开始
// ============================================================================

printHeader("ShowDoc MCP RunApi 功能自动化测试");
echo "测试用户 UID: " . TEST_UID . "\n";
echo "MCP 地址: " . MCP_URL . "\n";

$tester = new McpTester(MCP_URL);
$tokenManager = new TokenManager(TEST_UID);

$writeTokenData = $tokenManager->getOrCreateWriteToken();
$token = $writeTokenData['token'];
$tester->setToken('read_write', $token);

printInfo("读写 Token: " . substr($token, 0, 10) . "..." . substr($token, -6));

// ============================================================================
// 1. 创建 RunApi 项目 + 普通项目（用于后续校验测试）
// ============================================================================

printHeader("1. 创建测试项目");

$runapiItemName = '[MCP RunApi测试] ' . date('Y-m-d H:i:s');
$result = $tester->callTool('create_item', [
  'item_name' => $runapiItemName,
  'item_type' => 3,
  'item_description' => 'RunApi自动化测试项目，测试完成后会删除',
], $token);

$runapiItemId = null;
if (hasError($result)) {
  $tester->recordTest('create_item (RunApi)', false, extractError($result));
  echo "无法创建RunApi项目，测试终止\n";
  exit(1);
} else {
  $runapiItemId = extractData($result, 'item_id');
  if ($runapiItemId) {
    $tester->addCreatedItem($runapiItemId);
    $tester->recordTest('create_item (RunApi)', true, "RunApi项目 ID: $runapiItemId");
  } else {
    $tester->recordTest('create_item (RunApi)', false, '无法解析项目ID');
    echo "测试终止\n";
    exit(1);
  }
}

$normalItemName = '[MCP RunApi测试-普通项目] ' . date('Y-m-d H:i:s');
$result = $tester->callTool('create_item', [
  'item_name' => $normalItemName,
  'item_type' => 1,
  'item_description' => '普通文档项目，用于校验 item_type 测试',
], $token);

$normalItemId = null;
if (hasError($result)) {
  $tester->recordTest('create_item (普通项目)', false, extractError($result));
} else {
  $normalItemId = extractData($result, 'item_id');
  if ($normalItemId) {
    $tester->addCreatedItem($normalItemId);
    $tester->recordTest('create_item (普通项目)', true, "普通项目 ID: $normalItemId");
  }
}

// 同时在普通项目下创建一个普通页面，用于 get_runapi_page 的类型校验测试
$normalPageId = null;
if ($normalItemId) {
  $result = $tester->callTool('create_page', [
    'item_id' => $normalItemId,
    'page_title' => '[RunApi测试] 普通页面',
    'page_content' => '# 普通页面内容',
  ], $token);
  if (!hasError($result)) {
    $normalPageId = extractData($result, 'page_id');
    if ($normalPageId) {
      $tester->addCreatedPage($normalPageId);
    }
  }
}

// ============================================================================
// 2. create_runapi_page 测试
// ============================================================================

printHeader("2. create_runapi_page 测试");

$runapiJsonContent = [
  'info' => [
    'from' => 'runapi',
    'type' => 'api',
    'method' => 'post',
    'url' => '/api/test/login',
    'description' => '测试登录接口',
    'remark' => 'MCP自动化测试',
  ],
  'request' => [
    'params' => [
      'mode' => 'json',
      'json' => '{"username":"admin","password":"123456"}',
      'jsonDesc' => [
        ['name' => 'username', 'type' => 'string', 'require' => '1', 'remark' => '用户名'],
        ['name' => 'password', 'type' => 'string', 'require' => '1', 'remark' => '密码'],
      ],
      'formdata' => [],
      'urlencoded' => [],
    ],
    'headers' => [
      ['name' => 'Content-Type', 'type' => 'string', 'value' => 'application/json', 'require' => '1', 'remark' => ''],
    ],
    'query' => [],
    'pathVariable' => [],
    'cookies' => [],
    'auth' => [
      'type' => 'bearer',
      'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']],
    ],
  ],
  'response' => [
    'responseExample' => '{"code":0,"data":{"token":"eyJhbGc...","uid":1}}',
    'responseParamsDesc' => [
      ['name' => 'code', 'type' => 'int', 'remark' => '状态码'],
      ['name' => 'data.token', 'type' => 'string', 'remark' => 'JWT令牌'],
      ['name' => 'data.uid', 'type' => 'int', 'remark' => '用户ID'],
    ],
    'responseFailExample' => '{"code":10001,"msg":"参数错误"}',
    'responseFailParamsDesc' => [
      ['name' => 'code', 'type' => 'int', 'remark' => '错误码'],
      ['name' => 'msg', 'type' => 'string', 'remark' => '错误信息'],
    ],
  ],
  'scripts' => [
    'pre' => 'console.log("pre script");',
    'post' => 'console.log("post script");',
  ],
  'apiStatus' => '0',
];

$runapiPageId = null;
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[MCP测试] 登录接口',
  'page_content' => $runapiJsonContent,
  'cat_name' => '用户模块',
], $token);

if (hasError($result)) {
  $tester->recordTest('create_runapi_page', false, extractError($result));
} else {
  $data = extractData($result);
  $runapiPageId = $data['page_id'] ?? null;
  if ($runapiPageId) {
    $tester->addCreatedPage($runapiPageId);
    $tester->recordTest('create_runapi_page', true, "页面 ID: $runapiPageId");
  } else {
    $tester->recordTest('create_runapi_page', false, '无法解析页面ID');
  }
}

// 测试带目录创建
$runapiPageId2 = null;
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[MCP测试] 用户列表接口',
  'page_content' => [
    'info' => [
      'from' => 'runapi',
      'type' => 'api',
      'method' => 'get',
      'url' => '/api/test/users',
      'description' => '获取用户列表',
      'remark' => '',
    ],
    'request' => [
      'params' => ['mode' => 'formdata', 'formdata' => [], 'urlencoded' => [], 'json' => '', 'jsonDesc' => []],
      'headers' => [],
      'query' => [['name' => 'page', 'type' => 'int', 'value' => '1', 'require' => '0', 'remark' => '页码']],
      'pathVariable' => [],
      'cookies' => [],
      'auth' => [],
    ],
    'response' => [
      'responseExample' => '{"code":0,"data":[]}',
      'responseParamsDesc' => [],
      'responseFailExample' => '',
      'responseFailParamsDesc' => [],
    ],
    'scripts' => ['pre' => '', 'post' => ''],
    'apiStatus' => '3',
  ],
  'cat_name' => '用户模块/列表',
], $token);

if (hasError($result)) {
  $tester->recordTest('create_runapi_page (带多级目录)', false, extractError($result));
} else {
  $data = extractData($result);
  $runapiPageId2 = $data['page_id'] ?? null;
  if ($runapiPageId2) {
    $tester->addCreatedPage($runapiPageId2);
    $tester->recordTest('create_runapi_page (带多级目录)', true, "页面 ID: $runapiPageId2");
  } else {
    $tester->recordTest('create_runapi_page (带多级目录)', false, '无法解析页面ID');
  }
}

// ============================================================================
// 3. get_runapi_page 测试
// ============================================================================

printHeader("3. get_runapi_page 测试");

if ($runapiPageId) {
  // 3.1 基本读取
  $result = $tester->callTool('get_runapi_page', ['page_id' => $runapiPageId], $token);

  $getContentHash = null;
  if (hasError($result)) {
    $tester->recordTest('get_runapi_page', false, extractError($result));
  } else {
    $data = extractData($result);
    $pageType = $data['type'] ?? '';
    $pageContent = $data['page_content'] ?? null;
    $getContentHash = $data['content_hash'] ?? '';

    $tester->recordTest(
      'get_runapi_page 返回 type=runapi',
      $pageType === 'runapi',
      $pageType === 'runapi' ? 'type 正确' : "期望 runapi，实际: $pageType"
    );

    $isContentArray = is_array($pageContent);
    $tester->recordTest(
      'get_runapi_page page_content 是 JSON 对象',
      $isContentArray,
      $isContentArray ? '返回 JSON 对象（非字符串）' : '返回了非对象类型: ' . gettype($pageContent)
    );

    if ($isContentArray) {
      $method = $pageContent['info']['method'] ?? '';
      $url = $pageContent['info']['url'] ?? '';
      $tester->recordTest(
        'get_runapi_page info.method 正确',
        $method === 'post',
        $method === 'post' ? 'method = post' : "期望 post，实际: $method"
      );
      $tester->recordTest(
        'get_runapi_page info.url 正确',
        $url === '/api/test/login',
        $url === '/api/test/login' ? 'url = /api/test/login' : "期望 /api/test/login，实际: $url"
      );

      $preScript = $pageContent['scripts']['pre'] ?? '';
      $tester->recordTest(
        'get_runapi_page scripts.pre 保留',
        strpos($preScript, 'pre script') !== false,
        strpos($preScript, 'pre script') !== false ? 'scripts.pre 正确保留' : "scripts.pre 内容: $preScript"
      );

      $authType = $pageContent['request']['auth']['type'] ?? '';
      $tester->recordTest(
        'get_runapi_page auth.type 保留',
        $authType === 'bearer',
        $authType === 'bearer' ? 'auth.type = bearer' : "期望 bearer，实际: $authType"
      );

      $jsonDesc = $pageContent['request']['params']['jsonDesc'] ?? [];
      $hasUsernameDesc = false;
      foreach ($jsonDesc as $desc) {
        if (($desc['name'] ?? '') === 'username') {
          $hasUsernameDesc = true;
          break;
        }
      }
      $tester->recordTest(
        'get_runapi_page jsonDesc 保留',
        $hasUsernameDesc,
        $hasUsernameDesc ? 'jsonDesc 包含 username 字段' : 'jsonDesc 缺少 username 字段'
      );
    }

    $tester->recordTest(
      'get_runapi_page 返回 content_hash',
      !empty($getContentHash),
      !empty($getContentHash) ? "hash = $getContentHash" : '缺少 content_hash'
    );
  }

  // 3.2 通过 item_id + page_title 查询
  $result = $tester->callTool('get_runapi_page', [
    'item_id' => $runapiItemId,
    'page_title' => '[MCP测试] 登录接口',
  ], $token);

  if (hasError($result)) {
    $tester->recordTest('get_runapi_page (item_id+title)', false, extractError($result));
  } else {
    $data = extractData($result);
    $foundPageId = $data['page_id'] ?? 0;
    $tester->recordTest(
      'get_runapi_page (item_id+title) 定位正确',
      (int) $foundPageId === (int) $runapiPageId,
      (int) $foundPageId === (int) $runapiPageId ? '定位到同一页面' : "期望 $runapiPageId，实际: $foundPageId"
    );
  }

  // 3.3 验证 cat_name 返回
  $result = $tester->callTool('get_runapi_page', ['page_id' => $runapiPageId], $token);
  if (!hasError($result)) {
    $data = extractData($result);
    $catName = $data['cat_name'] ?? '';
    $tester->recordTest(
      'get_runapi_page 返回 cat_name',
      $catName === '用户模块',
      $catName === '用户模块' ? 'cat_name 正确' : "期望 '用户模块'，实际: '$catName'"
    );
  }
} else {
  $tester->recordTest('get_runapi_page', false, '跳过：创建页面失败');
}

// ============================================================================
// 4. update_runapi_page 测试
// ============================================================================

printHeader("4. update_runapi_page 测试");

if ($runapiPageId) {
  // 4.1 更新 URL 和 method
  $updatedContent = $runapiJsonContent;
  $updatedContent['info']['url'] = '/api/v2/test/login';
  $updatedContent['info']['method'] = 'get';

  $result = $tester->callTool('update_runapi_page', [
    'page_id' => $runapiPageId,
    'page_content' => $updatedContent,
  ], $token);

  if (hasError($result)) {
    $tester->recordTest('update_runapi_page', false, extractError($result));
  } else {
    $data = extractData($result);
    $newHash = $data['content_hash'] ?? '';
    $tester->recordTest(
      'update_runapi_page 成功',
      !empty($newHash),
      !empty($newHash) ? "新 hash = $newHash" : '缺少 content_hash'
    );
  }

  // 4.2 读取验证更新结果
  $result = $tester->callTool('get_runapi_page', ['page_id' => $runapiPageId], $token);
  if (!hasError($result)) {
    $data = extractData($result);
    $pageContent = $data['page_content'] ?? [];
    $url = $pageContent['info']['url'] ?? '';
    $method = $pageContent['info']['method'] ?? '';

    $tester->recordTest(
      'update_runapi_page URL 已更新',
      $url === '/api/v2/test/login',
      $url === '/api/v2/test/login' ? 'URL 正确' : "期望 /api/v2/test/login，实际: $url"
    );
    $tester->recordTest(
      'update_runapi_page method 已更新',
      $method === 'get',
      $method === 'get' ? 'method 正确' : "期望 get，实际: $method"
    );

    // 验证其他字段未被破坏
    $authType = $pageContent['request']['auth']['type'] ?? '';
    $tester->recordTest(
      'update_runapi_page 未修改字段保持不变',
      $authType === 'bearer',
      $authType === 'bearer' ? 'auth.type 仍为 bearer' : "auth.type 变为: $authType"
    );
  }

  // 4.3 只更新标题（不传 page_content）
  $result = $tester->callTool('update_runapi_page', [
    'page_id' => $runapiPageId,
    'page_title' => '[MCP测试] 登录接口-v2',
  ], $token);

  if (hasError($result)) {
    $tester->recordTest('update_runapi_page (仅更新标题)', false, extractError($result));
  } else {
    $tester->recordTest('update_runapi_page (仅更新标题)', true, '标题更新成功');
  }
} else {
  $tester->recordTest('update_runapi_page', false, '跳过：没有可用的测试页面');
}

// ============================================================================
// 5. upsert_runapi_page 测试
// ============================================================================

printHeader("5. upsert_runapi_page 测试");

$upsertContent = [
  'info' => [
    'from' => 'runapi',
    'type' => 'api',
    'method' => 'post',
    'url' => '/api/test/register',
    'description' => '注册接口',
    'remark' => '',
  ],
  'request' => [
    'params' => ['mode' => 'json', 'json' => '', 'jsonDesc' => [], 'formdata' => [], 'urlencoded' => []],
    'headers' => [],
    'query' => [],
    'pathVariable' => [],
    'cookies' => [],
    'auth' => [],
  ],
  'response' => [
    'responseExample' => '{"code":0}',
    'responseParamsDesc' => [],
    'responseFailExample' => '',
    'responseFailParamsDesc' => [],
  ],
  'scripts' => ['pre' => '', 'post' => ''],
  'apiStatus' => '0',
];

// 5.1 upsert 创建新页面
$result = $tester->callTool('upsert_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[MCP测试] upsert注册接口',
  'page_content' => $upsertContent,
], $token);

$upsertPageId = null;
if (hasError($result)) {
  $tester->recordTest('upsert_runapi_page (创建)', false, extractError($result));
} else {
  $data = extractData($result);
  $upsertPageId = $data['page_id'] ?? null;
  $message = $data['message'] ?? '';
  if ($upsertPageId) {
    $tester->addCreatedPage($upsertPageId);
    $tester->recordTest('upsert_runapi_page (创建)', true, "页面 ID: $upsertPageId, $message");
  } else {
    $tester->recordTest('upsert_runapi_page (创建)', true, $message);
  }
}

// 5.2 upsert 更新已有页面（相同标题）
$updatedUpsertContent = $upsertContent;
$updatedUpsertContent['info']['url'] = '/api/v2/test/register';

$result = $tester->callTool('upsert_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[MCP测试] upsert注册接口',
  'page_content' => $updatedUpsertContent,
], $token);

if (hasError($result)) {
  $tester->recordTest('upsert_runapi_page (更新)', false, extractError($result));
} else {
  $data = extractData($result);
  $message = $data['message'] ?? '';
  $tester->recordTest('upsert_runapi_page (更新)', true, $message);

  // 验证 URL 已更新
  $verifyResult = $tester->callTool('get_runapi_page', [
    'item_id' => $runapiItemId,
    'page_title' => '[MCP测试] upsert注册接口',
  ], $token);
  if (!hasError($verifyResult)) {
    $verifyData = extractData($verifyResult);
    $verifyUrl = $verifyData['page_content']['info']['url'] ?? '';
    $tester->recordTest(
      'upsert_runapi_page (更新) URL 验证',
      $verifyUrl === '/api/v2/test/register',
      $verifyUrl === '/api/v2/test/register' ? 'URL 已更新' : "URL = $verifyUrl"
    );
  }
}

// ============================================================================
// 6. 参数校验测试
// ============================================================================

printHeader("6. 参数校验测试");

// 6.1 缺少 info.url
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[校验测试] 缺少URL',
  'page_content' => [
    'info' => [
      'from' => 'runapi',
      'type' => 'api',
      'method' => 'post',
      'description' => 'test',
    ],
    'request' => [],
    'response' => [],
  ],
], $token);

$error = extractError($result);
$tester->recordTest(
  '校验: 缺少 info.url 被拒绝',
  hasError($result) && strpos($error, 'info.url') !== false,
  hasError($result) ? $error : '应该返回错误'
);

// 6.2 缺少 info.method
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[校验测试] 缺少method',
  'page_content' => [
    'info' => [
      'from' => 'runapi',
      'type' => 'api',
      'url' => '/api/test',
      'description' => 'test',
    ],
    'request' => [],
    'response' => [],
  ],
], $token);

$error = extractError($result);
$tester->recordTest(
  '校验: 缺少 info.method 被拒绝',
  hasError($result) && strpos($error, 'info.method') !== false,
  hasError($result) ? $error : '应该返回错误'
);

// 6.3 无效的 method
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[校验测试] 无效method',
  'page_content' => [
    'info' => [
      'from' => 'runapi',
      'type' => 'api',
      'method' => 'invalidmethod',
      'url' => '/api/test',
      'description' => 'test',
    ],
    'request' => [],
    'response' => [],
  ],
], $token);

$error = extractError($result);
$tester->recordTest(
  '校验: 无效 method 被拒绝',
  hasError($result) && (strpos($error, 'method') !== false || strpos($error, '无效') !== false),
  hasError($result) ? $error : '应该返回错误'
);

// 6.4 page_content 不是对象（传字符串）
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[校验测试] content非对象',
  'page_content' => 'not a json object',
], $token);

$error = extractError($result);
$tester->recordTest(
  '校验: page_content 非对象被拒绝',
  hasError($result),
  hasError($result) ? $error : '应该返回错误'
);

// 6.5 对非 RunApi 项目调用 create_runapi_page
if ($normalItemId) {
  $result = $tester->callTool('create_runapi_page', [
    'item_id' => $normalItemId,
    'page_title' => '[校验测试] 非RunApi项目',
    'page_content' => [
      'info' => [
        'from' => 'runapi',
        'type' => 'api',
        'method' => 'get',
        'url' => '/api/test',
        'description' => 'test',
      ],
      'request' => [],
      'response' => [],
    ],
  ], $token);

  $error = extractError($result);
  $tester->recordTest(
    '校验: 非 RunApi 项目被拒绝',
    hasError($result) && (strpos($error, 'RunApi') !== false || strpos($error, 'item_type') !== false),
    hasError($result) ? $error : '应该返回错误'
  );
} else {
  $tester->recordTest('校验: 非 RunApi 项目被拒绝', false, '跳过：没有普通项目');
}

// 6.6 对普通页面调用 get_runapi_page
if ($normalPageId) {
  $result = $tester->callTool('get_runapi_page', ['page_id' => $normalPageId], $token);

  $error = extractError($result);
  $tester->recordTest(
    '校验: 普通页面 get_runapi_page 被拒绝',
    hasError($result) && (strpos($error, 'RunApi') !== false || strpos($error, 'item_type') !== false),
    hasError($result) ? $error : '应该返回错误'
  );
} else {
  $tester->recordTest('校验: 普通页面 get_runapi_page 被拒绝', false, '跳过：没有普通页面');
}

// 6.7 重复标题创建应被拒绝（使用当前标题，因为4.3节已将标题改为v2）
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[MCP测试] 登录接口-v2',
  'cat_name' => '用户模块',
  'page_content' => [
    'info' => ['method' => 'get', 'url' => '/api/test'],
    'request' => [],
    'response' => [],
  ],
], $token);

$error = extractError($result);
$tester->recordTest(
  '校验: 重复标题被拒绝',
  hasError($result) && strpos($error, '已存在') !== false,
  hasError($result) ? $error : '应该返回标题已存在错误'
);

// ============================================================================
// 7. 乐观锁测试
// ============================================================================

printHeader("7. 乐观锁测试");

if ($runapiPageId) {
  // 先获取当前 hash
  $result = $tester->callTool('get_runapi_page', ['page_id' => $runapiPageId], $token);
  $currentHash = '';
  if (!hasError($result)) {
    $currentHash = extractData($result, 'content_hash') ?? '';
  }

  // 7.1 用错误的 hash 更新，应该冲突
  $result = $tester->callTool('update_runapi_page', [
    'page_id' => $runapiPageId,
    'page_content' => $runapiJsonContent,
    'expected_hash' => 'wrong_hash_value',
  ], $token);

  $error = extractError($result);
  $tester->recordTest(
    '乐观锁: 错误 hash 导致版本冲突',
    hasError($result) && (strpos($error, '版本冲突') !== false || strpos($error, 'version_conflict') !== false || strpos($error, '已被') !== false),
    hasError($result) ? $error : '应该返回版本冲突错误'
  );

  // 7.2 用正确的 hash 更新，应该成功
  if (!empty($currentHash)) {
    $result = $tester->callTool('update_runapi_page', [
      'page_id' => $runapiPageId,
      'page_content' => $runapiJsonContent,
      'expected_hash' => $currentHash,
    ], $token);

    $tester->recordTest(
      '乐观锁: 正确 hash 更新成功',
      !hasError($result),
      hasError($result) ? extractError($result) : '更新成功'
    );
  } else {
    $tester->recordTest('乐观锁: 正确 hash 更新成功', false, '跳过：无法获取当前 hash');
  }
} else {
  $tester->recordTest('乐观锁测试', false, '跳过：没有可用的测试页面');
}

// ===========================================================================
// 8. 读写往返一致性测试（6 场景）
// ===========================================================================

printHeader("8. 读写往返一致性测试（6 场景）");

// --- 辅助函数 ---

/**
 * 递归逐字段比对，记录不匹配路径
 */
function recursiveCompare($expected, $actual, string $path, array &$mismatches): void
{
  if (is_array($expected) && is_array($actual)) {
    // Check all keys in expected
    foreach ($expected as $key => $expVal) {
      $subPath = $path === '' ? (string) $key : "$path.$key";
      if (!array_key_exists($key, $actual)) {
        $mismatches[] = "$subPath: key missing in read data (expected: " . json_encode($expVal, JSON_UNESCAPED_UNICODE) . ")";
        continue;
      }
      recursiveCompare($expVal, $actual[$key], $subPath, $mismatches);
    }
    // Check for unexpected extra keys in actual
    foreach ($actual as $key => $actVal) {
      if (!array_key_exists($key, $expected)) {
        $subPath = $path === '' ? (string) $key : "$path.$key";
        $mismatches[] = "$subPath: unexpected extra key in read data";
      }
    }
  } else {
    if ($expected !== $actual) {
      $mismatches[] = "$path: expected " . json_encode($expected, JSON_UNESCAPED_UNICODE)
        . ", got " . json_encode($actual, JSON_UNESCAPED_UNICODE);
    }
  }
}

/**
 * 递归排序数组 key，使 json_encode 结果可比较
 */
function normalizeForComparison($data)
{
  if (!is_array($data)) {
    return $data;
  }
  // Check if indexed (list) array
  if (array_values($data) === $data && empty($data) === false && array_keys($data) === range(0, count($data) - 1)) {
    // Indexed: normalize each element, keep order
    return array_map('normalizeForComparison', $data);
  }
  // Associative: sort keys, normalize values
  $result = [];
  $keys = array_keys($data);
  sort($keys);
  foreach ($keys as $key) {
    $result[$key] = normalizeForComparison($data[$key]);
  }
  return $result;
}

/**
 * 运行单个场景的读写往返测试
 */
function runRoundtripScenario(McpTester $tester, string $token, int $itemId, string $scenarioNum, string $scenarioDesc, array $originalData, bool $fullCompare = true): ?int
{
  $pageId = null;

  // 写入
  $result = $tester->callTool('create_runapi_page', [
    'item_id' => $itemId,
    'page_title' => "[MCP测试] 往返{$scenarioNum} - {$scenarioDesc}",
    'page_content' => $originalData,
  ], $token);

  if (hasError($result)) {
    $tester->recordTest("场景{$scenarioNum}: 写入", false, extractError($result));
    return null;
  }

  $pageId = extractData($result, 'page_id');
  if (!$pageId) {
    $tester->recordTest("场景{$scenarioNum}: 写入", false, '无法解析页面ID');
    return null;
  }

  $tester->addCreatedPage($pageId);
  $tester->recordTest("场景{$scenarioNum}: 写入", true, "页面 ID: $pageId");

  // 读回
  $result = $tester->callTool('get_runapi_page', ['page_id' => $pageId], $token);
  if (hasError($result)) {
    $tester->recordTest("场景{$scenarioNum}: 读回", false, extractError($result));
    return $pageId;
  }

  $readContent = extractData($result, 'page_content');

  if ($fullCompare) {
    // 递归逐字段比对
    $mismatches = [];
    recursiveCompare($originalData, $readContent, '', $mismatches);

    $tester->recordTest(
      "场景{$scenarioNum}: 逐字段比对",
      empty($mismatches),
      empty($mismatches)
        ? '全部字段匹配'
        : count($mismatches) . ' 处不一致: ' . implode('; ', array_slice($mismatches, 0, 5))
          . (count($mismatches) > 5 ? ' ... (共' . count($mismatches) . '处)' : '')
    );

    // JSON 字符串级精确比对
    $origJson = json_encode(normalizeForComparison($originalData), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $readJson = json_encode(normalizeForComparison($readContent), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tester->recordTest(
      "场景{$scenarioNum}: JSON 精确比对",
      $origJson === $readJson,
      $origJson === $readJson
        ? '完全一致'
        : 'JSON 不一致（可能字段顺序或默认值差异）'
    );

    if ($origJson !== $readJson) {
      printInfo("  期望: " . mb_substr($origJson, 0, 200) . (mb_strlen($origJson) > 200 ? '...' : ''));
      printInfo("  实际: " . mb_substr($readJson, 0, 200) . (mb_strlen($readJson) > 200 ? '...' : ''));
    }
  }

  return $pageId;
}

// --- 执行 6 个场景 ---

if ($runapiItemId) {

  // ------------------------------------------------------------------
  // 场景 8.1：完整 RESTful 接口（POST）
  // ------------------------------------------------------------------
  $scenario81 = [
    'info' => [
      'name' => '用户登录',
      'url' => '/api/v2/auth/login',
      'method' => 'POST',
      'description' => '用户登录接口，支持手机号和邮箱登录',
      'contentType' => 'json',
      'remark' => '需要先获取验证码',
    ],
    'request' => [
      'headers' => [
        ['name' => 'X-Request-Id', 'value' => '{{$uuid}}', 'desc' => '请求唯一标识'],
        ['name' => 'Accept-Language', 'value' => 'zh-CN', 'desc' => '语言'],
      ],
      'params' => [
        ['name' => 'grant_type', 'value' => 'password', 'desc' => '授权类型', 'type' => 'string'],
        ['name' => 'scope', 'value' => 'read write', 'desc' => '权限范围'],
      ],
      'json' => '{"username":"test@example.com","password":"{{password}}"}',
      'body' => '{"username":"test@example.com","password":"{{password}}"}',
      'auth' => [
        'type' => 'bearer',
        'token' => '{{$token}}',
      ],
    ],
    'response' => [
      'statusCode' => 200,
      'headers' => [
        ['name' => 'Content-Type', 'value' => 'application/json; charset=utf-8'],
      ],
      'json' => '{"code":0,"message":"success","data":{"user_id":12345,"token":"eyJhbGci..."}}',
      'body' => '{"code":0,"message":"success","data":{"user_id":12345,"token":"eyJhbGci..."}}',
    ],
    'jsonDesc' => '{"request":{"method":"POST","url":"/api/v2/auth/login","header":[{"key":"X-Request-Id","value":"{{$uuid}}"},{"key":"Accept-Language","value":"zh-CN"}],"query":[{"key":"grant_type","value":"password"}],"body":{"mode":"json","json":"{\\"username\\":\\"test@example.com\\",\\"password\\":\\"{{password}}\\"}"}},"response":[{"code":200,"body":{"mode":"json","json":"{\\"code\\":0,\\"message\\":\\"success\\"}"}}]}',
    'pathVariable' => '{"id":"123","name":"test"}',
  ];

  runRoundtripScenario($tester, $token, $runapiItemId, '8.1', '完整RESTful-POST', $scenario81);

  // ------------------------------------------------------------------
  // 场景 8.2：GET + path 参数 + 多种参数类型
  // ------------------------------------------------------------------
  $scenario82 = [
    'info' => [
      'name' => '获取用户订单',
      'url' => '/api/users/{id}/orders/{orderId}',
      'method' => 'GET',
      'description' => '获取指定用户的某个订单详情',
      'contentType' => '',
    ],
    'request' => [
      'headers' => [
        ['name' => 'Authorization', 'value' => 'Bearer {{token}}', 'desc' => '认证令牌'],
      ],
      'params' => [
        ['name' => 'include_details', 'value' => 'true', 'desc' => '是否包含详情', 'type' => 'boolean'],
        ['name' => 'limit', 'value' => '10', 'desc' => '数量限制', 'type' => 'number'],
        ['name' => 'fields', 'value' => 'id,name,status', 'desc' => '返回字段', 'type' => 'string'],
      ],
    ],
    'response' => [
      'statusCode' => 200,
      'body' => '{"order_id":"ORD-20240101","items":[{"sku":"SKU001","qty":2,"price":99.9},{"sku":"SKU002","qty":1,"price":199.0}],"total":398.8}',
      'headers' => [
        ['name' => 'X-Total-Count', 'value' => '1'],
      ],
    ],
  ];

  runRoundtripScenario($tester, $token, $runapiItemId, '8.2', 'GET+path参数+多类型', $scenario82);

  // ------------------------------------------------------------------
  // 场景 8.3：PATCH + form-data + basic auth
  // ------------------------------------------------------------------
  $scenario83 = [
    'info' => [
      'name' => '更新用户头像',
      'url' => '/api/users/{id}/avatar',
      'method' => 'PATCH',
      'description' => '上传或更新用户头像',
      'contentType' => 'form-data',
    ],
    'request' => [
      'headers' => [],
      'params' => [
        ['name' => 'avatar', 'value' => '', 'desc' => '头像文件', 'type' => 'file'],
        ['name' => 'crop_x', 'value' => '0', 'desc' => '裁剪起始X', 'type' => 'number'],
        ['name' => 'crop_y', 'value' => '0', 'desc' => '裁剪起始Y', 'type' => 'number'],
      ],
      'auth' => [
        'type' => 'basic',
        'username' => 'admin',
        'password' => 's3cretP@ss!',
      ],
    ],
    'response' => [
      'statusCode' => 200,
      'body' => '{"avatar_url":"https://cdn.example.com/avatars/123.png","updated":true}',
    ],
  ];

  runRoundtripScenario($tester, $token, $runapiItemId, '8.3', 'PATCH+form-data+basic-auth', $scenario83);

  // ------------------------------------------------------------------
  // 场景 8.4：带前后置脚本
  // ------------------------------------------------------------------
  $preScript = <<<'SCRIPT'
// Pre-request script
const env = pm.environment;
env.set("timestamp", Date.now());
if (env.get("debug_mode") === "true") {
  console.log("Debug: request starting");
}
const config = JSON.parse(env.get("api_config"));
SCRIPT;

  $postScript = <<<'SCRIPT'
// Post-request script (test)
const resp = pm.response.json();
pm.test("Status code is 200", function() {
  pm.response.to.have.status(200);
});
if (resp.code === 0) {
  pm.environment.set("token", resp.data.token);
  console.log("Token saved");
} else {
  console.error("Login failed: " + resp.message);
}
SCRIPT;

  $scenario84 = [
    'info' => [
      'name' => '带脚本接口',
      'url' => '/api/scripted-endpoint',
      'method' => 'POST',
      'description' => '包含前后置脚本的接口测试',
    ],
    'request' => [
      'json' => '{"action":"test"}',
    ],
    'response' => [
      'statusCode' => 200,
      'body' => '{"result":"ok"}',
    ],
    'scripts' => [
      'pre_script' => $preScript,
      'test_script' => $postScript,
    ],
  ];

  runRoundtripScenario($tester, $token, $runapiItemId, '8.4', '前后置脚本', $scenario84);

  // ------------------------------------------------------------------
  // 场景 8.5：最小合法数据
  // ------------------------------------------------------------------
  $scenario85 = [
    'info' => [
      'url' => '/api/minimal',
      'method' => 'GET',
    ],
  ];

  // 最小数据场景：只验证必要字段存活，不做完整递归比对
  $result = $tester->callTool('create_runapi_page', [
    'item_id' => $runapiItemId,
    'page_title' => '[MCP测试] 往返8.5 - 最小合法数据',
    'page_content' => $scenario85,
  ], $token);

  $pageId85 = null;
  if (hasError($result)) {
    $tester->recordTest('场景8.5: 写入', false, extractError($result));
  } else {
    $pageId85 = extractData($result, 'page_id');
    if ($pageId85) {
      $tester->addCreatedPage($pageId85);
      $tester->recordTest('场景8.5: 写入', true, "页面 ID: $pageId85");

      $result = $tester->callTool('get_runapi_page', ['page_id' => $pageId85], $token);
      if (hasError($result)) {
        $tester->recordTest('场景8.5: 读回', false, extractError($result));
      } else {
        $readContent85 = extractData($result, 'page_content');

        // 验证必填字段存活
        $urlOk = ($readContent85['info']['url'] ?? '') === '/api/minimal';
        $methodOk = ($readContent85['info']['method'] ?? '') === 'GET';

        $tester->recordTest(
          '场景8.5: info.url 保留',
          $urlOk,
          $urlOk ? '/api/minimal' : '实际: ' . ($readContent85['info']['url'] ?? 'null')
        );
        $tester->recordTest(
          '场景8.5: info.method 保留',
          $methodOk,
          $methodOk ? 'GET' : '实际: ' . ($readContent85['info']['method'] ?? 'null')
        );

        // 验证读回的数据不崩溃、不返回 null
        $isArr = is_array($readContent85);
        $tester->recordTest(
          '场景8.5: 读回数据为数组',
          $isArr,
          $isArr ? '正常' : '类型: ' . gettype($readContent85)
        );

        if ($isArr) {
          // 原始写入的数据字段应该全部在读取结果中（可能多出默认字段）
          $mismatches85 = [];
          recursiveCompare($scenario85, $readContent85, '', $mismatches85);
          $tester->recordTest(
            '场景8.5: 原始字段全部保留',
            empty($mismatches85),
            empty($mismatches85)
              ? '原始写入字段全部存在且值正确'
              : implode('; ', $mismatches85)
          );
        }
      }
    } else {
      $tester->recordTest('场景8.5: 写入', false, '无法解析页面ID');
    }
  }

  // ------------------------------------------------------------------
  // 场景 8.6：特殊字符和转义
  // ------------------------------------------------------------------
  $scenario86 = [
    'info' => [
      'name' => '特殊字符测试',
      'url' => '/api/搜索?keyword=hello%20world&q=test%26debug',
      'method' => 'POST',
      'description' => "包含引号\"和换行\n以及emoji\xF0\x9F\x8C\x99\xF0\x9F\x94\xA5\xE2\x9C\x85",
    ],
    'request' => [
      'json' => '{"key":"value with \\"quotes\\"","nested":"{\\"inner\\":\\"val\\"}"}',
      'params' => [
        ['name' => '中文参数', 'value' => '值<>&"\'', 'desc' => '特殊<>&值'],
      ],
    ],
    'response' => [
      'body' => '{"msg":"你好世界\xF0\x9F\x8C\x8D","tags":["tag1","tag\xE2\x82\x82"]}',
    ],
  ];

  runRoundtripScenario($tester, $token, $runapiItemId, '8.6', '特殊字符和转义', $scenario86);

} else {
  $tester->recordTest('往返一致性测试', false, '跳过：没有可用的测试项目');
}

// ============================================================================
// 9. 工具列表验证
// ============================================================================

printHeader("9. 工具列表验证");

$result = $tester->listTools($token);
if (!hasError($result)) {
  $tools = $result['body']['result']['tools'] ?? [];
  $toolNames = array_column($tools, 'name');

  $requiredTools = ['get_runapi_page', 'create_runapi_page', 'update_runapi_page', 'upsert_runapi_page'];
  foreach ($requiredTools as $requiredTool) {
    $found = in_array($requiredTool, $toolNames);
    $tester->recordTest(
      "工具列表包含 $requiredTool",
      $found,
      $found ? '已注册' : '未找到'
    );
  }
} else {
  $tester->recordTest('工具列表验证', false, extractError($result));
}

// ============================================================================
// 10. 边界场景测试
// ============================================================================

printHeader("10. 边界场景测试");

// 10.1 page_content 为空 JSON 对象 {}
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[边界测试] 空JSON对象',
  'page_content' => [],
], $token);

$error = extractError($result);
$tester->recordTest(
  '边界: page_content 空对象 {} 被拒绝',
  hasError($result) && (strpos($error, 'info.url') !== false || strpos($error, 'info.method') !== false),
  hasError($result) ? $error : '应该返回错误（缺少 info.url/info.method）'
);

// 10.2 JSON 深度超过 10 层
$deepContent = ['value' => 'leaf'];
for ($i = 0; $i < 12; $i++) {
  $deepContent = ['level' => $deepContent];
}
$deepContent['info'] = [
  'from' => 'runapi',
  'type' => 'api',
  'method' => 'get',
  'url' => '/api/deep',
];
$deepContent['request'] = [];
$deepContent['response'] = [];

$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[边界测试] 超深JSON',
  'page_content' => $deepContent,
], $token);

$error = extractError($result);
$tester->recordTest(
  '边界: JSON 深度超过 10 层被拒绝',
  hasError($result) && strpos($error, '10') !== false,
  hasError($result) ? $error : '应该返回深度超限错误'
);

// 10.3 get_runapi_page 不传任何参数
$result = $tester->callTool('get_runapi_page', [], $token);

$error = extractError($result);
$tester->recordTest(
  '边界: get_runapi_page 无参数被拒绝',
  hasError($result) && (strpos($error, 'page_id') !== false || strpos($error, '参数') !== false),
  hasError($result) ? $error : '应该返回参数错误'
);

// 10.4 已删除页面访问
$deletedTestPageId = null;
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[边界测试] 待删除页面',
  'page_content' => [
    'info' => ['from' => 'runapi', 'type' => 'api', 'method' => 'get', 'url' => '/api/to-delete'],
    'request' => [],
    'response' => [],
  ],
], $token);

if (hasError($result)) {
  $tester->recordTest('边界: 已删除页面访问 (准备)', false, extractError($result));
} else {
  $deletedTestPageId = extractData($result, 'page_id');
  if ($deletedTestPageId) {
    // 记录以便清理（如果删除失败）
    $tester->addCreatedPage($deletedTestPageId);

    // 删除页面
    $delResult = $tester->callTool('delete_page', ['page_id' => $deletedTestPageId], $token);
    if (hasError($delResult)) {
      $tester->recordTest('边界: 已删除页面访问 (删除步骤)', false, extractError($delResult));
    } else {
      // 页面已成功删除，从清理列表中移除以避免清理阶段报错
      $key = array_search($deletedTestPageId, $tester->createdPages);
      if ($key !== false) {
        unset($tester->createdPages[$key]);
        $tester->createdPages = array_values($tester->createdPages);
      }

      // 尝试获取已删除的页面
      $result = $tester->callTool('get_runapi_page', ['page_id' => $deletedTestPageId], $token);
      $error = extractError($result);
      $tester->recordTest(
        '边界: 已删除页面访问返回不存在',
        hasError($result) && (strpos($error, '不存在') !== false || strpos($error, 'not_found') !== false),
        hasError($result) ? $error : '应该返回页面不存在'
      );
    }
  } else {
    $tester->recordTest('边界: 已删除页面访问 (准备)', false, '无法解析页面ID');
  }
}

// 10.5 只读权限调用写操作
$readOnlyTokenData = $tokenManager->getOrCreateReadOnlyToken();
$readOnlyToken = $readOnlyTokenData['token'];

// 只读 Token 创建 RunApi 页面
$result = $tester->callTool('create_runapi_page', [
  'item_id' => $runapiItemId,
  'page_title' => '[边界测试] 只读写操作',
  'page_content' => [
    'info' => ['from' => 'runapi', 'type' => 'api', 'method' => 'get', 'url' => '/api/readonly'],
    'request' => [],
    'response' => [],
  ],
], $readOnlyToken);

$error = extractError($result);
$tester->recordTest(
  '边界: 只读 Token create_runapi_page 被拒绝',
  hasError($result) && (strpos($error, '写入') !== false || strpos($error, '权限') !== false || strpos($error, '只读') !== false || strpos($error, 'permission') !== false),
  hasError($result) ? $error : '应该返回权限错误'
);

// 只读 Token 更新 RunApi 页面
if ($runapiPageId) {
  $result = $tester->callTool('update_runapi_page', [
    'page_id' => $runapiPageId,
    'page_title' => '[边界测试] 只读更新尝试',
  ], $readOnlyToken);

  $error = extractError($result);
  $tester->recordTest(
    '边界: 只读 Token update_runapi_page 被拒绝',
    hasError($result) && (strpos($error, '写入') !== false || strpos($error, '权限') !== false || strpos($error, '只读') !== false || strpos($error, 'permission') !== false),
    hasError($result) ? $error : '应该返回权限错误'
  );
} else {
  $tester->recordTest('边界: 只读 Token update_runapi_page 被拒绝', false, '跳过：没有可用的测试页面');
}

// ============================================================================
// 11. 清理测试数据
// ============================================================================

printHeader("11. 清理测试数据");

// 删除创建的页面
foreach ($tester->createdPages as $pageId) {
  $result = $tester->callTool('delete_page', ['page_id' => $pageId], $token);
  $success = !hasError($result);
  $errorMsg = $success ? '' : extractError($result);
  printInfo("删除页面 $pageId: " . ($success ? '成功' : "失败 - $errorMsg"));
  $tester->recordTest("清理-删除页面 $pageId", $success, $success ? '成功' : $errorMsg);
}

// 删除创建的项目（包括 RunApi 项目和普通项目）
foreach ($tester->createdItems as $itemId) {
  $result = $tester->callTool('delete_item', ['item_id' => $itemId], $token);
  $success = !hasError($result);
  $errorMsg = $success ? '' : extractError($result);
  printInfo("删除项目 $itemId: " . ($success ? '成功' : "失败 - $errorMsg"));
  $tester->recordTest("清理-删除项目 $itemId", $success, $success ? '成功' : $errorMsg);
}

// 清理测试 Token
$tokenManager->cleanup();
printInfo("清理测试 Token");

// ============================================================================
// 测试总结
// ============================================================================

$tester->printSummary();

echo "\nRunApi 测试完成！\n";
