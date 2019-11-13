<template>
  <div class="hello">


    <el-menu  @select="select_menu"
      background-color="#fafafa"
      text-color=""
      active-text-color="#008cff" 
      :default-active="item_info.default_page_id"
      :default-openeds='openeds'
    >

          <el-input 
            @keyup.enter.native="input_keyword"
            :placeholder="$t('input_keyword')"
            class="search-box"
            v-model="keyword">
           
          </el-input>

          <div class="new-bar" v-if="item_info.ItemPermn && item_info.is_archived < 1 ">
            <el-tooltip class="item" effect="dark" :content="$t('new_page')" placement="left">
                  <i class="el-icon-plus" @click="new_page"></i>
            </el-tooltip>
            <el-tooltip class="item" effect="dark" :content="$t('new_catalog')" placement="right">
                 <!--  <i class="el-icon-message" @click="mamage_catalog"></i> -->
                  <img src="static/images/folder.png" @click="mamage_catalog" class="icon-folder">
            </el-tooltip>      
          </div>

      <!-- 一级页面 -->
        <el-menu-item  v-if="menu.pages.length " v-for="(page ,index) in menu.pages" :index="page.page_id" :key="page.page_id" >
          <i class="el-icon-document"></i>
          {{page.page_title}}
        </el-menu-item>

        <!-- 目录开始 -->
      <el-submenu  v-if="menu.catalogs.length" v-for="(catalog2 ,catalog_index) in menu.catalogs" :index="catalog2.cat_id" :key="catalog2.cat_id">
        <!-- 二级目录名 -->
        <template slot="title"> <img src="static/images/folder.png"  class="icon-folder menu-icon-folder ">{{catalog2.cat_name}}</template>

        <!-- 二级目录的页面 -->
        <el-menu-item  v-if="catalog2.pages" v-for="(page2 ,page2_index) in catalog2.pages" :key="page2.page_id" :index="page2.page_id">
            <i class="el-icon-document"></i>{{page2.page_title}}
        </el-menu-item>

        <!-- 二级目录下的三级目录 -->
        <el-submenu  v-if="catalog2.catalogs.length" v-for="(catalog3 ,catalog_index3) in catalog2.catalogs" :index="catalog3.cat_id" :key="catalog3.cat_id">
          <template slot="title"><img src="static/images/folder.png">{{catalog3.cat_name}}</template>
          <!-- 三级目录的页面 -->
          <el-menu-item  v-if="catalog3.pages" v-for="(page3 ,page3_index) in catalog3.pages"  :index="page3.page_id" :key="page3.page_id"><i class="el-icon-document"></i>{{page3.page_title}}</el-menu-item>

            <!-- 三级目录下的四级目录 -->
            <el-submenu  v-if="catalog3.catalogs.length" v-for="(catalog4 ,catalog_index4) in catalog3.catalogs" :index="catalog4.cat_id" :key="catalog4.cat_id">
              <template slot="title"><img src="static/images/folder.png">{{catalog4.cat_name}}</template>
              <!-- 四级目录的页面 -->
              <el-menu-item  v-if="catalog4.pages" v-for="(page4 ,page4_index) in catalog4.pages" :index="page4.page_id"  :key="page4.page_id"><i class="el-icon-document"></i>{{page4.page_title}}</el-menu-item>
            </el-submenu>

        </el-submenu>




      </el-submenu>

    </el-menu>
  </div>
</template>


<script>
  import Editormd from '@/components/common/Editormd'
  export default {
  props:{
    get_page_content:'',
    item_info:'',
    search_item:'',
    keyword:'',
  },
    data() {
      return {
        openeds:[],
        menu:''
      }
    },
  components:{
    Editormd
  },
  methods:{
    //选中菜单的回调
    select_menu(index, indexPath){
      this.change_url(index);
      this.get_page_content(index);
    },
    new_page(){
      var url = '/page/edit/'+this.item_info.item_id+'/0';
      this.$router.push({path:url});
    },

    mamage_catalog(){
      var url = '/catalog/'+this.item_info.item_id;
      this.$router.push({path:url});
    },

    //改变url
    change_url(page_id){
        var base_url = '';
        var item_domain = '';
        var domain = this.item_info.item_domain ? this.item_info.item_domain : this.item_info.item_id ;
        this.$router.replace({
            path: '/'+domain,
            query: {page_id:page_id}
        });
    },

    input_keyword(){
      this.search_item(this.keyword);
    }

  },
  mounted () {
    var that = this ;
    this.menu = this.item_info.menu ;
    var item_info = this.item_info ;
    //默认展开页面
    if (item_info.default_page_id > 0 ) {
      that.select_menu(item_info.default_page_id);
      if (item_info.default_cat_id4) {
        that.openeds = [ item_info.default_cat_id4,item_info.default_cat_id3, item_info.default_cat_id2, item_info.default_page_id]; 
      }
      else if (item_info.default_cat_id3) {
        that.openeds = [ item_info.default_cat_id3, item_info.default_cat_id2, item_info.default_page_id]; 
      }
      else if (item_info.default_cat_id2) {
        that.openeds = [ item_info.default_cat_id2, item_info.default_page_id];
      };
    }
  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

  .el-header {
    color: #333;
    line-height: 60px;
  }
  
  .el-aside {
    color: #333;
    position:fixed;
    height: calc(100% - 20px);

  }
.el-input-group__append button.el-button{
    background-color: #ffffffa3;

  }
.new-bar{
  margin-left: 190px;
  font-size: 20px;
  margin-top: 10px;
  margin-bottom: 5px;
}
.new-bar i{
  cursor:pointer ;
}

.new-bar i:first-child{
  margin-right: 15px;
}
.el-menu{
  border-right:none;
}
.icon-folder{
  width: 18px;
  height: 15px;
  cursor: pointer;
}

.menu-icon-folder{
  margin-right: 5px;
  margin-top: -5px;
}

.el-menu-item, .el-submenu__title{
    height: 46px;
    line-height: 46px;
}
.el-submenu .el-menu-item {
    height: 40px;
    line-height: 40px;
}
.el-menu-item {
  line-height: 40px;
  height: 40px;
}
.el-menu-item [class^=el-icon-] {
  font-size: 17px;
  margin-bottom: 4px;
}
.el-submenu__title img {
  width: 14px;
  cursor: pointer;
  margin-left: 5px;
  margin-right: 10px;
  margin-bottom: 4px;
}
.search-box {
    padding: 20px 20px 0px 20px;
    box-sizing: border-box;
}
</style>
