<template>
  <div class="common-textarea" :style="{ minHeight: `${props.rows * 20 + 26}px` }">
    <textarea
      ref="textareaRef"
      :placeholder="props.placeholder"
      :disabled="props.disabled"
      :rows="props.rows"
      @input="inputHandle"
      @focus="focusHandle"
      @blur="blurHandle"
      @mouseenter="mouseenterHandle"
      @mousemove="mousemoveHandle"
      @mouseout="mouseoutHandle"
      v-model="modelValueText"
    ></textarea>
    <div
      class="common-textarea-tooltips"
      v-show="showTooltips && !focused"
      :style="tooltipsPosition"
    >
      点击编辑
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref, watchEffect, nextTick } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue: string;
    placeholder?: string;
    disabled?: boolean;
    rows?: number;
  }>(),
  {
    placeholder: "",
    disabled: false,
    rows: 5,
  }
);
const emits = defineEmits(["update:modelValue"]);

const modelValueText = ref("");

watchEffect(() => {
  modelValueText.value = props.modelValue;
});

function inputHandle(e: InputEvent) {
  const target = e.target as HTMLTextAreaElement;
  emits("update:modelValue", target.value);
}

const focused = ref(true);
function focusHandle() {
  focused.value = true;
}

function blurHandle() {
  focused.value = false;
}

const showTooltips = ref(false);
const tooltipsPosition = ref("top: 0px; left: 0px;");
let tooltipsTimer: NodeJS.Timeout;
function mouseenterHandle(_e: MouseEvent) {
  clearTimeout(tooltipsTimer);
  tooltipsTimer = setTimeout(() => {
    showTooltips.value = true;
  }, 300);
}
function mousemoveHandle(e: MouseEvent) {
  tooltipsPosition.value = `top: ${e.offsetY}px; left: ${e.offsetX}px;`;
}
function mouseoutHandle(_e: MouseEvent) {
  clearTimeout(tooltipsTimer);
  showTooltips.value = false;
}

const textareaRef = ref<HTMLTextAreaElement>();
function focusAtLast() {
  nextTick(() => {
    const length = props.modelValue.length;
    textareaRef.value?.focus();
    textareaRef.value?.setSelectionRange(length, length);
  });
}

defineExpose({
  focusAtLast,
});
</script>
<style scoped lang="scss">
.common-textarea {
  position: relative;
  min-height: 80px; // 默认高度，外部可通过 class/style 覆盖

  textarea {
    position: absolute;
    width: 100%;
    height: 100%;
    color: var(--color-primary);
    font-family: Consolas, Menlo, Courier, monospace;
    font-size: var(--font-size-s);
    line-height: 20px;
    // 允许自动换行，同时保留手动换行
    white-space: pre-wrap;
    word-wrap: break-word;
    background-color: transparent;
    padding: 13px 15px;
    border: 1px solid var(--color-inapparent);
    outline: none;
    resize: none;
    z-index: 1;

    &:focus {
      background-color: var(--color-obvious);
    }

    &:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      background-color: var(--color-inapparent);
    }
  }
  .common-textarea-tooltips {
    position: absolute;
    font-size: var(--font-size-s);
    color: var(--color-obvious);
    white-space: nowrap;
    background-color: var(--color-primary);
    padding: 2px 5px;
    border: 1px solid var(--color-inapparent);
    border-radius: 4px;
    transform: translate(-50%, -150%);
    transition: opacity 0.15s ease;
    pointer-events: none;
    z-index: 2;
  }
}
</style>
