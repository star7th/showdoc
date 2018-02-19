<template>
  <div class="hello">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-row class="masthead">

          <div class="logo-title ">
              <h2 class="muted"><img src="static/logo/b_64.png" style="width:50px;height:50px;margin-bottom:-10px;" alt="">ShowDoc</h2>
          </div>
          <div class="header-btn-group pull-right">
            <el-button type="text"  @click="feedback">{{$t("feedback")}}</el-button>
            &nbsp;&nbsp;&nbsp;
            <el-dropdown @command="dropdown_callback">
              <span class="el-dropdown-link">
                {{$t("more")}}<i class="el-icon-arrow-down el-icon--right"></i>
              </span>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item><router-link to="/user/setting">{{$t("personal_setting")}}</router-link></el-dropdown-item>
                <el-dropdown-item :command="logout">{{$t("logout")}}</el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>

          </div>

        
      </el-row>

      </el-container>

      <el-container class="container-narrow">
        <div class="container-thumbnails">
          <ul class="thumbnails" id="item-list" v-if="itemList">

              <li class=" text-center"  v-for="item in itemList">
                <router-link class="thumbnail item-thumbnail"  :to="'/' +  (item.item_domain ? item.item_domain:item.item_id )" title="">
                  <span class="item-setting " @click.prevent="click_item_setting(item.item_id)" :title="$t('item_setting')" >
                    <i class="el-icon-setting"></i>
                  </span>
                  <span class="item-top " @click.prevent="click_item_top(item.item_id,item.top)" :title="item.top ? $t('cancel_item_top'):$t('item_top')" >
                    <i :class="item_top_class(item.top)"></i>
                  </span>
                  <p class="my-item">{{item.item_name}}</p>
                </router-link>
              </li>

              <li class=" text-center"  >
                <router-link class="thumbnail item-thumbnail"  to="/item/add" title="">
                  <p class="my-item">{{$t('new_item')}}<i class="el-icon-plus"></i></p>
                </router-link>
              </li>

          </ul>
        </div>

    </el-container>

    <Footer> </Footer>
    
  </div>
</template>

<style scoped>


  .container-narrow{
    margin: 0 auto;
    max-width: 700px;
  }

  .masthead{
    width: 100%;
    margin-top: 30px;
  }

  .header-btn-group{
   margin-top: -38px;
  }

  .logo-title{
    margin-left: 0px;
  }

  .container-thumbnails{
    margin: 0 auto;
    margin-top: 30px;
    max-width: 700px;
  }

  .my-item{
    margin: 40px 5px;
  }

  .thumbnails>li {
      float: left;
      margin-bottom: 20px;
      margin-left: 20px;
    }

  .thumbnails li a{
    color: #777;
    font-weight: bold;
    height: 100px;
    width: 180px;
  }
  .thumbnails li a:hover,
  .thumbnails li a:focus{
    border-color:#f2f5e9;
    -webkit-box-shadow:none;
    box-shadow:none;
    text-decoration: none;
    background-color: #f2f5e9;
  }

  .thumbnail {
    display: block;
    padding: 4px;
    line-height: 20px;
    border: 1px solid #ddd;
    -webkit-border-radius: 4px;
    -moz-border-radius: 4px;
    border-radius: 4px;
    -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.055);
    -moz-box-shadow: 0 1px 3px rgba(0,0,0,0.055);
    box-shadow: 0 1px 3px rgba(0,0,0,0.055);
    -webkit-transition: all .2s ease-in-out;
    -moz-transition: all .2s ease-in-out;
    -o-transition: all .2s ease-in-out;
    transition: all .2s ease-in-out;
    list-style: none;
  }

  .item-setting{
    float:right;
    margin-right:15px;
    margin-top:5px;
    display: none;
  }

  .item-top{
    float:right;
    margin-right:5px;
    margin-top:5px;
   display: none;
  }

  .thumbnails li a i{
    color: #777;
    font-weight: bold;
    margin-left: 5px;
  }

</style>

<script>
if (typeof window !== 'undefined') {
  var $s = require('scriptjs');
}
export default {
  data() {
    return {
      currentDate: new Date(),
      itemList:{}
    };
  },
  methods:{
    get_item_list(){
        var that = this ;
        var url = DocConfig.server+'/api/item/myList';

        var params = new URLSearchParams();

        that.axios.get(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              //that.$message.success("加载成功");
              var json = response.data.data ;
              that.itemList = json ;
              that.bind_item_even();
            }else{
              that.$alert(response.data.error_message);
            }
            
          });
    },
    feedback(){
      window.open('https://github.com/star7th/showdoc/issues');
    },
    item_top_class(top){
      if (top) {
        return 'el-icon-arrow-down';
      };
      return 'el-icon-arrow-up';
    },

    bind_item_even(){

      //这里偷个懒，直接用jquery来操作DOM。因为老版本的代码就是基于jquery的，所以复制过来稍微改下
      $s(["static/jquery.min.js"],()=>{

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
      });
    },

    //进入项目设置页
    click_item_setting(item_id){
       this.$router.push({path:'/item/setting/'+item_id});
    },
    click_item_top(item_id ,old_top){
      if (old_top) {
        var action = 'cancel'; 
      }else{
        var action = 'top'; 
      }
      var that = this ;
      var url = DocConfig.server+'/api/item/top';
      var params = new URLSearchParams();
      params.append('action', action);
      params.append('item_id', item_id);
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            that.get_item_list();
          }else{
            that.$alert(response.data.error_message);
          }
          
        });
    },
    logout(){
        var that = this ;
        var url = DocConfig.server+'/api/user/logout';

        var params = new URLSearchParams();

        that.axios.get(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.$router.push({
                  path: '/'
                });
            }else{
              that.$alert(response.data.error_message);
            }
            
          });
    },

    dropdown_callback(data){
      if (data) {
        data();
      };
    },
  },
  mounted () {
    this.get_item_list();
    
  },
  beforeDestroy(){
    this.$message.closeAll();
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
}
</script>