<template>
  <div :class=" hideScrollbar ? 'hide-scrollbar' : 'normal-scrollbar' ">
    <i class="el-icon-menu header-left-btn" v-if="show_menu_btn" id="header-left-btn" @click="show_menu"></i>
    <i class="el-icon-menu header-left-btn" v-if="show_menu_btn" id="header-left-btn" @click="show_menu"></i>
    <el-aside  :class="menuMarginLeft"  id="left-side-menu" :width="asideWidth"
     @mouseenter.native="hideScrollbar = false" @mouseleave.native="hideScrollbar = true" 
     >
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
              :clearable="true"
              @clear="search_item()"
              v-model="keyword">
             
            </el-input>


        <!-- 一级页面 -->
          <el-menu-item  v-if="menu.pages.length " v-for="(page ,index) in menu.pages" :index="page.page_id" :key="page.page_id" >
            <i class="el-icon-document"></i>
             <span :title="page.page_title">{{page.page_title}}</span> 
          </el-menu-item>

          <!-- 目录开始 -->
        <el-submenu  v-if="menu.catalogs.length" v-for="(catalog2 ,catalog_index) in menu.catalogs" :index="catalog2.cat_id" :key="catalog2.cat_id">
          <!-- 二级目录名 -->
          <template slot="title"> <img src="static/images/folder.png"  class="icon-folder menu-icon-folder ">{{catalog2.cat_name}}</template>

          <!-- 二级目录的页面 -->
          <el-menu-item  v-if="catalog2.pages" v-for="(page2 ,page2_index) in catalog2.pages" :key="page2.page_id" :index="page2.page_id">
              <i class="el-icon-document"></i><span :title="page2.page_title">{{page2.page_title}}</span> 
          </el-menu-item>

          <!-- 二级目录下的三级目录 -->
          <el-submenu  v-if="catalog2.catalogs.length" v-for="(catalog3 ,catalog_index3) in catalog2.catalogs" :index="catalog3.cat_id" :key="catalog3.cat_id">
            <template slot="title"><img src="static/images/folder.png">{{catalog3.cat_name}}</template>
            <!-- 三级目录的页面 -->
            <el-menu-item  v-if="catalog3.pages" v-for="(page3 ,page3_index) in catalog3.pages"  :index="page3.page_id" :key="page3.page_id"><i class="el-icon-document"></i><span :title="page3.page_title">{{page3.page_title}}</span> </el-menu-item>

              <!-- 三级目录下的四级目录 -->
              <el-submenu  v-if="catalog3.catalogs.length" v-for="(catalog4 ,catalog_index4) in catalog3.catalogs" :index="catalog4.cat_id" :key="catalog4.cat_id">
                <template slot="title"><img src="static/images/folder.png">{{catalog4.cat_name}}</template>
                <!-- 四级目录的页面 -->
                <el-menu-item  v-if="catalog4.pages" v-for="(page4 ,page4_index) in catalog4.pages"  :index="page4.page_id" :key="page4.page_id"><span :title="page4.page_title">{{page4.page_title}}</span></el-menu-item>
              </el-submenu>

          </el-submenu>

          
        </el-submenu>

      </el-menu>
    </el-aside>
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
        menu:'',
          show_menu_btn:false,
          hideScrollbar:true,
          asideWidth:"250px",
          menuMarginLeft:"menu-margin-left1"
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
    },
    show_menu(){
        this.show_menu_btn = false;
        var element = document.getElementById('left-side-menu') ;
        element.style.display = 'block' ;
        element.style.marginLeft = '0px'; 
        element.style.marginTop = '0px'; 
        element.style.position = 'static'; 
        var element = document.getElementById('right-side') ;
        element.style.display = 'none'; 
    },
    hide_menu(){
        this.show_menu_btn = true;
        var element = document.getElementById('left-side-menu') ;
        element.style.display = 'none';
        var element = document.getElementById('right-side') ;
        element.style.marginLeft = '0px';
        element.style.display = 'block'; 
        var element = document.getElementById('page_md_content') ;
        element.style.width = '95%' ; 
    },


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

    //如果是大屏幕且存在目录，则把侧边栏调大
    if ( window.screen.width >= 1600  && this.menu.catalogs && this.menu.catalogs.length > 0 ) {
        this.asideWidth = "300px";
        this.menuMarginLeft = 'menu-margin-left2';
    };


  }
};
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

  .el-header {
    color: #333;
    line-height: 60px;
  }
  
  #left-side-menu {
    color: #333;
    position: fixed;
    margin-top: -20px;
    height: calc(100% - 90px);

  }
  .menu-margin-left1{
    margin-left: -273px;
  }
  .menu-margin-left2{
    margin-left: -323px;
  }

.el-input-group__append button.el-button{
    background-color: #ffffffa3;

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
  font-size: 12px;
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
    padding: 0px 20px 0px 20px;
    box-sizing: border-box;

}

/*隐藏滚动条*/
.hide-scrollbar ::-webkit-scrollbar
{
   display: none;
}
/*隐藏滚动条*/
.hide-scrollbar
{ 
    -ms-overflow-style: none;
    scrollbar-width: none;
}


.header-left-btn{
  font-size: 20px;
  margin-top: 5px;
  cursor: pointer;
  position: fixed;
}



</style>
<style type="text/css">
  #left-side-menu .el-input__inner{
      background-color: #fafafa !important;
      padding-right:10px;
  }

  .hide-scrollbar .el-submenu__title{
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .hide-scrollbar li{
    white-space: normal;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .normal-scrollbar .el-submenu__title{
    font-size: 12px;
  }
  .normal-scrollbar li{
    font-size: 12px;
  }
  
  #left-side-menu .el-input__suffix{
    right: 25px;
    padding-right:10px;
  }

</style>
