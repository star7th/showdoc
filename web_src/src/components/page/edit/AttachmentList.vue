<!-- 附件 -->
<template>
  <div class="">
    <SDialog
      :title="$t('attachment')"
      :onCancel="cancel"
      :showCancel="false"
      :showOk="false"
      :onOK="callback"
      width="55%"
    >
      <el-form v-if="manage" :inline="true" class="demo-form-inline">
        <el-form-item>
          <el-button @click="showFilehub = true">{{
            $t('from_file_gub')
          }}</el-button>
        </el-form-item>
        <el-form-item>
          <el-button @click="dialogUploadVisible = true">{{
            $t('upload')
          }}</el-button>
          <small>&nbsp;&nbsp;&nbsp;{{ $t('file_size_tips') }}</small>
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
    </SDialog>

    <!-- 附件列表 -->
    <filehub
      v-if="showFilehub"
      :item_id="item_id"
      :page_id="page_id"
      :callback="
        () => {
          showFilehub = false
          getContent()
        }
      "
    ></filehub>

    <!-- 上传文件 -->
    <SDialog
      v-if="dialogUploadVisible"
      :onCancel="
        () => {
          dialogUploadVisible = false
          resetUpload()
        }
      "
      :showCancel="false"
      :showOk="
        () => {
          dialogUploadVisible = false
          resetUpload()
        }
      "
      width="600px"
      top="15vh"
    >
      <div style="text-align: center;">
        <!-- 上传区域 -->
        <el-upload
          drag
          name="file"
          class="upload-file"
          :action="uploadUrl"
          :on-change="onFileChange"
          :on-success="uploadCallback"
          :on-error="uploadError"
          :data="uploadData"
          ref="uploadFile"
          :show-file-list="false"
          :auto-upload="false"
          multiple
        >
          <i class="el-icon-upload"></i>
          <div class="el-upload__text">
            <span class="tips-text" v-html="$t('import_file_tips2')"></span>
            <div style="margin-top: 5px; color: #606266; font-size: 12px;">
              {{ $t('batch_upload_support') }}
            </div>
          </div>
        </el-upload>

        <!-- 文件队列显示 -->
        <div v-if="uploadQueue.length > 0" style="margin-top: 15px;">
          <div style="margin-bottom: 10px; font-weight: bold;">
            {{ $t('upload_queue_files', { count: uploadQueue.length }) }}:
          </div>
          <div class="upload-queue">
            <div
              v-for="(item, index) in uploadQueue"
              :key="index"
              class="queue-item"
              :class="{
                uploading: item.status === 'uploading',
                success: item.status === 'success',
                error: item.status === 'error'
              }"
            >
              <div class="file-info">
                <i class="el-icon-document"></i>
                <span class="file-name">{{ item.file.name }}</span>
                <span class="file-size"
                  >({{ formatFileSize(item.file.size) }})</span
                >
              </div>
              <div class="status-info">
                <span v-if="item.status === 'waiting'" class="status waiting">{{
                  $t('file_waiting')
                }}</span>
                <span
                  v-else-if="item.status === 'uploading'"
                  class="status uploading"
                >
                  <i class="el-icon-loading"></i> {{ $t('file_uploading') }}
                </span>
                <span
                  v-else-if="item.status === 'success'"
                  class="status success"
                >
                  <i class="el-icon-check"></i>
                  <a
                    v-if="item.url"
                    :href="item.url"
                    target="_blank"
                    style="color: #67c23a;"
                    >{{ $t('file_upload_success') }}</a
                  >
                  <span v-else>{{ $t('file_upload_success') }}</span>
                </span>
                <span v-else-if="item.status === 'error'" class="status error">
                  <i class="el-icon-close"></i> {{ $t('file_upload_failed') }}:
                  {{ item.error }}
                </span>
              </div>
            </div>
          </div>

          <!-- 上传控制按钮 -->
          <div
            style="margin-top: 15px; text-align: center;"
            v-if="uploadQueue.length > 0 && !isUploading"
          >
            <el-button @click="clearQueue">{{ $t('clear_queue') }}</el-button>
          </div>

          <!-- 上传进度 -->
          <div v-if="isUploading" style="margin-top: 15px;">
            <div style="margin-bottom: 8px; font-size: 14px;">
              {{ $t('upload_progress') }}: {{ uploadedCount }}/{{ totalCount }}
            </div>
            <el-progress
              :percentage="uploadProgress"
              :status="uploadProgress === 100 ? 'success' : ''"
            ></el-progress>
          </div>
        </div>
      </div>
    </SDialog>
  </div>
</template>

<style scoped>
.upload-queue {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  padding: 10px;
}

.queue-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.queue-item:last-child {
  border-bottom: none;
}

.file-info {
  display: flex;
  align-items: center;
  flex: 1;
}

.file-info i {
  margin-right: 8px;
  font-size: 16px;
  color: #409eff;
}

.file-name {
  font-weight: 500;
  margin-right: 8px;
}

.file-size {
  color: #909399;
  font-size: 12px;
}

.status-info {
  min-width: 100px;
  text-align: right;
}

.status {
  padding: 2px 8px;
  border-radius: 3px;
  font-size: 12px;
}

.status.waiting {
  background-color: #f4f4f5;
  color: #909399;
}

.status.uploading {
  background-color: #ecf5ff;
  color: #409eff;
}

.status.success {
  background-color: #f0f9eb;
  color: #67c23a;
}

.status.error {
  background-color: #fef0f0;
  color: #f56c6c;
}

.queue-item.uploading {
  background-color: #fafbfc;
}

.queue-item.success {
  background-color: #f0f9eb;
}

.queue-item.error {
  background-color: #fef0f0;
}
</style>

<script>
import filehub from '@/components/page/edit/Filehub'
import { getUserInfoFromStorage } from '@/models/user.js'
export default {
  props: {
    callback: '',
    cancel: '',
    page_id: '',
    item_id: '',
    manage: true
  },
  data() {
    return {
      content: [],
      uploadUrl: DocConfig.server + '/api/page/upload',
      dialogUploadVisible: false,
      loading: '',
      user_token: '',
      showFilehub: false,
      // 批量上传相关
      uploadQueue: [],
      isUploading: false,
      uploadedCount: 0,
      totalCount: 0,
      currentUploadIndex: 0
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
    },
    uploadProgress() {
      if (this.totalCount === 0) return 0
      return Math.round((this.uploadedCount / this.totalCount) * 100)
    }
  },
  methods: {
    getContent() {
      this.request('/api/page/uploadList', {
        page_id: this.page_id
      }).then(data => {
        const json = data.data
        this.content = json
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
    uploadCallback(data) {
      const currentItem = this.uploadQueue[this.currentUploadIndex]
      if (currentItem) {
        // 检查上传是否成功
        if (data.success === 1) {
          currentItem.status = 'success'
          currentItem.url = data.url // 保存文件URL
        } else {
          currentItem.status = 'error'
          currentItem.error =
            data.error_message || this.$t('upload_failed_error')
        }
        this.uploadedCount++

        // 继续上传下一个文件
        this.uploadNextFile()
      }
    },
    uploadError(err, file) {
      const currentItem = this.uploadQueue[this.currentUploadIndex]
      if (currentItem) {
        currentItem.status = 'error'
        currentItem.error = this.$t('upload_failed_error')
        this.uploadedCount++

        // 继续上传下一个文件
        this.uploadNextFile()
      }
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

    onFileChange(file, fileList) {
      // 当文件改变时，将新文件添加到队列

      // 检查是否是新添加的文件
      const existingFile = this.uploadQueue.find(
        item =>
          item.file.name === file.raw.name && item.file.size === file.raw.size
      )

      if (!existingFile) {
        this.uploadQueue.push({
          file: file.raw,
          status: 'waiting',
          error: ''
        })

        // 自动开始上传
        if (!this.isUploading) {
          this.$nextTick(() => {
            this.startBatchUpload()
          })
        }
      }
    },
    startBatchUpload() {
      if (this.uploadQueue.length === 0) return

      // 如果已经在上传中，只需要更新总数，不重新开始
      if (this.isUploading) {
        this.totalCount = this.uploadQueue.filter(
          item =>
            item.status === 'waiting' ||
            item.status === 'uploading' ||
            item.status === 'success'
        ).length
        return
      }

      this.isUploading = true
      this.uploadedCount = 0
      this.totalCount = this.uploadQueue.filter(
        item => item.status === 'waiting'
      ).length
      this.currentUploadIndex = 0

      // 开始上传第一个文件
      this.uploadNextFile()
    },
    uploadNextFile() {
      // 寻找下一个等待上传的文件
      while (this.currentUploadIndex < this.uploadQueue.length) {
        const currentItem = this.uploadQueue[this.currentUploadIndex]
        if (currentItem.status === 'waiting') {
          currentItem.status = 'uploading'
          this.uploadSingleFile(currentItem.file)
          return
        }
        this.currentUploadIndex++
      }

      // 所有文件上传完成
      this.isUploading = false
      this.getContent() // 刷新列表

      // 显示上传结果
      const successCount = this.uploadQueue.filter(
        item => item.status === 'success'
      ).length
      const errorCount = this.uploadQueue.filter(
        item => item.status === 'error'
      ).length

      if (errorCount === 0) {
        // 全部上传成功，显示消息后关闭对话框
        this.$message.success(
          this.$t('batch_upload_complete_success', { count: successCount })
        )
        setTimeout(() => {
          this.dialogUploadVisible = false
          this.resetUpload()
        }, 1500) // 1.5秒后关闭对话框
      } else {
        this.$message.warning(
          this.$t('batch_upload_complete_partial', {
            success: successCount,
            error: errorCount
          })
        )
      }
    },
    uploadSingleFile(file) {
      // 使用 FormData 进行文件上传
      const formData = new FormData()
      formData.append('file', file)
      formData.append('page_id', this.page_id)
      formData.append('item_id', this.item_id)
      formData.append('user_token', this.user_token)

      // 使用项目中的 this.request 方法，现在支持 FormData
      this.request('/api/page/upload', formData, 'post', false)
        .then(data => {
          this.uploadCallback(data)
        })
        .catch(() => {
          this.uploadError(null, file)
        })
    },
    clearQueue() {
      this.uploadQueue = []
      this.$refs.uploadFile.clearFiles()
    },
    resetUpload() {
      this.clearQueue()
      this.isUploading = false
      this.uploadedCount = 0
      this.totalCount = 0
      this.currentUploadIndex = 0
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }
  },
  mounted() {
    this.getContent()
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
  }
}
</script>
