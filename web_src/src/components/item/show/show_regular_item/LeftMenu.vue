<template>
  <div class="hide-scrollbar">
    <div id="left-side-menu">
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
      <el-tree
        ref="tree"
        :data="menu"
        v-if="menu && menu.length > 0"
        :props="defaultProps"
        @node-click="handleNodeClick"
        node-key="id"
        :default-expanded-keys="openeds"
        :draggable="item_info.item_edit ? true : false"
        :allow-drop="allowDrop"
        @node-drag-end="handleDragEnd"
        :auto-expand-parent="false"
      >
        <span
          class="custom-tree-node"
          @contextmenu.prevent="
            e => {
              if (item_info.item_edit) {
                showContextMenu(e, node.data)
              }
            }
          "
          slot-scope="{ node, data }"
        >
          <span
            v-if="node.data.type === 'folder'"
            class="custom-tree-node node-folder"
            :id="'node-' + node.data.id"
          >
            <i
              v-if="openeds.includes(node.data.id) && node.data.children.length"
              class="mr-2 far fa-folder-open"
            ></i>
            <i v-else class="mr-2 far fa-folder-closed"></i>
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

          <span v-if="item_info.item_edit" class="node-tool">
            <span
              class=""
              @click.stop.prevent="showContextMenu($event, node.data)"
            >
              <i class="mr-3 fas fa-ellipsis"></i>
            </span>
          </span>
        </span>
      </el-tree>

      <!-- 新建/编辑/复制页面 -->
      <PageEdit
        v-if="showPageEdit"
        :edit_page_id="editPageId"
        :item_id="item_info.item_id"
        :copy_page_id="copyPageId"
        :callback="
          () => {
            showPageEdit = false
            $store.dispatch('reloadItem')
          }
        "
      ></PageEdit>

      <!-- 目录管理 -->
      <Catalog
        v-if="showCatalog"
        :item_id="item_id"
        :callback="
          () => {
            showCatalog = false
            $store.dispatch('reloadItem')
          }
        "
      ></Catalog>

      <!-- 历史版本 -->
      <HistoryVersion
        :page_id="editPageId"
        :is_show_recover_btn="false"
        :is_modal="false"
        v-if="showHistoryVersiong"
        :callback="
          data => {
            this.showHistoryVersiong = false
          }
        "
        :cancel="
          data => {
            this.showHistoryVersiong = false
          }
        "
      ></HistoryVersion>

      <CopyCatalog
        v-if="showCopyCatalog"
        :item_id="item_info.item_id"
        :cat_id="editCatId"
        :callback="
          () => {
            $store.dispatch('reloadItem')
          }
        "
      ></CopyCatalog>
    </div>
  </div>
</template>

<script>
import ContextmenuModal from '@/components/common/ContextmenuModal/index.js'
import PageEdit from '@/components/page/edit/Index'
import Catalog from '@/components/catalog/Index'
import HistoryVersion from '@/components/page/edit/HistoryVersion'
import CopyCatalog from '@/components/catalog/Copy'
import { itemMenuDataToTreeData, getParentIds } from '@/models/itemTree'

export default {
  props: {
    getPageContent: '',
    item_info: '',
    searchItem: () => {},
    keyword: ''
  },
  components: { PageEdit, Catalog, HistoryVersion, CopyCatalog },
  data() {
    return {
      openeds: [],
      menu: '',
      show_menu_btn: false,
      hideScrollbar: true,
      menuMarginLeft: 'menu-margin-left1',
      expandCollapseCatalogStatus: '0', // 目录状态。0：无设置；1：展开全部；2：折叠全部
      defaultProps: {
        children: 'children',
        label: 'title'
      },
      copyPageId: 0,
      editPageId: 0,
      showPageEdit: false,
      showCatalog: false,
      showRecycle: false,
      showHistoryVersiong: false,
      showCopyCatalog: false,
      editCatId: 0
    }
  },
  methods: {
    showContextMenu(e, data) {
      ContextmenuModal({
        x: e.x,
        y: e.y,
        list:
          data.type === 'folder'
            ? this.catContextmenu(data)
            : this.pageContextmenu(data)
      })
    },
    pageContextmenu(nodeData) {
      return [
        {
          icon: 'fas fa-edit',
          text: this.$t('edit_page'),
          onclick: () => {
            this.editPageId = nodeData.page_id
            this.showPageEdit = true
          }
        },
        {
          icon: 'fas fa-copy',
          text: this.$t('copy_page'),
          onclick: () => {
            this.editPageId = 0
            this.copyPageId = nodeData.page_id
            this.showPageEdit = true
          }
        },
        {
          icon: 'fas fa-circle-info',
          text: this.$t('page_info'),
          onclick: () => {
            this.request('/api/page/info', {
              page_id: nodeData.page_id
            }).then(pageData => {
              const html =
                '本页面由 ' +
                pageData.data.author_username +
                ' 于 ' +
                pageData.data.addtime +
                ' 更新'
              this.$alert(html)
            })
          }
        },
        {
          icon: 'fas fa-rectangle-history',
          text: this.$t('page_history_version'),
          onclick: () => {
            this.editPageId = nodeData.page_id
            this.showHistoryVersiong = true
          }
        },
        {
          icon: 'fas fa-trash-can',
          text: this.$t('delete_page'),
          onclick: () => {
            const page_id = nodeData.page_id
            this.$confirm(this.$t('comfirm_delete'), ' ', {
              confirmButtonText: this.$t('confirm'),
              cancelButtonText: this.$t('cancel'),
              type: 'warning'
            }).then(() => {
              this.request('/api/page/delete', {
                page_id: page_id
              }).then(data => {
                this.$store.dispatch('reloadItem')
              })
            })
          }
        }
      ]
    },
    catContextmenu(nodeData) {
      return [
        {
          icon: 'fas fa-plus',
          text: this.$t('add_sub_page'),
          onclick: () => {
            this.$store.dispatch('changeOpenCatId', nodeData.cat_id)
            this.editPageId = 0
            this.showPageEdit = true
          }
        },
        {
          icon: 'fas fa-folder-tree',
          text: this.$t('add_sub_cat'),
          onclick: () => {
            this.$prompt('', '').then(data => {
              this.request('/api/catalog/save', {
                item_id: this.item_info.item_id,
                cat_id: 0,
                parent_cat_id: nodeData.cat_id,
                cat_name: data.value
              }).then(data => {
                this.$store.dispatch('reloadItem')
              })
            })
          }
        },
        {
          icon: 'fas fa-folder-plus',
          text: this.$t('add_si_bling_cat'),
          onclick: () => {
            this.$prompt('', '').then(data => {
              this.request('/api/catalog/save', {
                item_id: this.item_info.item_id,
                cat_id: 0,
                parent_cat_id: nodeData.parent_cat_id,
                cat_name: data.value
              }).then(data => {
                this.$store.dispatch('reloadItem')
              })
            })
          }
        },
        {
          icon: 'fas fa-edit',
          text: this.$t('edt_cat'),
          onclick: () => {
            this.$prompt('', '', { inputValue: nodeData.title }).then(data => {
              this.request('/api/catalog/save', {
                item_id: this.item_info.item_id,
                cat_id: nodeData.cat_id,
                parent_cat_id: nodeData.parent_cat_id,
                cat_name: data.value
              }).then(data => {
                this.$store.dispatch('reloadItem')
              })
            })
          }
        },
        {
          icon: 'fas fa-clone',
          text: this.$t('clone_move'),
          onclick: () => {
            this.editCatId = nodeData.cat_id
            this.showCopyCatalog = true
          }
        },
        {
          icon: 'fas fa-trash-can',
          text: this.$t('delete'),
          onclick: () => {
            const cat_id = nodeData.cat_id
            this.$confirm(this.$t('confirm_cat_delete'), ' ', {
              confirmButtonText: this.$t('confirm'),
              cancelButtonText: this.$t('cancel'),
              type: 'warning'
            }).then(() => {
              this.request('/api/catalog/delete', {
                item_id: this.item_info.item_id,
                cat_id: cat_id
              }).then(data => {
                this.$store.dispatch('reloadItem')
              })
            })
          }
        }
      ]
    },
    // 根据page_id ，获取树状数据的目录id们

    // 判断节点是否可以被拖曳。比如说，就不会拖曳进 页面 节点里
    allowDrop(draggingNode, dropNode, type) {
      if (type == 'inner' && dropNode.data.page_id > 0) {
        // 不可以拖动到页面节点内部
        return false
      }
      return true
    },
    handleDragEnd() {
      const treeData = this.menu
      // 将拖动的顺序和层级信息保存到后台

      // 如果是搜索结果，则不保存目录层级关系到后台
      if (this.keyword) {
        return false
      }

      // 先定义一个函数，将目录数组降维
      const dimensionReduction = treeData => {
        const treeData2 = []

        const pushTreeData = (OneData, parent_cat_id, level, i) => {
          treeData2.push({
            cat_id: OneData.cat_id || 0,
            cat_name: OneData.title || '',
            page_id: OneData.page_id || 0,
            parent_cat_id: parent_cat_id || 0,
            page_cat_id: parent_cat_id || 0,
            level,
            s_number: i + 1
          })
          if (OneData.hasOwnProperty('children')) {
            for (let j = 0; j < OneData.children.length; j++) {
              pushTreeData(OneData.children[j], OneData.cat_id, level + 1, j)
            }
          }
        }

        for (let i = 0; i < treeData.length; i++) {
          pushTreeData(treeData[i], 0, 2, i)
        }
        return treeData2
      }
      // 开始执行这个函数
      const tdata = dimensionReduction(treeData)
      this.request('/api/catalog/batUpdate', {
        item_id: this.item_info.item_id,
        cats: JSON.stringify(tdata)
      })
    },
    handleNodeClick(data) {
      if (data.page_id) {
        this.selectMenu(data.page_id)
      }
      if (data.type == 'folder') {
        // 如果点击的是目录，则展开目录
        const list = this.openeds
        const findIndex = list.findIndex(v => v === data.id)
        if (findIndex === -1) {
          list.push(data.id)
        } else {
          list.splice(findIndex, 1)
        }
        this.openeds = list
      }
    },
    // 选中菜单的回调
    selectMenu(page_id) {
      this.changeUrl(page_id)
      this.getPageContent(page_id)
      this.$refs.tree.setCurrentKey(`page_${page_id}`)
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
    }
  },
  mounted() {
    this.menu = itemMenuDataToTreeData(this.item_info.menu)
    // 默认展开页面
    const page_id = this.item_info.default_page_id
      ? this.item_info.default_page_id
      : 0
    if (page_id) {
      const openeds = getParentIds(this.menu, page_id)
      if (openeds) {
        this.openeds = openeds
        // 延迟把左侧栏滚动到默认展开的那个页面，同时设置选中当前页面
        setTimeout(() => {
          const element = document.querySelector('#node-page_' + page_id)
          element.scrollIntoView()
          this.selectMenu(page_id)
        }, 1000)
      }
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
#left-side-menu {
  color: #333;
  position: fixed;
  height: calc(100% - 150px);
  overflow: scroll;
  margin-right: 10px;
  width: 300px;
  background: #f9f9f9;
}

/*隐藏滚动条*/
.hide-scrollbar ::-webkit-scrollbar {
  display: none;
}
/*隐藏滚动条*/
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
  overflow: hidden;
}

.search-box {
  box-sizing: border-box;
  width: calc(100% - 10px);
  margin-bottom: 5px;
  border-color: #0000001a;
  margin-left: 10px;
  margin-right: 10px;
}

.search-box >>> input {
  border-radius: 6px;
  height: 40px;
  border-color: #0000001a;
}

#left-side-menu >>> .el-tree {
  margin-left: 10px;
}

#left-side-menu >>> .el-tree-node {
  background: #f9f9f9;
}

#left-side-menu >>> .el-tree-node__content {
  min-height: 40px;
  background: #f9f9f9;
  border-radius: 6px;
}

#left-side-menu >>> .el-tree-node__content:hover,
#left-side-menu >>> .is-current .el-tree-node__content {
  background-color: #ffffff;
  border-radius: 6px;
  /* margin-top: 2px; */
}

#left-side-menu
  >>> .is-current
  > .el-tree-node__content
  .node-page
  .node-label {
  color: #409eff;
  font-weight: 700;
}

#left-side-menu >>> .custom-tree-node {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  width: 100%;
}

#left-side-menu >>> .node-tool {
  position: absolute;
  right: 0;
  opacity: 0;
  transition: opacity 10ms 10ms ease-in-out;
  cursor: pointer;
}

#left-side-menu >>> .el-tree-node__content:hover .node-tool {
  opacity: 1;
}

#left-side-menu >>> .node-page i {
  opacity: 0.3;
}
</style>
