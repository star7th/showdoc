<template>
  <CommonModal
    :show="show"
    :title="$t('page.save_and_notify')"
    :icon="['fas', 'fa-comment-dots']"
    width="500px"
    @close="handleClose"
  >
    <div class="notify-modal">
      <!-- 更新备注输入 -->
      <div class="form-section">
        <CommonTextarea
          v-model="notifyContent"
          :label="$t('page.notify_content')"
          :placeholder="$t('page.notify_content_placeholder')"
          :rows="4"
        />
      </div>

      <!-- 通知人员信息 -->
      <div class="notify-info">
        <CommonButton
          type="link"
          :text="$t('page.click_to_edit_member')"
          @click="showMemberModal = true"
        />
        <span class="notify-count">
          ( {{ $t('page.cur_setting_notify') }} {{ subscriptionList.length }} {{ $t('page.people') }} )
        </span>
      </div>

      <!-- 提示信息 -->
      <p class="tips-text">
        {{ $t('page.notify_tips1') }}
      </p>
    </div>

    <template #footer>
      <div class="footer-buttons">
        <CommonButton
          :text="$t('common.cancel')"
          theme="light"
          @click="handleClose"
        />
        <CommonButton
          :text="$t('common.confirm')"
          theme="dark"
          @click="handleConfirm"
        />
      </div>
    </template>
  </CommonModal>

  <!-- 通知人员编辑弹窗 -->
  <CommonModal
    v-if="showMemberModal"
    :show="showMemberModal"
    :title="$t('page.edit_notify_member')"
    :icon="['fas', 'fa-users']"
    width="700px"
    @close="showMemberModal = false"
  >
    <div class="member-modal-content">
      <!-- 操作按钮 -->
      <div class="member-actions">
        <CommonButton
          :text="$t('page.add_single_member')"
          :left-icon="['fas', 'fa-user-plus']"
          theme="light"
          @click="showAddMemberModal = true"
        />
        <CommonButton
          :text="$t('page.add_all_member')"
          :left-icon="['fas', 'fa-users']"
          theme="light"
          @click="handleAddAllMembers"
        />
      </div>

      <!-- 成员列表 -->
      <CommonTable
        :table-header="memberTableHeader"
        :table-data="memberTableData"
        :loading="loading"
        :row-key="(record) => record.uid"
        max-height="400px"
      >
        <template #cell-username="{ row }">
          {{ row.username }}
        </template>
        <template #cell-name="{ row }">
          {{ row.name || '-' }}
        </template>
        <template #cell-sub_time="{ row }">
          {{ formatDate(row.sub_time) }}
        </template>
        <template #cell-action="{ row }">
          <CommonButton
            type="link"
            :text="$t('common.delete')"
            theme="light"
            @click="handleDeleteMember(row)"
          />
        </template>
      </CommonTable>
    </div>

    <template #footer>
      <CommonButton
        :text="$t('common.close')"
        theme="light"
        @click="showMemberModal = false"
      />
    </template>
  </CommonModal>

  <!-- 添加成员弹窗 -->
  <CommonModal
    v-if="showAddMemberModal"
    :show="showAddMemberModal"
    :title="$t('page.add_single_member')"
    :icon="['fas', 'fa-user-plus']"
    width="500px"
    @close="showAddMemberModal = false"
  >
    <div class="add-member-content">
      <div class="form-section">
        <label>{{ $t('page.username') }}</label>
        <div class="select-wrapper">
          <a-select
            v-model:value="selectedMemberUids"
            mode="multiple"
            :placeholder="$t('page.select_or_search_member')"
            :options="memberOptions"
            show-search
            :filter-option="filterMemberOption"
            style="flex: 1"
          />
          <a-tooltip :title="$t('page.refresh_member_list')">
            <i
              class="fas fa-sync-alt refresh-btn"
              @click="loadAllMemberList"
            ></i>
          </a-tooltip>
        </div>
      </div>

      <p class="tips-text">
        {{ $t('page.notify_add_member_tips1') }}
      </p>
    </div>

    <template #footer>
      <div class="footer-buttons">
        <CommonButton
          :text="$t('common.cancel')"
          theme="light"
          @click="showAddMemberModal = false"
        />
        <CommonButton
          :text="$t('common.confirm')"
          theme="dark"
          @click="handleAddMembers"
        />
      </div>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'
import CommonTable from '@/components/CommonTable.vue'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import { getPageList, savePage, deletePage, getAllItemMemberList } from '@/models/subscription'

interface Props {
  itemId: number | string
  pageId: number | string
  onClose?: () => void
  onConfirm: (content: string) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const showMemberModal = ref(false)
const showAddMemberModal = ref(false)
const notifyContent = ref('')
const loading = ref(false)
const subscriptionList = ref<any[]>([])
const allMemberList = ref<any[]>([])
const selectedMemberUids = ref<number[]>([])

// 计算属性：成员选项
const memberOptions = computed(() => {
  return allMemberList.value.map(member => ({
    label: `${member.username}${member.name ? ` (${member.name})` : ''}`,
    value: member.uid,
  }))
})

// 成员表格表头
const memberTableHeader = computed(() => [
  { title: t('page.username'), key: 'username', width: 150 },
  { title: t('page.name'), key: 'name', width: 120 },
  { title: t('page.addtime'), key: 'sub_time', width: 180 },
  { title: t('common.operation'), key: 'action', width: 100, center: true }
])

// 成员表格数据
const memberTableData = computed(() => {
  return subscriptionList.value
})

// Methods
const handleClose = () => {
  show.value = false
  props.onClose?.()
}

const handleConfirm = () => {
  props.onConfirm(notifyContent.value)
}

// 加载订阅者列表
const loadSubscriptionList = async () => {
  try {
    loading.value = true
    const list = await getPageList(props.pageId)
    subscriptionList.value = list
  } catch (error) {
    console.error('加载订阅者列表失败:', error)
  } finally {
    loading.value = false
  }
}

// 加载所有成员列表
const loadAllMemberList = async () => {
  try {
    const list = await getAllItemMemberList(props.itemId)
    allMemberList.value = list
  } catch (error) {
    console.error('加载成员列表失败:', error)
  }
}

// 过滤成员选项
const filterMemberOption = (input: string, option: any) => {
  return option.label.toLowerCase().indexOf(input.toLowerCase()) >= 0
}

// 添加成员
const handleAddMembers = async () => {
  if (selectedMemberUids.value.length === 0) {
    Message.info(t('page.select_member_first'))
    return
  }

  try {
    await savePage(props.pageId, selectedMemberUids.value)
    await loadSubscriptionList()
    selectedMemberUids.value = []
    showAddMemberModal.value = false
    Message.success(t('common.op_success'))
  } catch (error) {
    console.error('添加成员失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

// 一键添加全部成员
const handleAddAllMembers = async () => {
  if (allMemberList.value.length === 0) {
    Message.info(t('page.no_member_to_add'))
    return
  }

  try {
    const allUids = allMemberList.value.map(m => m.uid)
    await savePage(props.pageId, allUids)
    await loadSubscriptionList()
    Message.success(t('common.op_success'))
  } catch (error) {
    console.error('添加全部成员失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

// 删除成员
const handleDeleteMember = async (member: any) => {
  try {
    await deletePage(props.pageId, [member.uid])
    await loadSubscriptionList()
    Message.success(t('common.delete_success'))
  } catch (error) {
    console.error('删除成员失败:', error)
    await AlertModal(t('common.delete_failed'))
  }
}

// 格式化日期
const formatDate = (timestamp: any) => {
  if (!timestamp) return '-'
  // 如果是字符串格式的日期，直接返回
  if (typeof timestamp === 'string') {
    return timestamp
  }
  // 如果是数字，按时间戳处理
  const date = new Date(timestamp * 1000)
  return date.toLocaleString()
}

// Lifecycle
onMounted(() => {
  show.value = true
  loadSubscriptionList()
  loadAllMemberList()
})

// 监听添加成员弹窗打开，自动加载成员列表
watch(showAddMemberModal, (newVal) => {
  if (newVal) {
    loadAllMemberList()
  }
})
</script>

<style scoped lang="scss">
.notify-modal {
  padding: 15px 0;
}

.form-section {
  margin-bottom: 15px;
}

.notify-info {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  gap: 8px;
  padding-left: 15px;
}

.notify-count {
  font-size: 13px;
  color: var(--color-text-secondary);
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 0;
  line-height: 1.6;
}

.member-modal-content {
  padding: 15px 0;
}

.member-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.add-member-content {
  padding: 15px 0;
}

.form-section {
  margin-bottom: 15px;

  label {
    display: block;
    font-size: 14px;
    color: var(--color-text-primary);
    margin-bottom: 8px;
  }

  .select-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .refresh-btn {
    cursor: pointer;
    color: var(--color-active);
    font-size: 16px;
    transition: color 0.15s ease;
    flex-shrink: 0;
    padding: 8px;

    &:hover {
      color: var(--color-active);
    }
  }
}

.footer-buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 15px;
}
</style>
