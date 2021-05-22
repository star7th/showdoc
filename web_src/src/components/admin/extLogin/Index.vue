<template>
  <div class="hello">
    <el-tabs type="border-card">
      <el-tab-pane label="LDAP">
        <el-form ref="form" :model="form" label-width="150px">
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
        <el-form ref="form" :model="form" label-width="150px">
          <el-form-item label="启动OAuth2登录">
            <el-switch v-model="form.ldap_open"></el-switch>
          </el-form-item>
          <div>
            <el-form-item label="入口文字">
              <el-input
                v-model="form.ldap_form.host"
                placeholder="eg: 使用公司OA登录"
                class="form-el"
              ></el-input>
              <el-tooltip
                class="item"
                effect="dark"
                content="当启动OAuth2登录时候，登录界面将在输入框的下方出现此入口。你可以填上如'使用公司OA登录'这样的提示"
                placement="top"
              >
                <i class="el-icon-question"></i>
              </el-tooltip>
            </el-form-item>
            <el-form-item label="Client id">
              <el-input
                v-model="form.ldap_form.host"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="Client secret">
              <el-input
                v-model="form.ldap_form.host"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="Oauth host">
              <el-select style="width:100px;">
                <el-option label="http://" value="http"></el-option>
                <el-option label="https://" value="https"></el-option>
              </el-select>
              <el-input
                class="form-el"
                placeholder="eg:  sso.your-site.com"
              ></el-input>
            </el-form-item>
            <el-form-item label="Authorize path">
              <el-input
                v-model="form.ldap_form.host"
                placeholder="eg:  /oauth/v2/authorize"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="AccessToken path">
              <el-input
                v-model="form.ldap_form.host"
                placeholder="eg:  /oauth/v2/token"
                class="form-el"
              ></el-input>
            </el-form-item>
            <el-form-item label="Resource path">
              <el-input
                v-model="form.ldap_form.host"
                placeholder="eg:  /oauth/v2/resource"
                class="form-el"
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
      <el-tab-pane label="通用接入">
        <div style="min-height:600px;margin-top:20px;">
          通用接入提供的是一种自动登录showdoc的能力，需要自己根据文档开发集成，详情请看：这里
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<style scoped>
.form-el {
  width: 230px;
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
        }
      },
      itemList: []
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
    }
  },
  mounted() {
    this.loadLdapConfig()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
