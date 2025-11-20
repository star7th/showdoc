<template>
  <div class="ai-knowledge-base">
    <el-form label-width="120px" class="infoForm">
      <!-- 功能开关区域 -->
      <el-form-item>
        <el-checkbox v-model="aiConfig.enabled" :disabled="!systemAiEnabled">
          {{ $t('ai_enable_for_project') }}
        </el-checkbox>
        <el-tooltip :content="$t('ai_enable_for_project_tips')" placement="top">
          <i class="el-icon-question form-tooltip-icon"></i>
        </el-tooltip>
        <div class="form-item-desc" v-if="systemAiEnabled">
          {{ $t('ai_enable_for_project_tips') }}
        </div>
      </el-form-item>

      <!-- 提示信息 -->
      <el-form-item v-if="!systemAiEnabled">
        <el-alert
          type="warning"
          :closable="false"
          show-icon
          :title="$t('ai_system_not_configured')"
        >
        </el-alert>
      </el-form-item>

      <el-form-item v-else-if="!aiConfig.enabled">
        <el-alert
          type="info"
          :closable="false"
          show-icon
          :title="$t('ai_project_not_enabled')"
        >
        </el-alert>
      </el-form-item>

      <!-- AI 配置（仅在系统级已配置时显示） -->
      <template v-if="systemAiEnabled">
        <el-divider content-position="left">{{ $t('ai_config') }}</el-divider>

        <div class="ai-config-section">
          <!-- 对话框默认状态 -->
          <el-form-item :label="$t('ai_dialog_expanded')">
            <el-checkbox v-model="dialogExpanded">
              {{ $t('ai_dialog_expanded_tips') }}
            </el-checkbox>
          </el-form-item>

          <!-- 欢迎语 -->
          <el-form-item :label="$t('ai_welcome_message')">
            <el-input
              v-model="aiConfig.welcome_message"
              type="textarea"
              :rows="3"
              :placeholder="$t('ai_welcome_message_placeholder')"
            ></el-input>
            <div class="form-item-desc">
              {{ $t('ai_welcome_message_tips') }}
            </div>
          </el-form-item>
        </div>

        <!-- 保存按钮（统一保存所有配置：启用状态 + 对话框折叠 + 欢迎语） -->
        <el-form-item>
          <el-button type="primary" @click="saveAiConfig">
            {{ $t('save') }}
          </el-button>
        </el-form-item>
      </template>

      <!-- 索引管理（仅在系统级已配置且项目已保存为启用状态时显示） -->
      <template v-if="systemAiEnabled && itemAiEnabled">
        <el-divider content-position="left">{{
          $t('ai_index_management')
        }}</el-divider>
        <!-- 索引进行中的醒目提示 -->
        <el-form-item v-if="indexStatus.status === 'indexing'">
          <el-alert
            type="info"
            :closable="false"
            show-icon
            :title="$t('ai_indexing_alert_title')"
          >
            <div slot="default" class="indexing-alert-content">
              <p>{{ $t('ai_indexing_alert_message') }}</p>
              <div
                class="indexing-progress-info"
                v-if="indexStatus.document_count !== undefined"
              >
                <i class="el-icon-loading"></i>
                <span>{{
                  $t('ai_indexing_progress_info').replace(
                    '{count}',
                    indexStatus.document_count
                  )
                }}</span>
                <span
                  v-if="countIncreased && indexStatus.document_count > lastDocumentCount"
                  class="count-increase-badge"
                >
                  +{{ indexStatus.document_count - lastDocumentCount }}
                </span>
              </div>
            </div>
          </el-alert>
        </el-form-item>

        <!-- 索引状态信息 -->
        <el-form-item>
          <div class="index-status-info">
            <div class="status-row">
              <span class="status-label">{{ $t('ai_index_status') }}：</span>
              <div v-if="indexStatus.status === 'indexed'" class="status-item">
                <i class="el-icon-success status-icon success"></i>
                <span>{{ $t('ai_indexed') }}</span>
              </div>
              <div
                v-else-if="indexStatus.status === 'indexing'"
                class="status-item"
              >
                <i class="el-icon-loading status-icon loading"></i>
                <span>{{ $t('ai_indexing') }}</span>
              </div>
              <div
                v-else-if="indexStatus.status === 'not_configured'"
                class="status-item"
              >
                <i class="el-icon-warning status-icon warning"></i>
                <span>{{ $t('ai_not_configured') }}</span>
              </div>
              <div v-else class="status-item">
                <i class="el-icon-info status-icon info"></i>
                <span>{{ $t('ai_unknown_status') }}</span>
              </div>
            </div>

            <div
              class="status-row"
              v-if="indexStatus.document_count !== undefined"
            >
              <span class="status-label">{{ $t('ai_chunk_count') }}：</span>
              <span
                class="status-value"
                :class="{ 'count-updated': countIncreased && indexStatus.status === 'indexing' }"
                >{{ indexStatus.document_count }} {{ $t('ai_chunks') }}</span
              >
              <span
                v-if="countIncreased && indexStatus.status === 'indexing' && indexStatus.document_count > lastDocumentCount"
                class="count-increase-indicator"
              >
                (+{{ indexStatus.document_count - lastDocumentCount }})
              </span>
            </div>

            <div class="status-row" v-if="indexStatus.last_update_time">
              <span class="status-label">{{ $t('ai_last_update') }}：</span>
              <span class="status-value">{{
                formatTime(indexStatus.last_update_time)
              }}</span>
            </div>
          </div>
        </el-form-item>

        <!-- 索引完成提示 -->
        <el-form-item
          v-if="
            indexStatus.status === 'indexed' && indexStatus.document_count > 0
          "
        >
          <el-alert
            type="success"
            :closable="false"
            show-icon
            :title="$t('ai_indexed_ready_title')"
          >
            <div slot="default">
              {{ $t('ai_indexed_ready_message') }}
            </div>
          </el-alert>
        </el-form-item>

        <!-- 操作按钮 -->
        <el-form-item>
          <el-button
            :loading="rebuilding"
            @click="rebuildIndex"
            :disabled="indexStatus.status === 'not_configured'"
          >
            {{ $t('ai_rebuild_index') }}
          </el-button>
          <el-button @click="refreshStatus">{{ $t('refresh') }}</el-button>
        </el-form-item>

        <!-- 索引日志 -->
        <el-form-item v-if="indexLogs.length > 0">
          <div class="index-logs-wrapper">
            <div class="index-logs-title">{{ $t('ai_index_log') }}</div>
            <div class="index-logs">
              <div
                v-for="(log, index) in indexLogs"
                :key="index"
                class="log-item"
              >
                <span class="log-time">{{ formatTime(log.time) }}</span>
                <span class="log-action">{{ log.action }}</span>
                <span class="log-page">{{ log.page_title }}</span>
              </div>
            </div>
          </div>
        </el-form-item>
      </template>
    </el-form>
  </div>
</template>

<script>
import request from '@/request'

export default {
  name: 'AiKnowledgeBase',
  props: {
    // 项目ID（可选，如果提供则使用，否则从路由获取）
    itemId: {
      type: [Number, String],
      default: null
    }
  },
  data() {
    return {
      item_id: 0, // 真实的项目ID（从info接口获取）
      itemAiEnabled: false,
      systemAiEnabled: false,
      aiConfig: {
        enabled: false,
        dialog_collapsed: true,
        welcome_message: ''
      },
      indexStatus: {
        status: 'unknown',
        document_count: 0,
        last_update_time: null
      },
      rebuilding: false,
      indexLogs: [],
      pollingTimer: null, // 轮询定时器
      pollingCount: 0, // 轮询次数（用于超时检测）
      maxPollingCount: 120, // 最大轮询次数（120次 * 5秒 = 10分钟超时）
      needRefresh: false, // 是否需要刷新页面（从未启用变为启用时设置）
      lastDocumentCount: 0, // 上一次的文档数量（用于显示增长）
      countIncreased: false // 文档数量是否刚增长（用于动画效果）
    }
  },
  computed: {
    // 对话框是否默认展开（与 dialog_collapsed 相反）
    dialogExpanded: {
      get() {
        return !this.aiConfig.dialog_collapsed
      },
      set(value) {
        this.aiConfig.dialog_collapsed = !value
      }
    }
  },
  mounted() {
    // 如果通过 prop 传入了 item_id，先设置 item_id，然后加载项目信息
    if (this.itemId) {
      this.item_id = Number(this.itemId)
      // 仍然调用 loadItemInfo 来获取完整的项目信息（包括可能的配置信息）
      this.loadItemInfo().then(() => {
        this.loadAiConfig()
        this.loadIndexStatus()
      })
    } else {
      // 否则从路由获取
      this.loadItemInfo().then(() => {
        this.loadAiConfig()
        this.loadIndexStatus()
      })
    }
  },
  beforeDestroy() {
    // 组件销毁时清理轮询定时器
    this.stopPolling()
  },
  methods: {
    async loadItemInfo() {
      try {
        // 优先使用 prop 传入的 item_id，否则从路由获取
        const route_item_id =
          this.itemId ||
          (this.$route && this.$route.params && this.$route.params.item_id)
        if (!route_item_id) {
          console.error('无法获取项目ID')
          return
        }
        // 通过项目信息接口获取项目配置（info接口支持自定义域名，会返回真实的item_id）
        const res = await request('/api/item/info', {
          item_id: route_item_id
        })

        if (res.error_code === 0 && res.data) {
          // 保存真实的项目ID（info接口返回的item_id是数字）
          this.item_id = res.data.item_id
          // 兼容旧接口返回的字段
          if (res.data.ai_config) {
            this.aiConfig = {
              enabled: res.data.ai_config.enabled > 0,
              dialog_collapsed: res.data.ai_config.dialog_collapsed > 0,
              welcome_message: res.data.ai_config.welcome_message || ''
            }
            this.itemAiEnabled = res.data.ai_config.enabled > 0
          } else {
            this.itemAiEnabled = res.data.ai_knowledge_base_enabled > 0
            this.aiConfig.enabled = res.data.ai_knowledge_base_enabled > 0
          }
        }
      } catch (error) {
        console.error('加载项目信息失败:', error)
      }
    },
    async loadIndexStatus() {
      if (!this.item_id) {
        // 如果还没有获取到真实的item_id，先加载项目信息
        await this.loadItemInfo()
      }
      if (!this.item_id) {
        console.error('无法获取项目ID')
        return
      }
      try {
        const res = await request('/api/ai/getIndexStatus', {
          item_id: this.item_id
        })

        if (res.error_code === 0 && res.data) {
          this.indexStatus = res.data
          // 根据返回的状态判断系统级是否配置
          // 如果返回 not_configured 且消息是"AI 服务未配置"，说明系统级未配置
          if (
            res.data.status === 'not_configured' &&
            res.data.message &&
            res.data.message.indexOf('AI 服务未配置') > -1
          ) {
            this.systemAiEnabled = false
          } else {
            this.systemAiEnabled = true
          }
        }
      } catch (error) {
        console.error('加载索引状态失败:', error)
        // 如果请求失败，可能是系统未配置
        this.systemAiEnabled = false
      }
    },
    async loadAiConfig() {
      if (!this.item_id) {
        await this.loadItemInfo()
      }
      if (!this.item_id) {
        return
      }
      try {
        const res = await request('/api/item/getAiKnowledgeBaseConfig', {
          item_id: this.item_id
        })

        if (res.error_code === 0 && res.data) {
          // 确保数据类型正确（checkbox需要布尔值）
          this.aiConfig = {
            enabled: res.data.enabled > 0,
            dialog_collapsed: res.data.dialog_collapsed > 0,
            welcome_message: res.data.welcome_message || ''
          }
          this.itemAiEnabled = res.data.enabled > 0
        }
      } catch (error) {
        console.error('加载AI配置失败:', error)
      }
    },
    async saveAiConfig() {
      if (!this.item_id) {
        this.$message.error('无法获取项目ID')
        return
      }
      try {
        const res = await request('/api/item/setAiKnowledgeBaseConfig', {
          item_id: this.item_id,
          enabled: this.aiConfig.enabled ? 1 : 0,
          dialog_collapsed: this.aiConfig.dialog_collapsed ? 1 : 0,
          welcome_message: this.aiConfig.welcome_message
        })

        if (res.error_code === 0) {
          this.$message.success(this.$t('save_success'))
          // 更新本地状态（已保存的状态）
          const wasEnabled = this.itemAiEnabled
          this.itemAiEnabled = this.aiConfig.enabled
          // 重新加载配置以确保数据同步
          await this.loadAiConfig()
          // 如果是从未启用变为启用，标记需要刷新页面（但不立即刷新，等对话框关闭时刷新）
          if (!wasEnabled && this.aiConfig.enabled) {
            this.needRefresh = true
            // 通知父组件需要刷新
            this.$emit('need-refresh')
          }
          // 如果启用了（无论是新启用还是之前已启用），自动触发索引构建
          if (this.aiConfig.enabled) {
            // 刷新索引状态
            await this.loadIndexStatus()
            // 如果之前未启用，现在启用了，或者索引状态是未配置，则触发索引构建
            if (!wasEnabled || this.indexStatus.status === 'not_configured') {
              // 延迟一下再触发索引，确保状态已更新
              setTimeout(() => {
                this.startIndexing()
              }, 500)
            }
          }
        } else {
          this.$message.error(res.error_message || this.$t('save_failed'))
          // 保存失败，恢复配置
          await this.loadAiConfig()
        }
      } catch (error) {
        console.error('保存配置失败:', error)
        this.$message.error(this.$t('save_failed'))
        // 保存失败，恢复配置
        await this.loadAiConfig()
      }
    },
    // 直接触发索引构建（不显示确认对话框）
    async startIndexing() {
      if (!this.item_id) {
        this.$message.error('无法获取项目ID')
        return
      }
      this.rebuilding = true
      // 先停止之前的轮询（如果有）
      this.stopPolling()

      try {
        const res = await request('/api/ai/rebuildIndex', {
          item_id: this.item_id
        })

        if (res.error_code === 0) {
          // 显示开始消息
          this.$message({
            message: this.$t('ai_rebuild_started'),
            type: 'info',
            duration: 3000
          })
          // 更新状态为索引中
          this.indexStatus.status = 'indexing'
          // 重置轮询计数和文档数量
          this.pollingCount = 0
          this.lastDocumentCount = 0
          this.countIncreased = false
          // 立即开始轮询状态
          this.startPolling()
        } else {
          this.$message.error(res.error_message || this.$t('ai_rebuild_failed'))
          this.rebuilding = false
        }
      } catch (error) {
        console.error('索引构建失败:', error)
        this.$message.error(this.$t('ai_rebuild_failed'))
        this.rebuilding = false
      }
    },
    // 开始轮询索引状态
    startPolling() {
      // 清除之前的定时器
      if (this.pollingTimer) {
        clearInterval(this.pollingTimer)
      }
      // 立即检查一次状态
      this.pollIndexStatus()
      // 每5秒轮询一次
      this.pollingTimer = setInterval(() => {
        this.pollIndexStatus()
      }, 5000)
    },
    // 停止轮询
    stopPolling() {
      if (this.pollingTimer) {
        clearInterval(this.pollingTimer)
        this.pollingTimer = null
      }
      this.pollingCount = 0
      this.countIncreased = false
    },
    // 轮询检查索引状态
    async pollIndexStatus() {
      // 检查是否超过最大轮询次数（防止无限轮询）
      this.pollingCount++
      if (this.pollingCount > this.maxPollingCount) {
        this.stopPolling()
        this.rebuilding = false
        this.$message.warning('索引构建超时，请手动刷新状态')
        return
      }

      try {
        const res = await request('/api/ai/getIndexStatus', {
          item_id: this.item_id
        })

        if (res.error_code === 0 && res.data) {
          const newStatus = res.data

          // 如果状态是 error，停止轮询
          if (newStatus.status === 'error') {
            this.indexStatus = newStatus
            this.stopPolling()
            this.rebuilding = false
            this.$message.error(newStatus.message || this.$t('ai_index_failed'))
            return
          }

          // 根据服务端返回的 status 字段判断索引状态
          // status 可能的值：'indexed'（已完成）、'indexing'（进行中）、'not_indexed'（未索引）
          if (newStatus.status === 'indexed') {
            // 索引已完成
            this.indexStatus = newStatus
            this.lastDocumentCount = newStatus.document_count || 0
            this.countIncreased = false
            this.stopPolling()
            this.rebuilding = false
            this.$message.success(this.$t('ai_index_completed'))
            return
          } else if (newStatus.status === 'indexing') {
            // 索引进行中，检查文档数量是否增长
            const currentCount = newStatus.document_count || 0
            const previousCount = this.indexStatus.document_count || 0

            // 如果文档数量增加了，显示增长动画
            if (currentCount > previousCount) {
              this.countIncreased = true
              this.lastDocumentCount = previousCount
              // 1秒后取消增长动画效果
              setTimeout(() => {
                this.countIncreased = false
              }, 1000)
            } else {
              this.countIncreased = false
            }

            // 更新状态信息并继续轮询
            this.indexStatus = newStatus
            // 继续轮询
          } else {
            // 其他状态（not_indexed 等），更新状态但继续轮询（可能任务刚提交）
            this.indexStatus = {
              ...newStatus,
              status: 'indexing' // 如果服务端返回 not_indexed 但任务已提交，保持 indexing 状态
            }
            this.countIncreased = false
          }
        }
      } catch (error) {
        console.error('轮询索引状态失败:', error)
        // 轮询失败不停止，继续尝试（可能是网络临时问题）
      }
    },
    async rebuildIndex() {
      this.$confirm(this.$t('ai_rebuild_confirm'), this.$t('tip'), {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      })
        .then(async () => {
          await this.startIndexing()
        })
        .catch(() => {})
    },
    refreshStatus() {
      this.loadIndexStatus()
      this.$message.success(this.$t('refreshed'))
    },
    formatTime(timestamp) {
      if (!timestamp) return ''
      const date = new Date(timestamp * 1000)
      return date.toLocaleString()
    }
  }
}
</script>

<style scoped>
.ai-knowledge-base {
  padding: 0;
}

.form-tooltip-icon {
  margin-left: 5px;
  color: #909399;
  cursor: help;
}

.form-item-desc {
  font-size: 12px;
  color: #909399;
  margin-top: 5px;
  line-height: 1.5;
}

.ai-config-section {
  padding: 10px 0;
  margin-bottom: 10px;
}

.index-status-info {
  width: 100%;
}

.status-row {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
  line-height: 1.8;
}

.status-row:last-child {
  margin-bottom: 0;
}

.status-label {
  min-width: 100px;
  color: #606266;
  font-size: 14px;
}

.status-value {
  color: #303133;
  font-size: 14px;
}

.status-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.status-icon {
  font-size: 16px;
}

.status-icon.success {
  color: #67c23a;
}

.status-icon.loading {
  color: #e6a23c;
  animation: rotating 2s linear infinite;
}

.status-icon.warning {
  color: #e6a23c;
}

.status-icon.info {
  color: #909399;
}

@keyframes rotating {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.index-logs-wrapper {
  width: 100%;
}

.index-logs-title {
  font-size: 14px;
  color: #606266;
  margin-bottom: 8px;
  font-weight: 500;
}

.index-logs {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #ebeef5;
  border-radius: 4px;
  padding: 10px;
  background-color: #fafafa;
}

.log-item {
  display: flex;
  gap: 10px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
}

.log-item:last-child {
  border-bottom: none;
}

.log-time {
  color: #909399;
  font-size: 12px;
  min-width: 150px;
}

.log-action {
  color: #409eff;
  font-size: 12px;
  min-width: 80px;
}

.log-page {
  flex: 1;
  font-size: 12px;
  color: #606266;
}

.indexing-tip {
  color: #409eff;
  display: flex;
  align-items: center;
  gap: 6px;
}

.indexing-tip .el-icon-loading {
  animation: rotating 2s linear infinite;
}

.indexing-alert-content {
  margin-top: 8px;
}

.indexing-alert-content p {
  margin: 0 0 8px 0;
  line-height: 1.6;
  color: #606266;
}

.indexing-progress-info {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  padding: 8px 12px;
  background-color: #f0f9ff;
  border-radius: 4px;
  border-left: 3px solid #409eff;
}

.indexing-progress-info .el-icon-loading {
  color: #409eff;
  animation: rotating 2s linear infinite;
}

.indexing-progress-info span {
  color: #303133;
  font-size: 14px;
}

.count-increase-badge {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  background-color: #67c23a;
  color: #fff;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
  animation: countPulse 0.6s ease-out;
}

.count-increase-indicator {
  display: inline-block;
  margin-left: 8px;
  color: #67c23a;
  font-size: 12px;
  font-weight: bold;
  animation: countPulse 0.6s ease-out;
}

.status-value.count-updated {
  color: #67c23a;
  font-weight: bold;
  animation: countHighlight 0.6s ease-out;
}

@keyframes countPulse {
  0% {
    transform: scale(1);
    opacity: 0.8;
  }
  50% {
    transform: scale(1.2);
    opacity: 1;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes countHighlight {
  0% {
    color: #303133;
    transform: scale(1);
  }
  50% {
    color: #67c23a;
    transform: scale(1.05);
  }
  100% {
    color: #67c23a;
    transform: scale(1);
  }
}
</style>

