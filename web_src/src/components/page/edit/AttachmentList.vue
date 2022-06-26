<!-- 附件 -->
<template>
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-dialog
        :title="$t('attachment')"
        :visible.sync="dialogTableVisible"
        :close-on-click-modal="false"
        @close="callback()"
      >
        <el-form v-if="manage" :inline="true" class="demo-form-inline">
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
      :callback="getContent"
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
          :before-upload="beforeUpload"
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
import { getUserInfoFromStorage } from '@/models/user.js'

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
      dialogTableVisible: true,
      uploadUrl: DocConfig.server + '/api/page/upload',
      dialogUploadVisible: false,
      loading: '',
      user_token: ''
    }
  },
  components: {
    filehub
  },
  computed: {
    uploadData: function() {
      return {
        page_id: this.page_id,
        item_id: this.item_id,
        user_token: this.user_token
      }
    }
  },
  methods: {
    getContent() {
      this.request(
        '/api/page/uploadList',
        {
          page_id: this.page_id
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.dialogTableVisible = true
          this.content = data.data
        } else {
          this.dialogTableVisible = false
          this.$alert(data.error_message)
        }
      })
    },
    downloadFile(row) {
      var url = row.url
      window.open(url)
    },

    deleteFile(row) {
      this.$confirm(this.$t('comfirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var file_id = row['file_id']

        this.request('/api/page/deleteUploadFile', {
          file_id: file_id,
          page_id: this.page_id
        }).then(data => {
          this.getContent()
        })
      })
    },
    clearFiles() {
      let childRef = this.$refs.uploadFile // 获取子组件
      childRef.clearFiles()
      this.getContent()
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
      this.loading.close()
      if (data.error_message) {
        this.$alert(data.error_message)
      }
      let childRef = this.$refs.uploadFile // 获取子组件
      childRef.clearFiles()
      this.getContent()
      this.dialogUploadVisible = false
    },
    // 文件库
    showFilehub() {
      let childRef = this.$refs.filehub // 获取子组件
      childRef.show()
    },
    beforeUpload() {
      this.loading = this.$loading()
    }
  },
  mounted() {
    this.getContent()
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
  }
}
</script>
