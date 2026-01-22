<template>
  <div class="ai-knowledge-base-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('ai.ai_knowledge_base')"
      :width="'800'"
      @close="handleClose"
    >
      <div class="ai-knowledge-base">
        <!-- 功能开关区域 -->
        <div class="function-switch-section">
          <div class="switch-row">
            <CommonSwitch
              v-model="aiConfig.enabled"
              :label="$t('ai.ai_enable_for_project')"
              :disabled="!systemAiEnabled"
            />
            <a-tooltip :title="$t('ai.ai_enable_for_project_tips')">
              <i class="fas fa-question-circle help-icon"></i>
            </a-tooltip>
          </div>
          <div class="switch-desc" v-if="systemAiEnabled">
            {{ $t('ai.ai_enable_for_project_tips') }}
          </div>
        </div>

        <!-- 系统未配置提示 -->
        <div class="system-warning" v-if="!systemAiEnabled">
          <a-alert
            type="warning"
            :show-icon="true"
            :message="$t('ai.ai_system_not_configured')"
          />
        </div>

        <!-- 项目未启用提示 -->
        <div class="info-warning" v-else-if="!aiConfig.enabled">
          <a-alert
            type="info"
            :show-icon="true"
            :message="$t('ai.ai_project_not_enabled')"
          />
        </div>

        <!-- AI 配置（仅在系统级已配置时显示） -->
        <div class="ai-config-section" v-if="systemAiEnabled">
          <a-divider orientation="left">{{ $t('ai.ai_config') }}</a-divider>

          <div class="config-form">
            <div class="form-row">
              <div class="form-item">
                <CommonSwitch
                  v-model="aiConfig.dialog_expanded"
                  :label="$t('ai.ai_dialog_expanded_tips')"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-item full-width">
                <div class="form-label">{{ $t('ai.ai_welcome_message') }}</div>
                <a-textarea
                  v-model:value="aiConfig.welcome_message"
                  :rows="4"
                  :placeholder="$t('ai.ai_welcome_message_placeholder')"
                ></a-textarea>
                <div class="form-hint">{{ $t('ai.ai_welcome_message_tips') }}</div>
              </div>
            </div>

            <!-- 保存按钮 -->
            <div class="form-row action-row">
              <CommonButton type="primary" @click="saveAiConfig" :loading="saving" size="large">
                <i class="fas fa-save"></i>
                <span>{{ $t('common.save') }}</span>
              </CommonButton>
            </div>
          </div>
        </div>

        <!-- 索引管理（仅在系统级已配置且项目已保存为启用状态时显示） -->
        <div class="index-management-section" v-if="systemAiEnabled && itemAiEnabled">
          <a-divider orientation="left">{{ $t('ai.ai_index_management') }}</a-divider>

          <div class="index-form">
            <!-- 索引状态概览 -->
            <div class="index-overview">
              <div class="overview-item">
                <div class="overview-label">{{ $t('ai.ai_index_status') }}</div>
                <div class="overview-value">
                  <div v-if="indexStatus.status === 'indexed'" class="status-badge success">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ $t('ai.ai_indexed') }}</span>
                  </div>
                  <div v-else-if="indexStatus.status === 'indexing'" class="status-badge processing">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>{{ $t('ai.ai_indexing') }}</span>
                  </div>
                  <div v-else-if="indexStatus.status === 'not_configured'" class="status-badge warning">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ $t('ai.ai_not_configured') }}</span>
                  </div>
                  <div v-else class="status-badge neutral">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ $t('ai.ai_unknown_status') }}</span>
                  </div>
                </div>
              </div>

              <div class="overview-item" v-if="indexStatus.document_count !== undefined">
                <div class="overview-label">{{ $t('ai.ai_chunk_count') }}</div>
                <div class="overview-value">
                  <span class="count-number">{{ indexStatus.document_count }}</span>
                  <span class="count-unit">{{ $t('ai.ai_chunks') }}</span>
                  <span v-if="countIncreased && indexStatus.document_count > lastDocumentCount" class="count-increase">
                    +{{ indexStatus.document_count - lastDocumentCount }}
                  </span>
                </div>
              </div>

              <div class="overview-item" v-if="indexStatus.last_update_time">
                <div class="overview-label">{{ $t('ai.ai_last_update') }}</div>
                <div class="overview-value">{{ formatTime(indexStatus.last_update_time) }}</div>
              </div>
            </div>

            <!-- 索引进行中提示 -->
            <div class="indexing-alert" v-if="indexStatus.status === 'indexing'">
              <a-alert
                type="info"
                :show-icon="true"
                :message="$t('ai.ai_indexing_alert_title')"
              />
              <div class="indexing-detail">
                <p>{{ $t('ai.ai_indexing_alert_message') }}</p>
                <div class="progress-info" v-if="indexStatus.document_count !== undefined">
                  <i class="fas fa-spinner fa-spin"></i>
                  <span>{{ $t('ai.ai_indexing_progress_info', { count: indexStatus.document_count }) }}</span>
                  <span v-if="countIncreased && indexStatus.document_count > lastDocumentCount" class="progress-badge">
                    +{{ indexStatus.document_count - lastDocumentCount }}
                  </span>
                </div>
              </div>
            </div>

            <!-- 索引完成提示 -->
            <div class="index-complete-alert" v-if="indexStatus.status === 'indexed' && indexStatus.document_count > 0">
              <a-alert
                type="success"
                :show-icon="true"
                :message="$t('ai.ai_indexed_ready_title')"
              />
              <div class="success-content">
                {{ $t('ai.ai_indexed_ready_message') }}
              </div>
            </div>

            <!-- 操作按钮 -->
            <div class="action-row">
              <CommonButton
                type="primary"
                @click="rebuildIndex"
                :loading="rebuilding"
                :disabled="indexStatus.status === 'not_configured'"
                size="large"
              >
                <i class="fas fa-sync-alt"></i>
                <span>{{ $t('ai.ai_rebuild_index') }}</span>
              </CommonButton>
              <CommonButton @click="refreshStatus" size="large">
                <i class="fas fa-redo"></i>
                <span>{{ $t('common.refresh') }}</span>
              </CommonButton>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <CommonButton @click="handleClose" size="large">
          <i class="fas fa-times"></i>
          <span>{{ $t('common.close') }}</span>
        </CommonButton>
      </template>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import Message from '@/components/Message'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSwitch from '@/components/CommonSwitch.vue'
import ConfirmModal from '@/components/ConfirmModal'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const saving = ref(false)
const itemAiEnabled = ref(false)
const systemAiEnabled = ref(false)
const aiConfig = ref({
  enabled: false,
  dialog_expanded: false,
  welcome_message: ''
})

const internalItemId = ref<string>('') // 改为字符串类型以避免大整数精度丢失
const indexStatus = ref({
  status: 'unknown',
  document_count: 0,
  last_update_time: null
})
const rebuilding = ref(false)
const pollingTimer = ref<any>(null)
const pollingCount = ref(0)
const maxPollingCount = 120
const needRefresh = ref(false)
const lastDocumentCount = ref(0)
const countIncreased = ref(false)

const loadItemInfo = async () => {
  try {
    const res = await request('/api/item/info', {
      item_id: props.item_id
    })

    if (res.error_code === 0 && res.data) {
      internalItemId.value = String(res.data.item_id)
      if (res.data.ai_config) {
        // 后端返回的是 dialog_collapsed（是否收缩）
        // dialog_collapsed: 1 = 收缩, 0 = 展开
        // 前端 dialog_expanded: true = 展开, false = 收缩
        aiConfig.value = {
          enabled: res.data.ai_config.enabled > 0,
          dialog_expanded: res.data.ai_config.dialog_collapsed === 0,
          welcome_message: res.data.ai_config.welcome_message || ''
        }
        itemAiEnabled.value = res.data.ai_config.enabled > 0
      } else {
        itemAiEnabled.value = res.data.ai_knowledge_base_enabled > 0
        aiConfig.value.enabled = res.data.ai_knowledge_base_enabled > 0
      }
    }
  } catch (error) {
    console.error('加载项目信息失败:', error)
  }
}

const loadIndexStatus = async () => {
  if (!internalItemId.value) {
    await loadItemInfo()
  }
  if (!internalItemId.value) {
    console.error('无法获取项目ID')
    return
  }
  try {
    const res = await request('/api/ai/getIndexStatus', {
      item_id: String(internalItemId.value)
    })

    if (res.error_code === 0 && res.data) {
      indexStatus.value = res.data
      if (
        res.data.status === 'not_configured' &&
        res.data.message &&
        res.data.message.indexOf('AI 服务未配置') > -1
      ) {
        systemAiEnabled.value = false
      } else {
        systemAiEnabled.value = true
      }
    }
  } catch (error) {
    console.error('加载索引状态失败:', error)
    systemAiEnabled.value = false
  }
}

const loadAiConfig = async () => {
  if (!internalItemId.value) {
    await loadItemInfo()
  }
  if (!internalItemId.value) {
    return
  }
  try {
    const res = await request('/api/item/getAiKnowledgeBaseConfig', {
      item_id: String(internalItemId.value)
    })

    if (res.error_code === 0 && res.data) {
      // 后端返回的是 dialog_collapsed（是否收缩）
      // dialog_collapsed: 1 = 收缩, 0 = 展开
      // 前端 dialog_expanded: true = 展开, false = 收缩
      aiConfig.value = {
        enabled: res.data.enabled > 0,
        dialog_expanded: res.data.dialog_collapsed === 0,
        welcome_message: res.data.welcome_message || ''
      }
      itemAiEnabled.value = res.data.enabled > 0
    }
  } catch (error) {
    console.error('加载AI配置失败:', error)
  }
}

const saveAiConfig = async () => {
  if (!internalItemId.value) {
    await AlertModal('无法获取项目ID')
    return
  }
  saving.value = true
  try {
    // 后端接收的是 dialog_collapsed（是否收缩）
    // dialog_expanded: true = 展开, false = 收缩
    // dialog_collapsed: 0 = 展开, 1 = 收缩
    const res = await request('/api/item/setAiKnowledgeBaseConfig', {
      item_id: String(internalItemId.value),
      enabled: aiConfig.value.enabled ? 1 : 0,
      dialog_collapsed: aiConfig.value.dialog_expanded ? 0 : 1,
      welcome_message: aiConfig.value.welcome_message
    })

    if (res.error_code === 0) {
      Message.success(
        aiConfig.value.enabled
          ? t('ai.ai_project_enabled_success')
          : t('ai.ai_project_disabled_success')
      )
      const wasEnabled = itemAiEnabled.value
      itemAiEnabled.value = aiConfig.value.enabled
      await loadAiConfig()
      if (!wasEnabled && aiConfig.value.enabled) {
        needRefresh.value = true
      }
      if (aiConfig.value.enabled) {
        await loadIndexStatus()
        if (!wasEnabled || indexStatus.value.status === 'not_configured') {
          setTimeout(() => {
            startIndexing()
          }, 500)
        }
      }
    } else {
      await AlertModal(res.error_message || t('ai.ai_update_failed'))
      await loadAiConfig()
    }
  } catch (error) {
    console.error('保存配置失败:', error)
    await AlertModal(t('ai.ai_update_failed'))
    await loadAiConfig()
  } finally {
    saving.value = false
  }
}

const rebuildIndex = async () => {
  const confirmed = await ConfirmModal({
    msg: t('ai.ai_rebuild_confirm'),
    title: t('common.tips'),
    confirmText: t('common.confirm'),
    cancelText: t('common.cancel')
  })

  if (confirmed) {
    await startIndexing()
  }
}

const startIndexing = async () => {
  if (!internalItemId.value) {
    await AlertModal('无法获取项目ID')
    return
  }
  rebuilding.value = true
  stopPolling()

  try {
    const res = await request('/api/ai/rebuildIndex', {
      item_id: String(internalItemId.value)
    })

    if (res.error_code === 0) {
      Message.info(t('ai.ai_rebuild_started'))
      indexStatus.value.status = 'indexing'
      pollingCount.value = 0
      lastDocumentCount.value = 0
      countIncreased.value = false
      startPolling()
    } else {
      // request 已经会自动弹窗
      rebuilding.value = false
    }
  } catch (error) {
    console.error('索引构建失败:', error)
    // request 已经会自动弹窗
    rebuilding.value = false
  }
}

const startPolling = () => {
  if (pollingTimer.value) {
    clearInterval(pollingTimer.value)
  }
  pollIndexStatus()
  pollingTimer.value = setInterval(() => {
    pollIndexStatus()
  }, 5000)
}

const stopPolling = () => {
  if (pollingTimer.value) {
    clearInterval(pollingTimer.value)
    pollingTimer.value = null
  }
  pollingCount.value = 0
  countIncreased.value = false
}

const pollIndexStatus = async () => {
  pollingCount.value++
  if (pollingCount.value > maxPollingCount) {
    stopPolling()
    rebuilding.value = false
    Message.info('索引构建超时，请手动刷新状态')
    return
  }

  try {
    const res = await request('/api/ai/getIndexStatus', {
      item_id: String(internalItemId.value)
    })

    if (res.error_code === 0 && res.data) {
      const newStatus = res.data

      if (newStatus.status === 'error') {
        indexStatus.value = newStatus
        stopPolling()
        rebuilding.value = false
        // request 已经会自动弹窗，不需要额外的 Message.error
        return
      }

      if (newStatus.status === 'indexed') {
        indexStatus.value = newStatus
        lastDocumentCount.value = newStatus.document_count || 0
        countIncreased.value = false
        stopPolling()
        rebuilding.value = false
        Message.success(t('ai.ai_index_completed'))
        return
      } else if (newStatus.status === 'indexing') {
        const currentCount = newStatus.document_count || 0
        const previousCount = indexStatus.value.document_count || 0

        if (currentCount > previousCount) {
          countIncreased.value = true
          lastDocumentCount.value = previousCount
          setTimeout(() => {
            countIncreased.value = false
          }, 1000)
        } else {
          countIncreased.value = false
        }

        indexStatus.value = newStatus
      } else {
        indexStatus.value = {
          ...newStatus,
          status: 'indexing'
        }
        countIncreased.value = false
      }
    }
  } catch (error) {
    console.error('轮询索引状态失败:', error)
  }
}

const refreshStatus = () => {
  loadIndexStatus()
  Message.success(t('common.refreshed'))
}

const formatTime = (timestamp: number) => {
  if (!timestamp) return ''
  const date = new Date(timestamp * 1000)
  return date.toLocaleString()
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(needRefresh.value)
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
  internalItemId.value = String(props.item_id) // 改为字符串转换
  loadItemInfo().then(() => {
    loadAiConfig()
    loadIndexStatus()
  })
})

onBeforeUnmount(() => {
  stopPolling()
})
</script>

<style scoped lang="scss">
.ai-knowledge-base-modal {
  .ai-knowledge-base {
    padding: 0;
    max-height: 70vh;
    overflow-y: auto;
  }
}

.function-switch-section {
  margin-bottom: 20px;
  padding: 16px;
  background-color: var(--color-obvious);
  border-radius: 4px;

  .switch-row {
    display: flex;
    align-items: center;
    gap: 8px;

    .help-icon {
      font-size: 14px;
      color: var(--color-text-secondary);
      flex-shrink: 0;
    }
  }

  .switch-desc {
    margin-top: 12px;
    padding-left: 0;
    font-size: 13px;
    color: var(--color-text-secondary);
    line-height: 1.6;
  }
}

.ai-config-section {
  margin-bottom: 24px;

  .config-form {
    padding: 0;
  }
}

.form-row {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }

  &.action-row {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid var(--color-interval);
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
}

.form-item {
  margin-bottom: 20px;

  &:last-child {
    margin-bottom: 0;
  }

  &.full-width {
    width: 100%;
  }
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--color-text-primary);
  margin-bottom: 8px;
  display: block;
}

.form-hint {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 6px;
  line-height: 1.5;
}

:deep(.ant-input),
:deep(.ant-textarea) {
  font-size: 14px;
}

.index-management-section {
  .index-form {
    padding: 0;
  }
}

.system-warning,
.info-warning {
  margin-bottom: 20px;
}

.index-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background-color: var(--color-obvious);
  border-radius: 4px;

  .overview-item {
    display: flex;
    flex-direction: column;
    gap: 6px;

    .overview-label {
      font-size: 13px;
      color: var(--color-text-secondary);
      font-weight: 400;
    }

    .overview-value {
      font-size: 15px;
      color: var(--color-text-primary);
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
    }
  }
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;

  &.success {
    background-color: var(--color-success-bg);
    color: var(--color-success);
  }

  &.processing {
    background-color: var(--color-active-bg);
    color: var(--color-active);
  }

  &.warning {
    background-color: var(--color-warning-bg);
    color: var(--color-warning);
  }

  &.neutral {
    background-color: var(--color-bg-tertiary);
    color: var(--color-text-secondary);
  }
}

.indexing-alert {
  margin-bottom: 24px;

  .indexing-detail {
    p {
      margin: 0 0 12px 0;
      font-size: 14px;
      color: var(--color-text-primary);
      line-height: 1.6;
    }
  }

  .progress-info {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background-color: var(--color-active-bg);
    border-radius: 6px;
    margin-top: 12px;

    .progress-badge {
      display: inline-block;
      padding: 2px 10px;
      background-color: var(--color-success);
      color: #fff;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      animation: countPulse 0.6s ease-out;
    }
  }
}

.index-complete-alert {
  margin-bottom: 24px;

  .success-content {
    font-size: 14px;
    color: var(--color-text-primary);
    line-height: 1.6;
  }
}

.action-row {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  flex-wrap: wrap;

  :deep(.common-button) {
    min-width: 120px;
    padding: 8px 20px;
    height: 40px;
    font-size: 14px;
    border-radius: 6px;

    i {
      margin-right: 8px;
    }
  }
}

.count-increase {
  color: var(--color-success);
  font-weight: bold;
  animation: countHighlight 0.6s ease-out;
}

.count-number {
  font-size: 18px;
  font-weight: 600;
}

.count-unit {
  font-size: 14px;
  font-weight: 400;
  color: var(--color-text-secondary);
}

@keyframes countPulse {
  0% {
    transform: scale(1);
    opacity: 0.8;
  }
  50% {
    transform: scale(1.1);
    opacity: 1;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

@keyframes countHighlight {
  0% {
    color: var(--color-text-primary);
    transform: scale(1);
  }
  50% {
    color: var(--color-success);
    transform: scale(1.05);
  }
  100% {
    color: var(--color-success);
    transform: scale(1);
  }
}

@media (max-width: 768px) {
  .function-switch-section {
    padding: 12px 16px;
  }

  .switch-row {
    flex-direction: column;
    gap: 16px;
  }

  .action-row {
    justify-content: stretch;
    flex-direction: column;

    :deep(.common-button) {
      width: 100%;
      min-width: auto;
    }
  }

  .index-overview {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}
</style>
