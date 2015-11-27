
$(function(){
  var item_id = $("#item_id").val();

  $('#edit-cat').modal({
    "backdrop":'static'
  });

  getList();

  function getList(){
      $.get(
        "getList",
        { "item_id": item_id },
        function(data){
          $("#show-cat").html('');
          if (data.error_code == 0) {
            json = data.data;
            console.log(json);
            for (var i = 0; i < json.length; i++) {
                cat_html ='<a class="badge badge-important single-cat" data-username="'+json[i].username+'" >'+json[i].username+'&nbsp;x</a>';
                $("#show-cat").append(cat_html);
            };


          };
          
        },
        "json"

        );
  }

  //保存
  $("#save-cat").click(function(){
      var username = $("#username").val();
      $.post(
        "save",
        {"username": username ,"item_id": item_id  },
        function(data){
          if (data.error_code == 0) {
            $("#username").val('');
            alert("保存成功！");
          }else{
            alert(data.error_message);

          }
          getList();
        },
        "json"

        );
      return false;
  });

  //删除
  $('#show-cat').delegate('.single-cat','click', function(){
      var username = $(this).attr("data-username");

      if (username) {
          $.post(
              "delete",
              { "username": username, "item_id" :item_id },
              function(data){
                if (data.error_code == 0) {
                  alert("删除成功！");
                  getList();
                }else{
                  alert("删除失败！");

                }
              },
              "json"
            );

      }
      return false;
  });

  $(".exist-cat").click(function(){
    
    window.location.href="../item/show?item_id="+item_id;
  });

});











