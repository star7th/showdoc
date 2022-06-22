<!-- 更多模板 -->
<template>
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-dialog
        :title="$t('history_version')"
        :modal="is_modal"
        :visible.sync="dialogTableVisible"
        :close-on-click-modal="false"
      >
        <el-table :data="content">
          <el-table-column
            property="addtime"
            :label="$t('update_time')"
            width="170"
          ></el-table-column>
          <el-table-column
            property="author_username"
            :label="$t('update_by_who')"
          ></el-table-column>
          <el-table-column property="page_comments" :label="$t('remark')">
            <template slot-scope="scope">
              {{ scope.row.page_comments }}
              <el-button
                v-if="is_show_recover_btn"
                @click="editComments(scope.row)"
                type="text"
                size="small"
                >{{ $t('edit') }}</el-button
              >
            </template>
          </el-table-column>
          <el-table-column label="操作" width="150">
            <template slot-scope="scope">
              <el-button
                @click="previewDiff(scope.row)"
                type="text"
                size="small"
                >{{ $t('overview') }}</el-button
              >
              <el-button
                v-if="is_show_recover_btn"
                type="text"
                size="small"
                @click="recover(scope.row)"
                >{{ $t('recover_to_this_version') }}</el-button
              >
            </template>
          </el-table-column>
        </el-table>
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
    is_modal: true,
    is_show_recover_btn: true
  },
  data() {
    return {
      currentDate: new Date(),
      content: [],
      dialogTableVisible: false
    }
  },
  components: {},
  methods: {
    getContent() {
      let page_id = this.page_id ? this.page_id : this.$route.params.page_id
      this.request('/api/page/history', {
        page_id: page_id
      }).then(data => {
        var json = data.data
        if (json.length > 0) {
          this.content = data.data
          this.dialogTableVisible = true
        } else {
          this.dialogTableVisible = false
          this.$alert('no data')
        }
      })
    },
    show() {
      this.getContent()
    },
    recover(row) {
      this.callback(row.page_content, true)
      this.dialogTableVisible = false
    },

    previewDiff(row) {
      var page_history_id = row['page_history_id']
      let page_id = this.page_id ? this.page_id : this.$route.params.page_id
      var url = '#/page/diff/' + page_id + '/' + page_history_id
      window.open(url)
    },
    editComments(row) {
      let page_id = this.page_id ? this.page_id : this.$route.params.page_id
      this.$prompt('', ' ', {}).then(data => {
        this.request('/api/page/updateHistoryComments', {
          page_id: page_id,
          page_history_id: row.page_history_id,
          page_comments: data.value
        }).then(() => {
          this.getContent()
        })
      })
    }
  },
  mounted() {}
}
</script>
