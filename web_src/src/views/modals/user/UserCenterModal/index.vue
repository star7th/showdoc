<template>
  <div class="user-center-modal">
    <!-- 主弹窗 -->
    <CommonModal
      :class="{ show }"
      :title="$t('user.user_center')"
      :icon="['fas', 'user']"
      width="600px"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- 用户信息 -->
        <div v-if="userInfo.username" class="user-info">
          <div @click="handleEditName" class="user-name">
            <span class="name">{{ userInfo.name || $t('user.unnamed_user') }}</span>
            <i class="fas fa-pencil edit-icon"></i>
          </div>
          <div class="username">{{ userInfo.username }}</div>
          
        </div>

        <!-- 菜单列表 -->
        <div class="menu-list">
          <div v-if="isZhCn" class="menu-item" @click="handlePushUrl">
            <div class="menu-item-content">
              <div class="menu-item-icon push-icon">
                <i class="fas fa-bell"></i>
              </div>
              <span class="menu-item-label">{{ $t('user.wechat_push_url') }}</span>
            </div>
            <i class="fas fa-chevron-right arrow-icon"></i>
          </div>
          
          <div class="menu-item" @click="handlePassword">
            <div class="menu-item-content">
              <div class="menu-item-icon password-icon">
                <i class="fas fa-key"></i>
              </div>
              <span class="menu-item-label">{{ $t('user.modify_password') }}</span>
            </div>
            <i class="fas fa-chevron-right arrow-icon"></i>
          </div>
        </div>

        <!-- 底部按钮 -->
        <div class="bottom-actions">
          <div class="action-btn logout-btn" @click="handleLogout">
            {{ $t('user.logout') }}
          </div>
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import CommonModal from '@/components/CommonModal.vue'
import ConfirmModal from '@/components/ConfirmModal'
import AlertModal from '@/components/AlertModal'
import Message from '@/components/Message'
import request from '@/utils/request'
import PromptModal from '@/components/PromptModal'
import PasswordModal from '../PasswordModal'
import PushUrlModal from '../PushUrlModal'

const { t, locale } = useI18n()
const router = useRouter()
const userStore = useUserStore()

const props = defineProps<{
  onClose: (result: boolean) => void
}>()

// 主弹窗显示状态
const show = ref(false)

// 用户信息
const userInfo = ref<any>({
  username: '',
  name: ''
})

// 推送地址
const pushUrlForm = ref({
  url: ''
})

// 是否为中文用户（推送地址只对中文用户显示）
const isZhCn = computed(() => {
  return locale.value.toLowerCase() === 'zh-cn'
})

// 获取用户信息
const getUserInfo = async () => {
  try {
    const data = await request('/api/user/info', {})
    if (data && data.data) {
      userInfo.value = data.data
      // 获取用户推送地址
      getPushUrl()
    }
  } catch (error) {
    console.error('获取用户信息失败:', error)
  }
}

// 编辑姓名
const handleEditName = async () => {
  const newName = await PromptModal(
    t('user.edit_name'),
    userInfo.value.name,
    t('user.name_tips')
  )
  if (newName && newName !== userInfo.value.name) {
    try {
      await request('/api/user/updateInfo', { name: newName })
      Message.success(t('user.modify_success'))
      getUserInfo()
    } catch (error) {
      console.error('修改姓名失败:', error)
    }
  }
}

// 修改密码
const handlePassword = async () => {
  await PasswordModal()
}

// 获取用户推送地址
const getPushUrl = async () => {
  try {
    const data = await request('/api/user/getPushUrl', {})
    if (data && data.data) {
      pushUrlForm.value.url = data.data
    }
  } catch (error) {
    console.error('获取推送地址失败:', error)
  }
}

// 编辑推送地址
const handlePushUrl = async () => {
  const result = await PushUrlModal()
  if (result) {
    // 刷新推送地址
    await getPushUrl()
  }
}

// 退出登录
const handleLogout = async () => {
  const confirmed = await ConfirmModal({
    title: t('common.confirm'),
    msg: t('user.confirm_logout'),
    confirmText: t('common.confirm'),
    cancelText: t('common.cancel'),
  })

  if (confirmed) {
    await request('/api/user/logout', { confirm: '1' })

    // 清空所有cookies
    const keys = document.cookie.match(/[^ =;]+(?==)/g)
    if (keys) {
      keys.forEach((key) => {
        document.cookie = `${key}=0;expires=${new Date(0).toUTCString()}`
      })
    }

    // 清空 localStorage
    localStorage.clear()

    // 更新用户 store
    userStore.logout()

    // 跳转到首页
    router.push({ path: '/' })

    handleClose()
  }
}

const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(true)
  }, 300)
}

onMounted(() => {
  getUserInfo()
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
}

.user-info {
  text-align: center;
  margin-bottom: 30px;

  .user-name {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    cursor: pointer;
    transition: opacity 0.15s ease;

    &:hover {
      opacity: 0.8;

      .edit-icon {
        color: var(--color-active);
      }
    }

    .name {
      color: var(--color-text-primary);
    }

    .edit-icon {
      color: var(--color-grey);
      font-size: 14px;
      transition: color 0.15s ease;
    }
  }

  .username {
    color: var(--color-grey);
    font-size: 14px;
  }

  .push-url-info {
    margin-top: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: opacity 0.15s ease;

    &:hover {
      opacity: 0.8;

      .edit-icon {
        color: var(--color-active);
      }
    }

    .push-url-label {
      color: var(--color-grey);
    }

    .push-url-value {
      color: var(--color-text-primary);
    }

    .edit-icon {
      color: var(--color-grey);
      font-size: 14px;
      transition: color 0.15s ease;
    }
  }
}

.menu-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.menu-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background-color: var(--color-bg-secondary);
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
  }
}

.menu-item-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.menu-item-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--color-obvious);

  i {
    font-size: 16px;
  }
}

.password-icon {
  background-color: var(--color-grey);
}

.push-icon {
  background-color: var(--color-success);
}

.menu-item-label {
  font-size: 14px;
  color: var(--color-text-primary);
}

.menu-item-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.arrow-icon {
  font-size: 12px;
  color: var(--color-grey);
}

.bottom-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 24px;
}

.action-btn {
  width: 100%;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg-secondary);
  border-radius: 8px;
  font-size: 14px;
  color: var(--color-text-primary);
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
  }
}

.logout-btn {
  &:hover {
    background-color: var(--hover-overlay);
  }
}

// 暗黑主题适配
[data-theme='dark'] {
  .menu-item {
    background-color: var(--color-bg-secondary);

    &:hover {
      background-color: var(--hover-overlay);
    }
  }

  .action-btn {
    background-color: var(--color-bg-secondary);

    &:hover {
      background-color: var(--hover-overlay);
    }
  }

  .logout-btn {
    color: var(--color-error);

    &:hover {
      background-color: var(--color-bg-secondary);
    }
  }
}
</style>
