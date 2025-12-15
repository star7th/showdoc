<template>
  <div>
    <el-card>
      <div slot="header" class="clearfix">
        <span>发布系统公告</span>
      </div>
      <el-form :model="form" label-width="100px">
        <el-form-item label="公告类型">
          <el-radio-group v-model="form.message_type">
            <el-radio label="announce_web">仅 ShowDoc 网页端</el-radio>
            <el-radio label="announce_runapi">仅 RunApi 客户端</el-radio>
            <el-radio label="announce_all">网页端 + RunApi</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="公告内容">
          <el-input
            type="textarea"
            :rows="6"
            v-model="form.message_content"
            placeholder="可填写基础HTML，发送前将进行安全过滤"
          />
        </el-form-item>
        <el-form-item label="发送时间">
          <el-date-picker
            v-model="form.send_at"
            type="datetime"
            placeholder="不选默认为当前时间"
            value-format="yyyy-MM-dd HH:mm:ss"
            :editable="false"
            :clearable="true"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="confirmSend">发送</el-button>
          <el-button @click="resetForm">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card style="margin-top: 20px;">
      <div slot="header" class="clearfix">
        <span>历史公告</span>
      </div>
      <el-table :data="list" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="message_type" label="类型" width="140">
          <template slot-scope="scope">
            <span v-if="scope.row.message_type === 'announce_all'">网页端 + RunApi</span>
            <span v-else-if="scope.row.message_type === 'announce_runapi'">仅 RunApi</span>
            <span v-else>仅网页端</span>
          </template>
        </el-table-column>
        <el-table-column prop="addtime" label="发送时间" width="180" />
        <el-table-column label="内容">
          <template slot-scope="scope">
            <div v-html="scope.row.message_content"></div>
          </template>
        </el-table-column>
      </el-table>
      <div class="block">
        <el-pagination
          :current-page="page"
          @current-change="handleCurrentChange"
          @size-change="handleSizeChange"
          :page-size="count"
          :page-sizes="pageSizes"
          layout="sizes, total, prev, pager, next"
          :total="total"
        />
      </div>
    </el-card>
  </div>
</template>

<script>
export default {
  data() {
    return {
      form: {
        message_type: 'announce_web',
        message_content: '',
        send_at: ''
      },
      list: [],
      page: 1,
      count: 10,
      pageSizes: [10, 20, 50, 100],
      total: 0
    }
  },
  methods: {
    confirmSend() {
      if (!this.form.message_content) {
        this.$message.error('请填写公告内容')
        return
      }
      this.$confirm('将要发布系统公告，是否继续？', '发送前确认', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning',
        dangerouslyUseHTMLString: true
      }).then(() => {
        this.request('/api/adminMessage/addAnnouncement', this.form, 'post').then(() => {
          this.$message.success('发布成功')
          this.resetForm()
          this.loadList()
        })
      }).catch(() => {})
    },
    resetForm() {
      this.form = { message_type: 'announce_web', message_content: '', send_at: '' }
    },
    loadList() {
      this.request('/api/adminMessage/listAnnouncements', {
        page: this.page,
        count: this.count
      }).then(res => {
        this.list = res.data || []
        // 简易总数估算
        this.total = this.page * this.count + (this.list.length === this.count ? this.count : 0)
      })
    },
    handleCurrentChange(currentPage) {
      this.page = currentPage
      this.loadList()
    },
    handleSizeChange(newSize) {
      this.count = newSize
      this.page = 1
      this.loadList()
    }
  },
  mounted() {
    this.loadList()
  }
}
</script>

<style scoped>
.block {
  margin-top: 15px;
}
</style>


