<template>
  <div class="hello">
    <Header> </Header>
    
    <!-- 展示常规项目 -->
    <ShowRegularItem :item_info="item_info" :search_item="search_item" :keyword="keyword" v-if="item_info && item_info.item_type == 1 "> 

    </ShowRegularItem>
    
    <!-- 展示单页项目 -->
    <ShowSinglePageItem :item_info="item_info" v-if="item_info && item_info.item_type == 2 ">
    
    </ShowSinglePageItem>

    <Footer> </Footer>
    
  </div>
</template>



<script>
  import ShowRegularItem from '@/components/item/show/show_regular_item/Index'
  import ShowSinglePageItem from '@/components/item/show/show_single_page_item/Index'
  export default {
    data() {
      return {
        item_info:'' ,
        keyword:'',
      }
    },
  components:{
    ShowRegularItem,
    ShowSinglePageItem
  },
  methods:{
    //获取菜单
    get_item_menu(keyword){
        if (!keyword) {
          keyword = '' ;
        };  
        var that = this ; 
        var loading = that.$loading();
        var item_id = this.$route.params.item_id ? this.$route.params.item_id : 0;
        var page_id = this.$route.query.page_id ? this.$route.query.page_id : 0  ;
        
        var url = DocConfig.server+'/api/item/info';

        var params = new URLSearchParams();
        params.append('item_id',  item_id);
        params.append('keyword',  keyword);
        if ( !that.keyword) {
          params.append('default_page_id',page_id  );
        };
        that.axios.post(url, params)
          .then(function (response) {
            loading.close();
            if (response.data.error_code === 0 ) {
              var json = response.data.data ;
              if (json.default_page_id <= 0 ) {
                if (json.menu.pages[0]) {
                  json.default_page_id = json.menu.pages[0].page_id
                };
              };
              that.item_info = json ;
              document.title = that.item_info.item_name +"--ShowDoc";
              if (json.unread_count > 0 ) {
                that.$message({
                  showClose: true,
                  duration:10000,
                  dangerouslyUseHTMLString: true,
                  message: '<a href="#/notice/index">你有新的未读消息，点击查看</a>'
                });
              };

            }
            else if (response.data.error_code === 10307 || response.data.error_code === 10303 ) {
              //需要输入密码
              that.$router.replace({
                  path: '/item/password/'+item_id,
                  query: {page_id: page_id,redirect: that.$router.currentRoute.fullPath}
              });
            }
            else{
              that.$alert(response.data.error_message);
            }
            
          });
        //设置一个最长关闭时间
        setTimeout(() => {
          loading.close();
        }, 20000);
    },
    search_item(keyword){
      this.item_info = '';
      this.keyword = keyword ;
      this.get_item_menu(keyword);
    }
  },
  mounted () {
    this.get_item_menu();

  },
  beforeDestroy(){
    this.$message.closeAll();
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
    document.title = "ShowDoc" ;
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

</style>
