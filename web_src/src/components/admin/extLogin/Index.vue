<template>
  <div class="hello">
    <el-tabs type="border-card">
      <el-tab-pane label="LDAP">
        <el-form ref="form" label-width="150px">
          <el-form-item :label="$t('ldap_open_label')">
            <el-switch v-model="form.ldap_open"></el-switch>
          </el-form-item>

          <div>
            <el-form-item label="ldap host">
              <el-input
                v-model="form.ldap_form.host"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item label="ldap port">
              <el-input
                v-model="form.ldap_form.port"
                style="width:90px"
              ></el-input>
            </el-form-item>

            <el-form-item label="ldap base dn ">
              <el-input
                v-model="form.ldap_form.base_dn"
                class="form-el"
                placeholder="例如 dc=showdoc,dc=com"
              ></el-input>
            </el-form-item>

            <el-form-item label="ldap bind dn ">
              <el-input
                v-model="form.ldap_form.bind_dn"
                class="form-el"
                placeholder="cn=admin,dc=showdoc,dc=com"
              ></el-input>
            </el-form-item>

            <el-form-item label="ldap bind password ">
              <el-input
                v-model="form.ldap_form.bind_password"
                class="form-el"
                placeholder="例如 123456"
              ></el-input>
            </el-form-item>

            <el-form-item label="ldap version">
              <el-select v-model="form.ldap_form.version" class="form-el">
                <el-option label="3" value="3"></el-option>
                <el-option label="2" value="2"></el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="ldap user filed">
              <el-input
                v-model="form.ldap_form.user_field"
                class="form-el"
                placeholder="例如 cn 或者 sAMAccountName"
              ></el-input>
            </el-form-item>
          </div>

          <br />
          <el-form-item>
            <el-button type="primary" @click="saveLdapConfig">{{
              $t('save')
            }}</el-button>
            <el-button>{{ $t('cancel') }}</el-button>
          </el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="OAuth2">
        <el-form ref="form" label-width="150px">
          <el-form-item :label="$t('enable_oauth')">
            <el-switch v-model="form.oauth2_open"></el-switch>
          </el-form-item>
          <el-form-item label="callback url">
            <el-input
              v-model="form.oauth2_form.redirectUri"
              class="form-el"
            ></el-input>
          </el-form-item>
          <el-form-item :label="$t('callback_eg')">
            http://{{
              $t('your_showdoc_server')
            }}/server/?s=/api/extLogin/oauth2
          </el-form-item>
          <div>
            <el-form-item :label="$t('入口文字提示')">
              <el-input
                v-model="form.oauth2_form.entrance_tips"
                placeholder=""
                class="form-el"
              ></el-input>
              <el-tooltip
                class="item"
                effect="dark"
                :content="$t('entrance_tips_content')"
                placement="top"
              >
                <i class="el-icon-question"></i>
              </el-tooltip>
            </el-form-item>
            <el-form-item label="Client id">
              <el-input
                v-model="form.oauth2_form.client_id"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="Client secret">
              <el-input
                v-model="form.oauth2_form.client_secret"
                class="form-el"
              ></el-input>
            </el-form-item>

            <el-form-item label="Oauth host">
              <el-select
                v-model="form.oauth2_form.protocol"
                style="width:100px;"
              >
                <el-option label="http://" value="http"></el-option>
                <el-option label="https://" value="https"></el-option>
              </el-select>
              <el-input
                v-model="form.oauth2_form.host"
                class="form-el"
                placeholder="eg:  sso.your-site.com"
              ></el-input>
            </el-form-item>
            <el-form-item label="Authorize path">
              <el-input
                v-model="form.oauth2_form.authorize_path"
                placeholder="eg:  /oauth/v2/authorize"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="AccessToken path">
              <el-input
                v-model="form.oauth2_form.token_path"
                placeholder="eg:  /oauth/v2/token"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="Resource path">
              <el-input
                v-model="form.oauth2_form.resource_path"
                placeholder="eg:  /oauth/v2/resource"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="User info path">
              <el-input
                v-model="form.oauth2_form.userinfo_path"
                placeholder="eg:  /oauth/v2/me"
                class="form-el"
              ></el-input>
              <el-tooltip
                class="item"
                effect="dark"
                :content="$t('userinfo_path_content')"
                placement="top"
              >
                <i class="el-icon-question"></i>
              </el-tooltip>
            </el-form-item>
          </div>
          <br />
          <el-form-item>
            <el-button type="primary" @click="saveOauth2Config">{{
              $t('save')
            }}</el-button>
            <el-button>{{ $t('cancel') }}</el-button>
          </el-form-item>
        </el-form>
      </el-tab-pane>
      <el-tab-pane label="通用接入" v-if="lang == 'zh-cn'">
        <div style="min-height:600px;margin-top:50px;margin-left:30px;">
          <p>
            LoginSecretKey:&nbsp;
            <el-input
              readonly
              v-model="login_secret_key"
              class="form-el"
            ></el-input>
            <el-button @click="resetLoginSecretKey">{{
              $t('reset')
            }}</el-button>
          </p>
          <p>
            通用接入提供的是一种自动登录showdoc的能力，需要自己根据文档开发集成。<a
              href="https://www.showdoc.com.cn/p/0fb2753c5a48acc7c3fbbb00f9504e6b"
              target="_blank"
              >点击这里查看文档</a
            >
          </p>
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<style scoped>
.form-el {
  width: 400px;
}
</style>

<script>
export default {
  data() {
    return {
      form: {
        ldap_open: false,
        ldap_form: {
          host: '',
          port: '389',
          version: '3',
          base_dn: '',
          bind_dn: '',
          bind_password: '',
          user_field: ''
        },
        oauth2_open: false,
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
          userinfo_path: ''
        }
      },
      login_secret_key: '',
      itemList: [],
      lang: ''
    }
  },
  methods: {
    saveLdapConfig() {
      var url = DocConfig.server + '/api/adminSetting/saveLdapConfig'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          this.$alert(this.$t('success'))
        } else {
          this.$alert(response.data.error_message)
        }
      })
    },
    loadLdapConfig() {
      var url = DocConfig.server + '/api/adminSetting/loadLdapConfig'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          if (response.data.data.length === 0) {
            return
          }
          this.form.ldap_open = response.data.data.ldap_open > 0
          this.form.ldap_form = response.data.data.ldap_form
            ? response.data.data.ldap_form
            : this.form.ldap_form
        } else {
          this.$alert(response.data.error_message)
        }
      })
    },
    saveOauth2Config() {
      var url = DocConfig.server + '/api/adminSetting/saveOauth2Config'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          this.$alert(this.$t('success'))
        } else {
          this.$alert(response.data.error_message)
        }
      })
    },
    loadOauth2Config() {
      var url = DocConfig.server + '/api/adminSetting/loadOauth2Config'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          if (response.data.data.length === 0) {
            return
          }
          this.form.oauth2_open = response.data.data.oauth2_open > 0
          this.form.oauth2_form = response.data.data.oauth2_form
            ? response.data.data.oauth2_form
            : this.form.oauth2_form
        } else {
          this.$alert(response.data.error_message)
        }
      })
    },

    getLoginSecretKey() {
      this.request('/api/adminSetting/getLoginSecretKey', {}).then(data => {
        this.login_secret_key = data.data.login_secret_key
      })
    },
    resetLoginSecretKey() {
      this.$confirm(this.$t('confirm') + '?', ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/adminSetting/resetLoginSecretKey', {}).then(data => {
          this.login_secret_key = data.data.login_secret_key
        })
      })
    }
  },
  mounted() {
    this.loadLdapConfig()
    this.loadOauth2Config()
    this.getLoginSecretKey()
    this.lang = DocConfig.lang
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
