<!-- 附件 -->
<template>
  <div class="">
    <SDialog
      :title="$t('item_change_log_dialog_title')"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
      width="70%"
      top="5vh"
    >
      <el-table :data="dataList">
        <el-table-column
          property="optime"
          :label="$t('optime')"
          width="170"
        ></el-table-column>
        <el-table-column property="oper" :label="$t('oper')"></el-table-column>
        <el-table-column
          v-if="$lang == 'zh-cn'"
          property="op_action_type_desc"
          :label="$t('op_action_type_desc')"
        ></el-table-column>
        <el-table-column
          v-if="$lang == 'zh-cn'"
          property="op_object_type_desc"
          :label="$t('op_object_type_desc')"
        ></el-table-column>
        <el-table-column
          v-if="$lang == 'en'"
          property="op_action_type"
          :label="$t('op_action_type_desc')"
        ></el-table-column>
        <el-table-column
          v-if="$lang == 'en'"
          property="op_object_type"
          :label="$t('op_object_type_desc')"
        ></el-table-column>
        <el-table-column
          property="op_object_name"
          :label="$t('op_object_name')"
        >
          <template slot-scope="scope">
            <el-button
              v-if="
                (scope.row.op_action_type == 'create' ||
                  scope.row.op_action_type == 'update') &&
                  scope.row.op_object_type == 'page'
              "
              @click="visitPage(scope.row.op_object_id)"
              type="text"
              >{{ scope.row.op_object_name }}</el-button
            >
            <span v-else>{{ scope.row.op_object_name }}</span>
          </template>
        </el-table-column>
      </el-table>

      <div class="block">
        <span class="demonstration"></span>
        <el-pagination
          @current-change="handleCurrentChange"
          :page-size="count"
          layout="total, prev, pager, next"
          :total="total"
        ></el-pagination>
      </div>
    </SDialog>
  </div>
</template>

<style></style>

<script>
export default {
  props: {
    callback: () => {},
    page_id: '',
    item_id: ''
  },
  data() {
    return {
      page: 1,
      count: 10,
      dataList: [],
      total: 0,
      dialogTableVisible: true
    }
  },
  components: {},
  computed: {},
  methods: {
    getList() {
      this.request('/api/item/getChangeLog', {
        page: this.page,
        count: this.count,
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.dialogTableVisible = true
        this.dataList = json.list
        this.total = parseInt(json.total)
      })
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getList()
    },
    visitPage(page_id) {
      let routeUrl = this.$router.resolve({
        path: '/' + this.item_id + '/' + page_id
      })
      window.open(routeUrl.href, '_blank')
    }
  },
  mounted() {
    this.getList()
  }
}
</script>
