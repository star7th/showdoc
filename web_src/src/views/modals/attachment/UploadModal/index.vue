<template>
  <div class="upload-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('common.upload')"
      :maxWidth="'700px'"
      @close="closeHandle(false)"
    >
      <div class="upload-container">
        <!-- 上传区域 -->
        <a-upload-dragger
          name="file"
          :multiple="true"
          :show-upload-list="false"
          :before-upload="handleBeforeUpload"
          :custom-request="() => {}"
        >
          <p class="ant-upload-drag-icon">
            <i class="fas fa-cloud-upload-alt"></i>
          </p>
          <p class="ant-upload-text" v-html="$t('attachment.import_file_tips2')"></p>
          <p class="ant-upload-hint">
            {{ $t('attachment.batch_upload_support') }}
          </p>
        </a-upload-dragger>

        <!-- 文件队列显示 -->
        <div v-if="uploadQueue.length > 0" class="upload-queue">
          <div class="queue-title">
            {{ $t('attachment.upload_queue_files', { count: uploadQueue.length }) }}:
          </div>
          <div class="queue-list">
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
                <i class="fas fa-file"></i>
                <span class="file-name">{{ item.file.name }}</span>
                <span class="file-size">({{ formatFileSize(item.file.size) }})</span>
              </div>
              <div class="status-info">
                <span v-if="item.status === 'waiting'" class="status waiting">
                  {{ $t('attachment.file_waiting') }}
                </span>
                <span v-else-if="item.status === 'uploading'" class="status uploading">
                  <i class="fas fa-spinner fa-spin"></i> {{ $t('attachment.file_uploading') }}
                </span>
                <span v-else-if="item.status === 'success'" class="status success">
                  <i class="fas fa-check"></i>
                  <a v-if="item.url" :href="item.url" target="_blank" class="success-link">
                    {{ $t('attachment.file_upload_success') }}
                  </a>
                  <span v-else>{{ $t('attachment.file_upload_success') }}</span>
                </span>
                <span v-else-if="item.status === 'error'" class="status error">
                  <i class="fas fa-times"></i> {{ $t('attachment.file_upload_failed') }}: {{ item.error }}
                </span>
              </div>
            </div>
          </div>

          <!-- 上传控制按钮 -->
          <div v-if="uploadQueue.length > 0 && !isUploading" class="upload-actions">
            <CommonButton @click="clearQueue">{{ $t('attachment.clear_queue') }}</CommonButton>
          </div>

          <!-- 上传进度 -->
          <div v-if="isUploading" class="upload-progress">
            <div class="progress-text">
              {{ $t('attachment.upload_progress') }}: {{ uploadedCount }}/{{ totalCount }}
            </div>
            <a-progress
              :percent="uploadProgress"
              :status="uploadProgress === 100 ? 'success' : 'active'"
            />
          </div>
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import { useUserStore } from '@/store/user'
import request from '@/utils/request'

interface UploadQueueItem {
  file: File
  status: 'waiting' | 'uploading' | 'success' | 'error'
  error: string
  url?: string
}

const { t } = useI18n()
const userStore = useUserStore()

const props = defineProps<{
  onClose: (result: boolean) => void
  callback?: () => void
}>()

const show = ref(false)
const uploadQueue = ref<UploadQueueItem[]>([])
const isUploading = ref(false)
const uploadedCount = ref(0)
const totalCount = ref(0)
const currentUploadIndex = ref(0)

const uploadProgress = computed(() => {
  if (totalCount.value === 0) return 0
  return Math.round((uploadedCount.value / totalCount.value) * 100)
})

// 处理文件选择
const handleBeforeUpload = (file: File, fileList: File[]) => {
  // 检查是否是新添加的文件
  const existingFile = uploadQueue.value.find(
    item => item.file.name === file.name && item.file.size === file.size
  )

  if (!existingFile) {
    uploadQueue.value.push({
      file,
      status: 'waiting',
      error: ''
    })

    // 自动开始上传
    if (!isUploading.value) {
      setTimeout(() => {
        startBatchUpload()
      }, 100)
    }
  }
  return false // 阻止自动上传
}

// 开始批量上传
const startBatchUpload = async () => {
  if (uploadQueue.value.length === 0) return

  // 如果已经在上传中，只需要更新总数
  if (isUploading.value) {
    totalCount.value = uploadQueue.value.filter(
      item => item.status === 'waiting' || item.status === 'uploading' || item.status === 'success'
    ).length
    return
  }

  isUploading.value = true
  uploadedCount.value = 0
  totalCount.value = uploadQueue.value.filter(item => item.status === 'waiting').length
  currentUploadIndex.value = 0

  uploadNextFile()
}

// 上传下一个文件
const uploadNextFile = () => {
  while (currentUploadIndex.value < uploadQueue.value.length) {
    const currentItem = uploadQueue.value[currentUploadIndex.value]
    if (currentItem.status === 'waiting') {
      currentItem.status = 'uploading'
      uploadSingleFile(currentItem.file)
      return
    }
    currentUploadIndex.value++
  }

  // 所有文件上传完成
  isUploading.value = false

  // 显示上传结果
  const successCount = uploadQueue.value.filter(item => item.status === 'success').length
  const errorCount = uploadQueue.value.filter(item => item.status === 'error').length

  if (errorCount === 0) {
    message.success(t('attachment.batch_upload_complete_success', { count: successCount }))
    setTimeout(() => {
      closeHandle(true)
    }, 1500)
  } else {
    message.warning(t('attachment.batch_upload_complete_partial', { success: successCount, error: errorCount }))
  }
}

// 上传单个文件
const uploadSingleFile = async (file: File) => {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('user_token', userStore.userToken)

  try {
    const data = await request('/api/page/upload', formData, 'post', false)
    uploadCallback(data)
  } catch (error) {
    console.error('上传文件失败:', error)
    uploadError(null, file)
  }
}

// 上传成功回调
const uploadCallback = (data: any) => {
  const currentItem = uploadQueue.value[currentUploadIndex.value]
  if (currentItem) {
    if (data.success === 1) {
      currentItem.status = 'success'
      currentItem.url = data.url
    } else {
      currentItem.status = 'error'
      currentItem.error = data.error_message || t('attachment.upload_failed_error')
    }
    uploadedCount.value++
    uploadNextFile()
  }
}

// 格式化文件大小
const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const closeHandle = (result: boolean) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.upload-modal :deep(.modal-content) {
  padding: 20px;
}

.upload-container {
  min-height: 400px;
}

// 上传区域样式
:deep(.ant-upload-drag) {
  background-color: var(--color-bg-secondary);
  border: 2px dashed var(--color-border);
  border-radius: 8px;
  padding: 40px 20px;
  transition: all 0.15s ease;

  &:hover {
    border-color: var(--color-active);
    background-color: var(--color-obvious);
  }
}

:deep(.ant-upload-drag-icon) {
  font-size: 48px;
  color: var(--color-text-secondary) !important;
  margin-bottom: 16px;

  i {
    color: var(--color-active) !important;
  }
}

:deep(.ant-upload-text) {
  color: var(--color-text-primary) !important;
  font-size: var(--font-size-m);
  margin-bottom: 8px;
}

:deep(.ant-upload-hint) {
  color: var(--color-text-secondary) !important;
  font-size: var(--font-size-s);
}

// 上传队列样式
.upload-queue {
  margin-top: 20px;
}

.queue-title {
  margin-bottom: 10px;
  font-weight: 600;
  color: var(--color-text-primary);
}

.queue-list {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 10px;
  background-color: var(--color-bg-secondary);
}

.queue-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--color-border);
  transition: background-color 0.15s ease;

  &:last-child {
    border-bottom: none;
  }

  &.uploading {
    background-color: var(--color-obvious);
  }

  &.success {
    background-color: var(--color-success-bg);
  }

  &.error {
    background-color: var(--color-error-bg);
  }
}

.file-info {
  display: flex;
  align-items: center;
  flex: 1;

  i {
    margin-right: 8px;
    font-size: 16px;
    color: var(--color-primary);
  }

  .file-name {
    font-weight: 500;
    margin-right: 8px;
    color: var(--color-text-primary);
  }

  .file-size {
    color: var(--color-text-secondary);
    font-size: var(--font-size-s);
  }
}

.status-info {
  min-width: 120px;
  text-align: right;
}

.status {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 3px;
  font-size: var(--font-size-s);
  font-weight: 500;

  &.waiting {
    background-color: var(--color-bg-tertiary);
    color: var(--color-text-secondary);
  }

  &.uploading {
    background-color: var(--color-primary-bg);
    color: var(--color-primary);
  }

  &.success {
    background-color: var(--color-success-bg);
    color: var(--color-success);

    i {
      margin-right: 4px;
    }

    .success-link {
      color: var(--color-success);
      text-decoration: underline;

      &:hover {
        color: var(--color-success-hover);
      }
    }
  }

  &.error {
    background-color: var(--color-error-bg);
    color: var(--color-error);

    i {
      margin-right: 4px;
    }
  }
}

.upload-actions {
  margin-top: 15px;
  text-align: center;
}

.upload-progress {
  margin-top: 20px;
  padding: 15px;
  background-color: var(--color-bg-secondary);
  border-radius: 4px;
}

.progress-text {
  margin-bottom: 10px;
  font-size: var(--font-size-s);
  color: var(--color-text-secondary);
  text-align: center;
}

// 暗黑主题适配
[data-theme='dark'] {
  :deep(.ant-upload-drag) {
    background-color: var(--color-bg-secondary);
    border-color: var(--color-border);

    &:hover {
      border-color: var(--color-primary);
      background-color: var(--color-obvious);
    }
  }

  :deep(.ant-upload-text) {
    color: var(--color-text-primary) !important;
  }

  :deep(.ant-upload-hint) {
    color: var(--color-text-secondary) !important;
  }

  // 上传区域图标和文字额外覆盖
  :deep(.ant-upload-drag-icon) {
    color: var(--color-primary) !important;
  }

  // 上传队列和进度条
  .queue-list {
    border-color: var(--color-border);
    background-color: var(--color-bg-secondary);
  }

  .queue-item {
    border-bottom-color: var(--color-border);

    &.uploading {
      background-color: var(--color-obvious);
    }

    &.success {
      background-color: var(--color-success-bg);
    }

    &.error {
      background-color: var(--color-error-bg);
    }
  }

  .file-info .file-name {
    color: var(--color-text-primary);
  }

  .status.waiting {
    background-color: var(--color-bg-tertiary);
    color: var(--color-text-secondary);
  }

  .upload-progress {
    background-color: var(--color-bg-secondary);
  }

  .progress-text {
    color: var(--color-text-secondary);
  }
}
</style>

