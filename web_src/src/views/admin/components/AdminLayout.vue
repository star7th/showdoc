<template>
  <div class="admin-layout">
    <!-- 顶部 Header -->
    <div class="admin-header">
      <div class="header-left">
        <a-tooltip :title="$t('item.back_to_item_list')" :mouseEnterDelay="0.3" :destroyTooltipOnHide="true">
          <div class="icon-item" @click="goBack">
            <i class="fas fa-arrow-left"></i>
          </div>
        </a-tooltip>
        <div class="header-title">{{ $t('admin.admin_panel') }}</div>
      </div>

      <div class="header-right">
        <!-- 主题切换 -->
        <a-tooltip :title="appStore.theme === 'light' ? $t('common.dark_mode') : $t('common.light_mode')" placement="bottom">
          <div class="icon-item theme-toggle-item" @click="handleToggleTheme">
            <i class="fas fa-circle-half-stroke"></i>
          </div>
        </a-tooltip>
      </div>
    </div>

    <div class="admin-container">
      <!-- 左侧菜单 -->
      <div class="admin-sidebar">
        <div class="sidebar-menu">
          <div
            v-for="menu in menuList"
            :key="menu.key"
            class="menu-item"
            :class="{ active: selectedMenuKeys[0] === menu.key }"
            @click="handleMenuSelect(menu.key)"
          >
            <div class="menu-icon">
              <i :class="menu.icon"></i>
            </div>
            <span class="menu-label">{{ menu.label }}</span>
          </div>
        </div>
      </div>

      <!-- 右侧内容 -->
      <div class="admin-content">
        <component :is="currentComponent" v-if="currentComponent" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/store'

// 导入各个管理模块组件
import UserManagement from './UserManagement.vue'
import ItemManagement from './ItemManagement.vue'
import AnnouncementManagement from './AnnouncementManagement.vue'
import AttachmentManagement from './AttachmentManagement.vue'
import ExtLogin from './ExtLogin.vue'
import SystemSettings from './SystemSettings.vue'
import AboutSite from './AboutSite.vue'

const router = useRouter()
const { t } = useI18n()
const appStore = useAppStore()

const selectedMenuKeys = ref(['user'])

// 切换主题
const handleToggleTheme = () => {
  appStore.toggleTheme()
}

// 菜单配置
const menuList = computed(() => [
  { key: 'user', label: t('admin.user_manage'), icon: 'fas fa-users' },
  { key: 'item', label: t('admin.item_manage'), icon: 'fas fa-file-alt' },
  { key: 'announcement', label: t('admin.announcement_manage'), icon: 'fas fa-bullhorn' },
  { key: 'attachment', label: t('admin.attachment_manage'), icon: 'fas fa-paperclip' },
  { key: 'ext-login', label: t('admin.ext_login'), icon: 'fas fa-plug' },
  { key: 'setting', label: t('admin.web_setting'), icon: 'fas fa-cog' },
  { key: 'about', label: t('admin.about_site'), icon: 'fas fa-info-circle' }
])

// 菜单项与组件的映射
const componentMap: Record<string, any> = {
  user: UserManagement,
  item: ItemManagement,
  announcement: AnnouncementManagement,
  attachment: AttachmentManagement,
  'ext-login': ExtLogin,
  setting: SystemSettings,
  about: AboutSite
}

// 当前组件
const currentComponent = computed(() => {
  return componentMap[selectedMenuKeys.value[0]]
})

const handleMenuSelect = (key: string) => {
  selectedMenuKeys.value = [key]
}

const goBack = () => {
  router.push('/item/index')
}

onMounted(() => {
  selectedMenuKeys.value = ['user']
})
</script>

<style lang="scss" scoped>
.admin-layout {
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background-color: var(--color-default);
}

.admin-header {
  height: 90px;
  background-color: var(--color-secondary);
  border-bottom: 1px solid var(--color-interval);
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 999;

  .header-left {
    display: flex;
    align-items: center;
    gap: 16px;

    .icon-item {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--color-obvious);
      border-radius: 8px;
      cursor: pointer;
      box-shadow: var(--shadow-xs);
      transition: all 0.15s ease;

      &:hover {
        background-color: var(--hover-overlay);
        box-shadow: var(--shadow-sm);
      }

      i {
        color: var(--color-primary);
        font-size: 14px;
        transition: all 0.15s ease;
      }
    }

    .header-title {
      font-size: 16px;
      font-weight: 600;
      color: var(--color-primary);
    }
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 10px;

    .icon-item {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--color-obvious);
      border-radius: 8px;
      cursor: pointer;
      box-shadow: var(--shadow-xs);
      transition: all 0.15s ease;

      &:hover {
        background-color: var(--hover-overlay);
        box-shadow: var(--shadow-sm);
      }

      i {
        color: var(--color-primary);
        font-size: 14px;
        transition: all 0.15s ease;
      }

      &.theme-toggle-item i {
        color: var(--color-active);
      }
    }
  }
}

.admin-container {
  display: flex;
  margin-top: 90px;
  height: calc(100vh - 90px);
}

.admin-sidebar {
  width: 200px;
  background-color: var(--color-secondary);
  border-right: 1px solid var(--color-interval);
  flex-shrink: 0;
  overflow-y: auto;

  .sidebar-menu {
    padding: 8px;

    .menu-item {
      display: flex;
      align-items: center;
      padding: 10px 12px;
      margin-bottom: 4px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.15s ease;
      color: var(--color-primary);

      .menu-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 8px;

        i {
          font-size: 14px;
          transition: all 0.15s ease;
        }
      }

      .menu-label {
        flex: 1;
        font-size: 13px;
        font-weight: 500;
      }

      &:hover {
        background-color: var(--hover-overlay);
      }

      &.active {
        background-color: var(--hover-overlay);
        font-weight: 600;
      }
    }
  }

  // 自定义滚动条
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: var(--color-default);
  }

  &::-webkit-scrollbar-thumb {
    background: var(--color-inactive);
    border-radius: 3px;

    &:hover {
      background: var(--color-primary);
    }
  }
}

.admin-content {
  flex: 1;
  overflow-y: auto;
  background-color: var(--color-default);

  // 自定义滚动条
  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: var(--color-secondary);
  }

  &::-webkit-scrollbar-thumb {
    background: var(--color-inactive);
    border-radius: 3px;

    &:hover {
      background: var(--color-primary);
    }
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .admin-header {
    .header-left {
      .icon-item {
        &:hover {
          background-color: var(--hover-overlay);
        }
      }
    }

    .header-right {
      .icon-item {
        &:hover {
          background-color: var(--hover-overlay);
        }
      }
    }
  }

  .admin-sidebar {
    .sidebar-menu {
      .menu-item {
        &:hover {
          background-color: var(--hover-overlay);
        }

        &.active {
          background-color: var(--color-primary);
          color: var(--color-text-primary);
        }
      }
    }
  }
}
</style>
