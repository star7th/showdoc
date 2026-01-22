<template>
  <CommonModal
    :show="show"
    :title="$t('admin.ban_item')"
    width="480px"
    @close="handleClose"
  >
    <div v-if="itemName" class="form-group">
      <label class="form-label">{{ $t('admin.item_name') }}</label>
      <span class="form-value">{{ itemName }}</span>
    </div>
    <div class="form-group">
      <label class="form-label">{{ $t('admin.ban_reason') }}</label>
      <CommonTextarea
        v-model="banForm.remark"
        :rows="3"
        :placeholder="$t('admin.ban_reason_placeholder')"
      />
    </div>
    <div class="form-group">
      <label class="form-label">{{ $t('admin.access_restrict') }}</label>
      <div class="checkbox-group">
        <label class="checkbox-item">
          <input
            v-model="banFormChecked.allow_paid_access"
            type="checkbox"
            value="allow_paid_access"
          />
          <span>{{ $t('admin.allow_paid_access') }}</span>
        </label>
        <label class="checkbox-item">
          <input
            v-model="banFormChecked.forbid_visitor"
            type="checkbox"
            value="forbid_visitor"
          />
          <span>{{ $t('admin.forbid_visitor') }}</span>
        </label>
        <label class="checkbox-item">
          <input
            v-model="banFormChecked.forbid_all"
            type="checkbox"
            value="forbid_all"
          />
          <span>{{ $t('admin.forbid_all') }}</span>
        </label>
      </div>
      <div class="tip-text">{{ $t('admin.allow_paid_access_tip') }}</div>
    </div>
    <template #footer>
      <CommonButton theme="light" :text="$t('common.cancel')" @click="handleClose" />
      <CommonButton theme="dark" :text="$t('common.confirm')" @click="handleConfirm" />
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'
import CommonButton from '@/components/CommonButton.vue'
import { normalizeBooleanToNumber } from '@/utils/system'

const props = defineProps<{
  item_id: number
  item_name?: string
  onClose: (result: boolean, data: any) => void
}>()

const show = ref(false)
const itemName = ref(props.item_name || '')
const banForm = reactive({
  item_id: props.item_id,
  remark: 'showdoc为IT团队文档工具，不支持其他内容',
  allow_paid_access: false,
  forbid_visitor: true,
  forbid_all: false
})
const banFormChecked = reactive({
  allow_paid_access: false,
  forbid_visitor: true,
  forbid_all: false
})

const handleClose = () => props.onClose(false, null)

const handleConfirm = () => {
  props.onClose(true, {
    item_id: banForm.item_id,
    remark: banForm.remark || 'showdoc为IT团队文档工具，不支持其他内容',
    // 将布尔值转换为数字 1/0，兼容后端期望的数据类型
    allow_paid_access: normalizeBooleanToNumber(banFormChecked.allow_paid_access),
    forbid_visitor: normalizeBooleanToNumber(banFormChecked.forbid_visitor),
    forbid_all: normalizeBooleanToNumber(banFormChecked.forbid_all)
  })
}

onMounted(() => {
  show.value = true
})
</script>

<style lang="scss" scoped>
.form-group {
  margin-bottom: 16px;

  .form-label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-primary);
  }

  .form-value {
    display: block;
    padding: 8px 0;
    color: var(--color-primary);
  }
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 8px;

  .checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: var(--color-primary);
    font-size: 14px;

    input[type="checkbox"] {
      width: 16px;
      height: 16px;
      cursor: pointer;

      // 适配原生checkbox颜色
      accent-color: var(--color-active);
    }
  }
}

.tip-text {
  margin-top: 8px;
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.5;
}
</style>

