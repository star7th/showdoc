var editormd;

$(function() {
  /*加载目录*/
  getCatList();

  function getCatList(){
      var default_cat_id = $("#default_cat_id").val();
      var item_id = $("#item_id").val();
      $.get(
        "../catalog/catList",
        { "item_id": item_id },
        function(data){
          $("#cat_id").html('<OPTION value="0">无</OPTION>');
          if (data.error_code == 0) {
            json = data.data;
            console.log(json);
            for (var i = 0; i < json.length; i++) {
                cat_html ='<OPTION value="'+json[i].cat_id+'" ';
                if (default_cat_id == json[i].cat_id ) {
                  cat_html += ' selected ';
                }

                cat_html +=' ">'+json[i].cat_name+'</OPTION>';
                $("#cat_id").append(cat_html);
            };
          };
          
        },
        "json"

        );
  }
  /*初始化编辑器*/
  editormd = editormd("editormd", {
      width   : "90%",
      height  : 1000,
      syncScrolling : "single",
      path    : DocConfig.pubile + "/editor.md/lib/" ,
      placeholder : "本编辑器支持Markdown编辑，左边编写，右边预览"
  });

  /*插入API接口模板*/
  $("#api-doc").click(function(){
      var tmpl = $("#api-doc-templ").html();
      editormd.insertValue(tmpl);
  });
  /*插入数据字典模板*/
  $("#database-doc").click(function(){
      var tmpl = $("#database-doc-templ").html();
      editormd.insertValue(tmpl);
  });
  
  /*保存*/
  $("#save").click(function(){
    var page_id = $("#page_id").val();
    var item_id = $("#item_id").val();
    var cat_id = $("#cat_id").val();
    var page_title = $("#page_title").val();
    var page_content = $("#page_content").val();
    var item_id = $("#item_id").val();
    var order = $("#order").val();
    $.post(
      "save",
      {"page_id":page_id ,"cat_id":cat_id ,"order":order ,"page_content":page_content,"page_title":page_title,"item_id":item_id },
      function(data){
          if (data.error_code == 0) {
            alert("保存成功！");
            window.location.href="../item/show?page_id="+data.data.page_id+"&item_id="+item_id;
          }else{
            alert("保存失败！");

          }
      },
      'json'
      )
  })


});











