<template>
  <a-popover
    v-model:open="showPopover"
    :placement="placement"
    trigger="hover"
    :overlayStyle="{ zIndex: 999 }"
  >
    <template #content>
      <div class="more-card" :style="{ width: width }">
        <!-- 标题栏 -->
        <div v-if="title" class="more-card-header">
          <i :class="titleIcon" class="mr-3"></i>
          <span>{{ title }}</span>
        </div>

        <!-- 分组菜单模式 -->
        <template v-if="menuListGroup">
          <template v-for="item in menuListGroup" :key="item.group_name">
            <div class="group-bar">{{ item.group_name }}</div>
            <div
              v-for="item2 in item.listMenu"
              :key="item2.title"
              class="more-card-item"
              @mousedown.prevent="handleMenuClick(item2)"
            >
              <div class="more-card-item-left">
                <div class="more-card-item-icon">
                  <i :class="item2.icon"></i>
                </div>
              </div>
              <div class="more-card-item-right">
                <div class="right-div">
                  <div class="title">{{ item2.title }}</div>
                  <div class="desc">{{ item2.desc }}</div>
                </div>
              </div>
            </div>
          </template>
        </template>

        <!-- 普通菜单模式 -->
        <template v-if="menuList">
          <div
            v-for="item in menuList"
            :key="item.title"
            class="more-card-item"
            @mousedown.prevent="handleMenuClick(item)"
          >
            <div class="more-card-item-left">
              <div class="more-card-item-icon">
                <i :class="item.icon"></i>
              </div>
            </div>
            <div class="more-card-item-right">
              <div class="right-div">
                <div class="title">{{ item.title }}</div>
                <div class="desc">{{ item.desc }}</div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </template>

    <!-- 触发元素 -->
    <slot></slot>
  </a-popover>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface MenuItem {
  title: string
  icon?: string
  desc?: string
  method?: () => void
}

interface MenuGroup {
  group_name: string
  listMenu: MenuItem[]
}

interface Props {
  width?: string
  title?: string
  titleIcon?: string
  placement?: string
  menuList?: MenuItem[]
  menuListGroup?: MenuGroup[]
}

const props = withDefaults(defineProps<Props>(), {
  width: '300px',
  title: '',
  titleIcon: 'fas fa-cubes',
  placement: 'bottomLeft',
  menuList: () => [],
  menuListGroup: () => []
})

const showPopover = ref(false)

const handleMenuClick = (item: MenuItem) => {
  // 先关闭弹窗
  showPopover.value = false
  
  // 立即执行菜单项的方法
  if (item.method) {
    item.method()
  }
}
</script>

<style scoped lang="scss">
.s-dropdown-wrapper {
  display: inline-block;
  position: relative;
}

.more-card {
  border-radius: 8px;
  padding-left: 20px;
  padding-right: 20px;
  padding-bottom: 10px;
  background: var(--color-bg-secondary);
}

.more-card-item {
  padding-left: 10px;
  height: 80px;
  display: flex;
  cursor: pointer;
}

.more-card-item:not(:first-child) {
  border-top: 1px solid var(--color-border);
}

.more-card-header {
  height: 40px;
  line-height: 40px;
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
}

.group-bar {
  color: var(--color-text-secondary);
  font-size: 11px;
  height: 40px;
  line-height: 40px;
}

.more-card-item-icon {
  background-color: var(--color-bg-primary);
  width: 40px;
  height: 40px;
  justify-content: center;
  align-items: center;
  display: inline-flex;
  margin-right: 20px;
  border-radius: 10px;
  box-shadow: 0 0 4px var(--shadow-default);
}

.more-card-item-left,
.more-card-item-right {
  height: 80px;
  display: flex;
  justify-content: left;
  align-items: center;
}

.more-card-item-left {
  width: 60px;
}

.more-card-item-right .right-div {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: left;
}

.more-card-item-right .title {
  font-weight: 600;
  font-size: 14px;
  color: var(--color-text-primary);
}

.more-card-item-right .desc {
  margin-top: 10px;
  font-size: 11px;
  color: var(--color-text-secondary);
}

.mr-3 {
  margin-right: 12px;
}
</style>


