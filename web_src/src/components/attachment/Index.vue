<template>
  <div class="hello">
    <Header></Header>
    <SDialog
      :title="$t('my_attachment')"
      :onCancel="callback"
      :showCancel="false"
      :onOK="callback"
      top="10vh"
      width="60%"
    >
      <div>
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
          <el-table-column prop :label="operation">
            <template slot-scope="scope">
              <el-button @click="visit(scope.row)" type="text" size="small">{{
                $t('visit')
              }}</el-button>
              <el-button @click="copy(scope.row)" type="text" size="small">{{
                $t('copy_link')
              }}</el-button>
              <el-button
                @click="deleteRow(scope.row)"
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
      </div>
    </SDialog>

    <SDialog
      v-if="dialogFormVisible"
      :title="$t('upload')"
      :onCancel="
        () => {
          dialogFormVisible = false
        }
      "
      :onOK="
        () => {
          dialogFormVisible = false
        }
      "
      width="400px"
    >
      <p>
        <el-upload
          :data="{ user_token: user_token }"
          drag
          name="file"
          :action="uploadUrl"
          :before-upload="beforeUpload"
          :on-success="uploadCallback"
          :show-file-list="false"
        >
          <i class="el-icon-upload"></i>
          <div class="el-upload__text">
            <span class="tips-text" v-html="$t('import_file_tips2')"></span>
          </div>
        </el-upload>
      </p>
    </SDialog>

    <Footer></Footer>
  </div>
</template>

<script>
import { getUserInfoFromStorage } from '@/models/user.js'
export default {
  name: '',
  components: {},
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    }
  },
  data() {
    return {
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
      uploadUrl: DocConfig.server + '/api/page/upload',
      dialogFormVisible: false,
      loading: '',
      user_token: ''
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
    deleteRow(row) {
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
      this.loading.close()
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
    },
    beforeUpload() {
      this.loading = this.$loading()
    }
  },

  mounted() {
    this.getList()
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
