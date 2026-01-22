<template>
  <div class="login-page">
    <div class="toggle-wrapper">
      <div class="toggle-group">
        <ThemeToggle />
        <LanguageToggle />
      </div>
    </div>
    <div class="login-card">
      <div class="card-header">
        <h2 class="title">{{ $t('user.login') }}</h2>
      </div>

      <div class="form-inputs">
        <CommonInput
          v-model="form.username"
          :placeholder="$t('user.username_description')"
          @keyup.enter="handleLogin"
        />

        <CommonInput
          v-model="form.password"
          type="password"
          :placeholder="$t('user.password')"
          @keyup.enter="handleLogin"
        />

        <div class="captcha-input">
          <CommonInput
            v-model="form.captcha"
            :placeholder="$t('user.verification_code')"
            @keyup.enter="handleLogin"
          />
          <img
            v-if="captchaImg"
            :src="captchaImg"
            alt="验证码"
            class="captcha-img"
            @click="refreshCaptcha"
          />
        </div>
      </div>

      <CommonButton
        :text="$t('user.login')"
        theme="dark"
        :loading="loading"
        :full-width="true"
        @click="handleLogin"
      />

      <div class="footer-links">
        <router-link v-if="registerOpen" to="/user/register">{{ $t('user.register_new_account') }}</router-link>
        <a v-if="oauth2Config.enabled" class="oauth2-link" @click="handleOauth2Login">
          {{ oauth2Config.entrance_tips || $t('user.oauth2_login') }}
        </a>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/store/user'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageToggle from '@/components/LanguageToggle.vue'
import AlertModal from '@/components/AlertModal/index'
import ConfirmModal from '@/components/ConfirmModal/index'
import { getServerHost, appendUrlParams } from '@/utils/system'
import request from '@/utils/request'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const userStore = useUserStore()

const form = ref({
  username: '',
  password: '',
  captcha: '',
})

const loading = ref(false)
const captchaId = ref(0)
const captchaImg = ref('')
const showAlert = ref(false)

// OAuth2 配置
const oauth2Config = ref({
  enabled: false,
  entrance_tips: ''
})
// 注册开关
const registerOpen = ref(true)

// 刷新验证码
const refreshCaptcha = async () => {
  try {
    const data = await request('/api/common/createCaptcha', {}, 'post', false)
    if (data.error_code === 0) {
      captchaId.value = data.data.captcha_id
      // 构建验证码图片URL
      const serverHost = getServerHost()
      let captchaUrl = `${serverHost}/api/common/showCaptcha`
      captchaUrl = appendUrlParams(captchaUrl, {
        captcha_id: captchaId.value,
        t: Date.now()
      })
      captchaImg.value = captchaUrl
    }
  } catch (error) {
    console.error('获取验证码失败:', error)
  }
}

// 获取 OAuth2 配置
const getOauthConfig = async () => {
  try {
    const data = await request('/api/user/oauthInfo', {}, 'get', false)
    if (data.error_code === 0 && data.data) {
      oauth2Config.value.enabled = data.data.oauth2_open > 0 || data.data.oauth2_open === true
      oauth2Config.value.entrance_tips = data.data.oauth2_entrance_tips || ''
    }
  } catch (error) {
    console.error('获取OAuth2配置失败:', error)
  }
}

// 获取注册配置
const getRegisterConfig = async () => {
  try {
    const data = await request('/api/common/homePageSetting', {}, 'post', false)
    if (data.error_code === 0 && data.data) {
      registerOpen.value = data.data.register_open > 0 || data.data.register_open === true
    }
  } catch (error) {
    console.error('获取注册配置失败:', error)
  }
}

// 脚本定时任务
const scriptCron = async () => {
  try {
    await request('/api/ScriptCron/run', {}, 'get', false)
  } catch (error) {
    console.error('脚本定时任务失败:', error)
  }
}

// 验证 redirect 参数，防止钓鱼跳转
const validateRedirect = (redirect: string): boolean => {
  try {
    const decoded = decodeURIComponent(redirect)
    if (
      decoded.search(/[^A-Za-z0-9/:\?\._\*\+\-]+.*/i) > -1 ||
      decoded.indexOf('.') > -1 ||
      decoded.indexOf('//') > -1
    ) {
      return false
    }
    return true
  } catch {
    return false
  }
}

// 登录处理
const handleLogin = async () => {
  if (showAlert.value) {
    return
  }

  // 验证 redirect 参数
  if (route.query.redirect && !validateRedirect(route.query.redirect as string)) {
    AlertModal(t('user.illegal_redirect'))
    return
  }

  loading.value = true

  try {
    const data = await request(
      '/api/user/loginByVerify',
      {
        username: form.value.username,
        password: form.value.password,
        captcha: form.value.captcha,
        captcha_id: captchaId.value,
        redirect_login: false,
      },
      'post',
      false
    )

    if (data.error_code === 0) {
      await handleLoginSuccess(data.data)
    } else {
      refreshCaptcha()
      showAlert.value = true
      AlertModal(data.error_message || t('common.error'), {
        callback: () => {
          setTimeout(() => {
            showAlert.value = false
          }, 500)
        },
      })
    }
  } catch (error) {
    refreshCaptcha()
    console.error('登录失败:', error)
  } finally {
    loading.value = false
  }
}

// 登录成功处理
const handleLoginSuccess = async (userinfo: any) => {
  // 检查是否有多个账号绑定了同一个邮箱
  if (userinfo.same_email_accounts && userinfo.same_email_accounts.length > 0) {
    const otherAccounts = userinfo.same_email_accounts.join('、')
    const result = await ConfirmModal(
      t('user.multi_account_binding_tip', {
        email: userinfo.email,
        accounts: otherAccounts,
        username: userinfo.username,
      }),
      t('user.multi_account_binding_title'),
      {
        confirmText: t('user.continue_login'),
        cancelText: t('user.switch_account'),
      }
    )

    if (result) {
      // 用户选择继续以当前账号登录
      localStorage.setItem('userinfo', JSON.stringify(userinfo))
      userStore.setUserInfo(userinfo)
      const redirectPath = route.query.redirect ? String(route.query.redirect) : '/item/index'
      router.replace({
        path: redirectPath
      })
    } else {
      // 用户选择切换到其他账号
      // 直接清理本地信息，不调用 logout 接口
      var keys = document.cookie.match(/[^ =;]+(?=\=)/g)
      if (keys) {
        for (var i = keys.length; i--; ) {
          document.cookie =
            keys[i] +
            '=0;expires=' +
            new Date(0).toUTCString() +
            ';path=/'
        }
      }

      // 清空 localStorage
      localStorage.clear()

      // 填充第一个关联账号到输入框
      if (userinfo.same_email_accounts.length > 0) {
        form.value.username = userinfo.same_email_accounts[0]
        form.value.password = ''
        form.value.captcha = ''
        refreshCaptcha()
      }
      // 注意：这里不进行页面跳转，保留当前URL的redirect参数
    }
  } else {
    // 正常登录流程
    localStorage.setItem('userinfo', JSON.stringify(userinfo))
    userStore.setUserInfo(userinfo)
    let redirect = decodeURIComponent(
      route.query.redirect || '/item/index'
    )
    router.replace({
      path: redirect
    })
  }
}

// OAuth2 登录处理
const handleOauth2Login = () => {
  const serverHost = getServerHost()
  window.location.href = `${serverHost}/api/ExtLogin/oauth2`
}

onMounted(async () => {
  // 验证 redirect 参数
  if (route.query.redirect && !validateRedirect(route.query.redirect as string)) {
    AlertModal(t('user.illegal_redirect'))
    return
  }

  // 检查是否已登录
  try {
    const data = await request('/api/user/info', { redirect_login: false }, 'post', false)
    const data2 = await request('/api/user/info', { redirect_login: false }, 'post', false)

    if (data.error_code === 0 && data2.error_code === 0) {
      let redirect = decodeURIComponent(
        route.query.redirect || '/item/index'
      )
      router.replace({
        path: redirect
      })
    }
  } catch {
    // 未登录，保持当前页面
  }

  // 如果是从对话框中跳转到登录页面，可能遮罩层来不及关闭，导致登录页面无法点击。这个时候，写js去掉遮罩层。
  const elements = document.getElementsByClassName('v-modal-leave')
  for (let index = 0; index < elements.length; index++) {
    elements[index].remove()
  }

  // 刷新验证码
  await refreshCaptcha()

  // 获取登录配置
  await getOauthConfig()
  await getRegisterConfig()
  await scriptCron()
})
</script>

<style scoped>
.login-page {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: var(--color-bg-secondary);
}

.toggle-wrapper {
  position: absolute;
  top: 20px;
  right: 20px;

  .toggle-group {
    display: flex;
    align-items: center;
    gap: 12px;
    background-color: var(--color-bg-secondary);
    padding: 4px;
    border-radius: 8px;
    box-shadow: var(--shadow-xs);
  }
}

.login-card {
  width: 400px;
  padding: 40px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-lg);
}

.card-header {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 20px;
}

.title {
  color: var(--color-text-primary);
  font-size: 24px;
  font-weight: 600;
  text-align: center;
}

.form-inputs {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 20px;
}


.captcha-input {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.captcha-img {
  width: 100px;
  height: 40px;
  margin-top: 2px;
  cursor: pointer;
  border: 1px solid var(--color-border);
  border-radius: 4px;
}

.captcha-img:hover {
  opacity: 0.7;
  transition: opacity 0.15s ease;
}

.footer-links {
  margin-top: 20px;
  text-align: center;
  display: flex;
  justify-content: center;
  gap: 20px;
}

.footer-links a,
.footer-links .oauth2-link {
  font-size: 12px;
  color: var(--color-text-secondary);
  text-decoration: none;
  transition: color 0.15s ease;
  cursor: pointer;
}

.footer-links a:hover,
.footer-links .oauth2-link:hover {
  color: var(--color-active);
}

.separator {
  margin: 0 10px;
  color: var(--color-text-secondary);
}
</style>
