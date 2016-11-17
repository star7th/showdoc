
$(function(){
    hljs.initHighlightingOnLoad();

    var EditormdView = editormd.markdownToHTML("page_md_content", {
      htmlDecode      : "style,script,iframe",  // you can filter tags decode
      emoji           : true,
      taskList        : true,
      tex             : true,  // 默认不解析
      flowChart       : true,  // 默认不解析
      sequenceDiagram : true,  // 默认不解析
    });
    
    //为所有table标签添加bootstap支持的表格类
    $("table").addClass("table table-bordered table-hover");
    //当表格列数过长时将自动出现滚动条
    $.each($('table'), function() {
        $(this).prop('outerHTML', '<div style="width: 100%;overflow-x: auto;">'+$(this).prop('outerHTML')+'</div>');
    });

      //超链接都在新窗口打开
    $('a[href^="http"]').each(function() {
          $(this).attr('target', '_blank');
    });
    if (!isMobile()) {
      $("th").css("min-width","77px");
    };

    //lightbox
    //增加返回顶部按钮
    $.goup({
          trigger: 100,
          bottomOffset: 150,
          locationOffset: 100,
          title: lang["back_to_top"] ,
          titleAsText: true,
          containerColor:"#08c",
      });

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

    history.replaceState(null, null, $("#share-item-link").html());

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

    $("table thead tr").css({"background-color":"#08c","color":"#fff"});
    $("table tr").each(function(){
    if($(this).find("td").eq(1).html()=="object" || $(this).find("td").eq(1).html()=="array[object]")
    {
      $(this).css({"background-color":"#99CC99","color":"#000"});
    }

    });

});

function AdaptToMobile(){
  $(".doc-container").css("width","90%");
  $("#doc-body").css("width","90%");
  $("#header").css("height","20px");
  $(".doc-title-box").css("margin","20px 20px 0px 20px");
  $("#footer").css("font-size","11pt");
  $(".tool-bar").hide();
}