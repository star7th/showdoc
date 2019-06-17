<template>
  <div class="hello">
    <Header> </Header>

      <div id="header"></div>
      <div class="container doc-container" id="doc-container">
         <div class="doc-title-box">
            <span id="doc-title-span" class="dn"></span>
            <h2 id="doc-title">{{page_title}}</h2>
        </div>
        <div id="doc-body" >

        <div id="page_md_content"  class="page_content_main" ><Editormd v-bind:content="content" v-if="content" type="html"></Editormd></div>
        </div>

      </div>
      <BackToTop></BackToTop>
      <Toc  v-if="page_id" > </Toc>
    <Footer> </Footer>
    <div class=""></div>
  </div>
</template>

<style scoped>


  #page_md_content{
    
      padding: 10px 10px 90px 10px;
      overflow: hidden;
      font-size: 11pt;
      line-height: 1.7;
      color: #333;
  }

  .doc-container {
      position: static;
      -webkit-box-shadow: 0px 1px 6px #ccc;
      -moz-box-shadow: 0px 1px 6px #ccc;
      -ms-box-shadow: 0px 1px 6px #ccc;
      -o-box-shadow: 0px 1px 6px #ccc;
      box-shadow: 0px 1px 6px #ccc;
      background-color: #fff;
      border-bottom: 1px solid #d9d9d9;
      margin-bottom: 20px;
      width: 800px;
      min-height: 500px;
      margin-left: auto;
      margin-right: auto;
      padding: 20px;
  }

  #header{
    height: 80px;
  }

  #doc-body{
    width: 90%;
    margin: 0 auto;
    background-color: #fff;
  }

  .doc-title-box{
      height: auto;
      margin: 30px 100px 10px 100px;
      width: auto;
      border-bottom: 1px solid #ebebeb;
      padding-bottom: 10px;
      width: 90%;
      margin: 10px auto;
  }
  #footer{
      margin: 0 auto;
      width: 180px;
      font-size: 8px;
      color: #959595;
  }

  pre ol{
    list-style: none;
  }

  .markdown-body pre {
    background-color: #f7f7f9;
    border: 1px solid #e1e1e8;
  }
  .hljs{
    background-color: #f7f7f9;
  }

  .editormd-html-preview, .editormd-preview-container{
    padding: 0px;
    font-size: 16px;
  }
</style>


<script>
import Editormd from '@/components/common/Editormd'
import BackToTop from '@/components/common/BackToTop'
import Toc from '@/components/common/Toc'

export default {
  data () {
    return {
      currentDate: new Date(),
      itemList:{},
      content:"",
      page_title:'',
      page_id:0,
    };
  },
  components:{
    Editormd,
    BackToTop,
    Toc
  },
  methods:{
    get_page_content(){
        var that = this ;
        var page_id = that.$route.params.page_id ;
        var unique_key = that.$route.params.unique_key ;
        if (unique_key) {
          var url = DocConfig.server+'/api/page/infoByKey';
        }else{
          var url = DocConfig.server+'/api/page/info';
        }
        
        var params = new URLSearchParams();
        params.append('page_id', page_id );
        params.append('unique_key', unique_key );
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              //that.$message.success("加载成功");
              that.content = response.data.data.page_content ;
              that.page_title = response.data.data.page_title ;
              that.page_id = response.data.data.page_id ;
            }
            else if (response.data.error_code === 10307 || response.data.error_code === 10303 ) {
              //需要输入密码
              that.$router.replace({
                  path: '/item/password/0',
                  query: {page_id: page_id,redirect: that.$router.currentRoute.fullPath}
              });
            }
            else{
              that.$alert(response.data.error_message);
            }
            
          });
    },
    AdaptToMobile(){
      var doc_container = document.getElementById('doc-container') ;
      doc_container.style.width = '95%';
      doc_container.style.padding = '5px';
      var header = document.getElementById('header') ;
      header.style.height = '10px';
    }

  },
  mounted () {
    this.get_page_content();
    //根据屏幕宽度进行响应(应对移动设备的访问)
    if( this.isMobile() ||  window.screen.width< 1000){
      this.$nextTick(() => {
        this.AdaptToMobile();
      });
    }
    /*给body添加类，设置背景色*/
    document.getElementsByTagName("body")[0].className="grey-bg";

  },
  beforeDestroy(){
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
}
</script>
