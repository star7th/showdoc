<!-- 更多模板 -->
<template>
  <div class="">
    <SDialog
      :title="$t('templ_list')"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
    >
      <el-tabs value="myList" type="card">
        <el-tab-pane :label="$t('my_template')" name="myList">
          <el-table :data="myList" :empty-text="$t('no_my_template_text')">
            <el-table-column
              property="addtime"
              :label="$t('save_time')"
              width="170"
            ></el-table-column>
            <el-table-column
              property="template_title"
              :label="$t('templ_title')"
            ></el-table-column>
            <el-table-column :label="$t('operation')" width="300">
              <template slot-scope="scope">
                <el-button
                  @click="insertTemplate(scope.row)"
                  type="text"
                  size="small"
                  >{{ $t('insert_templ') }}</el-button
                >
                <el-button
                  @click="shareToClick(scope.row)"
                  type="text"
                  size="small"
                  >{{ $t('share_to_these_items') }}({{
                    scope.row.share_item_count
                  }})</el-button
                >
                <el-button
                  type="text"
                  size="small"
                  @click="deleteTemplate(scope.row)"
                  >{{ $t('delete_templ') }}</el-button
                >
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
        <el-tab-pane :label="$t('item_template')" name="itemList">
          <el-table :data="itemList" :empty-text="$t('no_item_template_text')">
            <el-table-column
              property="created_at"
              :label="$t('save_time')"
              width="170"
            ></el-table-column>
            <el-table-column
              property="username"
              :label="$t('sharer')"
            ></el-table-column>
            <el-table-column
              property="template_title"
              :label="$t('templ_title')"
            ></el-table-column>
            <el-table-column :label="$t('operation')" width="150">
              <template slot-scope="scope">
                <el-button
                  @click="insertTemplate(scope.row)"
                  type="text"
                  size="small"
                  >{{ $t('insert_templ') }}</el-button
                >
              </template>
            </el-table-column>
          </el-table>
        </el-tab-pane>
      </el-tabs>
    </SDialog>

    <!-- 共享到这些项目 -->
    <SDialog
      v-if="dialogItemVisible"
      :onCancel="
        () => {
          dialogItemVisible = false
        }
      "
      :onOK="shareToItem"
      width="400px"
    >
      <el-form>
        <el-select
          multiple
          v-model="shareItemId"
          :placeholder="$t('please_choose')"
        >
          <el-option
            v-for="item in myItemList"
            :key="item.item_id"
            :label="item.item_name"
            :value="item.item_id"
          ></el-option>
        </el-select>
      </el-form>
      <p class="tips-text">{{ $t('share_items_tips') }}</p>
    </SDialog>
  </div>
</template>

<style></style>

<script>
export default {
  props: {
    callback: () => {},
    item_id: 0,
    opRow: {}
  },
  data() {
    return {
      myList: [],
      itemList: [],
      dialogTableVisible: true,
      dialogItemVisible: false,
      myItemList: [],
      shareItemId: []
    }
  },
  components: {},
  methods: {
    getMyList() {
      this.request('/api/template/getMyList', {}).then(data => {
        const json = data.data
        this.myList = json
      })
    },
    getItemList() {
      this.request('/api/template/getItemList', {
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.itemList = json
      })
    },
    // 获取我的项目列表，供用户选择
    getMyItemList() {
      this.request('/api/item/myList', {}).then(data => {
        this.myItemList = data.data
      })
    },
    insertTemplate(row) {
      this.callback(row.template_content)
    },

    deleteTemplate(row) {
      var id = row['id']
      this.request('/api/template/delete', {
        id: id
      }).then(data => {
        this.getMyList()
        this.getItemList()
      })
    },
    // 点击共享按钮
    shareToClick(row) {
      this.getMyItemList()
      this.opRow = row
      this.dialogItemVisible = true
      this.shareItemId = []
      // 假如之前已经选择了项目，则这里填上默认值
      if (row.share_item_count > 0) {
        let item_ids = []
        row.share_item.map(element => {
          item_ids.push(element.item_id)
        })
        this.shareItemId = item_ids
      }
    },
    shareToItem() {
      this.request('/api/template/shareToItem', {
        template_id: this.opRow.id,
        item_id: this.shareItemId.join(',')
      }).then(data => {
        this.dialogItemVisible = false
        this.getMyList()
        this.getItemList()
      })
    }
  },
  mounted() {
    this.getMyList()
    this.getItemList()
  }
}
</script>
<!-- 全局css -->
<style>
.el-table__empty-text {
  text-align: left;
  line-height: 30px !important;
  margin-top: 20px;
  margin-bottom: 20px;
}
</style>
