<template>
  <div class="hello">
    <el-form :inline="true" class="demo-form-inline">
      <el-form-item label>
        <el-input
          v-model="display_name"
          :placeholder="$t('display_name')"
        ></el-input>
      </el-form-item>
      <el-form-item label>
        <el-select v-model="attachment_type" placeholder>
          <el-option :label="$t('all_attachment_type')" value="-1"></el-option>
          <el-option :label="$t('image')" value="1"></el-option>
          <el-option :label="$t('general_attachment')" value="2"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item label>
        <el-input v-model="username" :placeholder="$t('uploader')"></el-input>
      </el-form-item>
      <el-form-item>
        <el-button @click="onSubmit">{{ $t('search') }}</el-button>
      </el-form-item>
    </el-form>
    <P>{{ $t('used_space') }} {{ used }}M</P>
    <div style="margin: 10px 0;">
      <el-button type="primary" @click="openCleanupDialog">{{ $t('cleanup_unused_attachments') }}</el-button>
    </div>
    <el-table :data="dataList" style="width: 100%">
      <el-table-column prop="file_id" :label="$t('file_id')"></el-table-column>
      <el-table-column
        prop="display_name"
        :label="$t('display_name')"
      ></el-table-column>
      <el-table-column
        prop="file_type"
        :label="$t('file_type')"
        width="140"
      ></el-table-column>
      <el-table-column
        prop="file_size_m"
        :label="$t('file_size_m')"
        width="140"
      ></el-table-column>
      <el-table-column
        prop="visit_times"
        :label="$t('visit_times')"
      ></el-table-column>
      <el-table-column
        prop="username"
        :label="$t('uploader')"
      ></el-table-column>
      <el-table-column
        prop="addtime"
        :label="$t('add_time')"
        width="160"
      ></el-table-column>
      <el-table-column prop :label="$t('operation')">
        <template slot-scope="scope">
          <el-button @click="visit(scope.row)" type="text" size="small">{{
            $t('visit')
          }}</el-button>
          <el-button @click="deleteRow(scope.row)" type="text" size="small">{{
            $t('delete')
          }}</el-button>
        </template>
      </el-table-column>
    </el-table>

    <div class="block">
      <span class="demonstration"></span>
      <el-pagination
        :current-page="page"
        @current-change="handleCurrentChange"
        @size-change="handleSizeChange"
        :page-size="count"
        :page-sizes="pageSizes"
        layout="sizes, total, prev, pager, next"
        :total="total"
      ></el-pagination>
    </div>

    <el-dialog :title="$t('cleanup_dialog_title')" :visible.sync="cleanup.visible" width="80%">
      <div style="margin-bottom:10px;">
        <el-form :inline="true">
          <el-form-item>
            <el-input v-model="display_name" :placeholder="$t('display_name')"></el-input>
          </el-form-item>
          <el-form-item>
            <el-input v-model="username" :placeholder="$t('uploader')"></el-input>
          </el-form-item>
          <el-form-item>
            <el-button type="primary" @click="onCleanupSearch">{{ $t('search') }}</el-button>
          </el-form-item>
        </el-form>
      </div>

      <el-table
        :data="cleanup.list"
        v-loading="cleanup.loading"
        style="width: 100%"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="55"></el-table-column>
        <el-table-column prop="file_id" :label="$t('file_id')" width="100"></el-table-column>
        <el-table-column prop="display_name" :label="$t('display_name')"></el-table-column>
        <el-table-column prop="file_type" :label="$t('file_type')" width="140"></el-table-column>
        <el-table-column prop="file_size_m" :label="$t('file_size_m')" width="140"></el-table-column>
        <el-table-column prop="username" :label="$t('uploader')"></el-table-column>
        <el-table-column prop="addtime" :label="$t('add_time')" width="160"></el-table-column>
        <el-table-column :label="$t('operation')" width="120">
          <template slot-scope="scope">
            <el-button @click="visit(scope.row)" type="text" size="small">{{ $t('visit') }}</el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="block" style="margin-top: 10px;">
        <el-pagination
          :current-page="cleanup.page"
          @current-change="handleCleanupPageChange"
          @size-change="handleCleanupSizeChange"
          :page-size="cleanup.count"
          :page-sizes="pageSizes"
          layout="sizes, total, prev, pager, next"
          :total="cleanup.total"
        ></el-pagination>
      </div>

      <span slot="footer" class="dialog-footer">
        <el-button @click="cleanup.visible = false">{{ $t('cancel') }}</el-button>
        <el-button type="primary" @click="batchDeleteSelected">{{ $t('delete_selected') }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<style scoped></style>

<script>
export default {
  data() {
    return {
      page: 1,
      count: 7,
      pageSizes: [7, 10, 20, 50, 100],
      display_name: '',
      username: '',
      dataList: [],
      total: 0,
      positive_type: '1',
      attachment_type: '-1',
      used: 0,
      cleanup: {
        visible: false,
        loading: false,
        list: [],
        total: 0,
        page: 1,
        count: 10,
        selected: []
      }
    }
  },
  methods: {
    getList() {
      this.request('/api/attachment/getAllList', {
        page: this.page,
        count: this.count,
        attachment_type: this.attachment_type,
        display_name: this.display_name,
        username: this.username
      }).then(data => {
        var json = data.data
        this.dataList = json.list
        this.total = parseInt(json.total)
        this.used = json.used_m
      })
    },
    openCleanupDialog() {
      this.cleanup.visible = true
      this.cleanup.page = 1
      this.fetchUnused()
    },
    fetchUnused() {
      this.cleanup.loading = true
      this.request('/api/attachment/getUnusedList', {
        page: this.cleanup.page,
        count: this.cleanup.count,
        display_name: this.display_name,
        username: this.username
      }).then(data => {
        const json = data.data
        this.cleanup.list = json.list || []
        this.cleanup.total = parseInt(json.total || 0)
      }).finally(() => {
        this.cleanup.loading = false
      })
    },
    handleCleanupPageChange(p) {
      this.cleanup.page = p
      this.fetchUnused()
    },
    handleCleanupSizeChange(s) {
      this.cleanup.count = s
      this.cleanup.page = 1
      this.fetchUnused()
    },
    onCleanupSearch() {
      this.cleanup.page = 1
      this.fetchUnused()
    },
    handleSelectionChange(selection) {
      this.cleanup.selected = selection
    },
    batchDeleteSelected() {
      if (!this.cleanup.selected.length) {
        this.$message.warning(this.$t('please_select_to_delete'))
        return
      }
      const ids = this.cleanup.selected.map(i => i.file_id)
      this.$confirm(this.$t('confirm_delete_selected'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/attachment/batchDeleteAttachments', { file_ids: ids.join(',') }).then(() => {
          this.$message.success(this.$t('op_success'))
          this.fetchUnused()
          this.getList()
        })
      })
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getList()
    },
    handleSizeChange(newSize) {
      this.count = newSize
      this.page = 1
      this.getList()
    },
    onSubmit() {
      this.page = 1
      this.getList()
    },
    visit(row) {
      window.open(row.url)
    },
    deleteRow(row) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/attachment/deleteAttachment', {
          file_id: row.file_id
        }).then(data => {
          this.$message.success(this.$t('op_success'))
          this.getList()
        })
      })
    }
  },
  mounted() {
    this.getList()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>

