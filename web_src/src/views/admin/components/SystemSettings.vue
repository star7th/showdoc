<template>
  <div class="system-settings">
    <div class="settings-container">
      <CommonTab
        :items="tabItems"
        :value="activeTab"
        type="segmented"
        @update-value="handleTabChange"
      >
        <!-- 基础设置 -->
        <template #basic>
          <div class="tab-content">
            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.open_register') }}
                <a-tooltip :title="$t('admin.open_register_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonSwitch v-model="form.register_open" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.site_url') }}
                <a-tooltip :title="$t('admin.site_url_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon clickable" @click="openLink('https://www.showdoc.com.cn/p/30dd0637811cd5c690ffd547f3c46889')" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="form.site_url"
                class="form-input"
                :placeholder="$t('admin.site_url_placeholder')"
              />
            </div>

            <!-- 首页跳转设置 -->
            <div class="section-title">{{ $t('admin.home_page') }}</div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.home_page') }}
                <a-tooltip :title="$t('admin.home_page_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <a-select
                v-model:value="form.home_page"
                class="form-select"
              >
                <a-select-option value="1">{{ $t('admin.full_page') }}</a-select-option>
                <a-select-option value="2">{{ $t('admin.login_page') }}</a-select-option>
                <a-select-option value="3">{{ $t('admin.jump_to_item') }}</a-select-option>
              </a-select>
            </div>

            <div v-show="form.home_page === '3'" class="form-row">
              <label class="form-label">
                {{ $t('admin.home_item') }}
                <a-tooltip :title="$t('admin.home_item_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <a-select
                v-model:value="form.home_item"
                class="form-select"
                :placeholder="$t('admin.home_item_placeholder')"
                :show-search="true"
                :filter-option="(input: string, option: any) => {
                  return option.label.toLowerCase().includes(input.toLowerCase())
                }"
              >
                <a-select-option
                  v-for="item in itemList"
                  :key="item.item_id"
                  :value="item.item_id"
                  :label="item.item_name"
                >
                  {{ item.item_name }}
                </a-select-option>
              </a-select>
            </div>
          </div>
        </template>

        <!-- 安全设置 -->
        <template #security>
          <div class="tab-content">
            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.force_login') }}
                <a-tooltip :title="$t('admin.force_login_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonSwitch v-model="form.force_login" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.enable_public_square') }}
                <a-tooltip :title="$t('admin.enable_public_square_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonSwitch v-model="form.enable_public_square" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.strong_password_enabled') }}
                <a-tooltip :title="$t('admin.strong_password_enabled_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonSwitch v-model="form.strong_password_enabled" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.session_expire_days') }}
                <a-tooltip :title="$t('admin.session_expire_days_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <a-input-number
                v-model:value="form.session_expire_days"
                :min="1"
                :max="3650"
                class="form-input-number"
                :placeholder="$t('admin.session_expire_days_placeholder')"
              />
            </div>

            <!-- 历史版本数量设置 -->
            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.history_version_count') }}
                <a-tooltip :title="$t('admin.history_version_count_content')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <a-input-number
                v-model:value="form.history_version_count"
                :min="0"
                :max="100"
                class="form-input-number"
                :placeholder="$t('admin.history_version_count_content')"
              />
            </div>

            <!-- 水印设置 -->
            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.watermark') }}
                <a-tooltip :title="$t('admin.watermark_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonSwitch v-model="form.show_watermark" />
            </div>
          </div>
        </template>

        <!-- 存储设置 -->
        <template #storage>
          <div class="tab-content">
            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oss_open') }}
              </label>
              <CommonSwitch v-model="ossForm.oss_open" />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oss_server') }}
              </label>
              <a-select
                v-model:value="ossForm.oss_setting.oss_type"
                class="form-select"
              >
                <a-select-option value="aliyun">{{ $t('admin.aliyun') }}</a-select-option>
                <a-select-option value="qiniu">{{ $t('admin.qiniu') }}</a-select-option>
                <a-select-option value="qcloud">{{ $t('admin.qcloud') }}</a-select-option>
                <a-select-option value="s3_storage">{{ $t('admin.s3_storage') }}</a-select-option>
              </a-select>
            </div>

            <!-- 阿里云/S3 设置 -->
            <template v-if="ossForm.oss_setting.oss_type === 'aliyun' || ossForm.oss_setting.oss_type === 's3_storage'">
              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_key') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.key"
                  class="form-input"
                  :placeholder="$t('admin.oss_key_placeholder')"
                />
              </div>

              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_secret') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.secret"
                  type="password"
                  class="form-input"
                  :placeholder="$t('admin.oss_secret_placeholder')"
                />
              </div>

              <div v-if="ossForm.oss_setting.oss_type === 'aliyun'" class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_endpoint') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.endpoint"
                  class="form-input"
                  :placeholder="$t('admin.oss_endpoint_placeholder')"
                />
              </div>
            </template>

            <!-- 七牛云设置 -->
            <template v-if="ossForm.oss_setting.oss_type === 'qiniu'">
              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_key') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.key"
                  class="form-input"
                  :placeholder="$t('admin.oss_key_placeholder')"
                />
              </div>

              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_secret') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.secret"
                  type="password"
                  class="form-input"
                  :placeholder="$t('admin.oss_secret_placeholder')"
                />
              </div>
            </template>

            <!-- 腾讯云设置 -->
            <template v-if="ossForm.oss_setting.oss_type === 'qcloud'">
              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_secretId') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.secretId"
                  class="form-input"
                  :placeholder="$t('admin.oss_secretId_placeholder')"
                />
              </div>

              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_secretKey') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.secretKey"
                  type="password"
                  class="form-input"
                  :placeholder="$t('admin.oss_secretKey_placeholder')"
                />
              </div>

              <div class="form-row">
                <label class="form-label">
                  {{ $t('admin.oss_region') }}
                </label>
                <CommonInput
                  v-model="ossForm.oss_setting.region"
                  class="form-input"
                  :placeholder="$t('admin.oss_region_placeholder')"
                />
              </div>
            </template>

            <!-- 通用设置 -->
            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oss_bucket') }}
              </label>
              <CommonInput
                v-model="ossForm.oss_setting.bucket"
                class="form-input"
                :placeholder="$t('admin.oss_bucket_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oss_subcat') }}
              </label>
              <CommonInput
                v-model="ossForm.oss_setting.subcat"
                class="form-input"
                :placeholder="$t('admin.oss_subcat_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.oss_domain') }}
              </label>
              <CommonInput
                v-model="ossForm.oss_setting.domain"
                class="form-input"
                :placeholder="$t('admin.oss_domain_placeholder')"
              />
            </div>
          </div>
        </template>

        <!-- AI相关设置 -->
        <template #ai>
          <div class="tab-content">
            <!-- AI编辑助手配置 -->
            <div class="section-title">{{ $t('admin.ai_edit_assistant') }}</div>

            <a-alert
              type="info"
              :closable="false"
              show-icon
              :message="$t('admin.ai_edit_assistant_desc')"
              class="info-alert"
            />

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ai_edit_assistant_key') }}
                <a-tooltip :title="$t('admin.ai_edit_assistant_key_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon clickable" @click="openLink('https://www.showdoc.com.cn/p/30dd0637811cd5c690ffd547f3c46889')" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="form.open_api_key"
                class="form-input"
                :placeholder="$t('admin.ai_edit_assistant_key_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ai_edit_assistant_host') }}
                <a-tooltip :title="$t('admin.ai_edit_assistant_host_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon clickable" @click="openLink('https://github.com/star7th/showdoc/issues/1904')" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="form.open_api_host"
                class="form-input"
                :placeholder="$t('admin.ai_edit_assistant_host_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ai_edit_assistant_model') }}
              </label>
              <CommonInput
                v-model="form.ai_model_name"
                class="form-input"
                :placeholder="$t('admin.ai_edit_assistant_model_placeholder')"
              />
            </div>

            <!-- AI 知识库服务配置 -->
            <div class="section-title">{{ $t('admin.ai_knowledge_base_service') }}</div>

            <a-alert
              type="warning"
              :closable="false"
              show-icon
              class="warning-alert"
            >
              <template #message>
                <div class="alert-content">
                  <div class="alert-text">{{ $t('admin.ai_knowledge_base_service_desc') }}</div>
                  <a
                    class="alert-link"
                    @click="openLink('https://github.com/star7th/showdoc-ai-service')"
                  >
                    {{ $t('admin.ai_knowledge_base_install_link') }}
                  </a>
                </div>
              </template>
            </a-alert>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ai_service_url') }}
                <a-tooltip :title="$t('admin.ai_service_url_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="form.ai_service_url"
                class="form-input"
                :placeholder="$t('admin.ai_service_url_placeholder')"
              />
            </div>

            <div class="form-row">
              <label class="form-label">
                {{ $t('admin.ai_service_token') }}
                <a-tooltip :title="$t('admin.ai_service_token_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="form.ai_service_token"
                type="password"
                class="form-input"
                :placeholder="$t('admin.ai_service_token_placeholder')"
              />
            </div>

            <div class="form-row form-actions">
              <CommonButton
                theme="dark"
                :text="$t('admin.ai_test_connection')"
                :leftIcon="['fas', 'link']"
                :loading="testing"
                @click="handleTestAiService"
              />
            </div>
          </div>
        </template>

        <!-- 其他配置 -->
        <template #other>
          <div class="tab-content">
            <!-- 备案号设置（仅中文） -->
            <div v-if="locale === 'zh-CN'" class="form-row">
              <label class="form-label">
                {{ $t('admin.beian') }}
                <a-tooltip :title="$t('admin.beian_tips')" placement="top">
                  <QuestionCircleOutlined class="question-icon" />
                </a-tooltip>
              </label>
              <CommonInput
                v-model="form.beian"
                class="form-input"
                :placeholder="$t('admin.beian_tips')"
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
import { QuestionCircleOutlined } from '@ant-design/icons-vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSwitch from '@/components/CommonSwitch.vue'
import CommonTab from '@/components/CommonTab.vue'
import { loadSystemConfig, saveSystemConfig, testAiService, getAdminItemList } from '@/models/admin'
import { normalizeToBinaryString } from '@/utils/system'

const { t, locale } = useI18n()

// 数据状态
const activeTab = ref('basic')
const testing = ref(false)
const itemList = ref<any[]>([])

// Tab 选项
const tabItems = computed(() => {
  const items = [
    { text: t('admin.basic_settings'), value: 'basic' },
    { text: t('admin.security_settings'), value: 'security' },
    { text: t('admin.storage_settings'), value: 'storage' }
  ]
  // 仅在中文环境下显示 AI 设置
  if (locale.value === 'zh-CN') {
    items.push({ text: t('admin.ai_related_settings'), value: 'ai' })
  }
  // 仅在中文环境下显示其他配置（备案号）
  if (locale.value === 'zh-CN') {
    items.push({ text: t('admin.other_settings'), value: 'other' })
  }
  return items
})

const handleTabChange = (value: string | number) => {
  activeTab.value = String(value)
}

// 表单数据
// 注意：所有布尔类型字段初始化为字符串 '0' 或 '1'，与 CommonSwitch 的返回值保持一致
const form = reactive({
  register_open: '1',
  ldap_open: '0',
  home_page: '1',
  home_item: '',
  site_url: '',
  open_api_key: '',
  open_api_host: '',
  ai_model_name: '',
  ai_service_url: '',
  ai_service_token: '',
  force_login: '0',
  enable_public_square: '0',
  strong_password_enabled: '0',
  session_expire_days: 180,
  history_version_count: 0,
  show_watermark: '0',
  beian: ''
})

// OSS 表单数据
const ossForm = reactive({
  oss_open: '0',
  oss_setting: {
    oss_type: 'aliyun',
    key: '',
    secret: '',
    endpoint: '',
    region: '',
    secretId: '',
    secretKey: '',
    bucket: '',
    subcat: '',
    protocol: 'https',
    domain: ''
  }
})

// 方法
const loadConfig = async () => {
  try {
    const res: any = await loadSystemConfig()
    if (!res.data || res.data.length === 0) {
      return
    }
    const data = res.data
    // 使用工具函数转换布尔类型字段，确保兼容后端返回的各种数据类型
    form.register_open = normalizeToBinaryString(data.register_open)
    form.ldap_open = normalizeToBinaryString(data.ldap_open)
    form.home_page = String(data.home_page || '1')
    form.home_item = data.home_item || ''
    form.site_url = data.site_url || ''
    form.open_api_key = data.open_api_key || ''
    form.open_api_host = data.open_api_host || ''
    form.ai_model_name = data.ai_model_name || ''
    form.ai_service_url = data.ai_service_url || ''
    form.ai_service_token = data.ai_service_token || ''
    form.force_login = normalizeToBinaryString(data.force_login)
    form.enable_public_square = normalizeToBinaryString(data.enable_public_square)
    form.strong_password_enabled = normalizeToBinaryString(data.strong_password_enabled)
    // 数字类型字段：转换为整数，兼容字符串数字
    form.session_expire_days = data.session_expire_days
      ? parseInt(String(data.session_expire_days))
      : 180
    form.history_version_count = data.history_version_count
      ? parseInt(String(data.history_version_count))
      : 0
    form.show_watermark = normalizeToBinaryString(data.show_watermark)
    form.beian = data.beian || ''

    // OSS 配置也包含在同一个接口返回中
    if (data.oss_open !== undefined) {
      ossForm.oss_open = normalizeToBinaryString(data.oss_open)
    }
    if (data.oss_setting) {
      Object.assign(ossForm.oss_setting, data.oss_setting)
    }
  } catch (error) {
    console.error('加载系统配置失败:', error)
  }
}

const handleSave = async () => {
  try {
    // 将所有配置（包括OSS）合并在一起保存，与旧版保持一致
    await saveSystemConfig({
      register_open: form.register_open === '1',
      site_url: form.site_url,
      home_page: form.home_page,
      home_item: form.home_item,
      open_api_key: form.open_api_key,
      open_api_host: form.open_api_host,
      ai_model_name: form.ai_model_name,
      ai_service_url: form.ai_service_url,
      ai_service_token: form.ai_service_token,
      force_login: form.force_login === '1',
      enable_public_square: form.enable_public_square === '1',
      strong_password_enabled: form.strong_password_enabled === '1',
      session_expire_days: form.session_expire_days,
      history_version_count: form.history_version_count,
      show_watermark: form.show_watermark === '1',
      beian: form.beian,
      // OSS 配置
      oss_open: ossForm.oss_open === '1',
      oss_setting: ossForm.oss_setting
    })

    message.success(t('common.save_success'))
  } catch (error) {
    message.error(t('common.save_failed'))
  }
}

const handleTestAiService = async () => {
  if (!form.ai_service_url) {
    message.warning(t('admin.ai_service_url_required'))
    return
  }
  if (!form.ai_service_token) {
    message.warning(t('admin.ai_service_token_required'))
    return
  }

  testing.value = true
  try {
    const res: any = await testAiService({
      ai_service_url: form.ai_service_url,
      ai_service_token: form.ai_service_token
    })
    if (res.error_code === 0) {
      message.success(t('admin.ai_connection_success'))
    } else {
      message.error(res.error_message || t('admin.ai_connection_failed'))
    }
  } catch (error: any) {
    message.error(`${t('admin.ai_connection_failed')}: ${error.message || t('admin.unknown_error')}`)
  } finally {
    testing.value = false
  }
}

const openLink = (url: string) => {
  window.open(url, '_blank')
}

// 加载项目列表
const loadItemList = async () => {
  try {
    const data = await getAdminItemList({
      item_name: '',
      username: '',
      page: 1,
      count: 100,
      positive_type: '',
      item_type: '',
      privacy_type: '',
      is_del: ''
    })
    if (data.data && data.data.items) {
      itemList.value = data.data.items
    }
  } catch (error) {
    console.error('加载项目列表失败:', error)
  }
}

onMounted(async () => {
  loadConfig()
  await loadItemList()
})
</script>

<style lang="scss" scoped>
.system-settings {
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

        .question-icon {
          flex-shrink: 0;
          color: var(--color-inactive);
          font-size: 14px;
          transition: all 0.15s ease;

          &.clickable {
            cursor: pointer;
          }

          &:hover {
            color: var(--color-active);
          }
        }
      }

      .form-input,
      .form-input-number,
      .form-select {
        flex: 1;
        max-width: 500px;
      }

      // 适配a-input-number颜色
      :deep(.ant-input-number) {
        .ant-input-number-handler-wrap {
          background-color: var(--color-bg-secondary);

          .ant-input-number-handler {
            color: var(--color-grey);

            &:hover {
              color: var(--color-active);
            }
          }
        }

        input {
          background-color: var(--color-obvious);
          color: var(--color-primary);
        }
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

      &.form-actions {
        margin-top: 32px;
      }
    }

    .section-title {
      position: relative;
      font-size: 16px;
      font-weight: 600;
      color: var(--color-primary);
      margin: 32px 0 20px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--color-border);
    }

    .info-alert {
      margin-bottom: 20px;

      // 适配ShowDoc颜色
      :deep(.ant-alert) {
        background-color: var(--color-bg-secondary);
        border-color: var(--color-border-light);

        .ant-alert-icon {
          color: var(--color-active);
        }
      }
    }

    .warning-alert {
      margin-bottom: 20px;

      // 适配ShowDoc颜色
      :deep(.ant-alert) {
        background-color: var(--color-yellow-bg);
        border-color: var(--color-yellow-border);

        .ant-alert-icon {
          color: var(--color-yellow-text);
        }

        .ant-alert-message {
          color: var(--color-yellow-text);
        }
      }

      .alert-content {
        .alert-text {
          margin-bottom: 8px;
          color: var(--color-primary);
        }

        .alert-link {
          color: var(--color-active);
          font-weight: 600;
          cursor: pointer;
          transition: all 0.15s ease;

          &:hover {
            background: var(--hover-overlay);
          }
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
  .system-settings {
    .settings-container {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .tab-content {
      .form-row {
        .form-label {
          color: var(--color-primary);

          .question-icon {
            color: var(--color-inactive);

            &:hover {
              color: var(--color-active);
            }
          }
        }
      }

      .section-title {
        color: var(--color-primary);
        border-bottom-color: var(--color-border);
      }

      .warning-alert {
        .alert-content {
          .alert-text {
            color: var(--color-primary);
          }

          .alert-link {
            color: var(--color-active);
          }
        }
      }

      // 适配a-input-number暗黑主题
      :deep(.ant-input-number) {
        input {
          background-color: var(--color-secondary);
          color: var(--color-primary);
        }
      }

      // 适配a-select暗黑主题
      :deep(.ant-select) {
        .ant-select-selector {
          background-color: var(--color-secondary);
          border-color: var(--color-border);
          color: var(--color-primary);
        }
      }
    }

    .save-button-wrapper {
      background: var(--color-default);
      border-top-color: var(--color-border);
    }
  }
}
</style>
