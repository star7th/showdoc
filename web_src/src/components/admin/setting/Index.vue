<template>
  <div class="setting-container">
    <el-form ref="form" :model="form" label-width="150px">
      <el-tabs v-model="activeTab" type="card">
        <!-- 基础设置 -->
        <el-tab-pane :label="$t('basic_settings')" name="basic" key="basic">
          <div v-if="activeTab === 'basic'">
          <el-form-item :label="$t('register_open')">
            <el-switch v-model="form.register_open"></el-switch>
          </el-form-item>

          <el-form-item :label="$t('home_page')">
            <el-select v-model="form.home_page" :placeholder="$t('please_choose')">
              <el-option :label="$t('full_page')" value="1"></el-option>
              <el-option :label="$t('login_page')" value="2"></el-option>
              <el-option :label="$t('jump_to_an_item')" value="3"></el-option>
            </el-select>
          </el-form-item>

          <el-form-item :label="$t('jump_to_item')" v-show="form.home_page == 3">
            <el-select v-model="form.home_item" :placeholder="$t('please_choose')">
              <el-option
                v-for="item in itemList"
                :label="item.item_name"
                :value="item.item_id"
                :key="item.item_id"
              ></el-option>
            </el-select>
          </el-form-item>

          <el-form-item :label="$t('site_url')">
            <el-input
              v-model="form.site_url"
              class="form-el"
              placeholder="https://www.your-site.com"
            ></el-input>
            <el-tooltip
              effect="dark"
              :content="$t('site_url_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>
          </div>
        </el-tab-pane>

        <!-- 安全设置 -->
        <el-tab-pane :label="$t('security_settings')" name="security" key="security">
          <div v-if="activeTab === 'security'">
          <el-form-item :label="$t('force_login')">
            <el-switch v-model="form.force_login"></el-switch>
            <el-tooltip
              effect="dark"
              :content="$t('force_login_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>

          <el-form-item :label="$t('enable_public_square')">
            <el-switch v-model="form.enable_public_square"></el-switch>
            <el-tooltip
              effect="dark"
              :content="$t('enable_public_square_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>

          <el-form-item :label="$t('strong_password_enabled')">
            <el-switch v-model="form.strong_password_enabled"></el-switch>
            <el-tooltip
              effect="dark"
              :content="$t('strong_password_enabled_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>

          <el-form-item :label="$t('session_expire_days')">
            <el-input-number
              v-model="form.session_expire_days"
              :min="1"
              :max="3650"
              class="form-el"
              :placeholder="$t('session_expire_days_placeholder')"
            ></el-input-number>
            <el-tooltip
              effect="dark"
              :content="$t('session_expire_days_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>
          </div>
        </el-tab-pane>

        <!-- AI相关设置 -->
        <el-tab-pane :label="$t('ai_related_settings')" name="ai" key="ai" v-if="$lang == 'zh-cn'">
          <div v-if="activeTab === 'ai'">
            <!-- AI编辑助手配置 -->
            <el-divider content-position="left">{{ $t('ai_edit_assistant') }}</el-divider>
            <el-alert
              type="info"
              :closable="false"
              show-icon
              :title="$t('ai_edit_assistant_desc')"
              style="margin-bottom: 20px;"
            >
            </el-alert>

            <el-form-item :label="$t('ai_edit_assistant_key')">
              <el-input
                v-model="form.open_api_key"
                class="form-el"
                :placeholder="$t('ai_edit_assistant_key_placeholder')"
              ></el-input>
              <el-tooltip effect="dark" :content="$t('ai_edit_assistant_key_tips')" placement="top">
                <i
                  class="el-icon-question cursor-pointer"
                  @click="
                    toOutLink(
                      'https://www.showdoc.com.cn/p/30dd0637811cd5c690ffd547f3c46889'
                    )
                  "
                ></i>
              </el-tooltip>
            </el-form-item>

            <el-form-item :label="$t('ai_edit_assistant_host')">
              <el-input
                v-model="form.open_api_host"
                class="form-el"
                :placeholder="$t('ai_edit_assistant_host_placeholder')"
              ></el-input>
              <el-tooltip effect="dark" :content="$t('ai_edit_assistant_host_tips')" placement="top">
                <i
                  class="el-icon-question cursor-pointer"
                  @click="toOutLink('https://github.com/star7th/showdoc/issues/1904')"
                ></i>
              </el-tooltip>
            </el-form-item>

            <el-form-item :label="$t('ai_edit_assistant_model')">
              <el-input
                v-model="form.ai_model_name"
                class="form-el"
                :placeholder="$t('ai_edit_assistant_model_placeholder')"
              ></el-input>
            </el-form-item>

            <!-- AI 知识库服务配置 -->
            <el-divider content-position="left">{{ $t('ai_knowledge_base_service') }}</el-divider>
            
            <el-alert
              type="warning"
              :closable="false"
              show-icon
              style="margin-bottom: 20px;"
            >
              <div slot="title">
                <div style="margin-bottom: 8px;">{{ $t('ai_knowledge_base_service_desc') }}</div>
                <div>
                  <el-button
                    type="text"
                    size="small"
                    icon="el-icon-link"
                    @click="toOutLink('https://github.com/star7th/showdoc-ai-service')"
                    style="padding: 0; color: #409eff;"
                  >
                    {{ $t('ai_knowledge_base_install_link') }}
                  </el-button>
                </div>
              </div>
            </el-alert>

            <el-form-item :label="$t('ai_service_url')">
              <el-input
                v-model="form.ai_service_url"
                class="form-el"
                :placeholder="$t('ai_service_url_placeholder')"
              ></el-input>
              <el-tooltip
                effect="dark"
                :content="$t('ai_service_url_tips')"
                placement="top"
              >
                <i class="el-icon-question"></i>
              </el-tooltip>
            </el-form-item>

            <el-form-item :label="$t('ai_service_token')">
              <el-input
                v-model="form.ai_service_token"
                class="form-el"
                type="password"
                :placeholder="$t('ai_service_token_placeholder')"
              ></el-input>
              <el-tooltip
                effect="dark"
                :content="$t('ai_service_token_tips')"
                placement="top"
              >
                <i class="el-icon-question"></i>
              </el-tooltip>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="testAiService">{{ $t('ai_test_connection') }}</el-button>
            </el-form-item>
          </div>
        </el-tab-pane>

        <!-- 存储设置 -->
        <el-tab-pane :label="$t('storage_settings')" name="storage" key="storage">
          <div v-if="activeTab === 'storage'">
          <el-form-item :label="$t('oss_open')">
            <el-switch v-model="form.oss_open"></el-switch>
          </el-form-item>

          <div v-if="form.oss_open" class="oss-settings">
            <el-form-item :label="$t('oss_server')">
              <el-select v-model="form.oss_setting.oss_type">
                <el-option :label="$t('aliyun')" value="aliyun"></el-option>
                <el-option :label="$t('qiniu')" value="qiniu"></el-option>
                <el-option :label="$t('qcloud')" value="qcloud"></el-option>
                <el-option :label="$t('s3_storage')" value="s3_storage"></el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="key" v-if="form.oss_setting.oss_type != 'qcloud'">
              <el-input v-model="form.oss_setting.key" class="form-el"></el-input>
            </el-form-item>

            <el-form-item
              label="secret"
              v-if="form.oss_setting.oss_type != 'qcloud'"
            >
              <el-input
                v-model="form.oss_setting.secret"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item
              label="endpoint"
              v-if="
                form.oss_setting.oss_type == 'aliyun' ||
                  form.oss_setting.oss_type == 's3_storage'
              "
            >
              <el-input
                v-model="form.oss_setting.endpoint"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item
              label="region"
              v-if="form.oss_setting.oss_type == 'qcloud'"
            >
              <el-input
                v-model="form.oss_setting.region"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item
              label="secretId"
              v-if="form.oss_setting.oss_type == 'qcloud'"
            >
              <el-input
                v-model="form.oss_setting.secretId"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item
              label="secretKey"
              v-if="form.oss_setting.oss_type == 'qcloud'"
            >
              <el-input
                v-model="form.oss_setting.secretKey"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item label="bucket">
              <el-input
                v-model="form.oss_setting.bucket"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item :label="$t('subcat') + '(' + $t('optional') + ')'">
              <el-input
                v-model="form.oss_setting.subcat"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item :label="$t('oss_domain')">
              <el-select v-model="form.oss_setting.protocol" style="width:100px;">
                <el-option label="http://" value="http"></el-option>
                <el-option label="https://" value="https"></el-option>
              </el-select>
              <el-input
                v-model="form.oss_setting.domain"
                class="form-el"
              ></el-input>
            </el-form-item>
          </div>
          </div>
        </el-tab-pane>

        <!-- 其他配置 -->
        <el-tab-pane :label="$t('other_settings')" name="other" key="other">
          <div v-if="activeTab === 'other'">
          <el-form-item v-show="$lang == 'zh-cn'" label="备案号">
            <el-input v-model="form.beian" class="form-el"></el-input>
            <el-tooltip
              effect="dark"
              content="设置后会展示在网站首页最下方"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>

          <el-form-item :label="$t('history_version_count')">
            <el-input
              v-model="form.history_version_count"
              class="form-el"
            ></el-input>
            <el-tooltip
              effect="dark"
              :content="$t('history_version_count_content')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>

          <el-form-item :label="$t('watermark')">
            <el-switch v-model="form.show_watermark"></el-switch>
            <el-tooltip
              effect="dark"
              :content="$t('watermark_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-form-item>
          </div>
        </el-tab-pane>
      </el-tabs>

      <!-- 保存按钮 -->
      <div class="save-button-wrapper">
        <el-button type="primary" @click="onSubmit">{{ $t('save') }}</el-button>
        <el-button @click="loadConfig">{{ $t('cancel') }}</el-button>
      </div>
    </el-form>
  </div>
</template>

<style scoped>
.setting-container {
  max-width: 900px;
  padding: 20px;
}

.form-el {
  width: 230px;
}

.oss-settings {
  margin-left: 20px;
  padding: 15px;
  background-color: #f5f7fa;
  border-radius: 4px;
  margin-top: 10px;
}

.save-button-wrapper {
  margin-top: 30px;
  padding: 20px 0;
  text-align: left;
  padding-left: 150px;
  border-top: 1px solid #e4e7ed;
}

/* Tab样式优化 */
.setting-container >>> .el-tabs__header {
  margin-bottom: 20px;
}

.setting-container >>> .el-tabs__item {
  padding: 0 20px;
  height: 40px;
  line-height: 40px;
}

.setting-container >>> .el-tabs__content {
  padding: 20px 0;
}
</style>

<script>
export default {
  data() {
    return {
      activeTab: 'basic',
      form: {
        register_open: true,
        home_page: '1',
        home_item: '',
        oss_open: false,
        oss_setting: {
          oss_type: 'aliyun',
          key: '',
          secret: '',
          endpoint: '',
          bucket: '',
          subcat: '',
          protocol: 'http',
          domain: '',
          region: '',
          secretId: '',
          secretKey: ''
        },
        history_version_count: 20,
        beian: '',
        show_watermark: false,
        site_url: '',
        open_api_key: '',
        open_api_host: '',
        ai_model_name: '',
        ai_service_url: '',
        ai_service_token: '',
        force_login:false,
        enable_public_square: false,
        strong_password_enabled: false,
        session_expire_days: 180
      },
      itemList: []
    }
  },
  methods: {
    onSubmit() {
      this.request(
        '/api/adminSetting/saveConfig',
        this.form,
        'post',
        true,
        'json'
      ).then(data => {
        this.$alert(this.$t('success'))
      })
    },
    loadConfig() {
      this.request('/api/adminSetting/loadConfig', {}).then(data => {
        if (data.data.length === 0) {
          return
        }
        this.form.register_open = data.data.register_open > 0
        this.form.history_version_count = data.data.history_version_count
          ? data.data.history_version_count
          : this.form.history_version_count
        this.form.oss_open = data.data.oss_open > 0
        this.form.home_page = data.data.home_page > 0 ? data.data.home_page : 1
        this.form.home_item = data.data.home_item > 0 ? data.data.home_item : ''
        // 逐一填充 oss_setting 的属性
        if (data.data.oss_setting) {
          Object.keys(this.form.oss_setting).forEach(key => {
            this.form.oss_setting[key] = data.data.oss_setting[key] !== undefined 
              ? data.data.oss_setting[key] 
              : this.form.oss_setting[key];
          });
        }
        this.form.oss_setting.region = this.form.oss_setting.region
          ? this.form.oss_setting.region
          : ''
        this.form.oss_setting.secretId = this.form.oss_setting.secretId
          ? this.form.oss_setting.secretId
          : ''
        this.form.oss_setting.secretKey = this.form.oss_setting.secretKey
          ? this.form.oss_setting.secretKey
          : ''
        this.form.oss_setting.subcat = this.form.oss_setting.subcat
          ? this.form.oss_setting.subcat
          : ''
        this.form.beian = data.data.beian ? data.data.beian : ''
        this.form.show_watermark = data.data.show_watermark > 0
        this.form.site_url = data.data.site_url ? data.data.site_url : ''
        this.form.open_api_key = data.data.open_api_key
          ? data.data.open_api_key
          : ''
        this.form.open_api_host = data.data.open_api_host
          ? data.data.open_api_host
          : ''
        this.form.ai_model_name = data.data.ai_model_name
          ? data.data.ai_model_name
          : ''
        this.form.ai_service_url = data.data.ai_service_url
          ? data.data.ai_service_url
          : ''
        this.form.ai_service_token = data.data.ai_service_token
          ? data.data.ai_service_token
          : ''
        this.form.force_login = data.data.force_login > 0
        this.form.enable_public_square = data.data.enable_public_square > 0
        this.form.strong_password_enabled = data.data.strong_password_enabled > 0
        this.form.session_expire_days = data.data.session_expire_days
          ? parseInt(data.data.session_expire_days)
          : 180
      })
    },
    getItemList() {
      this.request('/api/adminItem/getList', {
        page: 1,
        count: 100
      }).then(data => {
        this.itemList = data.data.items
      })
    },
    toOutLink(url) {
      window.open(url, '_blank')
    },
    async testAiService() {
      if (!this.form.ai_service_url) {
        this.$message.warning(this.$t('ai_service_url_required') || '请先填写AI服务地址')
        return
      }
      if (!this.form.ai_service_token) {
        this.$message.warning(this.$t('ai_service_token_required') || '请先填写AI服务Token')
        return
      }
      
      try {
        const res = await this.request('/api/adminSetting/testAiService', {
          ai_service_url: this.form.ai_service_url,
          ai_service_token: this.form.ai_service_token
        })
        if (res.error_code === 0) {
          this.$message.success(this.$t('ai_connection_success') || '连接成功！')
        } else {
          this.$message.error(res.error_message || (this.$t('ai_connection_failed') || '连接失败'))
        }
      } catch (error) {
        this.$message.error((this.$t('ai_connection_failed') || '连接失败') + '：' + (error.message || '未知错误'))
      }
    }
  },
  mounted() {
    this.getItemList()
    this.loadConfig()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
