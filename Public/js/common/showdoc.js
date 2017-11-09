
/*判断是否是移动设备*/
function isMobile(){
return navigator.userAgent.match(/iPhone|iPad|iPod|Android|android|BlackBerry|IEMobile/i) ? true : false; 
}

//判断是否是在线ShowDoc
function is_showdoc_online(){
	var host = window.location.host;
	if(host.indexOf("showdoc.cc") > -1 || host.indexOf("wu.com") > -1){
		return true;
	}else{
		return false;
	}
}

//给文字加上颜色
function set_text_color( id , color){
	var cookie_key = "is_"+id+"_click";

	var is_click = getCookie(cookie_key);
	if (!is_click) {
		$("#"+id).css("color",color);
	};

	$("#"+id).click(function(){
		var is_click = getCookie(cookie_key);
		if (!is_click) {
			$(this).css("color","");
			setCookie(cookie_key , 1 , 900);
		};
	});
}


///设置cookie 
function setCookie(NameOfCookie, value, expiredays) 
{ 
	//@参数:三个变量用来设置新的cookie: 
	//cookie的名称,存储的Cookie值, 
	// 以及Cookie过期的时间. 
	// 这几行是把天数转换为合法的日期 

	var ExpireDate = new Date (); 
	ExpireDate.setTime(ExpireDate.getTime() + (expiredays * 24 * 3600 * 1000)); 

	// 下面这行是用来存储cookie的,只需简单的为"document.cookie"赋值即可. 
	// 注意日期通过toGMTstring()函数被转换成了GMT时间。 

	document.cookie = NameOfCookie + "=" + escape(value) + 
	  ((expiredays == null) ? "" : "; expires=" + ExpireDate.toGMTString()); 
} 

///获取cookie值 
function getCookie(NameOfCookie) 
{ 

	// 首先我们检查下cookie是否存在. 
	// 如果不存在则document.cookie的长度为0 

	if (document.cookie.length > 0) 
	{ 

	// 接着我们检查下cookie的名字是否存在于document.cookie 

	// 因为不止一个cookie值存储,所以即使document.cookie的长度不为0也不能保证我们想要的名字的cookie存在 
	//所以我们需要这一步看看是否有我们想要的cookie 
	//如果begin的变量值得到的是-1那么说明不存在 

	begin = document.cookie.indexOf(NameOfCookie+"="); 
	if (begin != -1)    
	{ 

	// 说明存在我们的cookie. 

	begin += NameOfCookie.length+1;//cookie值的初始位置 
	end = document.cookie.indexOf(";", begin);//结束位置 
	if (end == -1) end = document.cookie.length;//没有;则end为字符串结束位置 
	return unescape(document.cookie.substring(begin, end)); } 
	} 

	return null; 

	// cookie不存在返回null 
} 

///删除cookie 
function delCookie (NameOfCookie) 
{ 
	// 该函数检查下cookie是否设置，如果设置了则将过期时间调到过去的时间; 
	//剩下就交给操作系统适当时间清理cookie啦 

	if (getCookie(NameOfCookie)) { 
	document.cookie = NameOfCookie + "=" + 
	"; expires=Thu, 01-Jan-70 00:00:01 GMT"; 
	} 
}

 function show_top_msg(msg,delay){
  $.bootstrapGrowl(msg, {
    ele: 'body', // which element to append to
    type: 'info', // (null, 'info', 'error', 'success')
    offset: {from: 'top', amount: 20}, // 'top', or 'bottom'
    align: 'center', // ('left', 'right', or 'center')
    width: 'auto', // (integer, or 'auto')
    delay: delay,
    allow_dismiss: true,
    stackup_spacing: 10 // spacing between consecutively stacked growls.
  });
 }

//关闭Div
function closeDiv(target) {
	$(target).hide();
}
