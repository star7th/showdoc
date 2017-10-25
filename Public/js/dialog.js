/**
 *  将layer.js的一些方法封装为showdoc常用的对话框.
 *  调用方法如$.window("https://www.showdoc.cc/","测试","50%","50%");
 */
$(document).ready(function(){
    jQuery.extend({
        dialog_self:null,
        //加载url，弹出窗口
        window:function(url ,title , width ,height ,callback){
            if (!title) {
                title = '';
            };
            if (!width) {
                width = '40%';
            };
            if (!height) {
                height = '40%' ;
            };
            return layer.open({
              type: 2,
              title: title,
              shadeClose: true,
              shade: 0.8,
              area: [width, height],
              content: url ,//iframe的url
              end:callback
            });
        },
        //这是对应上面window()的关闭函数。window里面的iframe页面可以调用此方法来关闭自身
        close_self_window:function(){
            //假设这是iframe页
            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            return parent.layer.close(index); //再执行关闭  
        },

        alert:function(content, options, yes){
            return layer.alert(content, options, yes)
        },

        confirm:function(content, options, yes, cancel){
            return layer.confirm(content, options, yes, cancel) ;
        },

        closeDialog:function(index){
            return layer.close(index)  ;
        },  

        closeAll:function(type){
            return layer.closeAll(type) ;
        },
    
        prompt:function(options, yes){
            return layer.prompt(options, yes);
        },

        msg:function(content, options, end){
            return layer.msg(content, options, end);
        },

        photos:function(options){
            return layer.photos(options);
        },

    });
});
