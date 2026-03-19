<?php

/**
 * ShowDoc MCP 测试引导文件（开源版）
 *
 * 包含公共函数、数据库初始化、McpTester 类
 *
 * 安全说明：此文件只能通过命令行执行，禁止 web 访问
 */

// 只允许命令行执行
if (php_sapi_name() !== 'cli') {
  http_response_code(403);
  exit('Forbidden: This script can only be run from command line');
}

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 加载 Composer 自动加载
$serverPath = dirname(__DIR__, 2); // server/tests/mcp -> server
$rootPath = dirname($serverPath);  // server -> 根目录

require $serverPath . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Illuminate\Database\Capsule\Manager as Capsule;

// 加载根目录 .env
if (is_file($rootPath . '/.env')) {
  Dotenv::createImmutable($rootPath)->load();
}

// 定义 RUNTIME_PATH 常量
if (!defined('RUNTIME_PATH')) {
  define('RUNTIME_PATH', $serverPath . '/app/Runtime/');
}

// 初始化数据库连接
$capsule = new Capsule();
$dbType = $_ENV['DB_TYPE'] ?? getenv('DB_TYPE') ?: 'sqlite';

if ($dbType === 'sqlite') {
  // 开源版数据库路径：项目根目录/Sqlite/showdoc.db.php
  $dbPath = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: $rootPath . '/Sqlite/showdoc.db.php';
  $capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => $dbPath,
    'prefix'   => '',
    'options'  => [PDO::ATTR_STRINGIFY_FETCHES => true],
  ]);
} else {
  $capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
    'port'      => (int) ($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306),
    'database'  => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'showdoc',
    'username'  => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
    'password'  => $_ENV['DB_PWD'] ?? getenv('DB_PWD') ?: '',
    'charset'   => $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'options'   => [PDO::ATTR_STRINGIFY_FETCHES => true],
  ]);
}
$capsule->setAsGlobal();
$capsule->bootEloquent();

// ============================================================================
// 颜色输出函数
// ============================================================================

function colorOutput($text, $color = 'white')
{
  $colors = [
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'white' => "\033[37m",
    'reset' => "\033[0m",
  ];
  return $colors[$color] . $text . $colors['reset'];
}

function printHeader($text)
{
  echo "\n" . colorOutput("=" . str_repeat("=", strlen($text) + 2) . "=", 'blue') . "\n";
  echo colorOutput("| $text |", 'blue') . "\n";
  echo colorOutput("=" . str_repeat("=", strlen($text) + 2) . "=", 'blue') . "\n\n";
}

function printSuccess($text)
{
  echo colorOutput("  ✓ $text", 'green') . "\n";
}

function printError($text)
{
  echo colorOutput("  ✗ $text", 'red') . "\n";
}

function printInfo($text)
{
  echo colorOutput("  ℹ $text", 'yellow') . "\n";
}

function printDebug($text)
{
  global $debugMode;
  if ($debugMode ?? false) {
    echo colorOutput("  [DEBUG] $text", 'white') . "\n";
  }
}

// ============================================================================
// 测试用户管理
// ============================================================================

class TestUserManager
{
  /**
   * 获取测试用户
   * 
   * @param string $type 'admin' 或 'normal'
   * @return array|null 用户信息
   */
  public static function getTestUser($type = 'admin')
  {
    $groupId = ($type === 'admin') ? 1 : 2;

    $user = Capsule::table('user')
      ->where('groupid', $groupId)
      ->orderBy('uid', 'asc')
      ->first();

    if (!$user) {
      return null;
    }

    return [
      'uid' => (int) $user->uid,
      'username' => $user->username ?? '',
      'groupid' => (int) $user->groupid,
      'type' => $type,
    ];
  }

  /**
   * 获取所有测试用户
   */
  public static function getAllTestUsers()
  {
    $admins = Capsule::table('user')
      ->where('groupid', 1)
      ->orderBy('uid', 'asc')
      ->limit(2)
      ->get();

    $normals = Capsule::table('user')
      ->where('groupid', 2)
      ->orderBy('uid', 'asc')
      ->limit(2)
      ->get();

    $users = [];
    foreach ($admins as $user) {
      $users[] = [
        'uid' => (int) $user->uid,
        'username' => $user->username ?? '',
        'groupid' => 1,
        'type' => 'admin',
      ];
    }
    foreach ($normals as $user) {
      $users[] = [
        'uid' => (int) $user->uid,
        'username' => $user->username ?? '',
        'groupid' => 2,
        'type' => 'normal',
      ];
    }

    return $users;
  }

  /**
   * 检查用户是否绑定邮箱
   */
  public static function hasEmail($uid)
  {
    $user = Capsule::table('user')
      ->where('uid', $uid)
      ->first();

    return !empty($user->email);
  }

  /**
   * 获取用户项目数量
   */
  public static function getItemCount($uid)
  {
    return Capsule::table('item')
      ->where('uid', $uid)
      ->where('is_del', 0)
      ->count();
  }

  /**
   * 获取用户已用空间
   */
  public static function getUsedSpace($uid)
  {
    $result = Capsule::table('upload_file')
      ->where('uid', $uid)
      ->sum('file_size');

    return (int) $result;
  }
}

// ============================================================================
// Token 管理类
// ============================================================================

class TokenManager
{
  private $uid;
  private $createdTokens = [];

  public function __construct($uid)
  {
    $this->uid = $uid;
  }

  /**
   * 生成 AI Token
   */
  public function createToken($permission = 'write', $scope = 'all', $allowedItems = [], $name = '')
  {
    $token = 'ai_' . bin2hex(random_bytes(43));

    $data = [
      'uid' => $this->uid,
      'token' => $token,
      'name' => $name ?: "MCP测试 Token " . date('Y-m-d H:i:s'),
      'permission' => $permission,
      'scope' => $scope,
      'allowed_items' => empty($allowedItems) ? null : json_encode($allowedItems),
      'can_create_item' => 1,
      'can_delete_item' => 1,
      'is_active' => 1,
      'created_at' => date('Y-m-d H:i:s'),
    ];

    $id = Capsule::table('user_ai_token')->insertGetId($data);
    $this->createdTokens[] = $id;

    return [
      'id' => $id,
      'token' => $token,
      'permission' => $permission,
      'scope' => $scope,
      'allowed_items' => $allowedItems,
      'can_create_item' => 1,
      'can_delete_item' => 1,
    ];
  }

  /**
   * 获取或创建读写 Token（确保有删除权限）
   */
  public function getOrCreateWriteToken()
  {
    // 优先查找有删除权限的 Token
    $existing = Capsule::table('user_ai_token')
      ->where('uid', $this->uid)
      ->where('permission', 'write')
      ->where('scope', 'all')
      ->where('can_delete_item', 1)
      ->where('is_active', 1)
      ->first();

    if ($existing) {
      return (array) $existing;
    }

    // 如果没有，创建新的 Token（带删除权限）
    return $this->createToken('write', 'all', [], 'MCP测试-读写Token');
  }

  /**
   * 获取或创建只读 Token
   */
  public function getOrCreateReadOnlyToken()
  {
    $existing = Capsule::table('user_ai_token')
      ->where('uid', $this->uid)
      ->where('permission', 'read')
      ->where('is_active', 1)
      ->first();

    if ($existing) {
      return (array) $existing;
    }

    return $this->createToken('read', 'all', [], 'MCP测试-只读Token');
  }

  /**
   * 获取或创建指定项目范围的 Token
   */
  public function getOrCreateScopedToken($allowedItems)
  {
    $existing = Capsule::table('user_ai_token')
      ->where('uid', $this->uid)
      ->where('scope', 'selected')
      ->where('is_active', 1)
      ->first();

    if ($existing) {
      return (array) $existing;
    }

    return $this->createToken('write', 'selected', $allowedItems, 'MCP测试-指定项目Token');
  }

  /**
   * 清理测试创建的 Token
   */
  public function cleanup()
  {
    if (!empty($this->createdTokens)) {
      Capsule::table('user_ai_token')
        ->whereIn('id', $this->createdTokens)
        ->delete();
    }
  }
}

// ============================================================================
// MCP 测试类
// ============================================================================

class McpTester
{
  private $baseUrl;
  private $tokens = [];
  private $testResults = [];
  public $createdItems = [];
  public $createdPages = [];
  public $createdCatalogs = [];
  public $uploadedFileSigns = [];

  public function __construct($baseUrl)
  {
    $this->baseUrl = $baseUrl;
  }

  /**
   * 发送 MCP 请求
   */
  public function sendRequest($method, $params = [], $token = null)
  {
    $requestData = [
      'jsonrpc' => '2.0',
      'id' => uniqid(),
      'method' => $method,
      'params' => $params,
    ];

    $headers = ['Content-Type: application/json'];
    if ($token) {
      $headers[] = "Authorization: Bearer $token";
    }

    $ch = curl_init($this->baseUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
      return ['error' => $error, 'http_code' => $httpCode];
    }

    return [
      'http_code' => $httpCode,
      'body' => json_decode($response, true),
    ];
  }

  /**
   * 调用 MCP Tool
   */
  public function callTool($name, $arguments = [], $token = null)
  {
    return $this->sendRequest('tools/call', [
      'name' => $name,
      'arguments' => $arguments,
    ], $token);
  }

  /**
   * 列出可用 Tools
   */
  public function listTools($token = null)
  {
    return $this->sendRequest('tools/list', [], $token);
  }

  /**
   * 初始化 MCP 连接
   */
  public function initialize()
  {
    return $this->sendRequest('initialize', [
      'protocolVersion' => '2024-11-05',
      'capabilities' => [],
      'clientInfo' => [
        'name' => 'showdoc-mcp-test',
        'version' => '1.0.0',
      ],
    ]);
  }

  /**
   * 记录测试结果
   */
  public function recordTest($name, $passed, $message = '')
  {
    $this->testResults[] = [
      'name' => $name,
      'passed' => $passed,
      'message' => $message,
    ];

    if ($passed) {
      printSuccess($name . ($message ? " - $message" : ''));
    } else {
      printError($name . ($message ? " - $message" : ''));
    }
  }

  /**
   * 设置 Token
   */
  public function setToken($name, $token)
  {
    $this->tokens[$name] = $token;
  }

  /**
   * 获取 Token
   */
  public function getToken($name)
  {
    return $this->tokens[$name] ?? null;
  }

  /**
   * 记录创建的项目（用于清理）
   */
  public function addCreatedItem($itemId)
  {
    $this->createdItems[] = $itemId;
  }

  /**
   * 记录创建的页面（用于清理）
   */
  public function addCreatedPage($pageId)
  {
    $this->createdPages[] = $pageId;
  }

  /**
   * 记录创建的目录（用于清理）
   */
  public function addCreatedCatalog($catId)
  {
    $this->createdCatalogs[] = $catId;
  }

  /**
   * 记录上传的文件（用于清理）
   */
  public function addUploadedFileSign($sign)
  {
    $this->uploadedFileSigns[] = $sign;
  }

  /**
   * 获取测试统计
   */
  public function getStats()
  {
    $passed = count(array_filter($this->testResults, fn($r) => $r['passed']));
    $total = count($this->testResults);
    return [
      'passed' => $passed,
      'failed' => $total - $passed,
      'total' => $total,
    ];
  }

  /**
   * 打印测试总结
   */
  public function printSummary()
  {
    $stats = $this->getStats();
    echo "\n";
    printHeader("测试总结");
    echo "  通过: " . colorOutput($stats['passed'], 'green') . "\n";
    echo "  失败: " . colorOutput($stats['failed'], 'red') . "\n";
    echo "  总计: " . $stats['total'] . "\n";
    echo "  成功率: " . round($stats['passed'] / max($stats['total'], 1) * 100, 1) . "%\n";

    if ($stats['failed'] > 0) {
      echo "\n失败的测试:\n";
      foreach ($this->testResults as $result) {
        if (!$result['passed']) {
          printError("{$result['name']}: {$result['message']}");
        }
      }
    }
  }
}
