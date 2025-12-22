<?php

// PHP 版本检测（开源版要求 PHP >= 7.4）
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('ShowDoc requires PHP >= 7.4.0. Current version: ' . PHP_VERSION);
}

// ===== 安装状态检测（参考旧版逻辑）=====
if (PHP_SAPI !== 'cli') {
    // 不存在安装文件夹的，表示已经安装过
    if (!file_exists("./install")) {
        header("location:./web/#/");
        exit();
    }

    // 如果 install 存在 && install.lock 存在 && install 可写 && install.lock 可写
    if (file_exists("./install") && file_exists("./install/install.lock") && newIsWriteable("./install") && newIsWriteable("./install/install.lock")) {
        header("location:./web/#/");
        exit();
    }
    
    // 其他情况都跳转到安装页面
    header("location:./install/index.php");
    exit();
}

/**
 * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
 * 参考旧版逻辑
 *
 * @param string $file 文件/目录
 * @return boolean
 */
function newIsWriteable($file)
{
    if (is_dir($file)) {
        $dir = $file;
        if ($fp = @fopen("$dir/test.txt", 'w')) {
            @fclose($fp);
            @unlink("$dir/test.txt");
            $writeable = 1;
        } else {
            $writeable = 0;
        }
    } else {
        if ($fp = @fopen($file, 'a+')) {
            @fclose($fp);
            $writeable = 1;
        } else {
            $writeable = 0;
        }
    }

    return $writeable;
}

require __DIR__ . '/server/vendor/autoload.php';

use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Dotenv\Dotenv;

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

// 兼容查询参数路由：?s=/user/login 形式的路由（仅限 Web 环境）
// 开源版主要使用此方式
if (PHP_SAPI !== 'cli' && isset($_GET['s']) && $_GET['s'] !== '') {
    $path = $_GET['s'];
    if ($path !== '' && $path[0] !== '/') {
        $path = '/' . $path;
    }
    // 设置 REQUEST_URI、PATH_INFO 和 SCRIPT_NAME，确保 Slim 能正确解析路径
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['PATH_INFO'] = $path;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    // 清除查询字符串，避免 Slim 重复处理
    $_SERVER['QUERY_STRING'] = '';
    unset($_GET['s']);
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

// 开源版开启错误显示，方便用户排查问题
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
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

// 兜底路由：所有其他路径（多段路径，如 /user/login）都返回 Vue 应用
$app->any('/{path:.*}', function (Request $request, Response $response) {
    // 开源版前端构建产物在 web/ 目录下，而不是使用主版的 web.html
    // 这里统一把所有未被前面路由匹配的路径交给前端 SPA 处理
    return $response
        ->withHeader('Location', './web/#/')
        ->withStatus(302);
});

$app->run();
