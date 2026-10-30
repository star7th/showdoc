var http = require('http');
var fs = require('fs');
var path = require('path');
var url = require('url');
var querystring = require('querystring');
var server = http.createServer();

var Mock = require('mockjs');
var util = require('util');

/**
 * mockjs 占位符参数存在 eval 求值漏洞（handler.placeholder 会把参数拼接进 eval），
 * 这里在调用 Mock.mock 前对模板做白名单校验：
 * - 递归遍历 JSON 解析后的所有字符串值（属性名如 "list|1-10" 是正常语法，不校验）
 * - 字符串中任何 @name( 调用，参数只允许：数字 / 单双引号字符串 /
 *   true / false / null / 裸标识符 token（如 @date(yyyy-MM-dd)，纯变量引用无调用面）/
 *   字面量数组（如 @pick([1,2,3])，元素仅限 数字/字符串/true/false/null），逗号分隔
 * - 拒绝：反引号（tagged template 无括号调用面）、\u 转义、
 *   占位符名后跟 . 或 [（属性链，如 constructor）、任何无法按字面量完整解析的参数
 * 校验在 JSON.parse 之后的解码形态上进行，\u0028 等转义此时已是真实字符，
 * 无法再藏在转义形态后面绕过结构校验。
 */
var MOCK_PLACEHOLDER_NAME = '@[A-Za-z][A-Za-z0-9_]*(?:\\/[A-Za-z][A-Za-z0-9_]*)?';
// mockjs RE_PLACEHOLDER 的真实名字字符集（src/mock/constant.js）：
//   @([^@#%&()?\s]+)\((.*?)\)  —— 名字允许数字开头、$、.、非 ASCII 等。
// 探测正则必须使用同一字符集，否则 @0(...)、@nàme(...) 等形态会绕过守卫直达 eval。
var MOCKJS_ANY_NAME = '@[^@#%&()\\?\\s]+';
var MOCK_STR_LITERAL = '\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"';
var MOCK_NUMBER = '-?\\d+(?:\\.\\d+)?(?:[eE][+-]?\\d+)?';
// 裸 token：字母/_/$ 开头，仅限 [A-Za-z0-9_$-]；另放行官方写法 \d+x\d+（如 200x100）
// 与 #RRGGBB 颜色字面量。eval 中只能是纯值引用，字符集排除 . [ ] ( ) = ` ' " \\ ，
// 无法构成调用/属性链/赋值/模板串
var MOCK_BARE = '[A-Za-z_$][A-Za-z0-9_$\\-]*|\\d+[xX]\\d+|#[0-9A-Fa-f]{3,8}';
// 数组字面量参数（@pick([1,2,3]) / @pick(['x','y']) 官方用法）：
// 元素仅限 数字/字符串/true/false/null，不允许裸标识符与嵌套数组
var MOCK_ARRAY_ELEM = '(?:' + MOCK_STR_LITERAL + '|' + MOCK_NUMBER + '|true|false|null)';
var MOCK_ARRAY = '\\[(?:' + MOCK_ARRAY_ELEM + '(?:\\s*,\\s*' + MOCK_ARRAY_ELEM + ')*)?\\]';
var MOCK_ARG = '(?:' + MOCK_STR_LITERAL + '|' + MOCK_NUMBER + '|' + MOCK_ARRAY + '|' + MOCK_BARE + ')';
var MOCK_ARGS = MOCK_ARG + '(?:\\s*,\\s*' + MOCK_ARG + ')*';
// 带参数占位符的白名单形态（锚定开头，防止内部片段误通过）
var MOCK_CALL_RE = new RegExp('^(?:' + MOCK_PLACEHOLDER_NAME + '\\((?:' + MOCK_ARGS + ')?\\))');
// 探测正则：名字使用 mockjs 真实字符集，与 ( 直接相邻（mockjs 不允许中间空白）
var MOCK_CALL_PROBE_RE = new RegExp(MOCKJS_ANY_NAME + '\\(', 'g');
// 属性链探测：mockjs 真实字符集的名字后跟 . 或 [（如 @0.constructor）
var MOCK_NAME_CHAIN_RE = new RegExp(MOCKJS_ANY_NAME + '(?:\\.|\\[)');

function isSafePlaceholderString(str) {
    // 反引号：tagged template 可无括号调用函数，整体拒绝
    if (str.indexOf('`') !== -1) return false;
    // \u 转义（含 \u0028 括号形态）：解码后会成为括号等可执行字符，拒绝
    if (/\\u/i.test(str)) return false;
    // 占位符名后跟 . 或 [ ：属性链访问（如 constructor），拒绝
    if (MOCK_NAME_CHAIN_RE.test(str)) return false;
    // 所有 @name( 调用必须能按白名单从占位符起始处整体匹配
    var m;
    MOCK_CALL_PROBE_RE.lastIndex = 0;
    while ((m = MOCK_CALL_PROBE_RE.exec(str)) !== null) {
        var rest = str.slice(m.index);
        if (!MOCK_CALL_RE.test(rest)) return false;
    }
    return true;
}

function isSafeMockTemplate(obj) {
    var stack = [obj];
    var cur, k;
    while (stack.length) {
        cur = stack.pop();
        if (typeof cur === 'string') {
            if (!isSafePlaceholderString(cur)) return false;
        } else if (Array.isArray(cur)) {
            for (k = 0; k < cur.length; k++) stack.push(cur[k]);
        } else if (cur && typeof cur === 'object') {
            for (k in cur) {
                if (Object.prototype.hasOwnProperty.call(cur, k)) stack.push(cur[k]);
            }
        }
    }
    return true;
}

// 监听客服端请求
server.on('request',function (req,res) {
    //console.log(req.url + req.method);
	if (req.url.indexOf('/mock') === 0 && req.method === 'POST') {

        var postData = '';
        // 18. 给req对象注册一个接收数据的事件
        req.on('data',function (chuck) {
            /**data事件详解
             * 浏览器每发送一次数据包（chuck），该函数就会调用一次。
             * 该函数可能调用多次，调用的次数是由数据和网速限制的
             */
            // 19. 每次发送的都数据叠加到postData里面
            postData += chuck;
        })
        // 20. 到post请求数据发完了之后会执行一个end事件，这个end事件只执行一次
        req.on('end', function () {
            // 21. 此时服务器成功接受了本次post请求的参数
            // post请求最终获取到的数据就是url协议组成结构中的query部分
            //console.log(postData);
            // 22. 使用querystring模块来解析post请求
            /**
             * querystring详解
             * 参数：要解析的字符串
             * 返回值：解析之后的对象。
             */
            var postObjc = querystring.parse(postData);
            // 23. 打印post请求参数，
            //console.log(postObjc.template);
            var data ;
		    try{
		    	var parsed = JSON.parse(postObjc.template);
		    	if (!isSafeMockTemplate(parsed)) {
		    		throw new Error('unsafe template');
		    	}
		    	data = JSON.stringify( Mock.mock(parsed) );

		    }catch(e){
		    	data = '为了服务器安全，只允许符合json语法且不含可执行代码的mock模板'
		    }
		    res.end(data);
        })
    }
})

// n. 启用服务器（默认只绑回环地址，仅本机 PHP 可访问；独立容器部署时可用 MOCK_BIND=0.0.0.0 覆盖）
var mockBind = process.env.MOCK_BIND || '127.0.0.1';
server.listen(7123, mockBind, function () { console.log('mock服务启用成功'); })
