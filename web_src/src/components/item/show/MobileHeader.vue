<template>
  <div :class="hideElement ? 'hideElement' : 'showElement'">
    <!-- pos 是占位的，便于把页面内容压下去 -->
    <div class="pos"></div>
    <div class="mobile-header">
      <div class="header-wrap">
        <div class="logo">
          <div class="logo-content">
            <img class="logo-img" src="@/assets/Logo.svg" alt="logo" />
            <span class="font-bold">{{ item_info.item_name }}</span>
          </div>
        </div>
        <div class="cat-btn-div" @click="drawer = true">
          <div class="cat-btn">
            <i class="fa-solid fa-folder-tree"></i> {{ $t('catalog') }}
          </div>
        </div>
      </div>
    </div>

    <el-drawer
      :title="$t('catalog')"
      :modal="false"
      :visible.sync="drawer"
      direction="rtl"
      size="80%"
    >
      <div class="tree-div hide-scrollbar">
        <el-tree
          ref="tree"
          :data="menu"
          v-if="menu && menu.length > 0"
          :props="defaultProps"
          :default-expanded-keys="openeds"
          @node-click="handleNodeClick"
          node-key="id"
        >
          <span class="custom-tree-node" slot-scope="{ node, data }">
            <span>
              <span
                v-if="node.data.type === 'folder'"
                class="custom-tree-node node-folder"
                :id="'node-' + node.data.id"
              >
                <i class="mr-2 far fa-folder-closed"></i>
                <span class="node-label">{{ node.label }}</span>
              </span>
              <span
                v-else
                class="custom-tree-node node-page"
                :id="'node-' + node.data.id"
              >
                <i class="mr-2 fas fa-file-alt"></i>
                <span class="node-label">{{ node.label }}</span>
              </span>
            </span>
          </span>
        </el-tree>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import { itemMenuDataToTreeData, getParentIds } from '@/models/itemTree'
export default {
  props: {
    item_info: {
      type: Object,
      required: true
    },
    searchItem: {
      type: Function,
      required: true
    },
    getPageContent: {
      type: Function,
      required: true
    }
  },
  data() {
    return {
      drawer: false,
      menu: [],
      defaultProps: {
        children: 'children',
        label: 'title'
      },
      openeds: [],
      hideElement: false,
      lastScrollTop: 0
    }
  },
  methods: {
    handleNodeClick(data) {
      if (data.page_id) {
        this.selectMenu(data.page_id)
        this.drawer = false
      }
    },
    // 选中菜单的回调
    selectMenu(page_id) {
      this.changeUrl(page_id)
      this.getPageContent(page_id)
      this.$refs.tree.setCurrentKey(parseInt(page_id))
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
    handleScroll() {
      // 获取滚动的位置
      const scrollTop =
        event.target.scrollTop ||
        window.pageYOffset ||
        document.documentElement.scrollTop
      // 判断滚动的方向
      if (scrollTop < 100 || scrollTop < this.lastScrollTop) {
        // 向上滚动时
        this.hideElement = false
      } else {
        // 向下滚动时
        this.hideElement = true
      }

      this.lastScrollTop = scrollTop
    }
  },
  mounted() {
    this.menu = itemMenuDataToTreeData(this.item_info.menu)
    window.addEventListener('scroll', this.handleScroll)

    // 默认展开页面
    const page_id = this.item_info.default_page_id
      ? this.item_info.default_page_id
      : 0
    if (page_id) {
      const openeds = getParentIds(this.menu, page_id)
      if (openeds) {
        this.openeds = openeds
        setTimeout(() => {
          this.selectMenu(page_id)
        }, 1000)
      }
    }
  },
  beforeDestroy() {
    // 离开该页面需要移除这个监听的事件
    window.removeEventListener('scroll', this.handleScroll)
  }
}
</script>

<style scoped>
.hideElement {
  opacity: 0;
  transition: opacity 0.1s ease-in-out;
}
.showElement {
  opacity: 1;
  transition: opacity 0.1s ease-in-out;
}

.pos {
  height: 60px;
  width: 100%;
}
.mobile-header {
  height: 60px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 999;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f9f9f9;
}

.header-wrap {
  height: 40px;
  line-height: 40px;
  width: 100%;
}

.logo {
  margin-left: 20px;
  float: left;
  align-items: center;
}

.logo-content {
  display: flex;
  align-items: center;
}

.logo-img {
  width: 40px;
  height: 40px;
  margin-right: 5px;
}

.cat-btn-div {
  margin-right: 20px;
  float: right;
  background: #ffffff;
  border-radius: 8px;
  cursor: pointer;
  padding-left: 15px;
  padding-right: 15px;
  margin-right: 25px;
  color: #343a40;
  font-weight: 700;
  cursor: pointer;
}

.tree-div {
  background: #f9f9f9;
  height: calc(100% - 10px);
  margin-left: 10px;
  margin-right: 10px;
}

.tree-div >>> .el-tree-node {
  background: #f9f9f9;
}
.tree-div >>> .el-tree-node__content {
  min-height: 40px;
  background: #f9f9f9;
  border-radius: 6px;
}
.tree-div >>> .el-tree-node__content:hover,
.tree-div >>> .is-current .el-tree-node__content {
  background-color: #ffffff;
  border-radius: 6px;
  /* margin-top: 2px; */
}
.tree-div >>> .is-current > .el-tree-node__content .node-page .node-label {
  color: #409eff;
  font-weight: 700;
}
</style>
