<template>
  <div
    class="message"
    :class="{
      show: show,
      'message-top': position === 'top',
      'message-bottom': position === 'bottom',
    }"
    @click="hide"
  >
    <span class="message-text" v-html="message"></span>
    <div class="close-icon" @click.stop="hide">
      <i class="fas fa-xmark"></i>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

const props = defineProps<{
  message?: string
  timer?: number
  position?: 'top' | 'bottom'
  onCancel?: () => void
}>()

const show = ref(false)
let timerId: ReturnType<typeof setTimeout> | null = null

onMounted(() => {
  setTimeout(() => {
    show.value = true
    if (props.timer) {
      timerId = setTimeout(() => {
        hide()
      }, props.timer)
    }
  })
})

function hide() {
  if (timerId) {
    clearTimeout(timerId)
    timerId = null
  }
  show.value = false
  setTimeout(() => {
    props.onCancel?.()
  }, 300)
}

defineExpose({
  hide,
})
</script>

<style lang="scss" scoped>
.message {
  position: fixed;
  left: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 144px;
  height: 30px;
  font-size: 12px;
  background-color: var(--color-default);
  padding: 0 10px;
  border-radius: 8px;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
  opacity: 0;
  z-index: 1001;
  transform: translate(-50%, 0);
  transition: all 0.15s ease-in-out;
  user-select: none;
  cursor: pointer;
  pointer-events: auto;

  &.message-bottom {
    bottom: -30px;

    &.show {
      opacity: 1;
      transform: translate(-50%, -40px);
    }
  }

  &.message-top {
    top: -30px;

    &.show {
      opacity: 1;
      transform: translate(-50%, 40px);
    }
  }

  .message-text {
    flex: 1;
    text-align: center;
  }

  .close-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    font-size: 12px;
    color: var(--color-primary);
    opacity: 0.6;
    transition: opacity 0.15s ease;
    flex-shrink: 0;

    &:hover {
      opacity: 1;
    }
  }
}
</style>

