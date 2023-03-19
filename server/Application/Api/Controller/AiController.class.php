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
        $res_array = json_decode($res, 1);
        if ($res_array && $res_array['id']) {
            $this->sendResult($res_array);
        } else {
            $this->sendError(10101, 'AI接口没有正常响应,错误信息:' . json_encode($res_array));
        }
    }



    public function send($content)
    {
        $postData = json_encode(array(
            "model" => "gpt-3.5-turbo",
            "messages" => array(
                array(
                    "role" => 'user',
                    "content" => $content,
                ),
            ),
        ));
        $api_base_url = env('API_BASE_URL', 'https://api.openai.com');
        $curl = curl_init();  //初始化
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_URL, $api_base_url . '/v1/chat/completions');  //设置url
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);  //设置http验证方法
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  //设置curl_exec获取的信息的返回方式
        curl_setopt($curl, CURLOPT_POST, 1);  //设置发送方式为post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);  //设置post的数据
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                "Authorization: Bearer {$this->open_api_key}",
                'Content-Length: ' . strlen($postData)
            )
        );

        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }
}
