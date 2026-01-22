<template>
  <CommonModal
    :show="show"
    :title="$t('page.ai_assistant')"
    @close="handleClose"
    maxWidth="1400px"
    minWidth="1000px"
    :use-line="true"
    :header-buttons="headerButtons"
  >
    <div class="ai-modal">
      <a-row :gutter="24">
        <!-- 输入区 -->
        <a-col :span="12" class="input-section">
          <div class="section-header">
            <h3>{{ $t('page.ai_input_area') }}</h3>
          </div>

          <CommonTextarea
            v-model="inputContent"
            :placeholder="$t('page.ai_input_placeholder')"
            class="ai-textarea"
            style="min-height: 400px;"
          />

          <div class="action-bar">
            <CommonButton
              theme="dark"
              @click="handleGenerate"
              :spinning="generating"
              :disabled="!inputContent.trim() || generating"
              :left-icon="['fas', 'fa-magic']"
            >
              {{ $t('page.ai_generate') }}
            </CommonButton>
            <CommonButton
              theme="light"
              @click="handleHelp"
              type="link"
              :left-icon="['fas', 'fa-question-circle']"
            >
              {{ $t('page.ai_help_text') }}
            </CommonButton>
          </div>
        </a-col>

        <!-- 输出区 -->
        <a-col :span="12" class="output-section">
          <div class="section-header">
            <h3>{{ $t('page.ai_output_area') }}</h3>
          </div>

          <CommonTextarea
            v-model="outputContent"
            :placeholder="$t('page.ai_output_placeholder')"
            class="ai-textarea ai-textarea-readonly"
            style="min-height: 400px;"
            readonly
          />
        </a-col>
      </a-row>
    </div>

    <template #footer>
      <CommonButton @click="handleClose">
        {{ $t('common.close') }}
      </CommonButton>
      <CommonButton
        theme="dark"
        @click="handleInsert"
        :disabled="!outputContent.trim()"
        :left-icon="['fas', 'fa-plus']"
      >
        {{ $t('page.ai_insert_to_editor') }}
      </CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { getUserInfoFromStorage } from '@/models/user'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'
import { getServerHost } from '@/utils/system'

// HeaderButton 接口定义（与 CommonModal 保持一致）
interface HeaderButton {
  text?: string
  icon?: string | string[]
  type?: 'default' | 'primary'
  size?: 'small' | 'middle' | 'large'
  danger?: boolean
  onClick: () => void
}

// Props
interface Props {
  pageId?: number
  itemId?: number
  itemName?: string
  onInsert?: (content: string) => void
  onClose: () => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const inputContent = ref('')
const outputContent = ref('')
const generating = ref(false)

// 头部按钮（复制输出内容）
const headerButtons = computed<HeaderButton[]>(() => [
  {
    icon: ['fas', 'fa-copy'],
    text: t('common.copy'),
    size: 'small',
    onClick: handleCopy,
    disabled: !outputContent.value || !outputContent.value.trim()
  }
])

// 生成内容
const handleGenerate = async () => {
  if (!inputContent.value.trim() || generating.value) {
    return
  }

  outputContent.value = ''
  generating.value = true

  try {
    const jsonBody: any = {
      content: inputContent.value
    }

    // 获取用户 token
    const userInfo = getUserInfoFromStorage()
    if (userInfo && userInfo.user_token) {
      jsonBody.user_token = userInfo.user_token
    }

    // 使用 fetch 实现流式响应
    const url = getServerHost() + '/api/ai/create'
    const response = await fetch(url, {
      method: 'POST',
      body: new URLSearchParams(jsonBody),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    })

    if (!response.ok) {
      throw new Error('AI 生成失败')
    }

    const reader = response.body?.getReader()
    const decoder = new TextDecoder('utf-8')
    let result = ''

    if (reader) {
      const readChunk = async () => {
        const { value, done } = await reader.read()

        if (!done) {
          const dataString = decoder.decode(value)
          const lines = dataString.trim().split('data: ')

          for (const line of lines) {
            if (line.trim() !== '') {
              try {
                const data = JSON.parse(line.replace('data: ', ''))

                if (
                  data.choices &&
                  data.choices[0] &&
                  data.choices[0].delta &&
                  data.choices[0].delta.content
                ) {
                  const content = data.choices[0].delta.content
                  result += content
                  outputContent.value = result
                }

                if (
                  data.choices &&
                  data.choices[0] &&
                  data.choices[0].finish_reason === 'length'
                ) {
                  await readChunk()
                } else if (
                  data.choices &&
                  data.choices[0] &&
                  data.choices[0].finish_reason === 'stop'
                ) {
                  outputContent.value = result
                  generating.value = false
                  return
                }
              } catch (error) {
                if (line.trim() === '[DONE]') {
                  outputContent.value = result
                  generating.value = false
                  return
                }

                // 如果返回的行是usage的情况，则跳过
                if (line.trim().indexOf('usage') > -1) {
                  continue
                }

                // 尝试解析错误信息
                try {
                  const obj = JSON.parse(line.trim())
                  if (obj.error_code !== 0) {
                    await AlertModal(obj.error_message)
                    generating.value = false
                    return
                  }
                } catch (e) {
                  // 忽略解析错误
                }
              }
            }
          }

          // 继续读取下一个 chunk
          await readChunk()
        } else {
          generating.value = false
        }
      }

      await readChunk()
    }
    } catch (error) {
    console.error('AI 生成失败:', error)
    await AlertModal(t('page.ai_generate_failed'))
    generating.value = false
  }
}

// 复制到剪贴板
const handleCopy = () => {
  navigator.clipboard.writeText(outputContent.value).then(() => {
    Message.success(t('common.copy_success'))
  }).catch(() => {
    AlertModal(t('common.copy_failed'))
  })
}

// 打开帮助文档
const handleHelp = () => {
  const helpUrl = t('page.ai_help_url')
  window.open(helpUrl, '_blank')
}

// 插入到编辑器
const handleInsert = () => {
  if (props.onInsert && outputContent.value.trim()) {
    props.onInsert(outputContent.value)
    handleClose()
  }
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}

// 生命周期
onMounted(() => {
  show.value = true
})
</script>

<style lang="scss" scoped>
.ai-modal {
  padding: 0;

  .input-section,
  .output-section {
    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;

      h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 500;
        color: var(--color-text-primary);
      }

      // CommonButton 作为链接按钮时的样式调整
      :deep(.common-button) {
        &.type-link {
          font-weight: normal;
          background: none;
          border: 1px solid var(--color-inactive);
          color: var(--color-primary);

          &:hover {
            color: var(--color-active);
            border-color: var(--color-active);
          }

          .icon {
            margin-right: 4px;
          }
        }
      }
    }

    .action-bar {
      margin-top: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
  }

  .ai-textarea {
    width: 100%;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
    font-size: 13px;
    line-height: 1.6;

    &.ai-textarea-readonly {
      // 只读样式：背景色不变，光标正常，不可编辑
      :deep(textarea) {
        cursor: default;
      }
    }
  }
}
</style>

