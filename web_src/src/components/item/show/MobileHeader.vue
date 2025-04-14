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
        <!-- 搜索框放在目录树上方 -->
        <div class="search-box-container">
          <div class="search-input-wrapper">
            <el-input
              @keyup.enter.native="searchLocalTree"
              :placeholder="$t('input_keyword')"
              class="search-box"
              :clearable="true"
              @clear="resetTree"
              size="small"
              v-model="searchKeyword"
            >
            </el-input>
            <div class="search-icon-btn" @click="searchLocalTree">
              <i class="el-icon-search"></i><span class="btn-text">{{ $t('search') }}</span>
            </div>
          </div>
        </div>

        <div v-if="isSearching" class="search-loading">
          <i class="el-icon-loading"></i> {{ $t('searching') }}...
        </div>

        <el-tree
          ref="tree"
          :data="currentMenu"
          v-if="currentMenu && currentMenu.length > 0"
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

        <div v-if="noSearchResults" class="no-results">
          {{ $t('no_results_found') }}
        </div>
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
      originalMenu: [], // 保存原始目录树
      currentMenu: [], // 当前显示的目录树（可能是搜索结果）
      defaultProps: {
        children: 'children',
        label: 'title'
      },
      openeds: [],
      hideElement: false,
      lastScrollTop: 0,
      searchKeyword: '',
      isSearching: false,
      noSearchResults: false
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
    },
    // 局部搜索目录树
    searchLocalTree() {
      if (!this.searchKeyword.trim()) {
        this.resetTree()
        return
      }

      this.isSearching = true
      this.noSearchResults = false

      const item_id = this.item_info.item_id

      // 使用与主搜索相同的接口获取搜索结果
      this.request(
        '/api/item/info',
        {
          item_id: item_id,
          keyword: this.searchKeyword
        },
        'post',
        false
      )
        .then(data => {
          this.isSearching = false

          if (data.error_code === 0) {
            const filteredMenu = itemMenuDataToTreeData(data.data.menu)
            this.currentMenu = filteredMenu

            // 展开所有搜索结果
            this.openeds = this.getAllNodeIds(filteredMenu)

            // 检查是否有搜索结果
            this.noSearchResults = !filteredMenu.length
          } else {
            this.$message.error(data.error_message || '搜索失败')
            this.noSearchResults = true
          }
        })
        .catch(err => {
          this.isSearching = false
          this.noSearchResults = true
          console.error('搜索出错:', err)
        })
    },
    // 重置目录树到原始状态
    resetTree() {
      this.searchKeyword = ''
      this.currentMenu = this.originalMenu
      this.noSearchResults = false

      // 重置展开状态
      const page_id = this.item_info.default_page_id
        ? this.item_info.default_page_id
        : 0
      if (page_id) {
        const openeds = getParentIds(this.originalMenu, page_id)
        if (openeds) {
          this.openeds = openeds
        }
      }
    },
    // 获取所有节点ID以便全部展开
    getAllNodeIds(nodes) {
      const ids = []
      const traverse = node => {
        if (node.id) ids.push(node.id)
        if (node.children && node.children.length) {
          node.children.forEach(child => traverse(child))
        }
      }

      if (Array.isArray(nodes)) {
        nodes.forEach(node => traverse(node))
      } else if (nodes) {
        traverse(nodes)
      }

      return ids
    }
  },
  mounted() {
    const menuData = itemMenuDataToTreeData(this.item_info.menu)
    this.menu = menuData
    this.originalMenu = menuData // 保存原始目录
    this.currentMenu = menuData // 设置当前目录

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
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
}

.logo {
  margin-left: 20px;
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
  background: #ffffff;
  border-radius: 8px;
  cursor: pointer;
  padding-left: 15px;
  padding-right: 15px;
  color: #343a40;
  font-weight: 700;
}

.search-box-container {
  padding: 15px 15px;
}

.search-input-wrapper {
  display: flex;
  align-items: center;
}

.search-box {
  flex: 1;
}

.search-icon-btn {
  margin-left: 5px;
  height: 32px;
  padding: 0 10px;
  background-color: #ffffff;
  color: #343a40;
  font-weight: 700;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.search-icon-btn .btn-text {
  margin-left: 5px;
}

.search-loading {
  text-align: center;
  padding: 10px 0;
  color: #909399;
}

.no-results {
  text-align: center;
  padding: 20px 0;
  color: #909399;
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
</style>
