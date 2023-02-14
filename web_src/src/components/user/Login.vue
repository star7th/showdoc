<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <el-form
          status-icon
          label-width="0px"
          class="demo-ruleForm"
          @keyup.enter.native="onSubmit"
        >
          <h2>{{ $t('login') }}</h2>
          <el-form-item label>
            <el-input
              type="text"
              auto-complete="off"
              :placeholder="$t('username_description')"
              v-model="username"
            ></el-input>
          </el-form-item>

          <el-form-item label>
            <el-input
              type="password"
              auto-complete="off"
              v-model="password"
              :placeholder="$t('password')"
            ></el-input>
          </el-form-item>

          <el-form-item label>
            <el-input
              type="text"
              auto-complete="off"
              v-model="captcha"
              :placeholder="$t('verification_code')"
            ></el-input>
            <img
              v-bind:src="v_code_img"
              class="v_code_img"
              v-on:click="changeVcodeImg"
            />
          </el-form-item>

          <el-form-item label>
            <el-button type="primary" style="width:100%;" @click="onSubmit">{{
              $t('login')
            }}</el-button>
          </el-form-item>

          <el-form-item label>
            <router-link to="/user/register">{{
              $t('register_new_account')
            }}</router-link
            >&nbsp;&nbsp;&nbsp;
            <a :href="oauth2_url">{{ oauth2_entrance_tips }}</a>
          </el-form-item>
        </el-form>
      </el-card>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
import { getUserInfo } from '@/models/user'
export default {
  name: 'Login',
  components: {},
  data() {
    return {
      username: '',
      password: '',
      v_code_img: '',
      is_show_alert: false,
      oauth2_entrance_tips: '',
      oauth2_url: DocConfig.server + '/api/ExtLogin/oauth2',
      captchaId: 0,
      captcha: ''
    }
  },
  methods: {
    onSubmit() {
      if (this.is_show_alert) {
        return
      }

      // 对redirect参数进行校验，以防止钓鱼跳转
      if (this.$route.query.redirect) {
        let redirect = decodeURIComponent(this.$route.query.redirect)
        if (
          redirect.search(/[^A-Za-z0-9/:\?\._\*\+\-]+.*/i) > -1 ||
          redirect.indexOf('.') > -1 ||
          redirect.indexOf('//') > -1
        ) {
          this.$alert('illegal redirect')
          return false
        }
      }
      this.request(
        '/api/user/loginByVerify',
        {
          username: this.username,
          password: this.password,
          captcha: this.captcha,
          captcha_id: this.captchaId,
          redirect_login: false
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.actionAfterLogin(data.data)
        } else {
          this.changeVcodeImg()
          this.is_show_alert = true
          this.$alert(data.error_message, {
            callback: () => {
              setTimeout(() => {
                this.is_show_alert = false
              }, 500)
            }
          })
        }
      })
    },
    // 登录成功后，在这里执行一些动作
    actionAfterLogin(userinfo) {
      // 对redirect参数进行校验，以防止钓鱼跳转
      if (this.$route.query.redirect) {
        let redirect = decodeURIComponent(this.$route.query.redirect)
        if (
          redirect.search(/[^A-Za-z0-9/:\?\._\*\+\-]+.*/i) > -1 ||
          redirect.indexOf('.') > -1 ||
          redirect.indexOf('//') > -1
        ) {
          this.$alert('illegal redirect')
          return false
        }
      }

      localStorage.setItem('userinfo', JSON.stringify(userinfo))
      let redirect = decodeURIComponent(
        this.$route.query.redirect || '/item/index'
      )
      this.$router.replace({
        path: redirect
      })
    },
    changeVcodeImg() {
      this.request('/api/common/createCaptcha', {}).then(data => {
        const json = data.data
        if (DocConfig.server.indexOf('?') > -1) {
          this.v_code_img =
            DocConfig.server +
            '/api/common/showCaptcha&captcha_id=' +
            json.captcha_id +
            '&' +
            Date.parse(new Date())
        } else {
          this.v_code_img =
            DocConfig.server +
            '/api/common/showCaptcha?captcha_id=' +
            json.captcha_id +
            '&' +
            Date.parse(new Date())
        }

        this.captchaId = json.captcha_id
      })
    },
    script_cron() {
      var url = DocConfig.server + '/api/ScriptCron/run'
      this.axios.get(url)
    },
    getOauth() {
      var url = DocConfig.server + '/api/user/oauthInfo'
      this.axios.get(url).then(response => {
        if (response.data.error_code === 0) {
          if (response.data.data.oauth2_open > 0) {
            this.oauth2_entrance_tips = response.data.data.oauth2_entrance_tips
          }
        }
      })
    }
  },
  mounted() {
    var that = this
    // 对redirect参数进行校验，以防止钓鱼跳转
    if (this.$route.query.redirect) {
      let redirect = decodeURIComponent(this.$route.query.redirect)
      if (
        redirect.search(/[^A-Za-z0-9/:\?\._\*\+\-]+.*/i) > -1 ||
        redirect.indexOf('.') > -1 ||
        redirect.indexOf('//') > -1
      ) {
        this.$alert('illegal redirect')
        return false
      }
    }
    // 如果是从对话框中跳转到登录页面，可能遮罩层来不及关闭，导致登录页面无法点击。这个时候，写js去掉遮罩层。
    const eles = document.getElementsByClassName('v-modal-leave')
    for (let index = 0; index < eles.length; index++) {
      const element = eles[index]
      element.remove()
    }
    getUserInfo(function(response) {
      if (response.data.error_code === 0) {
        let redirect = decodeURIComponent(
          that.$route.query.redirect || '/item/index'
        )
        that.$router.replace({
          path: redirect
        })
      }
    })

    this.script_cron()
    this.getOauth()
    this.changeVcodeImg()
  },
  watch: {
    $route(to, from) {
      this.$router.go(0)
    }
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: center;
}

.v_code_img {
  margin-top: 20px;
}
</style>
