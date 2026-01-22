<template>
  <div class="import-openapi-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('item.open_api')"
      :icon="['fas', 'fa-terminal']"
      @close="handleClose"
    >
      <div class="modal-content">
        <div class="tips-container">
          <p class="tips">
            <span v-html="$t('item.open_api_tips1')"></span>
          </p>
          <p class="tips">
            <span v-html="$t('item.open_api_tips2')"></span>
          </p>
          <p class="tips">
            <span v-html="$t('item.open_api_tips3')"></span>
          </p>
          <p class="tips">
            <span v-html="$t('item.open_api_tips4')"></span>
          </p>
          <p class="tips">
            <span v-html="$t('item.open_api_tips5')"></span>
          </p>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="handleClose">{{ $t('common.cancel') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'

const { t } = useI18n()

const props = defineProps<{
  onClose: (result: boolean) => void
}>()

const show = ref(false)

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(async () => {
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  width: 500px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
  max-height: 60vh;
  overflow-y: auto;
}

.tips-container {
  line-height: 1.8;
}

.tips {
  color: var(--color-text-primary);
  margin-bottom: 16px;
  line-height: 1.8;

  :deep(a) {
  color: var(--color-primary);
  text-decoration: underline;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    color: var(--color-primary);
    opacity: 0.8;
    text-decoration: underline;
  }

  &:active {
    opacity: 0.6;
  }
  }

  :deep(br) {
    margin-bottom: 8px;
    display: block;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .secondary-button {
    width: 160px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: var(--font-size-m);
    font-weight: bold;
    cursor: pointer;
    background-color: var(--color-obvious);
    color: var(--color-primary);
    white-space: nowrap;
    margin: 0 7.5px;

    &:hover {
      background-color: var(--color-secondary);
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] .tips {
  color: var(--color-text-primary);
}
</style>
