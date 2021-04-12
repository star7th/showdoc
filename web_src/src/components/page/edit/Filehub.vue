<!-- 附件 -->
<template>
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-dialog
        :title="$t('file_gub')"
        :visible.sync="dialogTableVisible"
        :close-on-click-modal="false"
        width="65%"
      >
        <el-form :inline="true" class="demo-form-inline">
          <el-form-item label>
            <el-input
              v-model="display_name"
              :placeholder="$t('display_name')"
            ></el-input>
          </el-form-item>
          <el-form-item label>
            <el-select v-model="attachment_type" placeholder>
              <el-option
                :label="$t('all_attachment_type')"
                value="-1"
              ></el-option>
              <el-option :label="$t('image')" value="1"></el-option>
              <el-option
                :label="$t('general_attachment')"
                value="2"
              ></el-option>
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button @click="onSubmit">{{ $t('search') }}</el-button>
          </el-form-item>
          <el-form-item>
            <el-upload
              class="upload-file"
              :action="uploadUrl"
              :on-success="uploadCallback"
              :on-error="uploadCallback"
              ref="uploadFile"
            >
              <el-button>{{ $t('upload') }}</el-button>
            </el-upload>
          </el-form-item>
        </el-form>
        <P
          >{{ $t('accumulated_used_sapce') }} {{ used }}M ,
          {{ $t('month_flow') }} {{ used_flow }}M</P
        >
        <el-table :data="dataList" style="width: 100%">
          <el-table-column
            prop="file_id"
            :label="$t('file_id')"
          ></el-table-column>
          <el-table-column
            prop="display_name"
            :label="$t('display_name')"
          ></el-table-column>
          <el-table-column
            prop="file_type"
            :label="$t('file_type')"
            width="160"
          ></el-table-column>
          <el-table-column
            prop="file_size_m"
            :label="$t('file_size_m')"
            width="160"
          ></el-table-column>
          <el-table-column
            prop="visit_times"
            :label="$t('visit_times')"
          ></el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('add_time')"
            width="160"
          ></el-table-column>
          <el-table-column prop :label="$t('operation')">
            <template slot-scope="scope">
              <el-button @click="select(scope.row)" type="text" size="small">{{
                $t('select')
              }}</el-button>
              <el-button @click="visit(scope.row)" type="text" size="small">{{
                $t('visit')
              }}</el-button>
              <el-button
                @click="delete_row(scope.row)"
                type="text"
                size="small"
                >{{ $t('delete') }}</el-button
              >
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
      </el-dialog>
    </el-container>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style></style>

<script>
export default {
  props: {
    callback: '',
    page_id: '',
    item_id: '',
    manage: true
  },
  data() {
    return {
      dialogTableVisible: false,
      page: 1,
      count: 5,
      display_name: '',
      username: '',
      dataList: [],
      total: 0,
      positive_type: '1',
      attachment_type: '-1',
      used: 0,
      used_flow: 0,
      uploadUrl: DocConfig.server + '/api/page/upload'
    }
  },
  components: {},
  computed: {
    uploadData: function() {
      return {
        page_id: this.page_id,
        item_id: this.item_id
      }
    }
  },
  methods: {
    getList() {
      this.request('/api/attachment/getMyList', {
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
        this.used_flow = json.used_flow_m
      })
    },
    show() {
      this.dialogTableVisible = true
      this.getList()
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.getList()
    },
    onSubmit() {
      this.page = 1
      this.getList()
    },
    visit(row) {
      window.open(row.url)
    },
    delete_row(row) {
      this.$confirm(this.$t('confirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/attachment/deleteMyAttachment', {
          file_id: row.file_id
        }).then(data => {
          this.$message.success(this.$t('op_success'))
          this.getList()
        })
      })
    },
    uploadCallback(data) {
      if (data.error_message) {
        this.$alert(data.error_message)
      }
      let childRef = this.$refs.uploadFile // 获取子组件
      childRef.clearFiles()
      this.getList()
    },
    select(row) {
      this.request('/api/attachment/bindingPage', {
        file_id: row.file_id,
        page_id: this.page_id
      }).then(data => {
        this.dialogTableVisible = false
        this.callback()
      })
    }
  },
  mounted() {}
}
</script>
