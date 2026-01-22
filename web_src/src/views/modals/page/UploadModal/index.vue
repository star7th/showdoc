<template>
  <div class="page-upload-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('common.upload')"
      :width="'600px'"
      :showOk="false"
      :showCancel="false"
      @close="closeHandle"
    >
      <div style="text-align: center;">
        <!-- 上传区域 -->
        <a-upload
          :file-list="fileList"
          :before-upload="beforeUpload"
          :multiple="true"
          :show-upload-list="false"
          :custom-request="customUpload"
          class="upload-area"
        >
          <div class="upload-content">
            <i class="fas fa-cloud-upload-alt"></i>
            <div class="upload-text">
              <span class="tips-text" v-html="$t('page.import_file_tips2')"></span>
              <div class="batch-upload-support">
                {{ $t('page.batch_upload_support') }}
              </div>
            </div>
          </div>
        </a-upload>

        <!-- 文件队列显示 -->
        <div v-if="uploadQueue.length > 0" style="margin-top: 15px;">
          <div style="margin-bottom: 10px; font-weight: bold;">
            {{ $t('page.upload_queue_files', { count: uploadQueue.length }) }}:
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
                <i class="fas fa-file"></i>
                <span class="file-name">{{ item.file.name }}</span>
                <span class="file-size">({{ formatFileSize(item.file.size) }})</span>
              </div>
              <div class="status-info">
                <span v-if="item.status === 'waiting'" class="status waiting">
                  {{ $t('page.file_waiting') }}
                </span>
                <span
                  v-else-if="item.status === 'uploading'"
                  class="status uploading"
                >
                  <i class="fas fa-spinner fa-spin"></i> {{ $t('page.file_uploading') }}
                </span>
                <span
                  v-else-if="item.status === 'success'"
                  class="status success"
                >
                  <i class="fas fa-check"></i>
                  <a
                    v-if="item.url"
                    :href="item.url"
                    target="_blank"
                    class="upload-success-link"
                  >
                    {{ $t('page.file_upload_success') }}
                  </a>
                  <span v-else>{{ $t('page.file_upload_success') }}</span>
                </span>
                <span v-else-if="item.status === 'error'" class="status error">
                  <i class="fas fa-times"></i> {{ $t('page.file_upload_failed') }}: {{ item.error }}
                </span>
              </div>
            </div>
          </div>

          <!-- 上传控制按钮 -->
          <div
            style="margin-top: 15px; text-align: center;"
            v-if="uploadQueue.length > 0 && !isUploading"
          >
            <CommonButton @click="clearQueue">{{ $t('page.clear_queue') }}</CommonButton>
          </div>

          <!-- 上传进度 -->
          <div v-if="isUploading" style="margin-top: 15px;">
            <div style="margin-bottom: 8px; font-size: 14px;">
              {{ $t('page.upload_progress') }}: {{ uploadedCount }}/{{ totalCount }}
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
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import request from '@/utils/request'

const { t } = useI18n()

const props = defineProps<{
  itemId: number
  pageId: number
  callback: () => void
}>()

const show = ref(false)
const fileList = ref<any[]>([])

// 批量上传相关
const uploadQueue = ref<Array<{
  file: File
  status: 'waiting' | 'uploading' | 'success' | 'error'
  error: string
  url?: string
}>>([])
const isUploading = ref(false)
const uploadedCount = ref(0)
const totalCount = ref(0)
const currentUploadIndex = ref(0)

const uploadProgress = computed(() => {
  if (totalCount.value === 0) return 0
  return Math.round((uploadedCount.value / totalCount.value) * 100)
})

// 文件上传前的处理
const beforeUpload = (file: File) => {
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

  return false // 阻止自动上传，使用自定义上传
}

// 自定义上传
const customUpload = () => {
  // 这个函数是为了满足 a-upload 的要求，实际上传在 startBatchUpload 中处理
}

// 开始批量上传
const startBatchUpload = () => {
  if (uploadQueue.value.length === 0) return

  // 如果已经在上传中，只需要更新总数，不重新开始
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

  // 开始上传第一个文件
  uploadNextFile()
}

// 上传下一个文件
const uploadNextFile = () => {
  // 寻找下一个等待上传的文件
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
    // 全部上传成功，显示消息后关闭对话框
    message.success(`${t('page.batch_upload_complete')}！${t('page.upload_success_count', { count: successCount })}`)
    setTimeout(() => {
      closeHandle()
      resetUpload()
    }, 1500)
  } else {
    message.warning(
      `${t('page.batch_upload_complete')}！${t('page.upload_success_count', { count: successCount })}，${t('page.upload_failed_count', { count: errorCount })}`
    )
  }
}

// 上传单个文件
const uploadSingleFile = async (file: File) => {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('page_id', String(props.pageId))
  formData.append('item_id', String(props.itemId))

  try {
    const data = await request('/api/page/upload', formData, 'post', false)

    const currentItem = uploadQueue.value[currentUploadIndex.value]
    if (currentItem) {
      if (data.success === 1) {
        currentItem.status = 'success'
        currentItem.url = data.url
      } else {
        currentItem.status = 'error'
        currentItem.error = data.message || data.error_message || t('page.upload_failed')
      }
      uploadedCount.value++
    }

    // 继续上传下一个文件
    uploadNextFile()
  } catch (error) {
    console.error('上传文件失败:', error)
    const currentItem = uploadQueue.value[currentUploadIndex.value]
    if (currentItem) {
      currentItem.status = 'error'
      currentItem.error = t('page.upload_failed')
      uploadedCount.value++
    }
    uploadNextFile()
  }
}

// 清空队列
const clearQueue = () => {
  uploadQueue.value = []
  fileList.value = []
}

// 重置上传
const resetUpload = () => {
  clearQueue()
  isUploading.value = false
  uploadedCount.value = 0
  totalCount.value = 0
  currentUploadIndex.value = 0
}

// 格式化文件大小
const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const closeHandle = () => {
  show.value = false
  setTimeout(() => {
    props.callback()
  }, 300)
}

// 组件挂载时显示
setTimeout(() => {
  show.value = true
}, 0)
</script>

<style scoped lang="scss">
.page-upload-modal :deep(.modal-content) {
  padding: 20px;
}

.upload-area {
  width: 100%;
  border: 2px dashed var(--color-border);
  border-radius: 4px;
  background: var(--color-bg-secondary);
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    border-color: var(--color-active);
  }
}

.upload-content {
  padding: 40px 20px;
  text-align: center;

  i {
    font-size: 48px;
    color: var(--color-active);
    margin-bottom: 15px;
  }
}

.upload-text {
  .tips-text {
    color: var(--color-text-secondary);
    font-size: 14px;
  }

  .batch-upload-support {
    margin-top: 5px;
    color: var(--color-text-secondary);
    font-size: 12px;
  }
}

.upload-queue {
  max-height: 300px;
  overflow-y: auto;
  border: 1px solid var(--color-border);
  border-radius: 4px;
  padding: 10px;
  text-align: left;
}

.queue-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid var(--color-border);
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
  color: var(--color-active);
}

.file-name {
  font-weight: 500;
  margin-right: 8px;
}

.file-size {
  color: var(--color-text-tertiary);
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
  background-color: var(--color-info-bg);
  color: var(--color-text-tertiary);
}

.status.uploading {
  background-color: var(--color-active-bg);
  color: var(--color-active);
}

.status.success {
  background-color: var(--color-success-bg);
  color: var(--color-success);
}

.status.error {
  background-color: var(--color-error-bg);
  color: var(--color-error);
}

.queue-item.uploading {
  background-color: var(--color-bg-tertiary);
}

.queue-item.success {
  background-color: var(--color-success-bg);
}

.queue-item.error {
  background-color: var(--color-error-bg);
}

.upload-success-link {
  color: var(--color-success);
}
</style>

