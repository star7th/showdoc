<?php

// PHP 版本检测（开源版要求 PHP >= 7.4）
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('ShowDoc requires PHP >= 7.4.0. Current version: ' . PHP_VERSION);
}


require __DIR__ . '/vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Dotenv\Dotenv;

// CLI 模式下支持：php server/index.php /ping
if (PHP_SAPI === 'cli') {
    $path = $argv[1] ?? '/';
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    // 与 Nginx 下保持一致：/server 前缀 + 实际路径
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI'] = '/server' . $path;
    // 兼容未来重命名为 index.php 或其他文件名
    $scriptBase = basename(__FILE__);
    $_SERVER['SCRIPT_NAME'] = '/server/' . $scriptBase;
}

// 加载根目录 .env（如果存在），供 Database 等使用 getenv() 读取配置
$rootPath = dirname(__DIR__);
if (is_file($rootPath . '/.env')) {
    Dotenv::createImmutable($rootPath)->load();
}

// 设置默认时区，优先使用环境变量 APP_TIMEZONE，默认为上海时间
$timezone = getenv('APP_TIMEZONE') ?: 'Asia/Shanghai';
@date_default_timezone_set($timezone);

// 定义日志路径常量（兼容旧代码，必须在命名空间之前定义）
if (!defined('LOG_PATH')) {
    // 计算 Runtime 目录路径（位于 app 目录下）
    $appPath = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
    $runtimePath = $appPath . 'Runtime' . DIRECTORY_SEPARATOR;
    if (!is_dir($runtimePath)) {
        @mkdir($runtimePath, 0755, true);
    }
    $logPath = $runtimePath . 'Logs' . DIRECTORY_SEPARATOR;
    if (!is_dir($logPath)) {
        @mkdir($logPath, 0755, true);
    }
    define('LOG_PATH', $logPath);
}

// 启用原生 Session，供验证码等接口使用（不依赖 ThinkPHP 的 session() 封装）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 兼容旧版查询参数路由（开源版主要使用此方式）
// 支持 ?s=Api/Item/info 或 ?s=/api/user/info 或 ?s=/server/api/xxx/xxxx/xxxx/1（路径参数写在 s 参数里）
if (PHP_SAPI !== 'cli' && isset($_GET['s']) && $_GET['s'] !== '') {
    $path = $_GET['s'];
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    // 与 Nginx 下保持一致：/server 前缀 + 实际路径
    $_SERVER['REQUEST_URI'] = '/server' . $path;
}

// 初始化 DI 容器
$container = new Container();

// 注册服务
require __DIR__ . '/app/Common/bootstrap.php';
require __DIR__ . '/app/Common/container.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

// 设置 base path
$app->setBasePath('/server');

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// 开源版开启错误显示，方便用户排查问题
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// 示例 API：/server/ping
$app->get('/ping', function (Request $request, Response $response) use ($container) {
    $controller = $container->get(\App\Api\Controller\PingController::class);
    return $controller->index($request, $response);
});

// Mock 路由：/server/mock-path/{item_id}?path=/test
// 兼容旧版路由规则：'mock-path/:id\s' => 'Api/Mock/infoByPath?item_id=:1'
$app->any('/mock-path/{item_id}', function (Request $request, Response $response, array $args) use ($container) {
    $controller = $container->get(\App\Api\Controller\MockController::class);
    // 将路径参数 item_id 设置为请求属性，以便 getParam 可以获取
    $request = $request->withAttribute('item_id', $args['item_id']);
    return $controller->infoByPath($request, $response);
});

// Mock 路由：/server/mock-data/{unique_key}
// 兼容旧版路由规则：'mock-data/:unique_key\s' => 'Api/Mock/infoByKey?unique_key=:1'
$app->any('/mock-data/{unique_key}', function (Request $request, Response $response, array $args) use ($container) {
    $controller = $container->get(\App\Api\Controller\MockController::class);
    // 将路径参数 unique_key 设置为请求属性，以便 getParam 可以获取
    $request = $request->withAttribute('unique_key', $args['unique_key']);
    return $controller->infoByKey($request, $response);
});

// 通用模块/控制器/方法路由：
// 兼容 /server/Api/User/login、/server/api/user/login，也兼容 /server/Api/User/info 等。
// 兼容下划线命名：/server/Api/Page_Comment/add -> PageCommentController
// 兼容驼峰命名：/server/Api/publicSquare/checkEnabled -> PublicSquareController
// 开源版也支持路径参数路由（如 /server/Api/Item/info/item_id/1），但主要使用查询参数路由
$app->any('/{module}/{controller}/{action}[/{params:.*}]', function (Request $request, Response $response, array $args) use ($container) {
    $module     = ucfirst(strtolower($args['module'] ?? ''));

    // 控制器名称转换：支持下划线命名和驼峰命名
    $controllerName = $args['controller'] ?? '';
    if (strpos($controllerName, '_') !== false) {
        // 下划线命名：page_comment -> PageComment
        $controller = str_replace('_', '', ucwords(strtolower($controllerName), '_'));
    } else {
        // 驼峰命名或全小写：publicSquare -> PublicSquare, user -> User
        // 方法：在每个大写字母前添加下划线（除了首字母），然后使用 ucwords
        $normalized = preg_replace('/([a-z])([A-Z])/', '$1_$2', $controllerName);
        $controller = str_replace('_', '', ucwords(strtolower($normalized), '_'));
    }

    $action     = strtolower($args['action'] ?? '');

    if ($module === '' || $controller === '' || $action === '') {
        $response->getBody()->write(json_encode([
            'error_code'    => 10400,
            'error_message' => 'Invalid route',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }

    // 解析路径参数（key/value 成对）
    if (!empty($args['params'])) {
        $parts  = array_values(array_filter(explode('/', $args['params'])));
        $count  = count($parts);
        for ($i = 0; $i + 1 < $count; $i += 2) {
            $key   = $parts[$i];
            $value = $parts[$i + 1];
            $request = $request->withAttribute($key, $value);
        }
    }

    $className = "\\App\\{$module}\\Controller\\{$controller}Controller";
    if (!class_exists($className)) {
        $response->getBody()->write(json_encode([
            'error_code'    => 10404,
            'error_message' => "Controller not found: {$className}",
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    $controllerInstance = $container->get($className);
    if (!method_exists($controllerInstance, $action)) {
        $response->getBody()->write(json_encode([
            'error_code'    => 10404,
            'error_message' => "Action not found: {$action}",
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
    }

    return $controllerInstance->$action($request, $response);
});

$app->run();
