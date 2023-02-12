<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card
        class="center-card"
        onkeydown="if(event.keyCode==13)return false;"
      >
        <el-form
          status-icon
          label-width="0px"
          class="demo-ruleForm"
          @keyup.enter.native="onSubmit"
        >
          <h2>{{ $t('input_visit_password') }}</h2>

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
              $t('submit')
            }}</el-button>
          </el-form-item>

          <el-form-item label>
            <router-link to="/user/login">{{ $t('login') }}</router-link
            >&nbsp;&nbsp;&nbsp;
          </el-form-item>
        </el-form>
      </el-card>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
  data() {
    return {
      password: '',
      captchaId: 0,
      captcha: '',
      v_code_img: ''
    }
  },
  methods: {
    onSubmit() {
      var item_id = this.$route.params.item_id ? this.$route.params.item_id : 0
      var page_id = this.$route.query.page_id ? this.$route.query.page_id : 0
      let params = {
        item_id: item_id,
        page_id: page_id,
        password: this.password,
        captcha: this.captcha,
        captcha_id: this.captchaId
      }
      this.request('/api/item/pwd', params, 'post', false).then(data => {
        if (data.error_code === 0) {
          // _item_pwd参数的作用在于：跨域请求的时候无法带cooies，自然无法记住session。用这个参数使记住用户输入过项目密码。
          sessionStorage.setItem('_item_pwd', this.password)
          let redirect = decodeURIComponent(
            this.$route.query.redirect || '/' + item_id
          )
          this.$router.replace({
            path: redirect
          })
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
