$(function(){

  var item_id = $("#item_id").val();

  $('#edit-cat').modal({
    "backdrop":'static'
  });

  getCatList();

  function getCatList(){
      $.get(
        "?s=home/catalog/catList",
        { "item_id": item_id },
        function(data){
          $("#show-cat").html('');
          if (data.error_code == 0) {
            json = data.data;
            console.log(json);
            for (var i = 0; i < json.length; i++) {
                cat_html ='<a class="badge badge-info single-cat " href="?s=home/catalog/edit&cat_id='+json[i].cat_id+'&item_id='+json[i].item_id+'">'+json[i].cat_name+'&nbsp;<i class="icon-edit"></i></a>';
                $("#show-cat").append(cat_html);
            };


          };
          
        },
        "json"

        );
  }

  /*加载二级目录，让用户选择上级目录*/
  secondCatList();

  function secondCatList() {
    var default_parent_cat_id = $("#default_parent_cat_id").val();
    var item_id = $("#item_id").val();
    $.get(
      "./", {
        "item_id": item_id,
        "s": "home/catalog/secondCatList",
      },
      function(data) {
        $("#parent_cat_id").html('<OPTION value="0">无</OPTION>');
        if (data.error_code == 0) {
          json = data.data;
          console.log(json);
          for (var i = 0; i < json.length; i++) {
            cat_html = '<OPTION value="' + json[i].cat_id + '" ';
            if (default_parent_cat_id == json[i].cat_id) {
              cat_html += ' selected ';
            }

            cat_html += ' ">' + json[i].cat_name + '</OPTION>';
            $("#parent_cat_id").append(cat_html);
          };
        };

      },
      "json"

    );
  }


  //保存目录
  $("#save-cat").click(function(){
      var cat_name = $("#cat_name").val();
      var s_number = $("#s_number").val();
      var cat_id = $("#cat_id").val();
      var parent_cat_id = $("#parent_cat_id").val();
      $.post(
        "?s=home/catalog/save",
        {"cat_name": cat_name , "s_number": s_number , "item_id": item_id , "cat_id": cat_id, "parent_cat_id": parent_cat_id  },
        function(data){
          if (data.error_code == 0) {
            $("#delete-cat").hide();
            $("#cat_name").val('');
            $("#s_number").val('');
            $("#cat_id").val('');
            $("#parent_cat_id").val('');
            secondCatList();
            alert("保存成功！");
          }else{
            alert("保存失败！");
          }
          getCatList();
        },
        "json"

        );
      return false;
  });

  //删除目录
  $("#delete-cat").click(function(){
    if(confirm('确认删除吗？')){
        var cat_id = $("#cat_id").val();
        if (cat_id > 0 ) {
            $.post(
                "?s=home/catalog/delete",
                { "cat_id": cat_id  },
                function(data){
                  if (data.error_code == 0) {
                    alert("删除成功！");
                    window.location.href="?s=home/catalog/edit&item_id="+item_id;
                  }else{
                    if (data.error_message) {
                      alert(data.error_message);
                    }else{
                      alert("删除失败！");
                    }
                    
                  }
                },
                "json"
              );
        }
    }

      return false;
  })

  $(".exist-cat").click(function(){
    window.location.href="?s=home/item/show&item_id="+item_id;
  });


});









