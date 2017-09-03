$(function(){
  $('a[data-toggle="tab"]').on('shown', function (e) {
          //e.target // activated tab
          //e.relatedTarget // previous tab
          console.log($(e.target).attr("href"));
        })

    //展示第一个tab
    $("#myTab a:first").tab("show");

    var item_id = $("#item_id").val() ;

    

    //获取基础信息
    get_base_info() ;
    function get_base_info(){
      $.get(
        DocConfig.server+"/api/item/detail",
        {"item_id":item_id},
        function(data){
          if (data.error_code === 0 ) {
            //console.log(data.data);
            $("#item_name").val(data.data.item_name);
            $("#item_description").val(data.data.item_description);
            $("#item_domain").val(data.data.item_domain);
            $("#password").val(data.data.password);
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
    }

    //保存项目基础信息
    $("#item_save").click(function(){

      var item_name = $("#item_name").val();
      var item_description = $("#item_description").val();
      var item_domain = $("#item_domain").val();
      var password = $("#password").val();
      $.post(
        DocConfig.server+"/api/item/update",
        {"item_id":item_id,"item_name":item_name,"item_description":item_description,"item_domain":item_domain,"password":password},
        function(data){
          if (data.error_code === 0 ) {
            $.msg('保存成功',{"time":1000});
            get_base_info() ;
          }else{
            $.alert(data.error_message);
          }
        },
        "json"
        );

      return false;
    });

    //点击转让按钮，弹出modal
    $("#attorn-btn").click(function(){
      $('#attorn-modal').modal({
        "backdrop":'static'
      });
    });

    //监听转让
    $("#attorn_save").click(function(){
      var username = $("#attorn_username").val();
      var password = $("#attorn_password").val();
      $.post(
        DocConfig.server+"/api/item/attorn",
        {"username": username ,"item_id": item_id , "password": password  },
        function(data){
          if (data.error_code == 0) {
            $.msg('转让成功，正在跳转回主页..',{"time":3000});
            //跳转
            setTimeout(function(){
              window.location.href="?s=/home/item/index";
            },3000)
            
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
      return false;
    });

    //删除项目
    $("#delete-item-btn").click(function(){
      $('#delete-item-modal').modal({
        "backdrop":'static'
      });
    });

    //监听删除
    $("#delete_item_save").click(function(){
      var password = $("#delete_item_password").val();
      $.post(
        DocConfig.server+"/api/item/delete",
        {"item_id": item_id , "password": password  },
        function(data){
          if (data.error_code == 0) {
            $.msg('删除成功，正在跳转回主页..',{"time":3000});
            //跳转
            setTimeout(function(){
              window.location.href="?s=/home/item/index";
            },3000)
            
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
      return false;
    });

    //点击添加成员，弹出modal
    $("#add-member-btn").click(function(){
      $('#member-modal').modal({
        "backdrop":'static'
      });
    });


    //获取成员列表
    get_member_list();
    function get_member_list(){
      $.get(
        DocConfig.server+"/api/member/getList",
        {"item_id":item_id},
        function(data){
          $("#member-list").html('');
          if (data.error_code === 0 ) {
            //console.log(data.data);
            var json = data.data ;
            if (json.length > 0 ) {
              for (var i = 0; i < json.length; i++) {
                var html = '<tr>'
                  +'<td><div class="type-parent">'+json[i].username+'</div></td>'
                  +'<td><div class="type-parent">'+json[i].addtime+'</div></td>'
                  +'<td><div class="type-parent">'+json[i].member_group+'</div></td>'
                  +'<td><a href="#" class="member-delete" data-id="'+json[i].item_member_id+'">删除</a></td>'
                +'</tr>';
                $("#member-list").append(html);
                
              };

            };
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
    }

    //添加成员
    $("#member_save").click(function(){
      var username = $("#member_username").val();
      var member_group_id = $("#member_group_id").is(':checked') ? 0 : 1 ;
      $.post(
        DocConfig.server+"/api/member/save",
        {"item_id": item_id , "username": username ,"member_group_id":member_group_id  },
        function(data){
          if (data.error_code == 0) {
            $('#member-modal').modal('hide');
            $("#member_username").val('');
            $("#member_group_id").removeAttr("checked");
            $.msg('添加成功',{"time":1000});
            get_member_list();
            
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
      return false;
    });

    //删除成员
    $("#member-list").on("click",'.member-delete',function(){
      var item_member_id = $(this).data("id");
      $.confirm("确定删除成员吗",{},function(){
          $.post(
            DocConfig.server+"/api/member/delete",
            {"item_id": item_id , "item_member_id": item_member_id  },
            function(data){
              if (data.error_code == 0) {
                $.msg('删除成功',{"time":1000});
                get_member_list();
                
              }else{
                $.alert(data.error_message);
              }
            },
            "json"

            );
      });
      return false;
    });

    //归档项目
    $("#archive-item-btn").click(function(){
      $('#archive-item-modal').modal({
        "backdrop":'static'
      });
    });

    //监听归档
    $("#archive_item_save").click(function(){
      var password = $("#archive_item_password").val();
      $.post(
        DocConfig.server+"/api/item/archive",
        {"item_id": item_id , "password": password  },
        function(data){
          if (data.error_code == 0) {
            $.msg('归档成功',{"time":3000});
            $('#archive-item-modal').modal('hide');
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
      return false;
    });

    //获取item api_key信息
    get_api_info() ;
    function get_api_info(){
      $.get(
        DocConfig.server+"/api/item/getKey",
        {"item_id":item_id},
        function(data){
          if (data.error_code === 0 ) {
            //console.log(data.data);
            $("#api_key").html(data.data.api_key);
            $("#api_token").html(data.data.api_token);
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
    }

    $("#reset_api_token").click(function(){
      $.post(
        DocConfig.server+"/api/item/resetKey",
        {"item_id":item_id},
        function(data){
          if (data.error_code === 0 ) {
            //console.log(data.data);
            $("#api_key").html(data.data.api_key);
            $("#api_token").html(data.data.api_token);
          }else{
            $.alert(data.error_message);
          }
        },
        "json"

        );
      return false;
    });

  });