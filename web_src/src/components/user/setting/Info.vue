<!-- 附件 -->
<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('detail')"
      width="400px"
      :onOK="callback"
      :showCancel="false"
    >
      <div class="p-6 text-center">
        <div class="leading-10 mb-8">
          <div>{{ $t('username') }} :&nbsp; {{ userInfo.username }}</div>
          <div>
            {{ $t('name') }} :&nbsp;
            {{ userInfo.name ? userInfo.name : '未命名用户' }}&nbsp;<i
              @click="nameFormSubmit"
              class="el-icon-edit-outline cursor-pointer"
            ></i>
          </div>
          
          <!-- 添加推送地址设置 - 只对中文用户显示 -->
          <div v-if="isZhCn">
            微信推送地址 :&nbsp;
            {{ pushUrlForm.url ? "已设置": '未设置' }}&nbsp;<i
              @click="pushUrlFormSubmit"
              class="el-icon-edit-outline cursor-pointer"
            ></i>
          </div>
        </div>

        <div>
          <div
            class="v3-text-link mb-2"
            @click="dialogPasswordFormVisible = true"
          >
            {{ $t('modify_password') }}
          </div>
          <div class="v3-text-link" @click="logout">
            {{ $t('logout') }}
          </div>
        </div>
      </div>
    </SDialog>

    <!-- 修改密码弹窗 -->
    <SDialog
      v-if="dialogPasswordFormVisible"
      :title="$t('modify_password')"
      :onCancel="
        () => {
          dialogPasswordFormVisible = false
        }
      "
      :onOK="passwordFormSubmit"
      width="400px"
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
    </SDialog>
  </div>
</template>

<style></style>

<script>
export default {
  components: {},
  props: {
    callback: () => {}
  },
  data() {
    return {
      dialogVisible: true,
      isEx: 1,
      vip_type: 0,
      expiration_time: '',
      vip_type_text: '',
      ivdialogVisible: false,
      ivcdialogVisible: false,
      inviteCode: '',
      num1: 0,
      num2: 0,
      userInfo: {},
      dialogPasswordFormVisible: false,
      passwordForm: {
        password: '',
        new_password: ''
      },
      pushUrlForm: {
        url: ''
      },
      isZhCn: window.DocConfig.lang.toLowerCase() === 'zh-cn'
    }
  },
  computed: {},
  methods: {
    getUserInfo() {
      this.request('/api/user/info', {}).then(data => {
        var userInfo = data.data
        this.userInfo = userInfo
        // 获取用户推送地址
        this.getPushUrl()
      })
    },
    nameFormSubmit() {
      this.$prompt(this.$t('update_user_name_tips'), ' ', {}).then(data => {
        this.request('/api/user/updateInfo', {
          name: data.value
        }).then(data => {
          this.getUserInfo()
        })
      })
    },
    passwordFormSubmit() {
      this.request('/api/user/resetPassword', {
        new_password: this.passwordForm.new_password,
        password: this.passwordForm.password
      }).then(data => {
        this.dialogPasswordFormVisible = false
        this.getUserInfo()
      })
    },
    logout() {
      this.request('/api/user/logout', {
        confirm: '1'
      }).then(data => {
        // 清空所有cookies
        var keys = document.cookie.match(/[^ =;]+(?=\=)/g)
        if (keys) {
          for (var i = keys.length; i--; ) {
            document.cookie =
              keys[i] + '=0;expires=' + new Date(0).toUTCString()
          }
        }
        // 清空 localStorage
        localStorage.clear()

        this.$router.push({
          path: '/'
        })
      })
    },
    // 获取用户推送地址
    getPushUrl() {
      this.request('/api/user/getPushUrl', {}).then(data => {
        if (data.data) {
          this.pushUrlForm.url = data.data
        }
      })
    },
    // 保存用户推送地址
    savePushUrl() {
      this.request('/api/user/savePushUrl', {
        push_url: this.pushUrlForm.url
      }).then(data => {
        this.$message.success('保存成功')
      })
    },
    // 编辑推送地址
    pushUrlFormSubmit() {
      this.$prompt('输入推送地址', ' ', {
        inputValue: this.pushUrlForm.url,
        inputPlaceholder: '从 push.showdoc.com.cn 获取您的推送地址',
        dangerouslyUseHTMLString: true,
        message: `
          <div style="margin-bottom: 10px; line-height: 1.5; color: #666;">
            <p>请按照以下步骤获取您的推送地址：</p>
            <ol style="padding-left: 20px; margin: 5px 0;">
              <li>访问 <a href="https://push.showdoc.com.cn" target="_blank">push.showdoc.com.cn</a></li>
              <li>使用微信扫码登录</li>
              <li>在"推送"导航菜单找到您的专属推送地址</li>
              <li>复制该地址粘贴到此处</li>
            </ol>
            <p>绑定后，当有消息时会自动推送到您的微信</p>
          </div>
        `
      }).then(data => {
        this.pushUrlForm.url = data.value
        this.savePushUrl()
      })
    }
  },
  mounted() {
    this.getUserInfo()
  },
  destroyed() {}
}
</script>
