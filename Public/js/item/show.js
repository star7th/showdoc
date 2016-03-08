
//页面加载完就执行
$(function(){

  //自动根据url把当前菜单激活
  var page_id = GetQueryString('page_id');
  //如果中没有指定page_id，则判断有没有父目录为0的页面，默认打开第一个
  if(!page_id) {
    page_id = $(".doc-left li").children("a").attr("data-page-id");
  };
  if(page_id !=null && page_id.toString().length>0)
  {
    var str = 'page_id='+page_id;
    $(".doc-left li").each(function(){
      url = $(this).children("a").attr("href");
      //如果链接中包含当前url的信息，两者相匹配
      if (url && url.indexOf(str) >= 0 ) {
        //激活菜单
        $(this).addClass("active");
        //如果该菜单是子菜单，则还需要把父菜单打开才行
        if ($(this).parent('.child-ul')) {
            $(this).parent('.child-ul').show();
            $(this).parent('.child-ul').parent('li').children("a").children('i').attr("class","icon-chevron-down");
        };
        //获取对应的page_id
          page_id = $(this).children("a").attr("data-page-id");
          if (page_id != '' && page_id !='#') {
              change_page(page_id)
          };
      };
    })
  }


  //根据屏幕宽度进行响应(应对移动设备的访问)
  if( isMobile()){
      AdaptToMobile();
  }

  $(window).resize(function(){
    if( isMobile()){
        AdaptToMobile();
    }

    else if($(window).width() < 600){
        AdaptToMobile();
    }else{
      window.location.reload();
    }
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
    $(".doc-right").removeClass("span12");
    $(".doc-head .right").hide();
    $(".page-edit-link").html('');
    $(".doc-left-newbar").html('');
    $(".iframe_content").css("padding-left","30px");
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
    //获取对应的page_id
    page_id = $(this).children("a").attr("data-page-id");
    if (page_id != '' && page_id != null  && page_id !='#') {
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
      var base_url = $("#base_url").val();
      $(".page-edit-link").show();
      $("#page-content").attr("src" , base_url+"/Home/page/index?page_id="+page_id);
      $("#edit-link").attr("href" , base_url+"/Home/page/edit?page_id="+page_id);
      $("#copy-link").attr("href" , base_url+"/Home/page/edit?item_id="+item_id+"&copy_page_id="+page_id);
      $("#share-page-link").html("http://"+window.location.host+base_url+"/"+item_id+"?page_id="+page_id);
      $("#delete-link").attr("href" , base_url+"/Home/page/delete?page_id="+page_id);
  }

  //分享项目
  $("#share").click(function(){
    $("#share-modal").modal();
    return false;
  });

  //分享页面
  $("#share-page").click(function(){
    $("#share-page-modal").modal();
    return false;
  });

  var ifr = document.getElementById('page-content')
  ifr.onload = function() {
      var iDoc = ifr.contentDocument || ifr.document
      var height = calcPageHeight(iDoc)
      ifr.style.height = height + 'px'
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
      if (confirm('确认删除吗？'))
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
    
})













