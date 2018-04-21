<template>
  
  <div :id="id" class="main-editor">
    <link href="static/editor.md/css/editormd.min.css" rel="stylesheet">

    <textarea v-html="content" style="display:none;"></textarea>

        <!-- 放大图片 -->
        <BigImg v-if="showImg" @clickit="showImg = false" :imgSrc="imgSrc"></BigImg>

  </div>
</template>
<style scoped>


</style>
<script>
import BigImg from '@/components/common/BigImg'
if (typeof window !== 'undefined') {
  var $s = require('scriptjs');
}


export default {
  name: 'Editor',
  props: {
    width: '',
    content:{
      type: String,
      default: '###初始化成功'
    },
    type: {
      type:String,
      default: 'editor'
    },
    id: {
      type: String,
      default: 'editor-md'
    },
    editorPath: {
      type: String,
      default: 'static/editor.md',
    },
    editorConfig: {
      type: Object,
      default() {
        return {
        path: 'static/editor.md/lib/', 
        height: 1000,
	      taskList        : true,
	      tex             : true,  // 默认不解析
	      flowChart       : true,  // 默认不解析
	      sequenceDiagram : true,  // 默认不解析
        syncScrolling: "single",
        htmlDecode: 'style,script,iframe|filterXSS',
        imageUpload: true,
        imageFormats: ["jpg", "jpeg", "gif", "png", "bmp", "webp", "JPG", "JPEG", "GIF", "PNG", "BMP", "WEBP"],
        imageUploadURL: DocConfig.server+"/api/page/uploadImg",
        onload: () => {
            console.log('onload');
          },
        };
      },
    },

  },
  components:{
    BigImg
  },
  data() {
    return {
      instance: null,
      showImg:false,
      imgSrc: ''
    };
  },
  computed: {
  },
  mounted() {
    
    //加载依赖""
    $s([`${this.editorPath}/../jquery.min.js`,
    	`${this.editorPath}/lib/raphael.min.js`,
    	`${this.editorPath}/lib/flowchart.min.js`,

    	],()=>{
	    $s([
	      `${this.editorPath}/../xss.min.js`,
	      `${this.editorPath}/lib/marked.min.js`,
	      `${this.editorPath}/lib/prettify.min.js`,
	      `${this.editorPath}/lib/underscore.min.js`,
		    `${this.editorPath}/lib/sequence-diagram.min.js`,
		    `${this.editorPath}/lib/jquery.flowchart.min.js`,
	    ], () => {
	       
	      $s(`${this.editorPath}/editormd.js`, () => {
	        this.initEditor();
	      });

	      $s(`${this.editorPath}/../highlight/highlight.min.js`, () => {
	        hljs.initHighlightingOnLoad();
	      });

    });
    
    
    });

  },
  beforeDestroy() {

    //清理所有定时器
    for (var i = 1; i < 999; i++){
      window.clearInterval(i);
    };

    //window.removeEventListener('beforeunload', e => this.beforeunloadHandler(e))
  },
  methods: {
    initEditor() {
      this.$nextTick((editorMD = window.editormd) => {
        if (editorMD) {
          if (this.type == 'editor'){
            this.instance = editorMD(this.id, this.editorConfig);
            //草稿
            //this.draft(); 鉴于草稿功能未完善。先停掉。
            //window.addEventListener('beforeunload', e => this.beforeunloadHandler(e));
          } else {
            this.instance = editorMD.markdownToHTML(this.id, this.editorConfig);
          }
          this.deal_with_content();
        } 
      });
    },

    //插入数据到编辑器中。插入到光标处
    insertValue(insertContent){
      this.instance.insertValue(insertContent);
    },

    getMarkdown(){
      return this.instance.getMarkdown();
    },

    clear(){
      return this.instance.clear();
    },

    //草稿
    draft(){
      var that = this ;
        //定时保存文本内容到localStorage
        setInterval(()=>{
            localStorage.page_content= that.getMarkdown() ;
        }, 60000);

      //检测是否有定时保存的内容
      var page_content = localStorage.page_content ;
      if (page_content && page_content.length > 0) {
        localStorage.removeItem("page_content");
        that.$confirm(that.$t('draft_tips'),'',{
          showClose:false
        }
        ).then(()=>{
            that.clear() ;
            that.insertValue(page_content) ;
            localStorage.removeItem("page_content");
          }).catch(()=>{
            localStorage.removeItem("page_content");
          });
      };

    },
    //关闭前提示
    beforeunloadHandler(e){
        e = e || window.event;  
        
        // 兼容IE8和Firefox 4之前的版本  
        if (e) {  
          e.returnValue = '提示';  
        }  
        
        // Chrome, Safari, Firefox 4+, Opera 12+ , IE 9+  
        return '提示';  
    },

    //对内容做些定制化改造
    deal_with_content(){
      var that = this ;
        //当表格列数过长时将自动出现滚动条
        $.each($("#"+this.id+' table'), function() {
            $(this).prop('outerHTML', '<div style="width: 100%;overflow-x: auto;">'+$(this).prop('outerHTML')+'</div>');
        });

        //超链接都在新窗口打开
        $("#"+this.id+' a[href^="http"]').each(function() {
          $(this).attr('target', '_blank');

        });

        //对表格进行一些改造
        $("#"+this.id+" table tbody tr").each(function(){
          var tr_this = $(this) ;
          var td1 =  tr_this.find("td").eq(1).html() ;
          var td2 =  tr_this.find("td").eq(2).html() ;
          if(td1 =="object" || td1 =="array[object]" || td2 =="object" || td2 =="array[object]"){
            tr_this.css({"background-color":"#F8F8F8"});
          }else{
            tr_this.css("background-color","#fff"); 
          }
          //设置表格hover
          tr_this.hover(function(){
              tr_this.css("background-color","#F8F8F8");
          },function(){
            if(td1 =="object" || td1 =="array[object]" || td2 =="object" || td2 =="array[object]"){
              tr_this.css({"background-color":"#F8F8F8"});
            }else{
              tr_this.css("background-color","#fff"); 
            }
          });

        });

        $("th").css("width","180px");
        //图片点击放大
        $("#"+this.id+" img").click(function(){
          var  img_url = $(this).attr("src");
          that.showImg = true;
　　　　　　// 获取当前图片地址
          that.imgSrc = img_url;
        });
        
        //表格头颜色
        $("#"+this.id+" table thead tr").css("background-color","#409eff") ;
        $("#"+this.id+" table thead tr").css("color","#fff") ;

        //代码块美化
        $("#"+this.id+" .linenums").css("padding-left","5px") ;
        $("#"+this.id+" .linenums li").css("list-style-type","none") ;
        $("#"+this.id+" .linenums li").css("background-color","#fcfcfc") ;
        $("#"+this.id+" pre").css("background-color","#fcfcfc") ;
        $("#"+this.id+" pre").css("border","1px solid #e1e1e8") ;
        
        $("#"+this.id+" code").css("color","#d14");
        
    },
  }
};
</script>
