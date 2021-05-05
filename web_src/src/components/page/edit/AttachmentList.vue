<!-- 附件 -->
<template>
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-dialog
        :title="$t('attachment')"
        :visible.sync="dialogTableVisible"
        :close-on-click-modal="false"
      >
        <el-form :inline="true" class="demo-form-inline">
          <el-form-item>
            <el-button @click="showFilehub">{{
              $t('from_file_gub')
            }}</el-button>
          </el-form-item>
          <el-form-item>
            <el-button @click="dialogUploadVisible = true">{{
              $t('upload')
            }}</el-button>
            <!-- <small>&nbsp;&nbsp;&nbsp;{{ $t('file_size_tips') }}</small> -->
          </el-form-item>
        </el-form>

        <el-table :data="content">
          <el-table-column
            property="addtime"
            :label="$t('add_time')"
            width="170"
          ></el-table-column>
          <el-table-column
            property="display_name"
            :label="$t('file_name')"
          ></el-table-column>
          <el-table-column :label="$t('operation')" width="150">
            <template slot-scope="scope">
              <el-button
                @click="downloadFile(scope.row)"
                type="text"
                size="small"
                >{{ $t('download') }}</el-button
              >
              <el-button
                @click="insertFile(scope.row)"
                type="text"
                size="small"
                v-if="manage"
                >{{ $t('insert') }}</el-button
              >
              <el-button
                type="text"
                size="small"
                @click="deleteFile(scope.row)"
                v-if="manage"
                >{{ $t('delete') }}</el-button
              >
            </template>
          </el-table-column>
        </el-table>
      </el-dialog>
    </el-container>
    <filehub
      :callback="get_content"
      :item_id="item_id"
      :page_id="page_id"
      ref="filehub"
    ></filehub>
    <el-dialog
      :visible.sync="dialogUploadVisible"
      :close-on-click-modal="false"
      width="400px"
    >
      <p>
        <el-upload
          drag
          name="file"
          class="upload-file"
          :action="uploadUrl"
          :on-success="uploadCallback"
          :on-error="uploadCallback"
          :data="uploadData"
          ref="uploadFile"
          :show-file-list="false"
        >
          <i class="el-icon-upload"></i>
          <div class="el-upload__text">
            <span v-html="$t('import_file_tips2')"></span>
          </div>
        </el-upload>
      </p>
    </el-dialog>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style></style>

<script>
import filehub from '@/components/page/edit/Filehub'
export default {
  props: {
    callback: '',
    page_id: '',
    item_id: '',
    manage: true
  },
  data() {
    return {
      currentDate: new Date(),
      content: [],
      dialogTableVisible: false,
      uploadUrl: DocConfig.server + '/api/page/upload',
      dialogUploadVisible: false
    }
  },
  components: {
    filehub
  },
  computed: {
    uploadData: function() {
      return {
        page_id: this.page_id,
        item_id: this.item_id
      }
    }
  },
  methods: {
    get_content() {
      var that = this
      var url = DocConfig.server + '/api/page/uploadList'
      var params = new URLSearchParams()
      params.append('page_id', this.page_id)
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.dialogTableVisible = true
          // that.$message.success("加载成功");
          that.content = response.data.data
        } else {
          that.dialogTableVisible = false
          that.$alert(response.data.error_message)
        }
      })
    },
    show() {
      this.get_content()
    },
    downloadFile(row) {
      var url = row.url
      window.open(url)
    },

    deleteFile(row) {
      var that = this
      this.$confirm(that.$t('comfirm_delete'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var file_id = row['file_id']
        var that = this
        var url = DocConfig.server + '/api/page/deleteUploadFile'
        var params = new URLSearchParams()
        params.append('file_id', file_id)
        params.append('page_id', that.page_id)
        that.axios.post(url, params).then(function(response) {
          if (response.data.error_code === 0) {
            that.get_content()
          } else {
            that.$alert(response.data.error_message)
          }
        })
      })
    },
    clearFiles() {
      let childRef = this.$refs.uploadFile // 获取子组件
      childRef.clearFiles()
      this.get_content()
    },
    insertFile(row) {
      var val =
        '[' +
        row['display_name'] +
        '](' +
        row['url'] +
        ' "[' +
        row['display_name'] +
        '")'
      this.callback(val)
      this.dialogTableVisible = false
    },
    uploadCallback(data) {
      if (data.error_message) {
        this.$alert(data.error_message)
      }
      let childRef = this.$refs.uploadFile // 获取子组件
      childRef.clearFiles()
      this.get_content()
      this.dialogUploadVisible = false
    },
    // 文件库
    showFilehub() {
      let childRef = this.$refs.filehub // 获取子组件
      childRef.show()
    }
  },
  mounted() {}
}
</script>
