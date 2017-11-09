<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Behavior;
use Think\Storage;
/**
 * 系统行为扩展：静态缓存写入
 */
class WriteHtmlCacheBehavior {

    // 行为扩展的执行入口必须是run
    public function run(&$content) {
        //2014-11-28 修改 如果有HTTP 4xx 3xx 5xx 头部，禁止存储
        //2014-12-1 修改 对注入的网址 防止生成，例如 /game/lst/SortType/hot/-e8-90-8c-e5-85-94-e7-88-b1-e6-b6-88-e9-99-a4/-e8-bf-9b-e5-87-bb-e7-9a-84-e9-83-a8-e8-90-bd/-e9-a3-8e-e4-ba-91-e5-a4-a9-e4-b8-8b/index.shtml
        if (C('HTML_CACHE_ON') && defined('HTML_FILE_NAME')
            && !preg_match('/Status.*[345]{1}\d{2}/i', implode(' ', headers_list()))
            && !preg_match('/(-[a-z0-9]{2}){3,}/i',HTML_FILE_NAME)) {
            //静态文件写入
            Storage::put(HTML_FILE_NAME, $content, 'html');
        }
    }
}