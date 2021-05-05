<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="hor-center-card">
        <el-button type="text" @click="goback" class="goback-btn">
          <i class="el-icon-back"></i>
        </el-button>
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
            <el-button @click="dialogFormVisible = true">{{
              $t('upload')
            }}</el-button>
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
            width="140"
          ></el-table-column>
          <el-table-column
            prop="file_size_m"
            :label="$t('file_size_m')"
            width="80"
          ></el-table-column>
          <el-table-column
            prop="visit_times"
            :label="$t('visit_times')"
          ></el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('add_time')"
            width="100"
          ></el-table-column>
          <el-table-column prop :label="$t('operation')">
            <template slot-scope="scope">
              <el-button @click="visit(scope.row)" type="text" size="small">{{
                $t('visit')
              }}</el-button>
              <el-button @click="copy(scope.row)" type="text" size="small">{{
                $t('copy_link')
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
      </el-card>
      <el-dialog
        :visible.sync="dialogFormVisible"
        :close-on-click-modal="false"
        width="400px"
      >
        <p>
          <el-upload
            drag
            name="file"
            :action="uploadUrl"
            :on-success="uploadCallback"
            :show-file-list="false"
          >
            <i class="el-icon-upload"></i>
            <div class="el-upload__text">
              <span v-html="$t('import_file_tips2')"></span>
            </div>
          </el-upload>
        </p>
      </el-dialog>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: '',
  components: {},
  data() {
    return {
      page: 1,
      count: 6,
      display_name: '',
      username: '',
      dataList: [],
      total: 0,
      positive_type: '1',
      attachment_type: '-1',
      used: 0,
      used_flow: 0,
      uploadUrl: DocConfig.server + '/api/page/upload',
      dialogFormVisible: false
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
    // 跳转到项目
    jump_to_item(row) {
      let url = '/' + row.item_id
      window.open(url)
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
    goback() {
      this.$router.push({ path: '/item/index' })
    },
    uploadCallback(data) {
      if (data.error_message) {
        this.$alert(data.error_message)
      }
      this.dialogFormVisible = false
      this.getList()
    },
    copy(row) {
      // 如果需要回调：
      this.$copyText(row.url).then(e => {
        this.$message.success(this.$t('copy_success'))
      })
    }
  },

  mounted() {
    this.getList()
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hor-center-card a {
  font-size: 12px;
}

.hor-center-card {
  width: 1000px;
}

.goback-btn {
  font-size: 18px;
  margin-right: 800px;
  margin-bottom: 5px;
}
</style>
