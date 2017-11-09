$(function(){
  var item_id = $("#item_id").val();

  $('#edit-cat').modal({
    "backdrop":'static'
  });

  //保存
  $("#save-cat").click(function(){
      var username = $("#username").val();
      var password = $("#password").val();
      $.post(
        "?s=/home/attorn/save",
        {"username": username ,"item_id": item_id , "password": password  },
        function(data){
          if (data.error_code == 0) {
            alert(lang['attorn_success']);
            window.location.href="?s=/home/item/index";
          }else{
            alert(data.error_message);

          }
        },
        "json"

        );
      return false;
  }); 
  
  $(".exist-cat").click(function(){
    window.location.href="?s=/home/item/show&item_id="+item_id;
  });

});





