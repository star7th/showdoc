<template>
  <div class="hello">
    <Header> </Header>

      <el-container>

        <el-aside class="el-aside" id="left-side">
            <LeftMenu :get_page_content="get_page_content" :keyword="keyword" :item_info="item_info" :search_item="search_item" v-if="item_info" ></LeftMenu>
        </el-aside>
        
        <el-container class="right-side" id="right-side">
 

          <el-header >
            <div class="header-left">
                 <i class="el-icon-menu header-left-btn" id="header-left-btn" @click="switch_menu"></i>
            </div> 

            <div class="header-right">
              <!-- 登录的事情下 -->
               <router-link v-if="item_info.is_login" to="/item/index" >{{$t('goback')}} </router-link>
                &nbsp;&nbsp;&nbsp;&nbsp;
              <el-dropdown @command="dropdown_callback" v-if="item_info.is_login">
                <span class="el-dropdown-link">
                  {{$t('item')}}<i class="el-icon-arrow-down el-icon--right"></i>
                </span>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item :command="share_item">{{$t('share')}}</el-dropdown-item>
                  <router-link :to="'/item/export/'+item_info.item_id" v-if="item_info.ItemPermn"><el-dropdown-item>{{$t('export')}}</el-dropdown-item></router-link>
                  <router-link :to="'/item/setting/'+item_info.item_id"  v-if="item_info.ItemCreator"><el-dropdown-item>{{$t('item_setting')}}</el-dropdown-item></router-link>
                </el-dropdown-menu>
              </el-dropdown>

              <!-- 非登录的情况下 -->

              <div v-if="!item_info.is_login">
                  <router-link to="/user/login">{{$t('login_or_register')}}</router-link>
                  &nbsp;&nbsp;&nbsp;&nbsp;
                  <a href="https://www.showdoc.cc/help" target="_blank">{{$t('about_showdoc')}}</a>
              </div>

            </div> 


          </el-header>
          
          <el-main class="page_content_main" id="page_content_main">
             <div class="doc-title-box"  v-if="page_id">
                <span id="doc-title-span" class="dn"></span>
                <h2 id="doc-title">{{page_title}}</h2>
                <el-badge :value="attachment_count" class="item"  id="attachment" v-if="attachment_count"   @click.native="ShowAttachment" >
                  <i class="el-icon-upload"></i> 
                </el-badge>
            </div>
              <Editormd v-bind:content="content" type="html" :keyword="keyword"  v-if="page_id" ></Editormd>

          </el-main>

          
        </el-container>

        <div class="page-bar" v-show="show_page_bar && item_info.ItemPermn && item_info.is_archived < 1 " >
          <PageBar v-if="page_id" :page_id="page_id" :item_id='item_info.item_id' :item_info='item_info'  :page_info="page_info"></PageBar>
        </div>
        
      </el-container>

      <BackToTop  > </BackToTop>
      <Toc  v-if="page_id" > </Toc>

  <el-dialog
    title="分享项目"
    :visible.sync="dialogVisible"
    width="600px"
    :modal="false"
    class="text-center"
    >
    
    <p>项目地址：<code >{{share_item_link}}</code></p>
    <p><a href="javascript:;" class="home-phone-butt" v-clipboard:copyhttplist="copyText" v-clipboard:success="onCopy">{{$t('copy_link')}}</a></p>
        <p style="border-bottom: 1px solid #eee;"><img id="" style="width:114px;height:114px;" :src="qr_item_link"> </p>
    <span slot="footer" class="dialog-footer">
      <el-button type="primary" @click="dialogVisible = false">{{$t('confirm')}}</el-button>
    </span>
  </el-dialog>

    <!-- 附件列表 -->
    <AttachmentList callback="" :item_id="page_info.item_id" :manage="false" :page_id="page_info.page_id" ref="AttachmentList"></AttachmentList>



    <Footer> </Footer>
    
  </div>
</template>



<script>
  import Editormd from '@/components/common/Editormd'
  import BackToTop from '@/components/common/BackToTop'
  import Toc from '@/components/common/Toc'
  import LeftMenu from '@/components/item/show/show_regular_item/LeftMenu'
  import PageBar from '@/components/item/show/show_regular_item/PageBar'
  import AttachmentList from '@/components/page/edit/AttachmentList'


  export default {
    props:{
      item_info:'',
      search_item:'',
      keyword:''
    },
    data() {
      return {
        content:"###正在加载...",
        page_id:'',
        page_title:'',
        dialogVisible:false,
        share_item_link:'',
        qr_item_link:'',
        page_info:'',
        show_page_bar:true,
        copyText:"",
        attachment_count:'',
      }
    },
  components:{
    Editormd,
    LeftMenu,
    PageBar,
    BackToTop,
    Toc,
    AttachmentList
  },
  methods:{
    //获取页面内容
    get_page_content(page_id){
      if (page_id <= 0 ) {return;};
      //根据屏幕宽度进行响应(应对移动设备的访问)
      if( this.isMobile() ||  window.screen.width< 1000){
        this.$nextTick(() => {
          this.AdaptToMobile();
        });
      }
        var that = this ;
        var url = DocConfig.server+'/api/page/info';
        //var loading = that.$loading({target:".page_content_main",fullscreen:false});
        var params = new URLSearchParams();
        params.append('page_id',  page_id);
        that.axios.post(url, params)
          .then(function (response) {
            //loading.close();
            if (response.data.error_code === 0 ) {
              that.content = response.data.data.page_content ;
              
              that.page_title = response.data.data.page_title ;
              that.page_info = response.data.data ;
              that.attachment_count = response.data.data.attachment_count > 0 ?  response.data.data.attachment_count  :'' ;
              //切换变量让它重新加载、渲染子组件
              that.page_id = 0 ;
              that.$nextTick(() => {
                that.page_id = page_id ;
              });
              
            }else{
              //that.$alert(response.data.error_message);
            }
            
          });
    },
    dropdown_callback(data){
      if (data) {
        data();
      };
    },
    share_item(){
      this.share_item_link =  this.getRootPath()+"#/"+this.item_info.item_id  ;
      this.qr_item_link = DocConfig.server +'/api/common/qrcode&size=3&url='+encodeURIComponent(this.share_item_link);
      this.dialogVisible = true;
      this.copyText = this.item_info.item_name+"  -- ShowDoc \r\n"+ this.share_item_link;
    },
    //根据屏幕宽度进行响应(应对移动设备的访问)
    AdaptToMobile(){
      this.hide_menu();
      this.show_page_bar = false;

    },
    show_menu(){
        var element = document.getElementById('left-side') ;
        element.style.display = 'block' ;
        var element = document.getElementById('right-side') ;
        element.style.marginLeft = '300px'; 
        var element = document.getElementById('page_content_main') ;
        element.style.width = '800px' ; 
    },
    hide_menu(){
        var element = document.getElementById('left-side') ;
        element.style.display = 'none';
        var element = document.getElementById('right-side') ;
        element.style.marginLeft = '0px'; 
        var element = document.getElementById('page_content_main') ;
        element.style.width = '95%' ; 
    },
    switch_menu(){
      var element = document.getElementById('left-side') ;
      if (element.style.display == 'none') {
        this.show_menu();
      }else{
        this.hide_menu();

      }

    },
    onCopy(){
      this.$message(this.$t("copy_success"));
    },
    ShowAttachment(){
        let childRef = this.$refs.AttachmentList ;//获取子组件
        childRef.show() ; 
    },
    
  },
  mounted () {
    //根据屏幕宽度进行响应(应对移动设备的访问)
    if( this.isMobile() ||  window.screen.width< 1000){
      this.$nextTick(() => {
        this.AdaptToMobile();
      });
    }
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

  .header-right{
    color: #333;
    line-height: 40px;
    text-align: right; 
    font-size: 12px;
    /*border: 1px solid #eee;*/
  }
  .header-right .el-dropdown-link{
    margin-right: 20px;
  }
  .el-aside {
    color: #333;
    position:fixed;
    height: calc(100% - 20px);
    background-color: rgb(250, 250, 250);
    border-right: solid 1px #e6e6e6;
  }
  .page-bar{
    color: #333;
    position:fixed;
    top: 100px;
    right: 10px;
    width: 100px;
  }

  .page_content_main{
    width:800px;
    margin: 0 auto ;
    height: 50%;
    overflow: visible;
  }

  .right-side{
    margin-left:300px;
  }

  .doc-title-box{
      height: auto;
      margin: 30px 30px 10px 30px;
      width: auto;
      border-bottom: 1px solid #ebebeb;
      padding-bottom: 10px;
  }
  .editormd-html-preview{
    width: 95%;
    font-size: 16px;
  }

  .header-left{
    float: left;
    
  }

  .header-left-btn{
    font-size: 20px;
    margin-top: 5px;
    margin-left: -15px;
    cursor: pointer;
    position: fixed;
  }
  #attachment{
    float: right;
    font-size: 25px;
    margin-top: -40px;
    margin-right: 5px;
    cursor:pointer;
    color: #abd1f1;
  }
  
</style>
