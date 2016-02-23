var editormd;

var json_table_data='|参数名|类型|说明|\n'+
		'|:-------|:-------|:-------|\n';

$(function() {
  /*加载目录*/
  getCatList();

  function getCatList() {
    var default_cat_id = $("#default_cat_id").val();
    var item_id = $("#item_id").val();
    $.get(
      "../catalog/catList", {
        "item_id": item_id
      },
      function(data) {
        $("#cat_id").html('<OPTION value="0">无</OPTION>');
        if (data.error_code == 0) {
          json = data.data;
          console.log(json);
          for (var i = 0; i < json.length; i++) {
            cat_html = '<OPTION value="' + json[i].cat_id + '" ';
            if (default_cat_id == json[i].cat_id) {
              cat_html += ' selected ';
            }

            cat_html += ' ">' + json[i].cat_name + '</OPTION>';
            $("#cat_id").append(cat_html);
          };
        };

      },
      "json"

    );
  }

  var keyMap = {
    // 保存
    "Ctrl-S": function() {
      $("#save").click();
    }
  };
  initEditorOutsideKeys();

  function initEditorOutsideKeys() {
    if (!editormd) return;
    var $doc = $(document);
    $.each(keyMap, function(key, fn) {
      $doc.on('keydown', null, key.replace('-', '+'), function(e) {
        e.preventDefault();
        fn();
      });
    });
  }
  // 如果是新增页面，则光标为标题文本框
  if (location.href.indexOf('type=new') !== -1) {
    setTimeout(function() {
      $('#page_title').focus();
    }, 1000);
  }

  /*初始化编辑器*/
  editormd = editormd("editormd", {
    width: "90%",
    height: 1000,
    syncScrolling: "single",
    path: DocConfig.pubile + "/editor.md/lib/",
    placeholder: "本编辑器支持Markdown编辑，左边编写，右边预览",
    imageUpload: true,
    imageFormats: ["jpg", "jpeg", "gif", "png", "bmp", "webp", "JPG", "JPEG", "GIF", "PNG", "BMP", "WEBP"],
    imageUploadURL: "uploadImg",
    onload: function() {
      this.addKeyMap(keyMap);
    }
  });

  /*插入API接口模板*/
  $("#api-doc").click(function() {
    var tmpl = $("#api-doc-templ").html();
    editormd.insertValue(tmpl);
  });
  /*插入数据字典模板*/
  $("#database-doc").click(function() {
    var tmpl = $("#database-doc-templ").html();
    editormd.insertValue(tmpl);
  });
  
   /*插入JSON*/
  $("#jsons").click(function() {
	  
   $("#json-templ").show();
	
  });
  
  

		
	$("#json-templ .editormd-enter-btn").click(function(){
		
		
		var datas=$("#json-templ .jsons").val();
		
		try{
			Change($.parseJSON(datas));   
		}
		catch(e){
			alert("json导入失败" + e);
		}
		
		//datas=processJSONImport(datas);
		//alert(datas);
		/*var datas='|键|值|类型|空|注释|\n'+
		'|:-------|:-------|:-------|:-------|:-------|\n'+
		'|uid|int(10)|否|||\n'+
		'|username|varchar(20)|否||用户名|';*/
		
		//alert(json_table_data);return;
		
		
		
		editormd.insertValue(json_table_data);
		
		json_table_data='|键|类型|说明|\n'+
		'|:-------|:-------|:-------|\n';
		
		
		$("#json-templ .jsons").val("");
		$("#json-templ").hide();
		
	});
	

//{"dgfgdfg":"gdfgdfg"}
  

  /*保存*/
  var saving = false;
  $("#save").click(function() {
    if (saving) return false;
    var page_id = $("#page_id").val();
    var item_id = $("#item_id").val();
    var cat_id = $("#cat_id").val();
    var page_title = $("#page_title").val();
    var page_content = $("#page_content").val();
    var item_id = $("#item_id").val();
    var order = $("#order").val();
    saving = true;
    $.post(
      "save", {
        "page_id": page_id,
        "cat_id": cat_id,
        "order": order,
        "page_content": page_content,
        "page_title": page_title,
        "item_id": item_id
      },
      function(data) {
        if (data.error_code == 0) {
          $.bootstrapGrowl("保存成功！");
          window.location.href = "../item/show?page_id=" + data.data.page_id + "&item_id=" + item_id;
        } else {
          $.bootstrapGrowl("保存失败！");

        }
        saving = false;
      },
      'json'
    )
  });



	$(".editormd-preview-container").bind('DOMNodeInserted', function(e){
		
		$(".editormd-preview-container table thead tr").css({"background-color":"#08c","color":"#fff"});
    $(".editormd-preview-container table tr").eq(0).css({"background-color":"#08c","color":"#fff"});
		$(".editormd-preview-container table tr").each(function(){
			if($(this).find("td").eq(1).html()=="object")
			{
				$(this).css({"background-color":"#99CC99","color":"#000"});
			}
			
			});
	});

});


function closeDiv(target)
{
	$(target).hide();
}

function Change(data)
{
	var level_str="- ";
	if(arguments.length>1)
	{
		var level;
		arguments[1]>0?level=arguments[1]:level=1;
		for(var i=0;i<level;i++)
		{
			level_str+="- ";
		}
	}
	
	for(var key in data)
	{
		var value = data[key];
		var type = typeof(value);
		if(type == "object")
		{
			json_table_data+='| '+level_str+key+' |'+type+'  | 无 |\n';
			if(value instanceof Array)
			{
				var j=level+1;
				Change(value[0],j);
				continue;
			}
			//else
			//{
				Change(value,level);
			//}
			
		}
		else
		{
			json_table_data+='| '+key+' | '+type+'| 无 |\n';
		}
	}
}

//{"Result":[{"name":"test1","list":{"pros":"prosfsf","ppps":{"images":[{"22":"22"}]}}}]}
