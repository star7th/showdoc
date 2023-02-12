<template>
  <div :class="hideScrollbar ? 'hide-scrollbar' : 'normal-scrollbar'">
    <i
      class="el-icon-menu header-left-btn"
      v-if="show_menu_btn"
      id="header-left-btn"
      @click="showMenu"
    ></i>
    <el-aside
      :class="menuMarginLeft"
      id="left-side-menu"
      @mouseenter.native="hideScrollbar = false"
      @mouseleave.native="hideScrollbar = true"
    >
      <el-menu
        @select="selectMenu"
        background-color="#fafafa"
        text-color
        active-text-color="#008cff"
        :default-active="item_info.default_page_id"
        :default-openeds="openeds"
      >
        <el-input
          @keyup.enter.native="inputKeyword"
          :placeholder="$t('input_keyword')"
          class="search-box"
          :clearable="true"
          @clear="searchItem()"
          size="small"
          v-model="keyword"
          suffix-icon="el-icon-search"
        >
        </el-input>

        <!-- 一级页面 -->
        <template v-if="menu.pages && menu.pages.length">
          <el-menu-item
            v-for="page in menu.pages"
            :index="page.page_id"
            :key="page.page_id"
          >
            <i class="el-icon-document"></i>
            <a
              :href="randerUrl(page.page_id)"
              @click.prevent="() => {}"
              :title="page.page_title"
              :id="'left_page_' + page.page_id"
              >{{ page.page_title }}</a
            >
          </el-menu-item>
        </template>

        <!-- 目录开始 -->
        <LeftMenuSub
          v-if="menu.catalogs && menu.catalogs.length"
          :catalog="menu.catalogs"
          :item_info="item_info"
        ></LeftMenuSub>
      </el-menu>
    </el-aside>
  </div>
</template>

<script>
import LeftMenuSub from './LeftMenuSub.vue'
export default {
  props: {
    getPageContent: '',
    item_info: '',
    searchItem: () => {},
    keyword: ''
  },
  data() {
    return {
      openeds: [],
      menu: '',
      show_menu_btn: false,
      hideScrollbar: true,
      menuMarginLeft: 'menu-margin-left1',
      expandCollapseCatalogStatus: '0' // 目录状态。0：无设置；1：展开全部；2：折叠全部
    }
  },
  components: {
    LeftMenuSub
  },
  methods: {
    // 选中菜单的回调
    selectMenu(index, indexPath) {
      this.changeUrl(index)
      this.getPageContent(index)
    },
    // 改变url.
    changeUrl(page_id) {
      if (
        page_id > 0 &&
        (page_id == this.$route.query.page_id ||
          page_id == this.$route.params.page_id)
      ) {
        return
      }
      var domain = this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
      this.$router.replace({
        path: '/' + domain + '/' + page_id
      })
    },

    inputKeyword() {
      this.searchItem(this.keyword)
    },
    showMenu() {
      this.show_menu_btn = false
      var element = document.getElementById('left-side-menu')
      element.style.display = 'block'
      element.style.marginLeft = '0px'
      element.style.marginTop = '0px'
      element.style.position = 'static'
      element = document.getElementById('p-content')
      element.style.display = 'none'
    },
    hideMenu() {
      this.show_menu_btn = true
      var element = document.getElementById('left-side-menu')
      element.style.display = 'none'
      element = document.getElementById('p-content')
      element.style.marginLeft = '0px'
      element.style.display = 'block'
      element = document.getElementById('page_md_content')
      element.style.width = '95%'
    },
    randerUrl(page_id) {
      var domain = this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
      return '/' + domain + '/' + page_id
    },
    expandCollapseCatalog() {
      if (
        this.expandCollapseCatalogStatus === '0' ||
        this.expandCollapseCatalogStatus === '2'
      ) {
        this.expandCollapseCatalogStatus = '1'
        const openeds = [] // 先定义一个空数组
        // 递归遍历获取目录id并全部展开
        // 先定义一个遍历函数，以便后续用名字来递归
        const addCatIdToOpens = catalogs => {
          console.log(catalogs)
          if (catalogs && catalogs.length > 0) {
            catalogs.forEach(element => {
              openeds.push(element.cat_id)
              if (element.catalogs && element.catalogs.length > 0) {
                addCatIdToOpens(element.catalogs)
              }
            })
          }
        }
        addCatIdToOpens(this.item_info.menu.catalogs)
        this.openeds = openeds
      } else if (this.expandCollapseCatalogStatus === '1') {
        this.expandCollapseCatalogStatus = '2'
        this.openeds = []
      }
    }
  },
  mounted() {
    this.menu = this.item_info.menu
    var item_info = this.item_info
    // 默认展开页面
    if (item_info.default_page_id > 0) {
      this.selectMenu(item_info.default_page_id)
      if (item_info.default_cat_id4) {
        this.openeds = [
          item_info.default_cat_id4,
          item_info.default_cat_id3,
          item_info.default_cat_id2,
          item_info.default_page_id
        ]
      } else if (item_info.default_cat_id3) {
        this.openeds = [
          item_info.default_cat_id3,
          item_info.default_cat_id2,
          item_info.default_page_id
        ]
      } else if (item_info.default_cat_id2) {
        this.openeds = [item_info.default_cat_id2, item_info.default_page_id]
      }
      // 延迟把左侧栏滚动到默认展开的那个页面
      setTimeout(() => {
        const element = document.querySelector(
          '#left_page_' + item_info.default_page_id
        )
        element.scrollIntoView()
      }, 1000)
    }
  }
}
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
  height: calc(100% - 150px);
}
.menu-margin-left1 {
  margin-left: 0px;
}
.menu-margin-left2 {
  margin-left: 0px;
}

.el-input-group__append button.el-button {
  background-color: #ffffffa3;
}

.el-menu {
  border-right: none;
}

.icon-folder {
  width: 18px;
  height: 15px;
  cursor: pointer;
}

.menu-icon-folder {
  margin-right: 5px;
  margin-top: -5px;
}

.el-menu-item,
.el-submenu__title {
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
.el-menu-item [class^='el-icon-'] {
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
  padding: 0px 0px 0px 20px;
  box-sizing: border-box;
  width: 95%;
}

/*隐藏滚动条*/
.hide-scrollbar ::-webkit-scrollbar {
  display: none;
}
/*隐藏滚动条*/
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.header-left-btn {
  font-size: 20px;
  margin-top: 5px;
  cursor: pointer;
  position: fixed;
  top: 10px;
  left: 15px;
}
.el-menu-item:not(.is-active) a {
  color: #303133;
}
</style>
<style type="text/css">
#left-side-menu .el-input__inner {
  background-color: #fafafa !important;
  padding-right: 10px;
}

.hide-scrollbar .el-submenu__title {
  font-size: 12px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.hide-scrollbar li {
  /*white-space: normal;*/
  overflow: hidden;
  text-overflow: ellipsis;
}

.normal-scrollbar .el-submenu__title {
  font-size: 12px;
}
.normal-scrollbar li {
  font-size: 12px;
}
</style>
