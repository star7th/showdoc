<template>

  <div class="hello">


    <el-container>
      <el-header>
      <div class="header_title">ShowDoc</div>
      <router-link class="goback" to="/item/index">{{$t('goback')}}</router-link>
    </el-header>
      <el-container>
        <el-aside width="150px">

        <el-menu
          default-active="1"
          class="el-menu-vertical-demo"
          background-color="#545c64"
          text-color="#fff"
          @select="select_menu"
          active-text-color="#ffd04b">
          <el-menu-item index="1">
            <i class="el-icon-info"></i>
            <span slot="title">{{$t('user_manage')}}</span>
          </el-menu-item>
          <el-menu-item index="2">
            <i class="el-icon-tickets"></i>
            <span slot="title">{{$t('item_manage')}}</span>
          </el-menu-item>
          <el-menu-item index="3">
            <i class="el-icon-tickets"></i>
            <span slot="title">{{$t('web_setting')}}</span>
          </el-menu-item>
        </el-menu>

      </el-aside>
        <el-container>
          <el-main>

            <User v-if="open_menu_index == 1 "> </User>
            <Item v-if="open_menu_index == 2 "> </Item> 
             <Setting v-if="open_menu_index == 3 "> </Setting> 

        </el-main>
          <el-footer>
            <!-- something -->
        </el-footer>
        </el-container>
      </el-container>
    </el-container>


    </div>


    
  </div>
</template>

<style scoped>
  .el-header {
    background-color: #fff;
    color: #333;
    text-align: center;
    line-height: 60px;
    border-bottom:1px solid #ddd;
    padding-left: 0px;

  }

   .el-footer {
    color: #333;
    text-align: center;
    line-height: 60px;
  }


  .el-aside {
    background-color: rgb(84, 92, 100);
    color: #333;
    text-align: center;
    line-height: 200px;
    height: calc(100% - 60px);
    position: absolute;
  }

  .el-menu{
    border-right: 0px;
  }

  .el-main {
    margin-left: 200px;
    overflow: visible;
  }
  
  body > .el-container {
    position: absolute;
    height: 100%;
    width: 100%;

  }
  
  .el-container:nth-child(5) .el-aside,
  .el-container:nth-child(6) .el-aside {
    line-height: 260px;
  }
  
  .el-container:nth-child(7) .el-aside {
    line-height: 320px;
  }

  .goback{
    float: right;
    margin-right: 20px;
  }

  .header_title{
    float: left;
    padding-right: 35px;
    padding-left: 25px;
    font-size: 20px;
    background-color: rgb(84, 92, 100);
    color: #fff;
  }
</style>

<script>
import Item from '@/components/admin/item/Index'
import User from '@/components/admin/user/Index'
import Setting from '@/components/admin/setting/Index'

export default {
  data() {
    return {
      open_menu_index:1,
    };
  },
  components:{
    Item,
    User,
     Setting,
  },
  methods:{
    select_menu(index,indexPath){
      this.open_menu_index = 0 ;
      this.$nextTick(()=>{
        this.open_menu_index = index ;
      });
      
    },
    check_upadte(){
        var that = this ;
        var url = DocConfig.server+'/api/adminUser/checkUpdate';
          var params = new URLSearchParams();
          that.axios.post(url, params)
            .then(function (response) {
              if (response && response.data && response.data.data && response.data.data.url) {
                  that.$message({
                    showClose: true,
                    duration:10000,
                    dangerouslyUseHTMLString: true,
                    message: '<a target="_blank" href="'+response.data.data.url+'">'+response.data.data.title+'</a>'
                  });
              };

            });
    },
  },
  mounted () {
    this.check_upadte();
    
  },
  beforeDestroy(){
    this.$message.closeAll();
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
}
</script>