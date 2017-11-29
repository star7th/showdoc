
layer_index = layer.load(1, {
  shade: [0.1, '#fff'] //0.1透明度的白色背景
});
$.get(
  DocConfig.server+"/api/item/myList",
  {},
  function(data){
    var html = '';
    if (data.error_code == 0) {
        console.log(data.data);
        var json = data.data;
        for (var i = 0; i < json.length; i++) {
             html += '<li class="span3 text-center">' ;
             html += '<a class="thumbnail item-thumbnail" href="?s=home/item/show&item_id='+json[i]['item_id']+'" title="'+json[i]['item_description']+'">';
             html +=  '<span class="item-setting" data-src="?s=home/item/setting&item_id='+json[i]['item_id']+'"  title="项目设置">';
             html +=  '<i class="icon-wrench" ></i>';
             html +=  '</span>';
             if (json[i]['top'] > 0 ) {
                html +=  '<span class="item-top" data-action="cancel" data-item_id="'+json[i]['item_id']+'" title="取消置顶"><i class="icon-arrow-down" ></i></span>';
             }else{
                html +=  '<span class="item-top" data-action="top" data-item_id="'+json[i]['item_id']+'" title="置顶项目"><i class="icon-arrow-up"  ></i></span>';
             }
             html +=  '<p class="my-item">'+json[i]['item_name']+'</p>';
             html +=  '</a>';
             html +=  '</li> ';
        };
        html +=  '<li class="span3 text-center" >';
        html +=  '<a class="thumbnail" href="?s=home/item/add" title="'+lang["add_an_item"]+'">';
        html +=  '<p class="my-item ">'+lang["new_item"]+'&nbsp;<i class="icon-plus"></i></p>';
        html +=  '</a></li>';
        $("#item-list").html(html);
        $("#add-item").show();
        layer.closeAll();
        bind_events();
    } else {
      $.alert(lang["save_fail"]);

    }
  },
  "json"

  );


function bind_events(){

  //当鼠标放在项目上时将浮现设置和置顶图标
  $(".item-thumbnail").mouseover(function(){
    $(this).find(".item-setting").show();
    $(this).find(".item-top").show();
    $(this).find(".item-down").show();
  });

  //当鼠标离开项目上时将隐藏设置和置顶图标
  $(".item-thumbnail").mouseout(function(){
    $(this).find(".item-setting").hide();
    $(this).find(".item-top").hide();
    $(this).find(".item-down").hide();
  });

  //点击项目设置图标时
  $(".item-setting").click(function(){
    var url = $(this).data("src");
    window.location.href = url ;
    return false;
  });

  //点击项目置顶图标时
  $(".item-top").click(function(){

    var action = $(this).data("action");
    var item_id = $(this).data("item_id");
    $.post(
      DocConfig.server+"/api/item/top",
      {"action":action,"item_id":item_id},
      function(data){
        window.location.reload();
      },
      "json"
      );
    return false;
  });

  //点击取消置顶图标时
  $(".item-down").click(function(){

    var action = 'cancel';
    var item_id = $(this).data("item_id");
    $.post(
      DocConfig.server+"/api/item/top",
      {"action":action,"item_id":item_id},
      function(data){
        window.location.reload();
      },
      "json"
      );
    return false;
  });

}