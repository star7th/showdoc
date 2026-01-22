<template>
  <div class="message-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('message.my_notice')"
      :icon="['fas', 'message']"
      :headerButtons="headerButtons"
      width="800px"
      @close="closeHandle"
    >
      <div class="modal-content">
        <a-tabs v-model:activeKey="activeTab" @change="handleTabChange">
          <!-- 系统提醒 -->
          <a-tab-pane key="remindList" :tab="$t('message.system_reminder')">
            <CommonTable
              :tableHeader="remindTableHeader"
              :tableData="remindList"
              :pagination="remindPagination"
              :loading="remindLoading"
              :rowKey="(record: MessageItem) => record.id"
              @pageChange="handleRemindPageChange"
            >
              <template #cell-status="{ row }: { row: MessageItem }">
                <a-badge v-if="row.status === 0" value="new" />
              </template>
              <template #cell-message_content="{ row }: { row: MessageItem }">
                <div class="message-content">
                  <!-- 页面更新/创建 -->
                  <div
                    v-if="
                      (row.action_type === 'create' || row.action_type === 'update') &&
                      row.object_type === 'page' && row.page_data
                    "
                  >
                    <div class="message-line">
                      {{ row.from_name }} {{ $t('message.update_the_page') }}
                      <a-button
                        type="link"
                        @click="visitPage(row.page_data!.item_id, row.page_data!.page_id)"
                      >
                        {{ row.page_data!.page_title }}
                      </a-button>
                    </div>
                    <div v-if="row.message_content" class="message-remark">
                      {{ $t('message.update_remark') }}: {{ row.message_content }}
                    </div>
                  </div>
                  <!-- 页面评论 -->
                  <div
                    v-if="
                      (row.action_type === 'comment' || row.action_type === 'comment_reply') &&
                      row.object_type === 'page' && row.page_data
                    "
                  >
                    <div class="message-line">
                      {{ row.message_content }}
                      <a-button
                        type="link"
                        @click="
                          visitPageWithComment(row.page_data!.item_id, row.page_data!.page_id)
                        "
                      >
                        {{ row.page_data!.page_title }}
                      </a-button>
                    </div>
                  </div>
                  <!-- VIP 提醒 -->
                  <div v-if="row.object_type === 'vip'">
                    <span v-html="vipMessage"></span>
                  </div>
                </div>
              </template>
            </CommonTable>
          </a-tab-pane>

          <!-- 系统公告 -->
          <a-tab-pane key="announcementList" :tab="$t('message.system_announcement')">
            <CommonTable
              :tableHeader="announcementTableHeader"
              :tableData="announcementList"
              :loading="announcementLoading"
              :rowKey="(record: MessageItem) => record.id"
              max-height="500px"
            >
              <template #cell-status="{ row }: { row: MessageItem }">
                <a-badge v-if="row.status === 0" value="new" />
              </template>
              <template #cell-message_content="{ row }: { row: MessageItem }">
                <div class="message-content" v-html="row.message_content"></div>
              </template>
            </CommonTable>
          </a-tab-pane>
        </a-tabs>
      </div>
      <div class="modal-footer">
        <div class="center-button">
          <CommonButton @click="closeHandle">
            {{ $t('common.close') }}
          </CommonButton>
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import { getRemindList, getAnnouncementList, setRead, type MessageItem } from '@/models/message'
import FeedbackModal from '../../common/FeedbackModal'

const { t, locale } = useI18n()
const router = useRouter()

const props = defineProps<{
  onClose: (res: boolean) => void
  initialTab?: string
}>()

const show = ref(false)
const activeTab = ref(props.initialTab || 'remindList')
const remindList = ref<MessageItem[]>([])
const announcementList = ref<MessageItem[]>([])
const remindLoading = ref(false)
const announcementLoading = ref(false)

// 头部按钮配置（反馈按钮）
// 注意：FeedbackModal 调用内联在 onClick 中，避免未使用变量警告
const headerButtons = computed(() => [
  {
    text: t('feedback.feedback'),
    icon: ['fas', 'headphones'],
    onClick: () => {
      // 显示反馈弹窗
      FeedbackModal()
    }
  }
])
const remindTableHeader = computed(() => [
  { title: '', key: 'status', width: 70 },
  { title: t('message.send_time'), key: 'addtime', width: 180 },
  { title: t('message.content'), key: 'message_content' }
])

// 公告表格配置
const announcementTableHeader = computed(() => [
  { title: '', key: 'status', width: 70 },
  { title: t('message.send_time'), key: 'addtime', width: 180 },
  { title: t('message.content'), key: 'message_content' }
])

// 分页配置
const remindPagination = ref({
  current: 1,
  pageSize: 6,
  total: 0
})

// VIP 消息文案
const vipMessage = computed(() => {
  if (locale.value === 'zh-CN') {
    return `你在showdoc购买的付费版资格很快过期了，你可以<a href="/user/setting" target="_blank">点此进入用户中心</a>进行续费 (如已续费请忽略该通知)`
  } else {
    return `Your paid ShowDoc subscription will expire soon. <a href="/user/setting" target="_blank">Click here to go to User Center</a> to renew (ignore if already renewed)`
  }
})

// 获取公告列表
const fetchAnnouncementList = async () => {
  announcementLoading.value = true
  try {
    const data = await getAnnouncementList()
    const list = data.data || []
    // 仅展示网页端相关公告: announce / announce_web / announce_all
    announcementList.value = list.filter((element: MessageItem) => {
      const messageType = (element as any).message_type || 'announce'
      return messageType === 'announce' || messageType === 'announce_web' || messageType === 'announce_all'
    })
    // 设置已读
    for (const element of announcementList.value) {
      setReadMessage(element.from_uid, element.message_content_id)
    }
  } catch (error) {
    console.error('获取公告列表失败:', error)
  } finally {
    announcementLoading.value = false
  }
}

// 获取提醒列表
const fetchRemindList = async () => {
  remindLoading.value = true
  try {
    const data = await getRemindList({
      page: remindPagination.value.current,
      count: remindPagination.value.pageSize
    })
    remindList.value = data.data.list || []
    remindPagination.value.total = data.data.total || 0
    // 设置已读
    for (const element of remindList.value) {
      setReadMessage(element.from_uid, element.message_content_id)
    }
  } catch (error) {
    console.error('获取提醒列表失败:', error)
  } finally {
    remindLoading.value = false
  }
}

// 设置消息已读
const setReadMessage = async (fromUid: number, messageContentId: number) => {
  if (messageContentId) {
    try {
      await setRead({
        from_uid: fromUid,
        message_content_id: messageContentId
      })
    } catch (error) {
      console.error('设置已读失败:', error)
    }
  }
}

// 访问页面
const visitPage = (itemId: number, pageId: number) => {
  const url = router.resolve({
    path: `/${itemId}/${pageId}`
  })
  window.open(url.href, '_blank')
}

// 访问页面并定位到评论区
const visitPageWithComment = (itemId: number, pageId: number) => {
  const url = router.resolve({
    path: `/${itemId}/${pageId}`
  })
  window.open(url.href + '#comment-area', '_blank')
}

// 提醒页码变化
const handleRemindPageChange = (page: number) => {
  remindPagination.value.current = page
  fetchRemindList()
}

// Tab 切换
const handleTabChange = (key: string) => {
  if (key === 'remindList') {
    fetchRemindList()
  } else if (key === 'announcementList') {
    fetchAnnouncementList()
  }
}

// 关闭弹窗
const closeHandle = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
  // 初始加载
  fetchRemindList()
  fetchAnnouncementList()
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 20px;
}

.message-content {
  font-size: var(--font-size-s);
  color: var(--color-text-primary);
  line-height: 1.6;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.message-line {
  margin-bottom: 8px;
}

.message-remark {
  margin-top: 4px;
  color: var(--color-text-secondary);
  font-size: var(--font-size-xs);
}

// 暗黑主题适配
[data-theme='dark'] {
  .message-content {
    color: var(--color-text-primary);
  }

  .message-remark {
    color: var(--color-text-secondary);
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  padding: 0 20px;
  user-select: none;

  .center-button {
    width: 160px;
    margin: 0 7.5px;
  }
}

:deep(.ant-tabs) {
  .ant-tabs-nav {
    margin-bottom: 16px;
  }
  .ant-tabs-tab{
    margin-right: 20px;
  }

  .ant-tabs-tab {
    font-weight: 600;
  }
}
</style>
