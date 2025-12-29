<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Common\Helper\UrlHelper;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Gregwar\Captcha\CaptchaBuilder;

class CommonController extends BaseController
{
    /**
     * 生成图片验证码，对应旧版 createCaptcha。
     *
     * 返回：{ error_code: 0, data: { captcha_id } }
     */
    public function createCaptcha(Request $request, Response $response): Response
    {
        // 生成 4 位验证码（数字 + 大写字母），不依赖旧 get_rand_str
        $chars   = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length  = 4;
        $captcha = '';
        $maxIdx  = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $captcha .= $chars[random_int(0, $maxIdx)];
        }

        $now = time();

        // 写入 captcha 表
        $captchaId = DB::table('captcha')->insertGetId([
            'mobile'      => '',
            'captcha'     => $captcha,
            'expire_time' => $now + 60 * 10, // 10 分钟有效
        ]);

        // 清理一年以前的历史验证码
        DB::table('captcha')
            ->where('expire_time', '<', $now - 365 * 24 * 60 * 60)
            ->delete();

        return $this->success($response, [
            'captcha_id' => $captchaId,
        ]);
    }

    /**
     * 展示验证码图片，对应旧版 showCaptcha。
     *
     * 路由：/server/Api/Common/showCaptcha?captcha_id=123
     */
    public function showCaptcha(Request $request, Response $response): Response
    {
        $captchaId = $this->getParam($request, 'captcha_id', 0);
        if ($captchaId <= 0) {
            // 简单返回 404 图片为空
            return $response->withStatus(404);
        }

        $row = DB::table('captcha')
            ->where('captcha_id', $captchaId)
            ->first();

        if (!$row || !isset($row->captcha)) {
            return $response->withStatus(404);
        }

        $builder = new CaptchaBuilder((string) $row->captcha);
        $builder->build();

        // 将图片内容写入 Response body
        ob_start();
        $builder->output();
        $imageData = ob_get_clean();

        $response->getBody()->write($imageData);

        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withStatus(200);
    }

    /**
     * 生成并输出简单的 Session 验证码图片，对应旧版 verify。
     *
     * 仅供老页面兼容使用，登录等推荐使用 createCaptcha / showCaptcha 组合。
     */
    public function verify(Request $request, Response $response): Response
    {
        $builder = new CaptchaBuilder();
        $builder->build();

        // 将验证码短语保存到原生 Session 中（统一小写）
        $_SESSION['v_code'] = strtolower($builder->getPhrase());

        ob_start();
        $builder->output();
        $imageData = ob_get_clean();

        $response->getBody()->write($imageData);

        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withStatus(200);
    }

    /**
     * 返回 ShowDoc 版本号，对标旧版 Api/Common/version。
     */
    public function version(Request $request, Response $response): Response
    {
        // server/app/Api/Controller => 上溯四级到项目根目录
        $rootPath = dirname(__DIR__, 4);
        $file     = $rootPath . '/composer.json';

        if (!is_file($file)) {
            return $this->error($response, 10500, 'composer.json not found');
        }

        $json = json_decode(file_get_contents($file), true);
        if (!is_array($json)) {
            return $this->error($response, 10500, 'composer.json parse error');
        }

        $version = isset($json['version']) ? (string) $json['version'] : '';

        return $this->success($response, [
            'version' => $version,
        ]);
    }

    /**
     * 获取网站首页配置（兼容旧接口 Api/Common/homePageSetting）。
     */
    public function homePageSetting(Request $request, Response $response): Response
    {
        $homePage   = \App\Model\Options::get('home_page', '');
        $homeItem   = \App\Model\Options::get('home_item', '');
        $openApiKey = \App\Model\Options::get('open_api_key', '');
        $beian      = \App\Model\Options::get('beian', '');
        $registerOpen = \App\Model\Options::get('register_open');

        // 兼容旧逻辑：
        // - 如果 register_open === null，表示尚未有数据，此时前端应视为"允许注册"（1）
        // - 否则将其转换为整数
        if ($registerOpen === null) {
            $registerOpenValue = 1;
        } else {
            $registerOpenValue = (int) $registerOpen;
        }

        $data = [
            'home_page'  => $homePage,
            'home_item'  => $homeItem,
            'is_show_ai' => $openApiKey ? 1 : 0,
            'beian'      => $beian ?: '',
            'register_open' => $registerOpenValue,
        ];

        return $this->success($response, $data);
    }

    /**
     * 生成二维码图片，对应旧版 Api/Common/qrcode。
     *
     * 说明：为保持兼容，这里仍然依赖全局 QRcode 类（通过 composer/自动加载引入），
     * 输出 PNG 图片内容。
     */
    public function qrcode(Request $request, Response $response): Response
    {
        $url  = (string) $this->getParam($request, 'url', '');
        $url  = $url !== '' ? urldecode($url) : $url;
        $size = (int) $this->getParam($request, 'size', 6);
        if ($size <= 0) {
            $size = 6;
        }

        if (!class_exists('\\QRcode')) {
            // 未安装 QRcode 库，返回错误
            return $this->error($response, 10500, 'QRcode library not available');
        }

        ob_start();
        // 与旧版一致：$level=3（纠错级别）、$margin=2
        \QRcode::png($url, false, 3, $size, 2);
        $imageData = ob_get_clean();

        $response->getBody()->write($imageData);

        return $response
            ->withHeader('Content-Type', 'image/png')
            ->withStatus(200);
    }

    /**
     * 公共文件访问兼容入口，对应旧版 Api/Common/visitFile。
     *
     * 现在统一通过 AttachmentController::visitFile 处理。
     *
     * 兼容旧版路由：home/common/visitfile/sign/:sign -> api/attachment/visitFile?sign=:1
     * 支持路径参数：/api/common/visitfile/sign/0653ae51cee82aa3a539a0cc95c8957f
     * 也支持查询参数：/api/common/visitfile?sign=0653ae51cee82aa3a539a0cc95c8957f
     *
     * 注意：直接调用 AttachmentController::visitFile，避免 302 重定向造成的循环
     */
    public function visitFile(Request $request, Response $response): Response
    {
        // 同时支持路径参数（兼容旧版路由：sign/:sign）
        $sign = $this->getParam($request, 'sign', '');
        if ($sign !== '') {
            // 将路径参数合并到查询参数中
            $queryParams = $request->getQueryParams();
            $queryParams['sign'] = $sign;
            $request = $request->withQueryParams($queryParams);
        }

        // 直接创建 AttachmentController 实例并调用 visitFile，避免重定向循环
        $controller = new \App\Api\Controller\AttachmentController();
        return $controller->visitFile($request, $response);
    }

}
