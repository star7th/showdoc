<template>
  <div class="my-modal">
    <CommonModal
      :class="{ show }"
      :title="props.title"
      :icon="['fas', 'circle-info']"
      @close="closeHandle"
    >
      <div class="modal-content">
        <div class="input-wrapper">
          <input
            ref="inputRef"
            type="text"
            v-model="input"
            :placeholder="placeholder"
            class="prompt-input"
          />
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="closeHandle()">{{ $t('common.cancel') }}</div>
        <div class="primary-button" @click="closeHandle(input)">{{ $t('common.confirm') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'

const { t } = useI18n()

const props = withDefaults(
  defineProps<{
    onClose: (input: string) => void
    input?: string
    title?: string
    desc?: string
    placeholder?: string
  }>(),
  {
    input: '',
    title: '请输入',
    desc: '',
    placeholder: '请输入内容'
  }
)

const show = ref(false)
const input = ref('')
const inputRef = ref<HTMLInputElement>()

onMounted(() => {
  input.value = props.input || ''
  setTimeout(() => {
    show.value = true
    // 自动聚焦到输入框
    setTimeout(() => {
      inputRef.value?.focus()
    }, 100)
  })
})

function closeHandle(input = '') {
  show.value = false
  setTimeout(() => {
    props.onClose(input)
  }, 300)
}
</script>

<style lang="scss" scoped>
.my-modal {
  :deep(.modal-content) {
    padding: 30px 50px;
  }
}

.input-wrapper {
  width: 100%;
  display: flex;
  justify-content: center;
  padding: 20px 0;
}

.prompt-input {
  width: 100%;
  max-width: 400px;
  height: 50px;
  background-color: var(--color-secondary);
  padding: 0 10px;
  border-radius: 8px;
  text-align: center;
  font-size: 14px;
  box-sizing: border-box;
  border: 1px solid var(--color-border);
  outline: none;

  &:focus {
    background-color: var(--color-obvious);
    border-color: var(--color-primary);
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

// 暗黑主题适配
[data-theme='dark'] {
  .prompt-input {
    background-color: var(--color-secondary);
    border-color: var(--color-border);

    &:focus {
      background-color: var(--color-obvious);
      border-color: var(--color-primary);
    }
  }
}
</style>
