<template>
  <div class="import-file-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('item.import_file')"
      :icon="['fa', 'fa-upload']"
      @close="handleClose"
    >
      <div class="modal-content">
        <p class="tips">
          <span class="tips-text" v-html="$t('item.import_file_tips1')"></span>
        </p>
        <div class="upload-area">
          <a-upload
            :data="uploadData"
            class="upload-demo"
            :action="uploadUrl"
            :before-upload="beforeUpload"
            :show-upload-list="false"
            @change="handleUploadChange"
            :disabled="loading"
            drag
          >
            <div class="upload-content" :class="{ 'is-loading': loading }">
              <i class="fas fa-cloud-upload-alt"></i>
              <div class="upload-text">
                <span v-html="$t('item.import_file_tips2')"></span>
              </div>
              <!-- Loading覆盖层 -->
              <div v-if="loading" class="loading-overlay">
                <i class="fas fa-spinner fa-spin"></i>
                <span>{{ $t('common.uploading') || '文件上传和导入中，请稍候...' }}</span>
              </div>
            </div>
          </a-upload>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="() => handleClose(false)">{{ $t('common.cancel') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import { getUserInfoFromStorage } from '@/models/user'
import { getServerHost } from '@/utils/system'

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  onClose: (result: boolean) => void
  itemId?: number  // 项目ID，用于导入到指定项目
}>()

const show = ref(false)
const loading = ref(false)

// 获取用户信息
const userInfo = getUserInfoFromStorage()
const uploadData = ref({
  user_token: userInfo?.user_token || '',
  item_id: props.itemId || ''  // 如果有项目ID，则添加到上传数据中
})

// 上传 URL
const uploadUrl = computed(() => {
  return getServerHost() + '/api/import/auto'
})

// 上传前校验
const beforeUpload = (_file: File) => {
  loading.value = true  // 文件上传中
  return true
}

// 上传状态改变
const handleUploadChange = (info: any) => {
  if (info.file.status === 'done') {
    // 文件上传完成，后端返回响应
    const response = info.file.response
    if (response && response.error_code === 0) {
      Message.success(t('common.op_success'))
      loading.value = false
      handleClose(true)
    } else {
      loading.value = false
      AlertModal(response?.error_message || t('common.op_failed'))
    }
  } else if (info.file.status === 'error') {
    loading.value = false
    AlertModal(t('common.op_failed'))
  } else if (info.file.status === 'uploading') {
    // 文件正在上传，保持loading状态
    loading.value = true
  }
}

const handleClose = (result: boolean = false) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
    
    // 导入成功后根据是否有 item_id 决定跳转逻辑
    if (result) {
      if (props.itemId && props.itemId > 0) {
        // 如果导入了指定项目，刷新页面
        window.location.reload()
      } else {
        // 如果没有指定项目，使用路由跳转到项目列表页（支持哈希模式）
        router.push({ path: '/item/index' })
      }
    }
  }, 300)
}

onMounted(async () => {
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  width: 450px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
}

.tips {
  color: var(--color-text-primary);
  margin-bottom: 20px;
  line-height: 1.6;
}

.tips-text :deep(b) {
  font-weight: 600;
  color: var(--color-primary);
}

.tips-text :deep(em) {
  font-style: italic;
}

.upload-area {
  margin: 20px 0;
  display: flex;
  justify-content: center;
}

.upload-demo {
  width: 100%;
  max-width: 100%;
  
  :deep(.ant-upload) {
    width: 100%;
    display: block;
  }
  
  :deep(.ant-upload-drag) {
    width: 100%;
    display: block;
  }
}

.upload-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  border: 2px dashed var(--color-border);
  border-radius: 8px;
  background-color: var(--color-bg-secondary);
  transition: all 0.15s ease;
  position: relative;
  min-height: 180px;

  &:hover {
    border-color: var(--color-primary);
    background-color: var(--color-bg-primary);
  }

  &.is-loading {
    pointer-events: none;
    opacity: 0.7;

    .fas,
    .upload-text {
      opacity: 0.3;
    }
  }

  .fas {
    font-size: 48px;
    color: var(--color-text-secondary);
    margin-bottom: 12px;
  }

  .upload-text {
    color: var(--color-text-primary);
    text-align: center;
    line-height: 1.6;

    :deep(em) {
      color: var(--color-primary);
      font-style: italic;
    }
  }

  .loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    z-index: 10;
    gap: 12px;

    i {
      font-size: 32px;
      color: var(--color-primary);
      animation: spin 1s linear infinite;
    }

    span {
      font-size: 14px;
      color: var(--color-text-primary);
      font-weight: 500;
    }
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .secondary-button {
    width: 160px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: var(--font-size-m);
    font-weight: bold;
    cursor: pointer;
    background-color: var(--color-obvious);
    color: var(--color-primary);
    white-space: nowrap;
    margin: 0 7.5px;

    &:hover {
      background-color: var(--color-secondary);
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] .tips {
  color: var(--color-text-primary);
}

[data-theme='dark'] .upload-content {
  background-color: var(--color-bg-secondary);

  &:hover {
    background-color: var(--color-bg-primary);
  }
}

// 旋转动画
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
