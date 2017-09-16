//页面加载完就执行
$(function(){

  //自动根据url把当前菜单激活
  var current_page_id = $("#current_page_id").val();
  //如果中没有指定page_id，则判断有没有父目录为0的页面，默认打开第一个
  if(!current_page_id) {
    current_page_id = $(".doc-left li").children("a").attr("data-page-id");
  };
  if(current_page_id !=null && current_page_id.toString().length>0)
  {
    $(".doc-left li").each(function(){
      page_id = $(this).children("a").attr("data-page-id");
      //如果链接中包含当前url的信息，两者相匹配
      if (page_id !=null && page_id.toString().length>0 && page_id == current_page_id) {
        //激活菜单
        $(this).addClass("active");
        //如果该菜单是子菜单，则还需要把父菜单打开才行
        if ($(this).parent('.child-ul')) {
            $(this).parent('.child-ul').show();
            $(this).parent('.child-ul').parent('li').children("a").children('i').attr("class","icon-chevron-down");
            if($(this).parent('.child-ul').parent().parent('.child-ul')){
              $(this).parent('.child-ul').parent().parent('.child-ul').show(); 
              $(this).parent('.child-ul').parent().parent('.child-ul').parent('li').children("a").children('i').attr("class","icon-chevron-down"); 
            }
        };
        page_title = $(this).children("a")[0].innerText;
        document.title = page_title + " - ShowDoc";
        if (page_id != '' && page_id !='#') {
            change_page(page_id)
        };
      };
    })
  }


  //根据屏幕宽度进行响应(应对移动设备的访问)
  if( isMobile() || $(window).width() < 1000){
      AdaptToMobile();
  }

  $(window).resize(function(){
    if( isMobile()){
        AdaptToMobile();
    }

    else if($(window).width() < 1000){
        AdaptToMobile();
    }else{
      window.location.reload();
    }
  });

  //增加返回顶部按钮
  $.goup({
        trigger: 100,
        bottomOffset: 150,
        locationOffset: 100,
        title: lang["back_to_top"] ,
        titleAsText: true,
        containerColor:"#08c",
    });


  //js获取url参数
  function GetQueryString(name)
  {
       var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
       var r = window.location.search.substr(1).match(reg);
       if(r!=null)return  unescape(r[2]); return null;
  }

  function AdaptToMobile(){
    $(".doc-left").removeClass("span3");
    $(".doc-left").css("width",'100%');
    $(".doc-left").css("height",'initial');
    $(".doc-left").css("min-height",'0px');
    $(".doc-left").css("position",'static');
    $(".doc-right").css("margin-top",'0px');
    $(".doc-right").css("margin-left",'0px');
    $(".doc-right").removeClass("span12");
    $(".doc-head .right").hide();
    $(".page-edit-link").html('');
    $(".doc-left-newbar").html('');
    //$(".iframe_content").css("padding-left","30px");
    $(".iframe_content").css("width",'');
    $(".doc-left .nav-list li a i ").css("margin-left" , '10px');
    $(".search-input-append").css("width","100%");
    $(".search-query-input").css("width","70%");



  }

  function mScroll(id){
    $("html,body").stop(true);
    $("html,body").animate(
    {scrollTop: $("#"+id).offset().top},
      2000);
  } 

  //点击左侧菜单事件
  $(".doc-left li").click(function(){
    //先把所有菜单的激活状态取消
    $(".doc-left li").each(function(){
      $(this).removeClass("active");
    });
    //先判断是否存在子菜单
    if ($(this).children('.child-ul').length != 0) {
      //如果子菜单是隐藏的，则显示之；如果是显示状态的，则隐藏
      if ($(this).children('.child-ul').css("display") == "none") {
        $(this).children('.child-ul').show();
        $(this).children("a").children('i').attr("class","icon-chevron-down");
      }else{
        $(this).children('.child-ul').hide();
        $(this).children("a").children('i').attr("class","icon-chevron-right");
      }
    };
    //激活菜单
    $(this).addClass("active");
    //获取对应的page_id
    page_id = $(this).children("a").attr("data-page-id");
    page_title = $(this).children("a")[0].innerText;
    if (page_id != '' && page_id != null  && page_id !='#') {
        if (page_title != '' && page_title != null) {
            document.title = page_title + " - ShowDoc";
        }
        change_page(page_id);
        //如果是移动设备的话，则滚动页面
        if( isMobile()){
            mScroll("page-content");
        }
    };
    return false;//禁止原有的href链接
  });

  //切换页面；
  function change_page(page_id){
      if(!page_id)return;
      var item_id = $("#item_id").val();
      var item_domain = $("#item_domain").val();
      var base_url = $("#base_url").val();
      var iframe_url =  base_url+"/home/page/index/page_id/"+page_id;

      $(".page-edit-link").show();
      //$("#page-content").attr("src" , iframe_url);
      $("#edit-link").attr("href" , base_url+"/home/page/edit/page_id/"+page_id);
      $("#copy-link").attr("href" , base_url+"/home/page/edit/item_id/"+item_id+"/copy_page_id/"+page_id);
      $("#delete-link").attr("href" , base_url+"/home/page/delete/page_id/"+page_id);
      
      var domain = item_domain ? item_domain : item_id ;
      var cur_page_url =  window.location.protocol +"//"+window.location.host+base_url+"/"+domain;
      if(base_url.length == 0){
        cur_page_url += "?page_id="+page_id;
      }else{
        cur_page_url += "&page_id="+page_id;
      }
      $("#share-page-link").html(cur_page_url);
      history.replaceState(null, null, cur_page_url);
      var single_page_url = window.location.protocol +"//"+window.location.host+base_url+"/page/"+page_id;
      $("#share-single-link").html(single_page_url);

      $("#qr-page-link").attr("src","?s=home/common/qrcode&size=3&url="+encodeURIComponent(cur_page_url));
      $("#qr-single-link").attr("src","?s=home/common/qrcode&size=3&url="+encodeURIComponent(single_page_url));
      $(".show_page_info").data("page_id",page_id);
      var html = '<iframe id="page-content" width="100%" scrolling="yes"  height="100%" frameborder="0" style=" overflow:visible; height:100%;" name="main"  seamless ="seamless"src="'+iframe_url+'"></iframe>';
      $(".iframe_content").html(html);
      iFrameHeight();
      
  }

  //分享项目
  $("#share").click(function(){
    $("#share-modal").modal();
      //延迟绑定分享事件
        setTimeout(function(){
          $('#copy-item-link').zclip(
          {
            path: DocConfig.pubile +'/jquery.zclip/ZeroClipboard.swf',
            copy:function()
            {
              return $('#share-item-link').html();
            },
            afterCopy: function() {
              show_top_msg("已经成功复制到剪切板",2000);
            }
          });

        },500);
    return false;
  });

  //分享页面
  $("#share-page").click(function(){
    $("#share-page-modal").modal();
      //延迟绑定分享事件
        setTimeout(function(){
          $('#copy-page-link').zclip(
          {
            path: DocConfig.pubile +'/jquery.zclip/ZeroClipboard.swf',
            copy:function()
            {
              return $('#share-page-link').html();
            },
            afterCopy: function() {
              show_top_msg("已经成功复制到剪切板",2000);
            }
          });

          $('#copy-single-link').zclip(
          {
            path:DocConfig.pubile +'/jquery.zclip/ZeroClipboard.swf',
            copy:function()
            {
              return $('#share-single-link').html();
            },
            afterCopy: function() {
              show_top_msg("已经成功复制到剪切板",2000);
            }
          });
        },500);

    return false;
  });

function iFrameHeight() { 
  var ifr = document.getElementById('page-content');
  ifr.onload = function() {
      var iDoc = ifr.contentDocument || ifr.document;
      var height = calcPageHeight(iDoc);
      ifr.style.height = height + 'px'; 
  }
 }



  // 计算页面的实际高度，iframe自适应会用到
  function calcPageHeight(doc) {
      var cHeight = Math.max(doc.body.clientHeight, doc.documentElement.clientHeight)
      var sHeight = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight)
      var height  = Math.max(cHeight, sHeight)
      return height
  }

  var keyMap = {
    // 编辑
    "Ctrl+E": function() {
      location.href = $("#edit-link").attr('href');
    },
    // 删除
    "Ctrl+D": function() {
      if (confirm(lang["confirm_to_delete"]))
        location.href = $("#delete-link").attr('href');
    },
    // 新建页面
    "Ctrl+F1": function() {
      location.href = $("#new-like").attr('href');
    },
    // 新建目录
    "Ctrl+F2": function() {
      location.href = $("#dir-like").attr('href');
    }
  };
  if (!isMobile()) initKeys();
  function initKeys() {
    var $doc = $(document);
    $.each(keyMap, function(key, fn) {
      $doc.on('keydown', null, key, function(e) {
        e.preventDefault();
        fn();
        return false;
      });
    });
  }

  $(".show_page_info").click(function(){
    var page_id =  $(this).data("page_id") ;
    $.post(
      DocConfig.server+"/api/page/info",
      {"page_id":page_id},
      function(data){
        var html = "<p>最后编辑时间："+data.data.addtime+"</p><p>编辑人："+data.data.author_username+"</p>";
         $.alert(html);
      },
      "json"

      );
    return false;
  });
  
  //监听来自iframe的消息。如果传递图片url过来则默认打开之
  window.addEventListener('message', function(e){
      if(e.origin != window.location.origin) return;
      if (e.data.meessage_type != 'img_url') {
        return ;
      }
     var img_url =e.data.img_url;
      var json = {
          "title": "", //相册标题
          "id": 123, //相册id
          "start": 0, //初始显示的图片序号，默认0
          "data": [   //相册包含的图片，数组格式
              {
                "alt": "",
                "pid": 666, //图片id
                "src": img_url, //原图地址
                "thumb": img_url //缩略图地址
              }
            ]
          }
        $.photos({
          photos: json
          ,anim: 5 //0-6的选择，指定弹出图片动画类型，默认随机（请注意，3.0之前的版本用shift参数）
        });
  }, false);

})











