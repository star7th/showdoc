<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <el-button type="text" @click="goback" class="goback-btn">
          <i class="el-icon-back"></i>
        </el-button>
        <el-form
          status-icon
          label-width="75px"
          class="infoForm"
          v-model="infoForm"
        >
          <el-form-item :label="$t('username') + ':'">
            <el-input
              type="text"
              auto-complete="off"
              v-model="infoForm.username"
              :disabled="true"
              placeholder
            ></el-input>
          </el-form-item>
          <!--
                  <el-form-item :label="$t('email')+':'" >
                       <span>{{emailForm.email}}({{emailForm.status}})</span> <a href="javascript:;" @click="dialogEmailFormVisible = true">{{$t("modify")}}</a>
                  </el-form-item>
          -->
          <el-form-item :label="$t('name') + ':'">
            <el-input
              type="text"
              auto-complete="off"
              v-model="infoForm.name"
              :placeholder="$t('name_tips')"
            ></el-input>
          </el-form-item>

          <el-form-item :label="$t('password') + ':'">
            <a href="javascript:;" @click="dialogPasswordFormVisible = true">{{
              $t('modify')
            }}</a>
          </el-form-item>

          <el-button type="primary" style="width:100%;" @click="formSubmit">{{
            $t('submit')
          }}</el-button>
        </el-form>
      </el-card>
    </el-container>

    <!-- 修改email弹窗 -->
    <el-dialog
      :visible.sync="dialogEmailFormVisible"
      top="10vh"
      width="300px"
      :close-on-click-modal="false"
    >
      <el-form class="emailForm">
        <el-form-item label>
          <el-input
            type="text"
            auto-complete="off"
            :placeholder="$t('input_email')"
            v-model="emailForm.email"
          ></el-input>
        </el-form-item>

        <el-form-item label>
          <el-input
            type="password"
            auto-complete="off"
            v-model="emailForm.password"
            :placeholder="$t('input_login_password')"
          ></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogEmailFormVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="emailFormSubmit">{{
          $t('confirm')
        }}</el-button>
      </div>
    </el-dialog>

    <!-- 修改密码弹窗 -->
    <el-dialog
      :visible.sync="dialogPasswordFormVisible"
      top="10vh"
      width="300px"
      :close-on-click-modal="false"
    >
      <el-form class="emailForm">
        <el-form-item label>
          <el-input
            type="password"
            auto-complete="off"
            :placeholder="$t('old_password')"
            v-model="passwordForm.password"
          ></el-input>
        </el-form-item>

        <el-form-item label>
          <el-input
            type="password"
            auto-complete="off"
            v-model="passwordForm.new_password"
            :placeholder="$t('new_password')"
          ></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogPasswordFormVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="passwordFormSubmit">{{
          $t('confirm')
        }}</el-button>
      </div>
    </el-dialog>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
  data() {
    return {
      infoForm: {
        username: '',
        name: ''
      },
      userInfo: {},
      emailForm: {
        email: '',
        status: ''
      },
      passwordForm: {
        password: '',
        new_password: ''
      },
      dialogEmailFormVisible: false,
      dialogPasswordFormVisible: false
    }
  },
  methods: {
    getUserInfo() {
      this.request('/api/user/info', {}).then(data => {
        var status
        var userInfo = data.data
        this.userInfo = userInfo
        this.passwordForm.username = userInfo.username
        this.emailForm.email = userInfo.email
        this.infoForm.username = userInfo.username
        this.infoForm.name = userInfo.name
        if (userInfo.email.length > 0) {
          this.emailForm.submit_text = this.$t('modify')
          if (userInfo.email_verify > 0) {
            status = this.$t('status_1')
          } else {
            status = this.$t('status_2')
          }
        } else {
          status = this.$t('status_3')
          this.emailForm.submit_text = this.$t('binding')
        }
        this.emailForm.status = status
      })
    },
    passwordFormSubmit() {
      this.request('/api/user/resetPassword', {
        new_password: this.passwordForm.new_password,
        password: this.passwordForm.password
      }).then(data => {
        this.dialogPasswordFormVisible = false
      })
    },
    emailFormSubmit() {
      this.request('/api/user/updateEmail', {
        email: this.emailForm.email,
        password: this.emailForm.password
      }).then(data => {
        this.dialogEmailFormVisible = false
      })
    },
    formSubmit() {
      this.request('/api/user/updateInfo', {
        name: this.infoForm.name
      }).then(data => {
        this.$message.success(this.$t('modify_success'))
        this.getUserInfo()
      })
    },
    goback() {
      this.$router.push({ path: '/item/index' })
    }
  },

  mounted() {
    this.getUserInfo()
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
  width: 500px;
  height: 400px;
}

.goback-btn {
  z-index: 999;
  font-size: 18px;
  margin-right: 800px;
}

.infoForm {
  width: 350px;
  margin: 0 auto;
  margin-top: 50px;
  text-align: left;
}
</style>
