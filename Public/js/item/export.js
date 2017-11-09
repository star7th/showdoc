$(".choose_type").change(function(){
    if ($("#item_type2").is(":checked") ) {
        $(".level_2_directory").removeAttr("disabled");
        $(".level_3_directory").removeAttr("disabled");
        $(".page").removeAttr("disabled");
    }else{
        $(".level_2_directory").attr("disabled","disabled");
        $(".level_3_directory").attr("disabled","disabled"); 
        $(".page").attr("disabled","disabled"); 
    }
}).trigger("change");

/*加载二级目录*/
secondCatList();

//监听是否选择了二级目录。如果选择了，则跟后台判断是否还子目录
$(".level_2_directory").change(function() {
    getChildCatList();
});

function secondCatList() {
    var item_id = $("#item_id").val();
    $.post(
      DocConfig.server+"/api/catalog/secondCatList", {
        "item_id": item_id,
      },
      function(data) {
        $(".level_2_directory").html('<OPTION value="0">请选择</OPTION>');
        if (data.error_code == 0) {
          json = data.data;
          for (var i = 0; i < json.length; i++) {
            cat_html = '<OPTION value="' + json[i].cat_id + '" ';
            cat_html += ' ">' + json[i].cat_name + '</OPTION>';
            $(".level_2_directory").append(cat_html);
          };
        };

      },
      "json"

    );
}

/*加载三级目录*/
function getChildCatList() {
    var cat_id = $(".level_2_directory").val();
    $.post(
      DocConfig.server+"/api/catalog/childCatList", {
        "cat_id": cat_id
      },
      function(data) {
        $(".level_3_directory").html('<OPTION value="0">全部</OPTION>');
        if (data.error_code == 0) {
          json = data.data;
          for (var i = 0; i < json.length; i++) {
            cat_html = '<OPTION value="' + json[i].cat_id + '" ';
            cat_html += ' ">' + json[i].cat_name + '</OPTION>';
            $(".level_3_directory").append(cat_html);
          };
        } else {}

      },
      "json"

    );
}

//提交
$(".export-submit").click(function(){
    var item_id = $("#item_id").val();
    var val=$('input:radio[name="item_type"]:checked').val();
    if (val == 1 ) {
        var url = DocConfig.server+'/api/export/word&item_id='+item_id ;
        window.location.href = url;
    }
    else if (val == 2) {
        var cat_id2 = $(".level_2_directory").val();
        var cat_id3 = $(".level_3_directory").val();

        if (cat_id2 > 0 ) {
            var cat_id = cat_id3 > 0 ? cat_id3 : cat_id2 ;
            var url = DocConfig.server+'/api/export/word_cat&item_id='+item_id+'&cat_id='+cat_id ;
            window.location.href = url;
        }else{
            $.alert("请选择要导出的目录");
        }
    }

    return false;
});