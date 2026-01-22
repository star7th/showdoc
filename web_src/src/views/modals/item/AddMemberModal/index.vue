<template>
  <div class="add-member-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.add_member')"
      :icon="['fas', 'fa-user-plus']"
      maxWidth="500px"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form layout="vertical">
          <a-form-item :label="t('item.input_target_member')">
            <CommonSelector
              v-model:value="form.username"
              :options="allUserOptions"
              :placeholder="t('item.search_member_placeholder')"
              :multiple="true"
              :show-search="true"
            />
          </a-form-item>

          <a-form-item :label="t('item.authority')">
            <a-radio-group v-model:value="form.member_group_id">
              <a-radio value="1">{{ t('item.edit_member') }}</a-radio>
              <a-radio value="0">{{ t('item.readonly_member') }}</a-radio>
              <a-radio value="2">{{ t('item.item_admin') }}</a-radio>
            </a-radio-group>
          </a-form-item>

          <a-form-item
            v-if="form.member_group_id < '2'"
            :label="t('item.catalog')"
          >
            <CommonSelector
              v-model:value="form.cat_ids"
              :multiple="true"
              :placeholder="t('item.all_cat2')"
              :options="catalogOptions"
            />
          </a-form-item>
        </a-form>

        <p class="tips-text">
          {{ t('item.member_authority_tips') }}
        </p>
      </div>

      <template #footer>
        <div class="footer-buttons">
          <CommonButton
            :text="t('common.cancel')"
            theme="light"
            @click="handleClose"
          />
          <CommonButton
            :text="t('common.confirm')"
            theme="dark"
            @click="handleSubmit"
          />
        </div>
      </template>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import AlertModal from '@/components/AlertModal'
import { saveMember, getMyAllList } from '@/models/member'
import { getAllUser } from '@/models/user'
import { getCatalogList } from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const myAllList = ref<any[]>([])
const allUserList = ref<any[]>([])
const catalogs = ref<any[]>([])

const form = ref({
  username: [] as string[],
  cat_ids: [],
  member_group_id: '1'
})

const catalogOptions = computed(() => {
  return catalogs.value.map(cat => ({
    label: cat.cat_name,
    value: cat.cat_id
  }))
})

// 全站用户选项
const allUserOptions = computed(() => {
  return allUserList.value.map(user => ({
    label: user.name
      ? `${user.username}(${user.name})`
      : user.username,
    value: user.username
  }))
})

// 获取全站用户列表
const fetchAllUser = async () => {
  try {
    const res = await getAllUser({ username: '' })
    if (res.error_code === 0 && res.data) {
      allUserList.value = res.data || []
    }
  } catch (error) {
    console.error('获取全站用户列表失败:', error)
  }
}

// 获取之前添加过的成员列表
const fetchMyAllList = async () => {
  try {
    const res = await getMyAllList()
    if (res.error_code === 0) {
      myAllList.value = res.data || []
    }
  } catch (error) {
    console.error('获取成员历史记录失败:', error)
  }
}

// 获取目录列表
const fetchCatalogs = async () => {
  try {
    const res = await getCatalogList(String(props.item_id))
    if (res.error_code === 0) {
      const list = res.data || []
      list.unshift({
        cat_id: 0,
        cat_name: t('item.all_cat')
      })
      catalogs.value = list.map((cat: any) => ({
        ...cat,
        cat_id: Number(cat.cat_id)
      }))
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

// 选择成员（保留向后兼容）
const handleSelectMember = ({ key }: { key: string }) => {
  // 这个函数保留，但现在主要使用 CommonSelector
}

// 提交
const handleSubmit = async () => {
  if (!form.value.username || form.value.username.length === 0) {
    await AlertModal(t('item.input_target_member') + t('common.required'))
    return
  }

  try {
    // 批量添加成员
    let successCount = 0
    let failCount = 0

    for (const username of form.value.username) {
      const res = await saveMember({
        item_id: props.item_id,
        username: username,
        cat_ids: (form.value.cat_ids || []).join(','),
        member_group_id: form.value.member_group_id
      })

      if (res.error_code === 0) {
        successCount++
      } else {
        failCount++
        console.error(`添加成员 ${username} 失败:`, res.error_message)
      }
    }

    if (successCount > 0) {
      let messageText = t('common.op_success')
      if (failCount > 0) {
        messageText = `${messageText} (成功${successCount}个，失败${failCount}个)`
      }
      message.success(messageText)
      handleClose(true)
    } else {
      message.error(t('common.op_failed'))
    }
  } catch (error) {
    console.error('添加成员失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 关闭
const handleClose = (success = false) => {
  show.value = false
  setTimeout(() => {
    props.onClose(success)
  }, 300)
}

onMounted(() => {
  show.value = true
  fetchMyAllList()
  fetchCatalogs()
  fetchAllUser()
})
</script>

<style scoped lang="scss">
.add-member-modal {
  :deep(.common-modal .modal-content) {
    max-width: 500px;
  }
}

.modal-content {
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

.dropdown-link {
  margin-left: 10px;
  cursor: pointer;
  color: var(--color-primary);

  i {
    margin-left: 4px;
  }
}

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin: 16px 0;
}

.footer-buttons {
  display: flex;
  justify-content: center;
  align-items: center;

  :deep(.common-button) {
    width: 160px;
    margin: 0 7.5px;
  }
}
</style>
