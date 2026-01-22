<template>
  <div>
    <a-spin :spinning="props.spinning" size="small" :delay="300">
      <div class="menu-button center" :class="props.theme">
        <div
          class="main-content center clickable bgColor-select"
          @click="handleMainButtonClick"
        >
          <i v-if="props.leftIcon" :class="getIconClass(props.leftIcon)"></i>
          <div class="text">{{ props.text }}</div>
        </div>
        <div
          class="menu-handle center clickable bgColor-select"
          @click.stop="menuHandle"
        >
          <i class="icon fas fa-angle-down"></i>
        </div>
      </div>
    </a-spin>
  </div>
</template>
<script setup lang="ts">
import ContextmenuModal from "./ContextmenuModal"
import type { ContextmenuModalItemInterface } from "./ContextmenuModal"

const props = withDefaults(
  defineProps<{
    text: string
    theme?: "light" | "dark"
    list: ContextmenuModalItemInterface[]
    spinning?: boolean
    leftIcon?: string[]
    onClick?: () => void
  }>(),
  {
    theme: "light",
    spinning: false,
  }
)

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

function menuHandle(e: MouseEvent) {
  ContextmenuModal({
    x: e.x,
    y: e.y,
    list: props.list,
  })
}

// 处理主按钮点击
function handleMainButtonClick() {
  // 如果设置了 onClick 回调，则执行
  if (props.onClick) {
    props.onClick()
  }
  // 否则不执行任何操作，用户可以点击下拉箭头打开菜单
}
</script>
<style lang="scss" scoped>
.menu-button {
  display: flex;
  align-items: center;
  width: 150px;
  height: 36px;
  font-weight: 600;
  border-radius: 6px;
  border: 1px solid var(--color-inactive);
  user-select: none;
  transition: all 0.15s ease;

  .main-content {
    flex-grow: 1;
    display: flex;
    align-items: center;
    gap: 0.6em;
    height: 100%;
    padding-left: 10px;

    i {
      font-size: 14px;
      flex-shrink: 0;
    }

    .text {
      white-space: nowrap;
    }
  }

  .menu-handle {
    min-width: 30px;
    height: 100%;

    .icon {
      font-size: 14px;
    }

    &::before {
      content: "";
      position: absolute;
      left: 0;
      width: 1px;
      height: 26px;
    }
  }

  &.light {
    // 亮色模式：浅色背景 + 深色文字（普通按钮）
    color: var(--color-primary);
    background-color: var(--color-obvious);

    // 确保文字颜色不被全局样式覆盖
    .text {
      color: var(--color-primary) !important;
    }

    .main-content {
      i {
        color: var(--color-primary);
      }
    }

    .menu-handle {
      .icon {
        color: var(--color-primary);
      }

      &::before {
        background-color: var(--color-inactive);
      }
    }
  }

  &.dark {
    // 亮色模式：深色背景 + 白色文字（强调按钮）
    color: var(--color-obvious);
    background-color: var(--color-primary);

    // 确保文字颜色不被全局样式覆盖
    .text {
      color: var(--color-obvious) !important;
    }

    .main-content {
      i {
        color: var(--color-obvious);
      }
    }

    .menu-handle {
      .icon {
        color: var(--color-obvious);
      }

      &::before {
        background-color: var(--color-sixth);
      }
    }
  }
  
  // 暗黑主题适配
  [data-theme="dark"] & {
    &.light {
      .main-content {
        i {
          color: var(--color-text-primary);
        }
      }

      .menu-handle {
        .icon {
          color: var(--color-text-primary);
        }
      }
    }

    &.dark {
      // 暗黑模式：使用激活色（蓝色）作为强调按钮背景，保持突出效果
      // 蓝色是常见的"主要操作"颜色，在暗黑模式下能有效吸引注意力
      color: var(--color-primary);
      background-color: var(--color-active);
      border-color: var(--color-interval); // 使用主题变量

      // 确保文字颜色不被全局样式覆盖
      .text {
        color: var(--color-primary) !important;
      }

      .main-content {
        i {
          color: var(--color-primary);
        }
      }

      .menu-handle {
        .icon {
          color: var(--color-primary);
        }

        &::before {
          background-color: var(--color-interval); // 使用主题变量
        }
      }
    }
  }
}
</style>

