
$(function(){
    hljs.initHighlightingOnLoad();

    //为所有table标签添加bootstap支持的表格类
    $("table").addClass("table table-bordered table-hover");

      //超链接都在新窗口打开
    $('a[href^="http"]').each(function() {
          $(this).attr('target', '_blank');
    });
    if (!isMobile()) {
      $("th").css("min-width","77px");
    };

    $("table thead tr").css({"background-color":"#08c","color":"#fff"});
    $("table tr").each(function(){
    if($(this).find("td").eq(1).html()=="object")
    {
      $(this).css({"background-color":"#99CC99","color":"#000"});
    }

    });

})