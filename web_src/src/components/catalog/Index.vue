<template>
  <div >
    <SDialog
      :onCancel="callback"
      :title="$t('add_cat')"
      width="600px"
      :onOK="callback"
      :showCancel="false"
      :btn1Text="$t('add_cat')"
      btn1Icon="el-icon-plus"
      :btn1Medthod="
        () => {
          addCat()
        }
      "
    >
      <div class="p-4">
        <p class=" v3-color-aux v3-font-size-sm " v-if="treeData.length > 1">
          {{ $t('cat_tips') }}
        </p>
        <el-tree
          class="tree-node"
          :data="treeData"
          node-key="id"
          default-expand-all
          @node-drag-end="handleDragEnd"
          draggable
        >
          <span class="custom-tree-node" slot-scope="{ node, data }">
            <span>{{ node.label }}</span>
            <span class="right-bar">
              <el-button
                type="text"
                size="mini"
                :title="$t('edit')"
                class="el-icon-edit-outline"
                @click.stop="edit(node, data)"
              ></el-button>
              <el-button
                type="text"
                size="mini"
                class="el-icon-plus"
                :title="$t('add_cat')"
                @click.stop="addCat(node, data)"
              ></el-button>
              <el-button
                type="text"
                size="mini"
                class="el-icon-document"
                :title="$t('sort_pages')"
                @click.stop="showSortPage(node, data)"
              ></el-button>
              <el-button
                type="text"
                size="mini"
                class="el-icon-copy-document"
                :title="$t('copy_or_mv_cat')"
                @click.stop="copyCat(node, data)"
              ></el-button>
              <el-button
                type="text"
                size="mini"
                class="el-icon-delete"
                @click.stop="deleteCat(node, data)"
              ></el-button>
            </span>
          </span>
        </el-tree>
      </div>
    </SDialog>

    <SortPage
      v-if="sortPageVisiable"
      :callback="
        () => {
          sortPageVisiable = false
        }
      "
      :item_id="item_id"
      :cat_id="curl_cat_id"
      ref="SortPage"
    ></SortPage>

    <Copy
      v-if="copyFormVisible"
      :item_id="item_id"
      :cat_id="curl_cat_id"
      :callback="copyCallback"
    ></Copy>

    <SDialog
      v-if="dialogFormVisible"
      :title="$t('add_cat')"
      :showCancel="false"
      :onCancel="
        () => {
          dialogFormVisible = false
        }
      "
      :onOK="MyFormSubmit"
      width="300px"
    >
      <el-form>
        <el-form-item :label="$t('cat_name') + ' : '">
          <el-input
            :placeholder="$t('input_cat_name')"
            v-model="MyForm.cat_name"
          ></el-input>
        </el-form-item>
        <el-form-item :label="$t('parent_cat_name') + ' : '">
          <el-select v-model="MyForm.parent_cat_id" :placeholder="$t('none')">
            <el-option
              v-for="item in belong_to_catalogs"
              :key="item.cat_id"
              :label="item.cat_name"
              :value="item.cat_id"
            ></el-option>
          </el-select>
        </el-form-item>
      </el-form>
    </SDialog>
  </div>
</template>

<script>
import SortPage from '@/components/page/edit/SortPage'
import Copy from './Copy'
export default {
  name: 'Login',
  components: {
    SortPage,
    Copy
  },
  props: {
    callback: () => {},
    item_id: 0
  },
  data() {
    return {
      MyForm: {
        cat_id: 0,
        parent_cat_id: '',
        cat_name: '',
        s_number: ''
      },
      catalogs: [],
      dialogFormVisible: false,
      copyFormVisible: false,
      treeData: [],
      defaultProps: {
        children: 'children',
        label: 'label'
      },

      curl_cat_id: '',
      sortPageVisiable: false
    }
  },
  computed: {
    // 新建/编辑目录时供用户选择的上级目录列表
    belong_to_catalogs: function() {
      if (!this.catalogs || this.catalogs.length <= 0) {
        return []
      }

      var Info = this.catalogs.slice(0)
      var cat_array = []

      // 这个函数将递归
      var rename = function(catalog, p_cat_name) {
        if (catalog.length > 0) {
          for (var j = 0; j < catalog.length; j++) {
            var cat_name = p_cat_name + ' / ' + catalog[j]['cat_name']
            cat_array.push({
              cat_id: catalog[j]['cat_id'],
              cat_name: cat_name
            })
            if (catalog[j].sub && catalog[j].sub.length > 0) {
              rename(catalog[j].sub, cat_name)
            }
          }
        }
      }

      for (var i = 0; i < Info.length; i++) {
        cat_array.push(Info[i])
        rename(Info[i]['sub'], Info[i].cat_name)
      }
      var no_cat = { cat_id: 0, cat_name: this.$t('none') }
      cat_array.push(no_cat)
      return cat_array
    }
  },
  methods: {
    getCatalog() {
      this.request('/api/catalog/catListGroup', {
        item_id: this.item_id
      }).then(data => {
        var Info = data.data
        this.catalogs = Info
        this.treeData = []
        var duang = function(Info) {
          var treeData = []
          for (var i = 0; i < Info.length; i++) {
            let node = { children: [] }
            node['id'] = Info[i]['cat_id']
            node['label'] = Info[i]['cat_name']
            if (Info[i]['sub'].length > 0) {
              node['children'] = duang(Info[i]['sub'])
            }
            treeData.push(node)
          }
          return treeData
        }
        this.treeData = duang(Info)
      })
    },
    MyFormSubmit() {
      this.request('/api/catalog/save', {
        item_id: this.item_id,
        cat_id: this.MyForm.cat_id,
        parent_cat_id: this.MyForm.parent_cat_id,
        cat_name: this.MyForm.cat_name
      }).then(data => {
        this.dialogFormVisible = false
        this.getCatalog()
        this.MyForm = []
      })
    },
    edit(node, data) {
      this.MyForm = {
        cat_id: data.id,
        parent_cat_id: node.parent.data.id,
        cat_name: data.label
      }

      this.dialogFormVisible = true
    },

    deleteCat(node, data) {
      var cat_id = data.id
      this.$confirm(this.$t('confirm_cat_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/catalog/delete', {
          item_id: this.item_id,
          cat_id: cat_id
        }).then(data => {
          this.getCatalog()
        })
      })
    },
    resetForm() {
      this.MyForm = {
        cat_id: 0,
        parent_cat_id: '',
        cat_name: '',
        s_number: ''
      }
    },
    addCat(node, data) {
      if (node && data.id) {
        this.MyForm = {
          cat_id: '',
          parent_cat_id: data.id,
          cat_name: ''
        }
      } else {
        this.resetForm()
      }

      this.dialogFormVisible = true
    },
    goback() {
      var url = '/' + this.item_id
      this.$router.push({ path: url })
    },
    handleDragEnd(node1, node2, position, evt) {
      let treeData2 = this.dimensionReduction(this.treeData)
      this.request('/api/catalog/batUpdate', {
        item_id: this.item_id,
        cats: JSON.stringify(treeData2)
      })
    },
    // 将目录数组降维
    dimensionReduction(treeData) {
      let treeData2 = []

      var pushTreeData = function(OneData, parent_cat_id, level, i) {
        treeData2.push({
          cat_id: OneData['id'],
          cat_name: OneData['label'],
          parent_cat_id: parent_cat_id,
          level: level,
          s_number: i + 1
        })
        if (OneData.hasOwnProperty('children')) {
          for (var j = 0; j < OneData['children'].length; j++) {
            pushTreeData(OneData['children'][j], OneData['id'], level + 1, j)
          }
        }
      }

      for (var i = 0; i < treeData.length; i++) {
        pushTreeData(treeData[i], 0, 2, i)
      }
      return treeData2
    },
    // 展示页面排序
    showSortPage(node, data) {
      this.curl_cat_id = data.id
      this.sortPageVisiable = true
    },
    copyCat(node, data) {
      this.curl_cat_id = data.id
      this.copyFormVisible = true
    },
    copyCallback() {
      this.copyFormVisible = false
      this.getCatalog()
    }
  },

  mounted() {
    this.getCatalog()
  },

  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.tree-node {
  margin-top: 10px;
  background-color: #f9f9f9;
}
.custom-tree-node {
  width: 100%;
  margin-top: 5px;
}
.right-bar {
  float: right;
  margin-right: 20px;
}
</style>

<style>
.tree-node .el-tree-node__content {
  height: 60px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}
.tree-node .el-tree-node__content:hover {
  background-color: white;
}
</style>
