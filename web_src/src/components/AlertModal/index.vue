<template>
  <div class="my-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('common.tips')"
      :icon="['fas', 'circle-info']"
      @close="closeHandle"
    >
      <div class="modal-content">
        <div class="text" v-html="dangerouslyUseHTMLString ? msg : msg"></div>
      </div>
      <div class="modal-footer">
        <div class="primary-button" @click="closeHandle">{{ $t('common.confirm') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import CommonModal from '@/components/CommonModal.vue'

const props = defineProps<{
  onClose: () => void
  msg: string
  dangerouslyUseHTMLString?: boolean
}>()

const show = ref(false)

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
})

function closeHandle() {
  show.value = false
  setTimeout(() => {
    props.onClose()
  }, 300)
}
</script>

<style lang="scss" scoped>
.modal-content {
  width: 400px;
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);

  .text {
    line-height: 24px;
    word-break: break-all;
    text-align: center;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .secondary-button,
  .primary-button {
    width: 160px;
    margin: 0 7.5px;
  }
}
</style>

