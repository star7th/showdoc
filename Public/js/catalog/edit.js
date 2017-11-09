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
          $("#show-second-cat").html('');
          $("#show-third-cat").html('');
          if (data.error_code == 0) {
            json = data.data;
            console.log(json);
            for (var i = 0; i < json.length; i++) {
                if (json[i].level == 2  ) {
                  cat_html ='<a class="badge badge-info single-cat " href="?s=home/catalog/edit&cat_id='+json[i].cat_id+'&item_id='+json[i].item_id+'">'+json[i].cat_name+'&nbsp;<i class="icon-edit"></i></a>';
                  $("#show-second-cat").append(cat_html);
                };

                if (json[i].level == 3  ) {
                  cat_html ='<a class="badge badge-info single-cat " href="?s=home/catalog/edit&cat_id='+json[i].cat_id+'&item_id='+json[i].item_id+'">'+json[i].cat_name+'&nbsp;<i class="icon-edit"></i></a>';
                  $("#show-third-cat").append(cat_html);
                };

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
      "?s=home/catalog/secondCatList", 
      {"item_id": item_id},
      function(data) {
        $("#parent_cat_id").html('<OPTION value="0">'+lang["none"]+'</OPTION>');
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
            //alert(lang["save_success"]);
          }else{
            $.alert(lang["save_fail"]);
          }
          getCatList();
        },
        "json"

        );
      return false;
  });

  //删除目录
  $("#delete-cat").click(function(){
    $.confirm(lang["confirm_to_delete"],{},function(){
        var cat_id = $("#cat_id").val();
        if (cat_id > 0 ) {
            $.post(
                "?s=home/catalog/delete",
                { "cat_id": cat_id  },
                function(data){
                  if (data.error_code == 0) {
                    //alert(lang["delete_success"]);
                    window.location.href="?s=home/catalog/edit&item_id="+item_id;
                  }else{
                    if (data.error_message) {
                      $.alert(data.error_message);
                    }else{
                      $.alert(lang["delete_fail"]);
                    }
                    
                  }
                },
                "json"
              );
        }
      });

      return false;
  })

  $(".exist-cat").click(function(){
    window.location.href="?s=home/item/show&item_id="+item_id;
  });


});









