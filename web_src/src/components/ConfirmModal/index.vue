<template>
  <div class="my-modal">
    <CommonModal
      :class="{ show }"
      :title="props.title || $t('common.tips')"
      :icon="['fas', 'circle-info']"
      :maxWidth="props.maxWidth || '450px'"
      @close="closeHandle"
    >
      <div class="modal-content">
        <div class="text">{{ msg }}</div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="closeHandle(false)">{{ cancelText }}</div>
        <div class="primary-button" @click="closeHandle(true)">
          {{ confirmText }}
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'

const { t } = useI18n()

const props = defineProps<{
  onClose: (res?: boolean) => void
  msg: string
  title?: string
  OkText?: string
  cancelText?: string
  maxWidth?: string
}>()

const confirmText = computed(() => props.OkText || t('common.confirm'))
const cancelText = computed(() => props.cancelText || t('common.cancel'))

const show = ref(false)

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
})

function closeHandle(res?: boolean) {
  show.value = false
  setTimeout(() => {
    props.onClose(res)
  }, 300)
}
</script>

<style lang="scss" scoped>
.my-modal :deep(.modal-content) {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
}

.text {
  width: 100%;
  line-height: 24px;
  word-break: break-word;
  text-align: center;
  padding: 0 20px;
  box-sizing: border-box;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  width: 100%;
  border-bottom: none;
}

.secondary-button,
.primary-button {
  width: 160px;
  margin: 0 7.5px;
  flex-shrink: 0;
}
</style>
