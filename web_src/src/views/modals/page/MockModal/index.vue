<template>
  <CommonModal
    :show="show"
    :title="$t('page.mock_config')"
    width="900px"
    @close="handleClose"
  >
    <div class="mock-config">
      <!-- Mock 响应内容 -->
      <div class="mock-response-section">
        <label class="section-label">{{ $t('page.mock_response') }}</label>
        <p class="section-tips">{{ $t('page.mock_response_tips') }}</p>
        <CommonTextarea
          v-model="mockConfig.content"
          :placeholder="$t('page.mock_response_placeholder')"
          :rows="15"
          class="mock-textarea"
        />
      </div>

      <!-- Mock URL 和路径 -->
      <div class="mock-url-section">
        <label class="section-label">{{ $t('page.mock_url_and_path') }}</label>
        <div class="url-container">
          <code class="mock-url-prefix">{{ mockUrlPre }}</code>
          <CommonInput
            v-model="mockConfig.path"
            class="path-input"
            placeholder="/path"
          />
          <i class="fas fa-copy copy-icon" @click="handleCopyUrl" :title="$t('common.copy')"></i>
        </div>
        <code v-if="fullMockUrl" class="full-mock-url">{{ fullMockUrl }}</code>
      </div>

      <!-- 操作按钮 -->
      <div class="action-buttons">
        <CommonButton
          :text="$t('page.beautify_json')"
          :left-icon="['fas', 'fa-th']"
          @click="handleBeautifyJson"
        />
        <a
          href="https://www.showdoc.com.cn/p/d952ed6b7b5fb454df13dce74d1b41f8"
          target="_blank"
          class="help-link"
        >
          <i class="fas fa-question-circle"></i>
          {{ $t('page.help_document') }}
        </a>
      </div>
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton theme="dark" @click="handleSave">{{ $t('common.save') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import request from '@/utils/request'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'
import { copyToClipboard } from '@/utils/tools'
import { getServerHost } from '@/utils/system'

// Props
interface Props {
  pageId: number
  itemId: number
  onClose: () => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()
const route = useRoute()

// Refs
const show = ref(false)
const mockConfig = ref({
  content: '',
  path: '/'
})
const mockUrlPre = ref('')

// Computed
const fullMockUrl = computed(() => {
  return mockConfig.value.path ? mockUrlPre.value + mockConfig.value.path : ''
})

// Methods
const handleClose = () => {
  props.onClose()
}

const handleSave = async () => {
  try {
    const result = await request('/api/mock/add', {
      page_id: props.pageId,
      template: mockConfig.value.content,
      path: mockConfig.value.path
    }, 'post', false)

    if (result.error_code === 0) {
      Message.success(t('page.save_success'))
      props.onClose()
    } else {
      await AlertModal(result.error_message || t('page.save_failed'))
    }
  } catch (error) {
    console.error('保存 Mock 配置失败:', error)
    await AlertModal(t('page.save_failed'))
  }
}

const handleCopyUrl = async () => {
  if (fullMockUrl.value) {
    const success = await copyToClipboard(fullMockUrl.value)
    if (success) {
      Message.success(t('common.copy_success'))
    }
  }
}

const handleBeautifyJson = () => {
  try {
    const content = mockConfig.value.content.trim()
    if (!content) {
      Message.warning(t('page.please_input_content'))
      return
    }

    // 尝试解析为 JSON
    const jsonObj = JSON.parse(content)
    mockConfig.value.content = JSON.stringify(jsonObj, null, 2)
    Message.success(t('page.beautify_success'))
  } catch (error) {
    AlertModal(t('page.json_format_error'))
  }
}

const loadMockConfig = async () => {
  if (props.pageId <= 0) {
    Message.warning(t('page.please_save_page_first'))
    props.onClose()
    return
  }

  try {
    const result = await request('/api/mock/infoByPageId', {
      page_id: props.pageId
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      if (result.data.unique_key && result.data.template) {
        mockConfig.value.content = unescapeHTML(result.data.template)
        mockConfig.value.path = result.data.path || '/'
      }
    }
  } catch (error) {
    console.error('获取 Mock 配置失败:', error)
  }
}

// HTML 转义反转义函数
const unescapeHTML = (html: string): string => {
  if (!html) return ''
  return html
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
}

// Lifecycle
onMounted(() => {
  show.value = true

  // 构建 Mock URL 前缀
  const baseUrl = window.location.protocol + '//' + window.location.host
  mockUrlPre.value = `${baseUrl}${getServerHost()}mock-path/${props.itemId}?path=`

  loadMockConfig()
})
</script>

<style scoped lang="scss">
.mock-config {
  padding: 10px 0;
}

.mock-response-section {
  margin-bottom: 24px;
}

.section-label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text-primary);
}

.section-tips {
  margin: 0 0 10px 0;
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.6;
}

.mock-textarea {
  min-height: 300px;
}

.mock-url-section {
  margin-bottom: 20px;
}

.url-container {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 10px;
}

.mock-url-prefix {
  padding: 8px 12px;
  background-color: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  font-family: Consolas, Menlo, Monaco, monospace;
  font-size: 13px;
  color: var(--color-text-secondary);
  flex-shrink: 0;
}

.path-input {
  flex: 1;
  min-width: 0;
}

.copy-icon {
  padding: 8px;
  cursor: pointer;
  color: var(--color-text-secondary);
  font-size: 14px;
  transition: color 0.15s ease;

  &:hover {
    color: var(--color-active);
  }
}

.full-mock-url {
  display: block;
  padding: 10px 12px;
  background-color: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  font-family: Consolas, Menlo, Monaco, monospace;
  font-size: 13px;
  color: var(--color-text-primary);
  word-break: break-all;
  line-height: 1.5;
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 16px;
  padding-top: 10px;
  border-top: 1px solid var(--color-border);
}

.help-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: var(--color-active);
  text-decoration: none;
  transition: opacity 0.15s ease;

  &:hover {
    opacity: 0.8;
    text-decoration: underline;
  }

  i {
    font-size: 14px;
  }
}
</style>
