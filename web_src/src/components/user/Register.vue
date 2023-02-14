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
          <h2>{{ $t('register') }}</h2>
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
              type="password"
              auto-complete="off"
              v-model="confirm_password"
              :placeholder="$t('password_again')"
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
              $t('register')
            }}</el-button>
          </el-form-item>

          <el-form-item label>
            <router-link to="/user/login">{{ $t('login') }}</router-link>
            &nbsp;&nbsp;&nbsp;
          </el-form-item>
        </el-form>
      </el-card>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: 'Register',
  components: {},
  data() {
    return {
      username: '',
      password: '',
      confirm_password: '',
      v_code_img: '',
      captchaId: 0,
      captcha: ''
    }
  },
  methods: {
    onSubmit() {
      this.showLoding = true
      // 如果后面的接口回调关闭不了loading的话，那这个loading最迟3秒关闭
      setTimeout(() => {
        this.showLoding = false
      }, 3000)

      this.request(
        '/api/user/registerByVerify',
        {
          username: this.username,
          password: this.password,
          confirm_password: this.confirm_password,
          captcha: this.captcha,
          captcha_id: this.captchaId,
          inviteCode: this.inviteCode
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          localStorage.setItem('userinfo', JSON.stringify(data.data))
          this.$router.push({ path: '/item/index' })
        } else {
          this.changeVcodeImg()
          this.$alert(data.error_message)
        }
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
    }
  },
  mounted() {
    // 如果是从对话框中跳转到登录页面，可能遮罩层来不及关闭，导致登录页面无法点击。这个时候，写js去掉遮罩层。
    const eles = document.getElementsByClassName('v-modal-leave')
    for (let index = 0; index < eles.length; index++) {
      const element = eles[index]
      element.remove()
    }
    this.changeVcodeImg()
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
