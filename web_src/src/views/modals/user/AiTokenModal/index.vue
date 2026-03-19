<template>
  <div class="ai-token-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('user.ai_token_management')"
      :icon="['fas', 'robot']"
      width="700px"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- 说明区域 -->
        <div class="info-section">
          <p class="info-text">
            <i class="fas fa-info-circle"></i>
            {{ $t('user.ai_token_desc') }}
          </p>
        </div>

        <!-- Token 列表 -->
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

        <!-- 配置说明 -->
        <div class="config-section">
          <div class="section-header" @click="showConfigHelp = !showConfigHelp">
            <span>{{ $t('user.config_help') }}</span>
            <i
              :class="[
                'fas',
                showConfigHelp ? 'fa-chevron-up' : 'fa-chevron-down',
              ]"
            ></i>
          </div>
          <div v-if="showConfigHelp" class="config-help">
            <p>{{ $t('user.config_help_desc') }}</p>
            <div class="config-content">
              <pre><code>{{ getConfigExample() }}</code></pre>
              <button class="copy-config-btn" @click="handleCopyConfig">
                <i class="fas fa-copy"></i>
                {{ $t('common.copy') }}
              </button>
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

// 配置帮助展开状态
const showConfigHelp = ref(false)

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

// 获取 Token 列表
const fetchTokens = async () => {
  loading.value = true
  try {
    const data = await request('/api/ai_token/list', {})
    if (data && data.data) {
      tokens.value = data.data.tokens || []
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
  try {
    await navigator.clipboard.writeText(token)
    Message.success(t('common.copy_success'))
  } catch (error) {
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

// 获取配置示例
const getConfigExample = (): string => {
  const token = 'ai_xxxx' // 示例 token
  return generateConfig(token)
}

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

// 复制配置
const handleCopyConfig = async () => {
  try {
    await navigator.clipboard.writeText(getConfigExample())
    Message.success(t('common.copy_success'))
  } catch (error) {
    Message.error(t('common.copy_failed'))
  }
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

  .info-section {
    background: var(--color-active);
    background: rgba(0, 123, 255, 0.08);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;

    .info-text {
      margin: 0;
      font-size: var(--font-size-m);
      color: var(--color-text-secondary);

      i {
        color: var(--color-active);
        margin-right: 8px;
      }
    }
  }

  .token-list {
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

  .config-section {
    margin-top: 24px;
    border-top: 1px solid var(--color-border);
    padding-top: 16px;

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      padding: 8px 0;
      color: var(--color-text-primary);
      font-weight: 500;

      i {
        font-size: 12px;
        color: var(--color-text-secondary);
        transition: transform 0.2s;
      }
    }

    .config-help {
      margin-top: 16px;

      p {
        font-size: var(--font-size-m);
        color: var(--color-text-secondary);
        margin-bottom: 16px;
      }
    }

    .config-content {
      position: relative;

      pre {
        background: var(--color-bg-secondary);
        border-radius: 8px;
        padding: 16px;
        margin: 0;
        overflow-x: auto;

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
}
</style>
