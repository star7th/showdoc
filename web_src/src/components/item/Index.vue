<template>
  <div class="hello">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-row class="masthead">

          <div class="logo-title ">
              <h2 class="muted"><img src="static/logo/b_64.png" style="width:50px;height:50px;margin-bottom:-10px;" alt="">ShowDoc</h2>
          </div>
          <div class="header-btn-group pull-right">
                <el-tooltip class="item" effect="dark" :content="$t('feedback')" placement="top">
                      <router-link to="" ><i @click="feedback" class="el-icon-phone-outline" ></i></router-link>
                </el-tooltip>

                <el-tooltip v-if="lang =='zh-cn'" class="item" effect="dark" content="客户端" placement="top">
                  <a target="_blank"  href="https://www.showdoc.cc/clients"><i class="el-icon-mobile-phone" ></i></a>
                </el-tooltip>

                <el-tooltip class="item" effect="dark" :content="$t('team_mamage')" placement="top">
                      <router-link to="/team/index"><i class="el-icon-s-flag" ></i></router-link>
                </el-tooltip>

                <el-tooltip v-if="isAdmin" class="item" effect="dark" :content="$t('background')" placement="top">
                      <router-link to="/admin/index" ><i class="el-icon-s-tools" ></i></router-link>
                </el-tooltip>

              &nbsp;&nbsp;
              <el-tooltip class="item" effect="dark" :content="$t('more')" placement="top">
                <el-dropdown @command="dropdown_callback" trigger="click">
                  <span class="el-dropdown-link">
                    <i class="el-icon-caret-bottom el-icon--right"></i>
                  </span>
                  <el-dropdown-menu slot="dropdown">
                    <el-dropdown-item><router-link to="/user/setting">{{$t("personal_setting")}}</router-link></el-dropdown-item>
                    <el-dropdown-item><a target="_blank" v-if="lang =='zh-cn'" href="http://runapi.showdoc.cc/">RunApi</a></el-dropdown-item>
                    <el-dropdown-item :command="logout">{{$t("logout")}}</el-dropdown-item>
                  </el-dropdown-menu>
                </el-dropdown>
              </el-tooltip>

          </div>

        
      </el-row>

      </el-container>

      <el-container class="container-narrow">

        <div class="container-thumbnails">

          <div class="search-box-div" v-if="itemList.length > 9">
              <div class="search-box el-input el-input--prefix">
                <input autocomplete="off" type="text" rows="2" validateevent="true" class="el-input__inner" v-model="keyword">
                <span class="el-input__prefix">
                  <i class="el-input__icon el-icon-search"></i>
                </span>
              </div>
          </div>

          <ul class="thumbnails" id="item-list" v-if="itemListByKeyword">
                <draggable v-model="itemListByKeyword" tag="span" group="item"  @end="endMove" ghostClass="sortable-chosen">
                  <li class=" text-center"  v-for="item in itemListByKeyword"
                     v-dragging="{ item: item, list: itemListByKeyword, group: 'item' }"
                  >
                    <router-link class="thumbnail item-thumbnail"  :to="'/' +  (item.item_domain ? item.item_domain:item.item_id )" title="">
                      <span class="item-setting " @click.prevent="click_item_setting(item.item_id)" :title="$t('item_setting')" v-if="item.creator" >
                        <i class="el-icon-setting"></i>
                      </span>
                      <span class="item-exit" @click.prevent="click_item_exit(item.item_id)" :title="$t('item_exit')" v-if="! item.creator">
                        <i class="el-icon-close"></i>
                      </span>
                      <p class="my-item">{{item.item_name}}</p>
                    </router-link>
                  </li>
                </draggable>
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
    max-width: 930px;
  }

  .masthead{
    width: 100%;
    margin-top: 30px;
  }

  .header-btn-group{
   margin-top: -38px;
   font-size: 18px;
  }

  .header-btn-group a{
    color: #333;
    margin-left: 25px;
  }

  .el-dropdown{
      font-size: 18px;
  }
  .el-dropdown-link , a {
    color: #333;
  }


  .logo-title{
    margin-left: 0px;
  }

  .container-thumbnails{
    margin-top: 30px;
    max-width: 1000px;
  }

  .my-item{
    margin: 40px 5px;
  }

  .thumbnails li {
      float: left;
      margin-bottom: 20px;
      margin-left: 20px;
    }

  .thumbnails li a{
    color: #444;
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
    background-color: #ffffff;
  }

  .item-setting{
    float:right;
    margin-right:15px;
    margin-top:5px;
    display: none;
  }

  .item-exit{
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

  .item-thumbnail:hover .item-setting {
    display: block;
  }
  .item-thumbnail:hover .item-exit {
    display: block;
  }

  .search-box-div{
    width: 190px;
    margin-left: 60px;
  }

  .sortable-chosen .item-thumbnail{
    color: #ffffff;
    background-color: #ffffff;
  }

</style>

<script>
import draggable from 'vuedraggable'
if (typeof window !== 'undefined') {
  var $s = require('scriptjs');
}
export default {
  components: {
      draggable,
  },
  data() {
    return {
      currentDate: new Date(),
      itemList:{},
      isAdmin:false,
      keyword:'',
      lang:''
    };
  },
  computed:{
    itemListByKeyword:function(){
      if (!this.keyword) {
        return this.itemList ;
      };
      let itemListByKeyword = [] ;
      for (var i = 0; i < this.itemList.length; i++) {
        if (this.itemList[i]['item_name'].indexOf(this.keyword) > -1 ) {
          itemListByKeyword.push(this.itemList[i]);
        };
        
      };
      return itemListByKeyword ;
    }
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
              //that.bind_item_even();
            }else{
              //that.$alert(response.data.error_message);
            }
            
          });
    },
    feedback(){
      if (DocConfig.lang =='en') {
        window.open('https://github.com/star7th/showdoc/issues');
      }else{
        var msg = "你正在使用免费开源版showdoc，如有问题或者建议，请到github提issue：";
        msg += "<a href='https://github.com/star7th/showdoc/issues' target='_blank'>https://github.com/star7th/showdoc/issues</a><br>";
        msg += "如果你觉得showdoc好用，不妨给开源项目点一个star。良好的关注度和参与度有助于开源项目的长远发展。";
        this.$alert(msg, {
            dangerouslyUseHTMLString: true
        });
      }

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
            //$(this).find(".item-top").show();
            //$(this).find(".item-down").show();
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
    click_item_exit(item_id){
      var that = this ;
      this.$confirm(that.$t('confirm_exit_item'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var url = DocConfig.server+'/api/item/exitItem';
        var params = new URLSearchParams();
        params.append('item_id', item_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              window.location.reload();
            }else{
              that.$alert(response.data.error_message);
            }
          });
      })
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
    
    user_info(){
        var that = this ;
        this.get_user_info(function(response){
          if (response.data.error_code === 0 ) {
            if (response.data.data.groupid == 1 ) {
              that.isAdmin = true ;
            };
          }
        });

    },
    dropdown_callback(data){
      if (data) {
        data();
      };
    },


    sort_item(data){
      var that = this ;
      var url = DocConfig.server+'/api/item/sort';
      var params = new URLSearchParams();
      params.append('data', JSON.stringify(data));
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            that.get_item_list();
            //window.location.reload();

          }else{
            that.$alert(response.data.error_message,'',{
              callback:function(){
                window.location.reload();
              }
            });
            
          }
          
        });
    },
    exchangeArray( data ,  oldIndex , newIndex ){
      let tmp = data[oldIndex];
      data.splice(oldIndex,1);
      data.splice(newIndex , 0, tmp);
      return data;
    },
    endMove(evt){
      let data = {} ;
      let list = this.exchangeArray( this.itemList , evt['oldIndex'] , evt['newIndex']  ) ;
      this.itemList = [] ;
      this.$nextTick(()=>{
        this.itemList = list ;
      });
      for (var i = 0; i < list.length; i++) {
        let key = list[i]['item_id'] ;
        data[key] = i + 1  ;
      };
      this.sort_item(data);
    },
    script_cron(){
      var url = DocConfig.server+'/api/ScriptCron/run';
      this.axios.get(url);
    }

  },
  mounted () {
    this.get_item_list();
    this.user_info();
    this.lang = DocConfig.lang ;
    this.script_cron();
  },
  beforeDestroy(){
    this.$message.closeAll();
  }
}
</script>
