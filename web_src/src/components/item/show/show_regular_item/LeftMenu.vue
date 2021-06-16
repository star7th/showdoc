<template>
  <div :class="hideScrollbar ? 'hide-scrollbar' : 'normal-scrollbar'">
    <i
      class="el-icon-menu header-left-btn"
      v-if="show_menu_btn"
      id="header-left-btn"
      @click="show_menu"
    ></i>
    <i
      class="el-icon-menu header-left-btn"
      v-if="show_menu_btn"
      id="header-left-btn"
      @click="show_menu"
    ></i>
    <el-aside
      :class="menuMarginLeft"
      id="left-side-menu"
      :width="asideWidth"
      @mouseenter.native="hideScrollbar = false"
      @mouseleave.native="hideScrollbar = true"
    >
      <el-menu
        @select="select_menu"
        background-color="#fafafa"
        text-color
        active-text-color="#008cff"
        :default-active="item_info.default_page_id"
        :default-openeds="openeds"
      >
        <el-input
          @keyup.enter.native="input_keyword"
          :placeholder="$t('input_keyword')"
          class="search-box"
          :clearable="true"
          @clear="search_item()"
          size="medium"
          v-model="keyword"
        ></el-input>

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
    get_page_content: '',
    item_info: '',
    search_item: '',
    keyword: ''
  },
  data() {
    return {
      openeds: [],
      menu: '',
      show_menu_btn: false,
      hideScrollbar: true,
      asideWidth: '250px',
      menuMarginLeft: 'menu-margin-left1'
    }
  },
  components: {
    LeftMenuSub
  },
  methods: {
    // 选中菜单的回调
    select_menu(index, indexPath) {
      this.change_url(index)
      this.get_page_content(index)
    },
    new_page() {
      var url = '/page/edit/' + this.item_info.item_id + '/0'
      this.$router.push({ path: url })
    },

    mamage_catalog() {
      var url = '/catalog/' + this.item_info.item_id
      this.$router.push({ path: url })
    },

    // 改变url
    change_url(page_id) {
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

    input_keyword() {
      this.search_item(this.keyword)
    },
    show_menu() {
      this.show_menu_btn = false
      var element = document.getElementById('left-side-menu')
      element.style.display = 'block'
      element.style.marginLeft = '0px'
      element.style.marginTop = '0px'
      element.style.position = 'static'
      element = document.getElementById('p-content')
      element.style.display = 'none'
    },
    hide_menu() {
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
      return '#/' + domain + '/' + page_id
    }
  },
  mounted() {
    var that = this
    this.menu = this.item_info.menu
    var item_info = this.item_info
    // 默认展开页面
    if (item_info.default_page_id > 0) {
      that.select_menu(item_info.default_page_id)
      if (item_info.default_cat_id4) {
        that.openeds = [
          item_info.default_cat_id4,
          item_info.default_cat_id3,
          item_info.default_cat_id2,
          item_info.default_page_id
        ]
      } else if (item_info.default_cat_id3) {
        that.openeds = [
          item_info.default_cat_id3,
          item_info.default_cat_id2,
          item_info.default_page_id
        ]
      } else if (item_info.default_cat_id2) {
        that.openeds = [item_info.default_cat_id2, item_info.default_page_id]
      }
      // 延迟把左侧栏滚动到默认展开的那个页面
      setTimeout(() => {
        const element = document.querySelector(
          '#left_page_' + item_info.default_page_id
        )
        element.scrollIntoView()
      }, 1000)
    }

    // 如果是大屏幕且存在目录，则把侧边栏调大
    if (
      window.screen.width >= 1600 &&
      this.menu.catalogs &&
      this.menu.catalogs.length > 0
    ) {
      this.asideWidth = '300px'
      this.menuMarginLeft = 'menu-margin-left2'
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
  margin-top: -20px;
  height: calc(100% - 90px);
}
.menu-margin-left1 {
  margin-left: -273px;
}
.menu-margin-left2 {
  margin-left: -323px;
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
  padding: 0px 20px 0px 20px;
  box-sizing: border-box;
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
  /* white-space: normal;*/
  overflow: hidden;
  text-overflow: ellipsis;
}

.normal-scrollbar .el-submenu__title {
  font-size: 12px;
}
.normal-scrollbar li {
  font-size: 12px;
}

#left-side-menu .el-input__suffix {
  right: 25px;
  padding-right: 10px;
}
</style>
