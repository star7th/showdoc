<template>
  <div>
    <a-spin :spinning="props.spinning" size="small" :delay="300">
      <div
        class="common-button center"
        :class="[props.theme, { disabled: props.disabled, 'full-width': props.fullWidth }]"
        :style="{ fontWeight: props.bold ? '600' : 'normal' }"
      >
        <i v-if="props.leftIcon" class="icon" :class="getIconClass(props.leftIcon)"></i>
        <div class="common-button-text">
          <slot v-if="$slots.default"></slot>
          <span v-else>{{ props.text }}</span>
        </div>
        <i v-if="props.rightIcon" class="icon" :class="getIconClass(props.rightIcon)"></i>
      </div>
    </a-spin>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    text?: string;
    theme?: "light" | "dark";
    leftIcon?: string[];
    rightIcon?: string[];
    disabled?: boolean;
    spinning?: boolean;
    bold?: boolean;  // 默认值为 true，即加粗
    fullWidth?: boolean;  // 是否全宽
    type?: "default" | "link";  // 按钮类型：默认按钮或链接按钮
    danger?: boolean;  // 是否为危险操作（删除等）
  }>(),
  {
    theme: "light",
    disabled: false,
    spinning: false,
    bold: true,  // 默认值为 true，即加粗
    fullWidth: false,  // 默认不全宽
    type: "default",  // 默认为普通按钮
    danger: false,  // 默认不是危险操作
  }
);

// 内部实现：处理图标数组，转换为 FontAwesome 类名格式
function getIconClass(icon: string[] | string): string {
  if (Array.isArray(icon)) {
    const style = icon[0]; // fas, far 等
    const iconName = icon[1]; // 图标名称

    // 如果图标名称已经包含 fa- 前缀，就直接使用
    if (iconName.startsWith('fa-')) {
      return `${style} ${iconName}`;
    }

    // 否则，添加 fa- 前缀
    return `${style} fa-${iconName}`;
  }
  return icon;
}
</script>

<style lang="scss" scoped>
.common-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6em;
  width: 150px;
  height: 36px;
  white-space: nowrap;
  border-radius: 6px;
  border: 1px solid var(--color-inactive);
  user-select: none;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover:not(.disabled) {
    background: color-mix(in srgb, var(--color-obvious) 90%, black 10%);
    box-shadow: var(--shadow-sm);
  }

  &:active:not(.disabled) {
    transform: scale(0.98);
  }

  &.full-width {
    width: 100%;
  }

  &.disabled {
    opacity: 0.3;
    pointer-events: none;
    cursor: not-allowed;
  }

  // 链接类型按钮（用于表格操作等场景）
  &.type-link {
    width: auto;
    height: auto;
    min-width: auto;
    border: none;
    background: none;
    color: var(--color-active);
    font-weight: 400;
    padding: 0;
    gap: 0.3em;

    &:hover:not(.disabled) {
      opacity: 0.8;
      transform: none;
      text-decoration: underline;
    }

    &:active:not(.disabled) {
      transform: none;
    }

    &.danger {
      color: #ff4d4f;

      &:hover:not(.disabled) {
        opacity: 0.8;
        color: #ff4d4f;
      }
    }
  }

  &.light {
    background-color: var(--color-obvious);
    border-color: var(--color-border);

    .common-button-text,
    .common-button-text span {
      color: var(--color-text-primary) !important;
    }

    .icon {
      color: var(--color-text-primary);
    }

    &:hover:not(.disabled) {
      background: color-mix(in srgb, var(--color-obvious) 90%, black 10%);
    }
  }

  &.dark {
    background-color: var(--color-primary);
    border-color: var(--color-primary);

    .common-button-text,
    .common-button-text span {
      color: var(--color-obvious) !important;
    }

    .icon {
      color: var(--color-obvious);
    }

    &:hover:not(.disabled) {
      background: color-mix(in srgb, var(--color-primary) 85%, black 15%);
    }
  }

  // 暗黑主题适配
  [data-theme="dark"] & {
    &.light {
      .icon {
        color: var(--color-text-primary);
      }
    }

    &.dark {
      .icon {
        color: var(--color-primary);
      }
    }
  }

  .icon {
    flex-shrink: 0;
    font-size: 14px;
  }

  .common-button-text {
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>
