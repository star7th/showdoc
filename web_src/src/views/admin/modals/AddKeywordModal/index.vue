<template>
  <CommonModal
    :show="show"
    :title="$t('admin.add_filter_keyword')"
    width="480px"
    @close="handleClose"
  >
    <div class="form-group">
      <label class="form-label">{{ $t('admin.keyword') }}</label>
      <CommonInput
        v-model="form.keyword"
        :placeholder="$t('admin.keyword_placeholder')"
      />
      <div class="tip-text">{{ $t('admin.keyword_tip') }}</div>
    </div>
    <div class="form-group">
      <label class="form-label">{{ $t('admin.process_option') }}</label>
      <div class="checkbox-group">
        <label class="checkbox-item">
          <input v-model="form.is_replace" type="checkbox" />
          <span>{{ $t('admin.replace_and_block') }}</span>
        </label>
        <label class="checkbox-item">
          <input v-model="form.paid_skip" type="checkbox" />
          <span>{{ $t('admin.paid_skip') }}</span>
        </label>
      </div>
      <div class="tip-text">{{ $t('admin.process_option_tip') }}</div>
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
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import { normalizeBooleanToNumber } from '@/utils/system'

const props = defineProps<{
  onClose: (result: boolean, data: any) => void
}>()

const show = ref(false)
const form = reactive({
  keyword: '',
  is_replace: false,
  paid_skip: true
})

const handleClose = () => props.onClose(false, null)
const handleConfirm = () => {
  if (!form.keyword) {
    return
  }
  // 将布尔值转换为数字 1/0，兼容后端期望的数据类型
  props.onClose(true, {
    keyword: form.keyword,
    is_replace: normalizeBooleanToNumber(form.is_replace),
    paid_skip: normalizeBooleanToNumber(form.paid_skip)
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

