
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
