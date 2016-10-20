
$(function(){
  var item_id = $("#item_id").val();

  $('#edit-cat').modal({
    "backdrop":'static'
  });

  getList();

  function getList(){
      $.get(
        "?s=/home/member/getList",
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
      var member_group_id = $("#member_group_id").is(':checked') ? 0 : 1 ;
      $.post(
        "?s=/home/member/save",
        {"username": username ,"item_id": item_id,"member_group_id": member_group_id   },
        function(data){
          if (data.error_code == 0) {
            $("#username").val('');
            alert(lang["save_success"]);
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
        if (!confirm(lang['confirm_to_delete_member'])) {
            return false;
        }
      if (username) {
          $.post(
              "?s=/home/member/delete",
              { "username": username, "item_id" :item_id },
              function(data){
                if (data.error_code == 0) {
                  alert(lang["delete_success"]);
                  getList();
                }else{
                  alert(lang["delete_fail"]);

                }
              },
              "json"
            );

      }
      return false;
  });

  $(".exist-cat").click(function(){
    
    window.location.href="?s=/home/item/show&item_id="+item_id;
  });

});











