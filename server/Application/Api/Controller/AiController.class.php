<?php

namespace Api\Controller;

use Think\Controller;
use EasyWeChat\Factory;

class AiController extends BaseController
{

    private $open_api_key = '';
    public function __construct()
    {
        $this->open_api_key = D("Options")->get("open_api_key");
    }

    public function create()
    {
        $content = I("post.content");
        $login_user = $this->checkLogin();
        if (!$this->open_api_key) {
            $this->sendError(10101, '管理员没有在管理后台配置AI助手认证KEY,因此你无法使用AI功能。请联系管理员');
            return;
        }
        $res = $this->send($content);
    }

    public function send($content)
    {
        header("Content-Type: text/event-stream");
        header("X-Accel-Buffering: no");
        $ai_model_name = D("Options")->get("ai_model_name");
        $ai_model_name = $ai_model_name ? $ai_model_name : 'gpt-4o';
        $postData = json_encode(array(
            "model" => $ai_model_name,
            "messages" => array(
                array(
                    "role" => 'system',
                    "content" => "你是文档工具 showdoc 的AI助手，主要是职责是协助用户生成各种各样的文档/代码/文案，提高用户的写作效率。用户提出要求，你应该帮助用户生成文字。如果用户不懂，你可以引导用户怎么使用怎么提问，或者让用户点击当前网页左下角的帮助说明链接查看一些使用例子",
                ),
                array(
                    "role" => 'user',
                    "content" => $content,
                ),
            ),
            "stream" => true,
        ));
        $open_api_host = D("Options")->get("open_api_host");
        if (!$open_api_host) {
            $open_api_host = 'https://api.openai.com';
        }
        if (!strstr($open_api_host, 'http')) {
            $open_api_host = 'https://' . $open_api_host;
        }
        if (substr($open_api_host, -1) === '/') { // 如果字符串以 / 符号结尾:
            $open_api_host = substr($open_api_host, 0, -1); // 将字符串的最后一个字符剪切掉
        }

        // 参考 https://github.com/dirk1983/chatgpt/blob/main/stream.php
        $callback = function ($ch, $data) {
            $complete = json_decode($data);
            if (isset($complete->error)) {
                setcookie("errcode", $complete->error->code);
                setcookie("errmsg", $data);
                if (strpos($complete->error->message, "Rate limit reached") === 0) { //访问频率超限错误返回的code为空，特殊处理一下
                    setcookie("errcode", "rate_limit_reached");
                }
                if (strpos($complete->error->message, "Your access was terminated") === 0) { //违规使用，被封禁，特殊处理一下
                    setcookie("errcode", "access_terminated");
                }
                if (strpos($complete->error->message, "You didn't provide an API key") === 0) { //未提供API-KEY
                    setcookie("errcode", "no_api_key");
                }
                if (strpos($complete->error->message, "You exceeded your current quota") === 0) { //API-KEY余额不足
                    setcookie("errcode", "insufficient_quota");
                }
                if (strpos($complete->error->message, "That model is currently overloaded") === 0) { //OpenAI模型超负荷
                    setcookie("errcode", "model_overloaded");
                }
            } else {
                echo $data;
                flush();
            }
            return strlen($data);
        };

        $curl = curl_init();  //初始化
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, $open_api_host . '/v1/chat/completions');  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl, CURLOPT_TIMEOUT, 600);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);  //设置post的数据
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, $callback);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                "Authorization: Bearer {$this->open_api_key}",
                'Content-Length: ' . strlen($postData)
            )
        );
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3); // 设置最大重定向次数为3次
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // 允许自动重定向
        curl_setopt($curl, CURLOPT_AUTOREFERER, true); // 自动设置Referer

        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}
