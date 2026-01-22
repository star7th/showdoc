<template>
  <div class="announcement-management">
    <!-- 发布系统公告 -->
    <div class="publish-section">
      <div class="section-header">
        <div class="header-title">
          <i class="fas fa-bullhorn"></i>
          <span>{{ $t('admin.publish_announcement') }}</span>
        </div>
      </div>

      <div class="publish-form">
        <div class="form-row">
          <label class="form-label">{{ $t('admin.announcement_type') }}</label>
          <div class="radio-group">
            <div
              class="radio-item"
              :class="{ active: form.message_type === 'announce_web' }"
              @click="form.message_type = 'announce_web'"
            >
              <div class="radio-icon">
                <i v-if="form.message_type === 'announce_web'" class="fas fa-circle"></i>
                <i v-else class="far fa-circle"></i>
              </div>
              <span>{{ $t('admin.announce_web_only') }}</span>
            </div>
            <div
              class="radio-item"
              :class="{ active: form.message_type === 'announce_runapi' }"
              @click="form.message_type = 'announce_runapi'"
            >
              <div class="radio-icon">
                <i v-if="form.message_type === 'announce_runapi'" class="fas fa-circle"></i>
                <i v-else class="far fa-circle"></i>
              </div>
              <span>{{ $t('admin.announce_runapi_only') }}</span>
            </div>
            <div
              class="radio-item"
              :class="{ active: form.message_type === 'announce_all' }"
              @click="form.message_type = 'announce_all'"
            >
              <div class="radio-icon">
                <i v-if="form.message_type === 'announce_all'" class="fas fa-circle"></i>
                <i v-else class="far fa-circle"></i>
              </div>
              <span>{{ $t('admin.announce_all') }}</span>
            </div>
          </div>
        </div>

        <div class="form-row">
          <label class="form-label">{{ $t('admin.announcement_content') }}</label>
          <CommonTextarea
            v-model="form.message_content"
            :placeholder="$t('admin.announcement_content_placeholder')"
            :rows="6"
          />
        </div>

        <div class="form-row">
          <label class="form-label">{{ $t('admin.send_time') }}</label>
          <a-date-picker
            v-model:value="form.send_at"
            show-time
            format="YYYY-MM-DD HH:mm:ss"
            :placeholder="$t('admin.send_time_placeholder')"
            style="width: 100%; height: 40px"
          />
          <div class="tip-text">{{ $t('admin.send_time_tip') }}</div>
        </div>

        <div class="form-actions">
          <CommonButton
            theme="dark"
            :text="$t('admin.send')"
            :leftIcon="['fas', 'paper-plane']"
            @click="handlePublish"
          />
          <CommonButton
            theme="light"
            :text="$t('admin.reset')"
            :leftIcon="['fas', 'redo']"
            @click="handleReset"
          />
        </div>
      </div>
    </div>

    <!-- 项目列表表格 -->
    <div class="table-section">
      <CommonTable
        :table-header="tableHeader"
        :table-data="list"
        :pagination="pagination"
        :loading="loading"
        row-key="id"
        max-height="calc(100vh - 450px)"
        @page-change="handleTableChange"
      >
        <!-- 消息类型列 -->
        <template #cell-message_type="{ row }">
          <span class="type-badge" :class="getTypeClass(row.message_type)">
            {{ getTypeLabel(row.message_type) }}
          </span>
        </template>

        <!-- 消息内容列 -->
        <template #cell-message_content="{ row }">
          <div class="announcement-content" v-html="row.message_content"></div>
        </template>
      </CommonTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import ConfirmModal from '@/components/ConfirmModal'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'
import CommonTable from '@/components/CommonTable.vue'
import { addAnnouncement, getAnnouncementList } from '@/models/admin'
import dayjs from 'dayjs'

const { t } = useI18n()

// 数据状态
const list = ref<any[]>([])
const loading = ref(false)

// 表单数据
const form = reactive({
  message_type: 'announce_web',
  message_content: '',
  send_at: null as any
})

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
})

// 表格头部配置
const tableHeader = computed(() => [
  { title: 'ID', key: 'id', width: 80 },
  { title: t('admin.announcement_type'), key: 'message_type', width: 140, center: true },
  { title: t('admin.send_time'), key: 'addtime', width: 180 },
  { title: t('admin.content'), key: 'message_content', width: 0 }
])

const getTypeClass = (type: string) => {
  switch (type) {
    case 'announce_all':
      return 'all'
    case 'announce_runapi':
      return 'runapi'
    default:
      return 'web'
  }
}

const getTypeLabel = (type: string) => {
  switch (type) {
    case 'announce_all':
      return t('admin.announce_all')
    case 'announce_runapi':
      return t('admin.announce_runapi_only')
    default:
      return t('admin.announce_web_only')
  }
}

// 方法
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getAnnouncementList({
      page: pagination.current,
      count: pagination.pageSize
    })
    list.value = res.data || []
    pagination.total =
      pagination.current * pagination.pageSize + (list.value.length === pagination.pageSize ? pagination.pageSize : 0)
  } catch (error) {
    console.error('获取公告列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handlePublish = async () => {
  if (!form.message_content) {
    message.error(t('admin.announcement_content_required'))
    return
  }
  const confirmed = await ConfirmModal({
    title: t('admin.confirm_publish'),
    msg: t('admin.publish_warning')
  })
  if (confirmed) {
    try {
      const sendTime = form.send_at ? dayjs(form.send_at).format('YYYY-MM-DD HH:mm:ss') : ''
      await addAnnouncement({
        message_type: form.message_type,
        message_content: form.message_content,
        send_at: sendTime
      })
      message.success(t('admin.publish_success'))
      handleReset()
      fetchList()
    } catch (error) {
      message.error(t('common.op_failed'))
    }
  }
}

const handleReset = () => {
  form.message_type = 'announce_web'
  form.message_content = ''
  form.send_at = null
}

const handleTableChange = (page: number, pageSize: number) => {
  pagination.current = page
  pagination.pageSize = pageSize
  fetchList()
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.announcement-management {
  .publish-section,
  .history-section {
    background: var(--color-obvious);
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
    overflow: hidden;

    .section-header {
      padding: 20px 20px 0;
      border-bottom: 1px solid var(--color-inactive);

      .header-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 16px;
        font-weight: 600;
        color: var(--color-primary);

        i {
          color: var(--color-active);
          font-size: 18px;
        }
      }
    }

    .publish-form {
      padding: 20px;

      .form-row {
        margin-bottom: 20px;

        .form-label {
          display: block;
          margin-bottom: 8px;
          font-size: 14px;
          font-weight: 600;
          color: var(--color-primary);
        }

        .radio-group {
          display: flex;
          gap: 16px;

          .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            background: var(--color-default);
            color: var(--color-primary);

            .radio-icon {
              font-size: 14px;
              color: var(--color-inactive);
              transition: all 0.15s ease;
            }

            &.active {
              background: var(--hover-overlay);
              font-weight: 600;

              .radio-icon {
                color: var(--color-active);
              }
            }

            &:hover:not(.active) {
              background: var(--color-secondary);
            }
          }
        }
      }

      .form-actions {
        display: flex;
        gap: 12px;
        padding-top: 8px;
      }
    }

    .tip-text {
      color: var(--color-grey);
      font-size: 12px;
      line-height: 1.6;
      margin-top: 8px;
    }
  }

  .history-section {
    .table-container {
      padding: 20px;
    }

    .type-badge {
      padding: 4px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;

      &.all {
        background: var(--color-inactive);
        color: var(--color-primary);
      }

      &.runapi {
        background: var(--color-inactive);
        color: var(--color-primary);
      }

      &.web {
        background: var(--color-inactive);
        color: var(--color-primary);
      }
    }

    .announcement-content {
      max-width: 500px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;

      // 支持基础HTML样式
      :deep(*) {
        margin: 0;
        padding: 0;
      }

      :deep(a) {
        color: var(--color-active);

        &:hover {
          text-decoration: underline;
        }
      }
    }
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .announcement-management {
    .publish-section,
    .history-section {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .publish-section {
      .section-header {
        border-bottom-color: var(--color-inactive);

        .header-title {
          color: var(--color-primary);

          i {
            color: var(--color-active);
          }
        }
      }

      .publish-form {
        .form-row {
          .form-label {
            color: var(--color-primary);
          }

          .radio-group {
            .radio-item {
              background: var(--color-default);
              color: var(--color-primary);

              .radio-icon {
                color: var(--color-inactive);
              }

              &.active {
                background: var(--hover-overlay);

                .radio-icon {
                  color: var(--color-active);
                }
              }

              &:hover:not(.active) {
                background: var(--color-default);
              }
            }
          }
        }
      }
    }

    .tip-text {
      color: var(--color-grey);
    }

    .history-section {
      .type-badge {
        &.all,
        &.runapi,
        &.web {
          background: var(--color-default);
          color: var(--color-primary);
        }
      }
    }
  }
}
</style>
