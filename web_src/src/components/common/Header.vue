<template>
  <div>
    <div class="header">
      <el-container class="header-narrow">
        <el-row class="masthead">
          <div class="logo-title">
            <h2 class="muted">
              <img src="static/logo/b_64.png" style="width:50px;height:50px;margin-bottom:-10px;" alt />ShowDoc
            </h2>
          </div>
          <div class="header-btn-group pull-right">
            <el-tooltip class="item" effect="dark" :content="$t('feedback')" placement="top">
              <router-link to>
                <i @click="feedback" class="el-icon-phone-outline"></i>
              </router-link>
            </el-tooltip>

            <el-tooltip
              v-if="lang =='zh-cn'"
              class="item"
              effect="dark"
              content="客户端"
              placement="top"
            >
              <a target="_blank" href="https://www.showdoc.cc/clients">
                <i class="el-icon-mobile-phone"></i>
              </a>
            </el-tooltip>

            <el-tooltip
              v-if="lang =='zh-cn'"
              class="item"
              effect="dark"
              content="接口开发调试工具RunApi"
              placement="top"
            >
              <a target="_blank" href="https://www.showdoc.cc/runapi">
                <i class="el-icon-connection"></i>
              </a>
            </el-tooltip>

            <el-tooltip class="item" effect="dark" :content="$t('team_mamage')" placement="top">
              <router-link to="/team/index">
                <i class="el-icon-s-flag"></i>
              </router-link>
            </el-tooltip>

            <el-tooltip
              v-if="isAdmin"
              class="item"
              effect="dark"
              :content="$t('background')"
              placement="top"
            >
              <router-link to="/admin/index">
                <i class="el-icon-s-tools"></i>
              </router-link>
            </el-tooltip>&nbsp;&nbsp;
            <el-tooltip class="item" effect="dark" :content="$t('more')" placement="top">
              <el-dropdown @command="dropdown_callback" trigger="click">
                <span class="el-dropdown-link">
                  <i class="el-icon-caret-bottom el-icon--right"></i>
                </span>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item>
                    <router-link to="/user/setting">{{$t("Logged")}}:{{username}}</router-link>
                  </el-dropdown-item>
                  <el-dropdown-item>
                    <router-link to="/attachment/index">{{$t("my_attachment")}}</router-link>
                  </el-dropdown-item>
                  <el-dropdown-item :command="logout">{{$t("logout")}}</el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </el-tooltip>
          </div>
        </el-row>
      </el-container>
    </div>
    <router-view  style="padding-top: 100px"/>
  </div>
</template>

<script>
export default {
  name: 'Header',
  data() {
    return {
      msg: '头部',
      isAdmin: false,
      lang: '',
      username: ''
    }
  },

  methods: {
    dropdown_callback(data) {
      if (data) {
        data()
      }
    },
    feedback() {
      if (DocConfig.lang == 'en') {
        window.open('https://github.com/star7th/showdoc/issues')
      } else {
        var msg =
          '你正在使用免费开源版showdoc，如有问题或者建议，请到github提issue：'
        msg +=
          "<a href='https://github.com/star7th/showdoc/issues' target='_blank'>https://github.com/star7th/showdoc/issues</a><br>"
        msg +=
          '如果你觉得showdoc好用，不妨给开源项目点一个star。良好的关注度和参与度有助于开源项目的长远发展。'
        this.$alert(msg, {
          dangerouslyUseHTMLString: true
        })
      }
    },
    logout() {
      var that = this
      var url = DocConfig.server + '/api/user/logout'

      var params = new URLSearchParams()

      that.axios.get(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.$router.push({
            path: '/'
          })
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
  },
  mounted() {
    this.lang = DocConfig.lang
    this.get_user_info(response => {
      if (response.data.error_code === 0) {
        this.username = response.data.data.username
        if (response.data.data.groupid == 1) {
          that.isAdmin = true
        }
      }
    })
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.header{
  width: 100%;
  background: rgb(44, 96, 106);
  position: fixed;
  top: 0;
  height: 82px;
  margin-bottom: 20px;
  z-index: 100;
}
.header-narrow{
  margin: 0 auto;
  max-width: 1030px;
  color: #fff;
  width: 100%;
}
.masthead {
  width: 100%;
}
.header-btn-group {
  margin-top: -38px;
  font-size: 18px;
}

.header-btn-group a {
  color: #333;
  margin-left: 25px;
}
.header-btn-group i {
  color: #fff;
}
.el-dropdown {
  font-size: 18px;
}
.el-dropdown-link,
a {
  color: #333;
}
.logo-title {
  margin-left: 0px;
}


</style>
