<?php

// PHP 版本检测（开源版要求 PHP >= 7.4）
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('ShowDoc requires PHP >= 7.4.0. Current version: ' . PHP_VERSION);
}

// ===== 禁止爬虫和搜索引擎访问 =====
if (PHP_SAPI !== 'cli') {
    require __DIR__ . '/server/app/Common/BotDetector.php';
    \App\Common\BotDetector::blockBot();
}
// ===== 爬虫拦截结束 =====

// ===== 安装状态检测 =====
// 计算站点基础路径（脚本所在目录），避免使用相对路径导致深层路径下重定向失效
$webBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');

if (PHP_SAPI !== 'cli') {
    // 不存在安装文件夹的，表示已经安装过
    if (!file_exists("./install")) {
        header("location:{$webBase}/web/#/");
        exit();
    }

    // 存在安装锁文件的，表示已经安装过。
    // 不依赖目录可写性：部署后常会把 install 目录设为只读加固，此时应视为已安装。
    if (file_exists("./install/install.lock")) {
        header("location:{$webBase}/web/#/");
        exit();
    }

    // 其他情况都跳转到安装页面
    header("location:{$webBase}/install/index.php");
    exit();
}

require __DIR__ . '/server/vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Dotenv\Dotenv;
use App\Common\Helper\Env;

// CLI 模式下支持：php index.php / 或 /uid/xxx
if (PHP_SAPI === 'cli') {
    $path = $argv[1] ?? '/';
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    $_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $_SERVER['REQUEST_URI'] = $path;
    $scriptBase = basename(__FILE__);
    $_SERVER['SCRIPT_NAME'] = '/' . $scriptBase;
}

// 加载根目录 .env（如果存在），供 Database 等使用 getenv() 读取配置
if (is_file(__DIR__ . '/.env')) {
    Dotenv::createImmutable(__DIR__)->load();
}

// 设置默认时区，优先使用环境变量 APP_TIMEZONE，默认为上海时间
$timezone = getenv('APP_TIMEZONE') ?: 'Asia/Shanghai';
@date_default_timezone_set($timezone);

// 定义日志路径常量（兼容旧代码，必须在命名空间之前定义）
if (!defined('LOG_PATH')) {
    // 计算 Runtime 目录路径（位于 app 目录下）
    $appPath = __DIR__ . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
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

// 初始化 DI 容器
$container = new Container();

// 注册服务
require __DIR__ . '/server/app/Common/bootstrap.php';
require __DIR__ . '/server/app/Common/container.php';

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addRoutingMiddleware();

// 错误处理：生产环境不向前端暴露堆栈与路径信息
// 可通过环境变量 APP_DEBUG=true 在开发环境开启详细错误显示
$appDebug = filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);
$errorMiddleware = $app->addErrorMiddleware($appDebug, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('text/html');

// 首页：/
$app->get('/', function (Request $request, Response $response) use ($container) {
    $controller = $container->get(\App\Home\Controller\IndexController::class);
    return $controller->index($request, $response);
});

// 项目页：/123
$app->get('/{item_id:\\d+}', function (Request $request, Response $response, array $args) use ($container) {
    $request = $request->withAttribute('item_id', (int) $args['item_id']);
    $controller = $container->get(\App\Home\Controller\ItemController::class);
    return $controller->show($request, $response);
});

// 单页：/page/456
$app->get('/page/{page_id:\\d+}', function (Request $request, Response $response, array $args) use ($container) {
    $request = $request->withAttribute('page_id', (int) $args['page_id']);
    $controller = $container->get(\App\Home\Controller\PageController::class);
    return $controller->show($request, $response);
});

// 用户主页：/uid/username
$app->get('/uid/{username}', function (Request $request, Response $response, array $args) use ($container) {
    $request = $request->withAttribute('username', $args['username']);
    $controller = $container->get(\App\Home\Controller\UserController::class);
    return $controller->profile($request, $response);
});

// 个性域名项目：/mydomain（只匹配单段路径，不包含斜杠）
$app->get('/{domain:[a-zA-Z0-9_-]+}', function (Request $request, Response $response, array $args) use ($container) {
    $request = $request->withAttribute('domain', $args['domain']);
    $controller = $container->get(\App\Home\Controller\DomainController::class);
    return $controller->show($request, $response);
});

// CLI 命令路由：home/common/repasswd（重置管理员密码）
$app->any('/home/common/repasswd', function (Request $request, Response $response) use ($container) {
    $controller = $container->get(\App\Home\Controller\CommonController::class);
    return $controller->repasswd($request, $response);
});

// 兜底路由：所有其他路径（多段路径，如 /user/login）都返回 Vue 应用
$app->any('/{path:.*}', function (Request $request, Response $response) use ($webBase) {
    // 开源版前端构建产物在 web/ 目录下，而不是使用主版的 web.html
    // 这里统一把所有未被前面路由匹配的路径交给前端 SPA 处理
    return $response
        ->withHeader('Location', $webBase . '/web/#/')
        ->withStatus(302);
});

$app->run();
