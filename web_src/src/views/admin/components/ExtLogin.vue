<template>
  <div class="ext-login">
    <div class="settings-container">
      <CommonTab
        :items="tabItems"
        :value="activeTab"
        type="segmented"
        @update-value="handleTabChange"
      >
        <!-- LDAP 登录配置 -->
        <template #ldap>
          <div class="tab-content">
            <a-alert
              type="info"
              :closable="false"
              show-icon
              :message="$t('admin.ldap_login_tips')"
              class="info-alert"
            />

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.enable_ldap_login') }}
              </label>
              <CommonSwitch v-model="ldapForm.ldap_open" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_host') }}
                <a-tooltip :title="$t('admin.ldap_host_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.host"
                class="form-input"
                :placeholder="$t('admin.ldap_host_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_port') }}
                <a-tooltip :title="$t('admin.ldap_port_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.port"
                class="form-input"
                :placeholder="$t('admin.ldap_port_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_version') }}
                <a-tooltip :title="$t('admin.ldap_version_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <a-select
                v-model:value="ldapForm.ldap_form.version"
                class="form-select"
              >
                <a-select-option value="2">LDAP v2</a-select-option>
                <a-select-option value="3">LDAP v3</a-select-option>
              </a-select>
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_base_dn') }}
                <a-tooltip :title="$t('admin.ldap_base_dn_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.base_dn"
                class="form-input"
                :placeholder="$t('admin.ldap_base_dn_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_bind_dn') }}
                <a-tooltip :title="$t('admin.ldap_bind_dn_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.bind_dn"
                class="form-input"
                :placeholder="$t('admin.ldap_bind_dn_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_bind_password') }}
                <a-tooltip :title="$t('admin.ldap_bind_password_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.bind_password"
                type="password"
                class="form-input"
                :placeholder="$t('admin.ldap_bind_password_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_user_field') }}
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.user_field"
                class="form-input"
                :placeholder="$t('admin.ldap_user_field_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_name_field') }}
                <a-tooltip :title="$t('admin.ldap_name_field_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.name_field"
                class="form-input"
                :placeholder="$t('admin.ldap_name_field_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ldap_search_filter') }}
                <a-tooltip :title="$t('admin.ldap_search_filter_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="ldapForm.ldap_form.search_filter"
                class="form-input"
                :placeholder="$t('admin.ldap_search_filter_placeholder')"
              />
            </div>

            <div class="form-row form-actions">
              <CommonButton
                theme="dark"
                :text="$t('admin.ldap_sync_users')"
                :leftIcon="['fas', 'sync']"
                :loading="syncingLdap"
                @click="handleSyncLdapUsers"
              />
            </div>
          </div>
        </template>

        <!-- OAuth2 登录配置 -->
        <template #oauth2>
          <div class="tab-content">
            <a-alert
              type="info"
              :closable="false"
              show-icon
              :message="$t('admin.oauth2_login_tips')"
              class="info-alert"
            />

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.enable_oauth2_login') }}
              </label>
              <CommonSwitch v-model="oauth2Form.oauth2_open" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_callback_url') }}
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.redirectUri"
                class="form-input"
                :placeholder="$t('admin.oauth2_callback_eg')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_entrance_tips') }}
                <a-tooltip :title="$t('admin.oauth2_entrance_tips_content')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.entrance_tips"
                class="form-input"
                :placeholder="$t('admin.oauth2_entrance_tips_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_client_id') }}
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.client_id"
                class="form-input"
                :placeholder="$t('admin.oauth2_client_id_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_client_secret') }}
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.client_secret"
                type="password"
                class="form-input"
                :placeholder="$t('admin.oauth2_client_secret_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_protocol') }}
              </label>
              <a-select
                v-model:value="oauth2Form.oauth2_form.protocol"
                class="form-select"
              >
                <a-select-option value="http">HTTP</a-select-option>
                <a-select-option value="https">HTTPS</a-select-option>
              </a-select>
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_host') }}
                <a-tooltip :title="$t('admin.oauth2_host_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.host"
                class="form-input"
                :placeholder="$t('admin.oauth2_host_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_authorize_path') }}
                <a-tooltip :title="$t('admin.oauth2_authorize_path_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.authorize_path"
                class="form-input"
                :placeholder="$t('admin.oauth2_authorize_path_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_token_path') }}
                <a-tooltip :title="$t('admin.oauth2_token_path_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.token_path"
                class="form-input"
                :placeholder="$t('admin.oauth2_token_path_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_resource_path') }}
                <a-tooltip :title="$t('admin.oauth2_resource_path_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.resource_path"
                class="form-input"
                :placeholder="$t('admin.oauth2_resource_path_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_userinfo_path') }}
                <a-tooltip :title="$t('admin.oauth2_userinfo_path_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.userinfo_path"
                class="form-input"
                :placeholder="$t('admin.oauth2_userinfo_path_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oauth2_logout_redirect_uri') }}
                <a-tooltip :title="$t('admin.oauth2_logout_redirect_uri_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="oauth2Form.oauth2_form.logout_redirect_uri"
                class="form-input"
                :placeholder="$t('admin.oauth2_logout_redirect_uri_placeholder')"
              />
            </div>
          </div>
        </template>

        <!-- 通用接入登录 -->
        <template #general>
          <div class="tab-content">
            <a-alert
              type="info"
              :closable="false"
              show-icon
              :message="$t('admin.general_login_desc')"
              class="info-alert"
            />

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.login_secret_key') }}
              </label>
              <CommonInput
                v-model="generalForm.login_secret_key"
                class="form-input"
                disabled
              />
            </div>

            <div class="form-row form-actions">
              <CommonButton
                theme="dark"
                :text="$t('admin.reset_login_secret_key')"
                :leftIcon="['fas', 'redo']"
                :loading="resettingSecretKey"
                @click="handleResetSecretKey"
              />
              <CommonButton
                theme="light"
                :text="$t('admin.general_login_doc_link')"
                :leftIcon="['fas', 'book']"
                @click="handleOpenDoc"
              />
            </div>
          </div>
        </template>
      </CommonTab>

      <!-- 保存按钮 -->
      <div class="save-button-wrapper">
        <CommonButton
          theme="dark"
          :text="$t('common.save')"
          :leftIcon="['fas', 'save']"
          @click="handleSave"
        />
        <CommonButton
          theme="light"
          :text="$t('common.cancel')"
          @click="loadConfig"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSwitch from '@/components/CommonSwitch.vue'
import CommonTab from '@/components/CommonTab.vue'
import {
  loadLdapConfig,
  saveLdapConfig,
  syncLdapUsers,
  loadOauth2Config,
  saveOauth2Config,
  getLoginSecretKey,
  resetLoginSecretKey
} from '@/models/admin'
import { normalizeToBinaryString } from '@/utils/system'
import { getServerHost } from '@/utils/system'

const { t } = useI18n()

// 数据状态
const activeTab = ref('ldap')
const syncingLdap = ref(false)
const resettingSecretKey = ref(false)

// Tab 选项
const tabItems = computed(() => [
  { text: t('admin.ldap_login'), value: 'ldap' },
  { text: t('admin.oauth2_login'), value: 'oauth2' },
  { text: t('admin.general_login'), value: 'general' }
])

const handleTabChange = (value: string | number) => {
  activeTab.value = String(value)
}

// LDAP 表单数据
const ldapForm = reactive({
  ldap_open: '0',
  ldap_form: {
    host: '',
    port: '389',
    version: '3',
    base_dn: '',
    bind_dn: '',
    bind_password: '',
    user_field: '',
    name_field: '',
    search_filter: '(cn=*)'
  }
})

// OAuth2 表单数据
const oauth2Form = reactive({
  oauth2_open: '0',
  oauth2_form: {
    redirectUri: '',
    entrance_tips: '',
    client_id: '',
    client_secret: '',
    protocol: 'https',
    host: '',
    authorize_path: '',
    token_path: '',
    resource_path: '',
    userinfo_path: '',
    logout_redirect_uri: ''
  }
})

// 通用接入表单数据
const generalForm = reactive({
  login_secret_key: ''
})

// 方法
const loadConfig = async () => {
  try {
    // 加载 LDAP 配置
    const ldapRes: any = await loadLdapConfig()
    if (ldapRes.data) {
      ldapForm.ldap_open = normalizeToBinaryString(ldapRes.data.ldap_open)
      if (ldapRes.data.ldap_form) {
        Object.assign(ldapForm.ldap_form, ldapRes.data.ldap_form)
      }
    }

    // 加载 OAuth2 配置
    const oauth2Res: any = await loadOauth2Config()
    if (oauth2Res.data) {
      oauth2Form.oauth2_open = normalizeToBinaryString(oauth2Res.data.oauth2_open)
      if (oauth2Res.data.oauth2_form) {
        Object.assign(oauth2Form.oauth2_form, oauth2Res.data.oauth2_form)
      }
    }

    // 加载通用接入密钥
    const secretRes: any = await getLoginSecretKey()
    if (secretRes.data && secretRes.data.login_secret_key) {
      generalForm.login_secret_key = secretRes.data.login_secret_key
    }
  } catch (error) {
    console.error('加载扩展登录配置失败:', error)
  }
}

const handleSave = async () => {
  try {
    // 保存 LDAP 配置
    await saveLdapConfig({
      ldap_open: ldapForm.ldap_open === '1',
      ldap_form: ldapForm.ldap_form
    })

    // 保存 OAuth2 配置
    await saveOauth2Config({
      oauth2_open: oauth2Form.oauth2_open === '1',
      oauth2_form: oauth2Form.oauth2_form
    })

    message.success(t('common.save_success'))
  } catch (error) {
    message.error(t('common.save_failed'))
  }
}

const handleSyncLdapUsers = async () => {
  syncingLdap.value = true
  try {
    const res: any = await syncLdapUsers()
    if (res.error_code === 0) {
      message.success(t('admin.ldap_sync_success'))
    } else {
      message.error(res.error_message || t('admin.ldap_sync_failed'))
    }
  } catch (error: any) {
    message.error(`${t('admin.ldap_sync_failed')}: ${error.message || t('admin.unknown_error')}`)
  } finally {
    syncingLdap.value = false
  }
}

const handleResetSecretKey = async () => {
  try {
    const res: any = await resetLoginSecretKey()
    if (res.error_code === 0) {
      generalForm.login_secret_key = res.data.login_secret_key
      message.success(t('admin.reset_login_secret_key_success'))
    } else {
      message.error(res.error_message || t('admin.reset_login_secret_key_failed'))
    }
  } catch (error: any) {
    message.error(`${t('admin.reset_login_secret_key_failed')}: ${error.message || t('admin.unknown_error')}`)
  }
}

const handleOpenDoc = () => {
  window.open('https://www.showdoc.com.cn/p/0fb2753c5a48acc7c3fbbb00f9504e6b', '_blank')
}

onMounted(() => {
  loadConfig()
})
</script>

<style lang="scss" scoped>
.ext-login {
  width: 100%;
  max-width: 900px;
  margin: 0;

  .settings-container {
    background: var(--color-obvious);
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    padding: 20px 24px 24px;
  }

  .tab-content {
    .form-row {
      display: flex;
      align-items: flex-start;
      margin-bottom: 24px;
      gap: 16px;

      .form-label {
        min-width: 200px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-top: 6px;
        font-size: 14px;
        font-weight: 600;
        color: var(--color-primary);
      }

      .form-input,
      .form-select {
        flex: 1;
        max-width: 500px;
      }

      // 适配a-select颜色
      :deep(.ant-select) {
        .ant-select-selector {
          background-color: var(--color-obvious);
          border-color: var(--color-border);
          color: var(--color-primary);
        }

        .ant-select-arrow {
          color: var(--color-inactive);
        }

        &:hover .ant-select-selector {
          border-color: var(--color-active);
        }
      }
    }

    .form-actions {
      margin-top: 32px;
      display: flex;
      gap: 12px;
    }

    .info-alert {
      margin-bottom: 20px;

      :deep(.ant-alert) {
        background-color: var(--color-bg-secondary);
        border-color: var(--color-border-light);

        .ant-alert-icon {
          color: var(--color-active);
        }

        .ant-alert-message {
          color: var(--color-primary);
        }
      }
    }
  }

  .save-button-wrapper {
    display: flex;
    gap: 12px;
    margin-top: 24px;
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .ext-login {
    .settings-container {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .tab-content {
      .form-row {
        .form-label {
          color: var(--color-primary);
        }

        :deep(.ant-select) {
          .ant-select-selector {
            background-color: var(--color-secondary);
            border-color: var(--color-border);
            color: var(--color-primary);
          }
        }
      }

      .info-alert {
        :deep(.ant-alert) {
          background-color: var(--color-bg-secondary);
          border-color: var(--color-border-light);

          .ant-alert-message {
            color: var(--color-primary);
          }
        }
      }
    }
  }
}
</style>
