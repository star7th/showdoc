var http = require('http');
var fs = require('fs');
var path = require('path');
var url = require('url');
var querystring = require('querystring');
var server = http.createServer();

var Mock = require('mockjs');
var util = require('util');

// 监听客服端请求
server.on('request',function (req,res) {
    //console.log(req.url + req.method);
	if (req.url.indexOf('/mock') === 0 && req.method === 'POST') {

        var postData = '';
        // 18. 给req对象注册一个接收数据的事件
        req.on('data',function (chuck) {  
            /**data事件详解
             * 浏览器每发送一次数据包（chuck），该函数会调用一次。
             * 该函数会调用多次，调用的次数是由数据和网速限制的
             */
            // 19. 每次发送的都数据都叠加到postData里面
            postData += chuck;
        })
        // 20. 到post请求数据发完了之后会执行一个end事件，这个事件只执行一次
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
            // 23. 打印出post请求参数，
            //console.log(postObjc.template);
            var data ;
		    try{
		    	data = JSON.stringify( Mock.mock(JSON.parse(postObjc.template)) );

		    }catch(e){
		    	data = '为了服务器安全，只允许符合json语法的字符串'
		    }
		    res.end(data);
        })
    }
})

// n. 启用服务器
server.listen(7123,function () { console.log('mock服务启用成功'); })
