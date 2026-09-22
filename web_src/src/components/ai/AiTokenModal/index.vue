<template>
  <div class="ai-token-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.ai_access_title')"
      :icon="['fas', 'plug']"
      width="min(860px, calc(100vw - 32px))"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- ① 上半部分：左右两栏（窄屏回退为上下堆叠） -->
        <div class="main-layout">
          <!-- 左栏：定位句 + 两步引导 -->
          <div class="left-col">
            <!-- ② 两步引导区（定位句并入向导头部，不再单独做色块） -->
            <div class="steps-section">
              <p class="lead-text">
                {{ $t('user.ai_access_desc') }}
              </p>

              <!-- 第 1 步：创建令牌 -->
              <div class="step-item" :class="{ done: hasToken }">
                <div class="step-marker">
                  <span class="step-num">
                    <i v-if="hasToken" class="fas fa-check"></i>
                    <template v-else>1</template>
                  </span>
                  <span class="step-line" aria-hidden="true"></span>
                </div>
                <div class="step-content">
                  <div class="step-head">
                    <span class="step-title">{{ $t('user.ai_access_step1') }}</span>
                    <span v-if="hasToken" class="step-done">
                      <i class="fas fa-check"></i>
                      {{ $t('user.ai_access_step_done') }}
                    </span>
                  </div>
                  <div v-if="!hasToken" class="step-body">
                    <p class="step-desc">{{ $t('user.ai_access_step1_desc') }}</p>
                    <CommonButton
                      :text="$t('user.create_token')"
                      theme="dark"
                      :left-icon="['fas', 'plus']"
                      @click="handleCreate"
                    />
                  </div>
                </div>
              </div>

              <!-- 第 2 步：复制配置到编辑器 -->
              <div class="step-item">
                <div class="step-marker">
                  <span class="step-num">2</span>
                  <span class="step-line" aria-hidden="true"></span>
                </div>
                <div class="step-content">
                  <div class="step-head">
                    <span class="step-title">{{ $t('user.ai_access_step2') }}</span>
                  </div>
                  <div class="step-body">
                    <p class="step-desc">{{ $t('user.ai_access_step2_desc') }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 右栏：配置 JSON + 复制按钮 + 教程链接 -->
          <div class="right-col">
            <div class="config-content">
              <pre><code>{{ configJson }}</code></pre>
              <div class="copy-config-btn" @click="handleCopyConfig">
                <i class="fas fa-copy"></i>
                {{ $t('common.copy') }}
              </div>
            </div>

            <div class="footer-link" @click="openTutorial">
              <i class="fas fa-book-open"></i>
              {{ $t('user.ai_access_full_tutorial') }}
              <i class="fas fa-arrow-up-right-from-square"></i>
            </div>
          </div>
        </div>

        <!-- ③ 令牌管理区 -->
        <div class="token-list">
          <div class="list-header">
            <span class="header-label">{{ $t('user.my_tokens') }}</span>
            <CommonButton
              :text="$t('user.create_token')"
              theme="dark"
              :left-icon="['fas', 'plus']"
              @click="handleCreate"
            />
          </div>

          <div v-if="loading" class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            {{ $t('common.loading') }}
          </div>

          <div v-else-if="tokens.length === 0" class="empty-state">
            <i class="fas fa-key"></i>
            <p>{{ $t('user.no_tokens') }}</p>
          </div>

          <div v-else class="token-items">
            <div v-for="token in tokens" :key="token.id" class="token-item">
              <div class="token-info">
                <div class="token-name">
                  {{ token.name || $t('user.unnamed_token') }}
                </div>
                <div class="token-value">
                  <code>{{ token.token_preview }}</code>
                </div>
                <div class="token-meta">
                  <span class="meta-item">
                    <i class="fas fa-calendar"></i>
                    {{ formatDate(token.created_at) }}
                  </span>
                  <span v-if="token.last_used_at" class="meta-item">
                    <i class="fas fa-clock"></i>
                    {{ $t('user.last_used') }}:
                    {{ formatDate(token.last_used_at) }}
                  </span>
                </div>
              </div>
              <div class="token-actions">
                <span
                  class="action-icon-btn copy"
                  :title="$t('common.copy')"
                  @click="handleCopy(token.token)"
                >
                  <i class="fas fa-copy"></i>
                </span>
                <span
                  class="action-icon-btn edit"
                  :title="$t('common.edit')"
                  @click="handleEdit(token)"
                >
                  <i class="fas fa-edit"></i>
                </span>
                <span
                  class="action-icon-btn delete"
                  :title="$t('common.delete')"
                  @click="handleDelete(token)"
                >
                  <i class="fas fa-trash"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import ConfirmModal from '@/components/ConfirmModal'
import Message from '@/components/Message'
import request from '@/utils/request'
import { getBaseUrl } from '@/utils/system'
import { useUserStore } from '@/store/user'
import { copyToClipboard } from '@/utils/tools'
import CreateTokenModal from './CreateTokenModal'

interface Token {
  id: string
  name: string
  token: string
  token_preview: string
  item_id: string | null
  permission: string
  created_at: string
  last_used_at: string | null
}

interface Project {
  item_id: string
  item_name: string
}

const { t } = useI18n()
const userStore = useUserStore()

const props = defineProps<{
  onClose: (result: boolean) => void
}>()

// 主弹窗显示状态
const show = ref(false)

// Token 列表
const tokens = ref<Token[]>([])
const loading = ref(false)

// 项目列表
const projects = ref<Project[]>([])

// MCP 教程地址（开源版与主版共用同一份公开教程文档）
const tutorialUrl =
  'https://www.showdoc.com.cn/p/d974cb91609ffecba40153354794bd75'

// MCP 服务端地址（考虑二级目录场景）
// 开源版部署方式：前端在 /web/ 目录下，MCP 入口在根目录
// 例如：http://127.0.0.1/showdoc/web/#/ → http://127.0.0.1/showdoc/mcp.php
const mcpServerUrl = computed(() => {
  const origin = window.location.origin
  const pathname = window.location.pathname

  // 从 pathname 中提取基础路径
  // 例如：/showdoc/web/index.html 或 /showdoc/web/ → /showdoc
  // 例如：/web/ 或 /web/index.html → '' (根目录)
  let basePath = ''

  // 查找 /web/ 目录的位置，截取之前的部分作为基础路径
  const webIndex = pathname.indexOf('/web/')
  if (webIndex > 0) {
    // 有二级目录，如 /showdoc/web/
    basePath = pathname.substring(0, webIndex)
  } else if (pathname.startsWith('/web/')) {
    // 没有二级目录，直接是 /web/
    basePath = ''
  } else {
    // 其他情况，尝试使用 getBaseUrl 作为后备
    basePath = getBaseUrl().replace(/^\.\./, '').replace(/\/$/, '')
  }

  return `${origin}${basePath}/mcp.php`
})

// 是否已有令牌（用于第 1 步状态展示）
const hasToken = computed(() => tokens.value.length > 0)

// 生成配置
const generateConfig = (token: string): string => {
  const url = mcpServerUrl.value

  // 统一的 MCP 配置示例
  return JSON.stringify(
    {
      mcpServers: {
        showdoc: {
          type: 'streamable-http',
          url: url,
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      },
    },
    null,
    2,
  )
}

// 第 2 步展示的配置 JSON：有令牌用真实令牌，否则用占位符
const configJson = computed(() => {
  const realToken = tokens.value[0]?.token
  return generateConfig(realToken || 'YOUR_TOKEN_HERE')
})

// 获取 Token 列表
const fetchTokens = async () => {
  loading.value = true
  try {
    const data = await request('/api/ai_token/list', {})
    if (data && data.data) {
      tokens.value = data.data.tokens || []
      // 同步 store 中的令牌状态（控制顶部图标红点）
      userStore.setAiTokenStatus(tokens.value.length > 0)
    }
  } catch (error) {
    console.error('获取 Token 列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 获取项目列表
const fetchProjects = async () => {
  try {
    const data = await request('/api/item/myList', {})
    if (data && data.data) {
      projects.value = data.data || []
    }
  } catch (error) {
    console.error('获取项目列表失败:', error)
  }
}

// 格式化日期
const formatDate = (dateStr: string): string => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString()
}

// 创建 Token
const handleCreate = async () => {
  const result = await CreateTokenModal({
    projects: projects.value,
  })
  if (result.success) {
    fetchTokens()
  }
}

// 编辑 Token
const handleEdit = async (token: Token) => {
  const result = await CreateTokenModal({
    editTokenId: token.id,
    projects: projects.value,
  })
  if (result.success) {
    fetchTokens()
  }
}

// 复制 Token
const handleCopy = async (token: string) => {
  const ok = await copyToClipboard(token)
  if (ok) {
    Message.success(t('common.copy_success'))
  } else {
    Message.error(t('common.copy_failed'))
  }
}

// 删除 Token
const handleDelete = async (token: Token) => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm'),
    msg: t('user.delete_token_confirm', {
      name: token.name || t('user.unnamed_token'),
    }),
    confirmText: t('common.confirm'),
    cancelText: t('common.cancel'),
  })

  if (confirmed) {
    try {
      await request('/api/ai_token/delete', { id: token.id })
      Message.success(t('user.delete_success'))
      fetchTokens()
    } catch (error) {
      console.error('删除 Token 失败:', error)
    }
  }
}

// 复制配置
const handleCopyConfig = async () => {
  const ok = await copyToClipboard(configJson.value)
  if (ok) {
    Message.success(t('common.copy_success'))
  } else {
    Message.error(t('common.copy_failed'))
  }
}

// 打开完整教程
const openTutorial = () => {
  window.open(tutorialUrl, '_blank')
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(true)
  }, 300)
}

onMounted(() => {
  show.value = true
  fetchTokens()
  fetchProjects()
})
</script>

<style lang="scss" scoped>
.ai-token-modal {
  .modal-content {
    padding: 24px;
  }

  // 上半部分：左右两栏，窄屏回退上下堆叠
  .main-layout {
    display: flex;
    gap: 24px;
    align-items: stretch;
    margin-bottom: 20px;
  }

  .left-col {
    flex: 0 0 320px;
    min-width: 0;
    display: flex;
    flex-direction: column;
  }

  .right-col {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
  }

  @media (max-width: 768px) {
    .main-layout {
      flex-direction: column;
    }

    .left-col {
      flex: none;
    }
  }

  .steps-section {
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 16px 16px 4px;
    background: var(--color-bg-secondary);
    border-radius: 8px;

    .lead-text {
      margin: 0 0 16px;
      font-size: var(--font-size-m);
      line-height: 1.6;
      color: var(--color-text-secondary);
    }
  }

  .step-item {
    display: flex;
    align-items: stretch;
    gap: 12px;

    // 已完成步骤：序号圆点变绿
    &.done .step-num {
      background: var(--color-success);
    }

    // 序号列：圆点 + 竖向连接线（timeline 风格）
    .step-marker {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex-shrink: 0;
    }

    .step-num {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: var(--color-active);
      color: #fff;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .step-line {
      flex: 1;
      width: 2px;
      min-height: 12px;
      margin-top: 4px;
      background: var(--color-inactive);
      border-radius: 1px;
    }

    &:last-child .step-line {
      display: none;
    }

    .step-content {
      flex: 1;
      min-width: 0;
      padding-bottom: 20px;
    }

    .step-head {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 6px;
      // 与 24px 序号圆点垂直居中
      min-height: 24px;
    }

    .step-title {
      font-size: 14px;
      font-weight: 600;
      color: var(--color-text-primary);
      line-height: 1.4;
    }

    .step-done {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      color: var(--color-success);

      i {
        font-size: 11px;
      }
    }

    .step-body {
      padding-left: 2px;
    }

    .step-desc {
      font-size: 13px;
      color: var(--color-text-secondary);
      margin: 0 0 12px;
      line-height: 1.6;
    }
  }

  .token-list {
    border-top: 1px solid var(--color-border);
    padding-top: 16px;

    .list-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;

      .header-label {
        font-size: 15px;
        font-weight: 500;
        color: var(--color-text-primary);
      }
    }

    .loading-state,
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: var(--color-text-secondary);

      i {
        font-size: 40px;
        margin-bottom: 12px;
        display: block;
        opacity: 0.5;
      }
    }
  }

  .token-items {
    max-height: 300px;
    overflow-y: auto;
    .token-item {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 16px;
      background: var(--color-bg-secondary);
      border-radius: 8px;
      margin-bottom: 12px;
      transition: background 0.15s;

      &:hover {
        background: var(--color-active);
        background: rgba(0, 123, 255, 0.05);
      }

      .token-info {
        flex: 1;
        min-width: 0;

        .token-name {
          font-size: 15px;
          font-weight: 500;
          color: var(--color-text-primary);
          margin-bottom: 4px;
        }

        .token-value {
          margin-bottom: 8px;

          code {
            font-family: 'SF Mono', Monaco, Consolas, monospace;
            font-size: 12px;
            background: var(--color-bg-tertiary);
            padding: 2px 6px;
            border-radius: 4px;
            color: var(--color-text-secondary);
          }
        }

        .token-meta {
          display: flex;
          flex-wrap: wrap;
          gap: 16px;

          .meta-item {
            font-size: 12px;
            color: var(--color-text-tertiary);

            i {
              margin-right: 4px;
            }
          }
        }
      }

      .token-actions {
        display: flex;
        gap: 8px;
        margin-left: 16px;

        .action-icon-btn {
          width: 32px;
          height: 32px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 6px;
          cursor: pointer;
          transition: all 0.15s;
          color: var(--color-text-secondary);

          &:hover {
            background: var(--color-active);
            color: var(--color-active);
          }

          &.delete:hover {
            background: #dc3545;
            color: #fff;
          }

          i {
            font-size: 14px;
          }
        }
      }
    }
  }

  .footer-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
    padding: 10px;
    font-size: 13px;
    color: var(--color-text-secondary);
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s;

    &:hover {
      color: var(--color-active);
      background: rgba(0, 123, 255, 0.05);
    }
  }

  .config-content {
    position: relative;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;

    pre {
      background: var(--color-bg-secondary);
      border-radius: 8px;
      padding: 16px;
      margin: 0;
      overflow: auto;
      flex: 1;
      max-height: 320px;

      code {
        font-family: 'SF Mono', Monaco, Consolas, monospace;
        font-size: 13px;
        color: var(--color-text-primary);
      }
    }

    .copy-config-btn {
      position: absolute;
      top: 12px;
      right: 12px;
      padding: 6px 12px;
      background: var(--color-bg-tertiary);
      border: 1px solid var(--color-border);
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
      color: var(--color-text-secondary);
      transition: all 0.15s;

      &:hover {
        background: var(--color-active);
        border-color: var(--color-active);
        color: #fff;
      }

      i {
        margin-right: 4px;
      }
    }
  }
}
</style>
