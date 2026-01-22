<template>
  <div class="add-team-member-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.add_member')"
      :icon="['fas', 'user-plus']"
      width="500px"
      @close="handleClose"
    >
      <div class="form-content">
        <div class="form-item">
          <div class="form-label">{{ t('item.member_username') }}</div>
          <div class="select-wrapper">
            <a-select
              v-model:value="memberForm.member_username"
              show-search
              :filter-option="filterOption"
              :placeholder="t('item.search_member_placeholder')"
              :loading="searchLoading"
              style="width: 100%"
            >
              <a-select-option
                v-for="user in userList"
                :key="user.username"
                :value="user.username"
                :label="user.username"
              >
                <div class="user-option">
                  <span class="username">{{ user.username }}</span>
                  <span class="name" v-if="user.name && user.name !== user.username">
                    ({{ user.name }})
                  </span>
                </div>
              </a-select-option>
            </a-select>
          </div>
        </div>

        <div class="form-item">
          <div class="form-label">{{ t('item.member_authority') }}</div>
          <div class="radio-group-wrapper">
            <a-radio-group v-model:value="memberForm.team_member_group_id">
              <a-radio value="1" class="radio-option">{{ t('item.ordinary_member') }}</a-radio>
              <a-radio value="2" class="radio-option">{{ t('item.team_admin') }}</a-radio>
            </a-radio-group>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="handleClose()">{{ t('common.cancel') }}</div>
        <div class="primary-button" @click="handleSubmit()">{{ t('common.confirm') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import { saveTeamMember } from '@/models/team'
import { getAllUser } from '@/models/user'

const { t } = useI18n()

const props = defineProps<{
  team_id: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const searchLoading = ref(false)
const userList = ref<any[]>([])
const memberForm = ref({
  member_username: '',
  team_member_group_id: '1'
})

// 获取全站用户列表
const fetchAllUser = async () => {
  searchLoading.value = true
  try {
    const res = await getAllUser({ username: '' })
    if (res.error_code === 0 && res.data) {
      // 格式化为下拉选项
      userList.value = res.data.map((user: any) => ({
        username: user.username,
        name: user.name,
        value: user.username,
        label: user.name ? `${user.username}(${user.name})` : user.username
      }))
    }
  } catch (error) {
    console.error('获取全站用户列表失败:', error)
  } finally {
    searchLoading.value = false
  }
}

// 过滤选项
const filterOption = (input: string, option: any) => {
  const label = option.label?.toLowerCase() || ''
  const value = option.value?.toLowerCase() || ''
  return label.includes(input.toLowerCase()) || value.includes(input.toLowerCase())
}

// 提交添加成员
const handleSubmit = async () => {
  if (!memberForm.value.member_username.trim()) {
    await AlertModal(t('item.member_username') + t('common.required'))
    return
  }

  try {
    const res = await saveTeamMember({
      team_id: props.team_id,
      member_username: memberForm.value.member_username,
      team_member_group_id: memberForm.value.team_member_group_id
    })

    if (res.error_code === 0) {
      Message.success(t('common.op_success'))
      handleClose()
      props.onClose(true)
    } else if (res.error_code === 10310) {
      await AlertModal(t('item.member_limit_exceeded_with_link'), { dangerouslyUseHTMLString: true })
    } else {
      await AlertModal(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('添加成员失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  fetchAllUser()
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.form-content {
  padding: 20px 24px;
}

.form-item {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  font-size: 14px;
  color: var(--color-text-primary);
  margin-bottom: 10px;
  font-weight: 500;
}

.input-wrapper {
  width: 100%;
}

.input-group {
  display: flex;
  align-items: stretch;
  position: relative;
  width: 100%;
}

.username-input {
  flex: 1;
}

.dropdown-trigger {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 8px;
  transition: color 0.15s ease;

  &:hover {
    color: var(--color-active);
  }
}

:deep(.member-menu) {
  max-height: 300px;
  overflow-y: auto;
}

.radio-group-wrapper {
  padding-top: 4px;
}

.radio-option {
  margin-right: 32px;

  &:last-child {
    margin-right: 0;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  border-bottom: none;
  width: 100%;
}

.secondary-button,
.primary-button {
  width: 120px;
  margin: 0 8px;
  flex-shrink: 0;
}
</style>

