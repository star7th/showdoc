<template>
  <div class="item-header">
    <div class="header-left">
      <a-tooltip :title="$t('item.back_to_item_list')">
        <div class="icon-item" @click="goBack">
          <i class="fas fa-arrow-left"></i>
        </div>
      </a-tooltip>
      <div class="item-name">{{ itemInfo?.item_name || '' }}</div>
    </div>
    <slot name="right"></slot>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'

// Props
interface Props {
  itemInfo?: any
  slots?: {
    right?: () => any
  }
}

const props = withDefaults(defineProps<Props>(), {
  itemInfo: () => ({}),
  slots: () => ({})
})

// Composables
const router = useRouter()
const { t } = useI18n()

// Methods
const goBack = () => {
  router.push('/item/index')
}
</script>

<style scoped lang="scss">
.item-header {
  height: 90px;
  background-color: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 15px;
}

.icon-item {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
    box-shadow: var(--shadow-sm);
  }

  i {
    color: var(--color-text-primary);
    font-size: 16px;
  }
}

.item-name {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-text-primary);
}
</style>

