<template>
  <div class="header-right float-right  mt-5 mr-10">
    <div>
      <el-tooltip effect="dark" :content="$t('feedback')" placement="top">
        <div @click="feedback" class="icon-item">
          <i class="el-icon-phone"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('team_mamage')" placement="top">
        <div @click="showTeam = true" class="icon-item">
          <i class="el-icon-s-flag"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('my_attachment')" placement="top">
        <div @click="showAttachment = true" class="icon-item">
          <i class="el-icon-upload"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('my_notice')" placement="top">
        <div
          @click="
            () => {
              $store.dispatch('changeNewMsg', 0)
              showMessage = true
            }
          "
          class="icon-item"
        >
          <el-badge :value="$store.state.new_msg ? 'New' : ''">
            <i class="el-icon-message-solid"></i
          ></el-badge>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('user_center')" placement="top">
        <div @click="showUserSetting = true" class="icon-item">
          <i class="el-icon-user-solid"></i>
        </div>
      </el-tooltip>
      <el-tooltip
        v-if="$lang == 'zh-cn'"
        effect="dark"
        content="客户端"
        placement="top"
      >
        <div
          @click="toOutLink('https://www.showdoc.com.cn/clients')"
          class="icon-item"
        >
          <i class="el-icon-s-platform"></i>
        </div>
      </el-tooltip>
      <el-tooltip
        v-if="$lang == 'zh-cn'"
        effect="dark"
        content="runapi"
        placement="top"
      >
        <div
          @click="toOutLink('https://www.showdoc.com.cn/runapi')"
          class="icon-item"
        >
          <i class="el-icon-s-promotion"></i>
        </div>
      </el-tooltip>
      <el-tooltip
        v-if="isAdmin"
        effect="dark"
        :content="$t('background')"
        placement="top"
      >
        <div @click="toPath('/admin/index')" class="icon-item">
          <i class="el-icon-s-tools"></i>
        </div>
      </el-tooltip>
      <div class="inline">
        <el-dropdown :show-timeout="0" trigger="hover">
          <div class="icon-item">
            <span class="el-dropdown-link">
              <i class="el-icon-more"></i>
            </span>
          </div>
          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item
              v-if="$lang == 'zh-cn'"
              @click.native="toOutLink('https://www.dfyun.com.cn/')"
            >
              <i class="el-icon-s-marketing"></i>
              CDN加速
            </el-dropdown-item>
            <el-dropdown-item
              v-if="$lang == 'zh-cn'"
              @click.native="toOutLink('https://push.showdoc.com.cn')"
            >
              <i class="el-icon-s-promotion"></i>
              推送服务
            </el-dropdown-item>

            <el-dropdown-item @click.native="logout">
              <i class="el-icon-s-unfold"></i
              >{{ $t('logout') }}</el-dropdown-item
            >
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </div>

    <!-- 团队管理 -->
    <Team
      v-if="showTeam"
      :callback="
        () => {
          showTeam = false
        }
      "
    ></Team>

    <!-- 文件库 -->
    <Attachment
      v-if="showAttachment"
      :callback="
        () => {
          showAttachment = false
        }
      "
    ></Attachment>

    <!-- 我的消息 -->
    <Message
      v-if="showMessage"
      :callback="
        () => {
          showMessage = false
        }
      "
    ></Message>

    <!-- 用户设置（用户中心） -->
    <UserSetting
      v-if="showUserSetting"
      :callback="
        () => {
          showUserSetting = false
        }
      "
    ></UserSetting>
  </div>
</template>

<script>
import Team from '@/components/team/Index'
import Attachment from '@/components/attachment/Index'
import Message from '@/components/message/Index'
import UserSetting from '@/components/user/setting/Index'
export default {
  name: 'HeaderRight',
  components: {
    Team,
    Attachment,
    Message,
    UserSetting
  },
  props: {
    isAdmin: {
      type: Boolean,
      required: false,
      default: false
    }
  },
  data() {
    return {
      msg: 'HeaderRight',
      showTeam: false,
      showAttachment: false,
      showMessage: false,
      showUserSetting: false
    }
  },
  methods: {
    feedback() {
      if (this.$lang == 'en') {
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
    toPath(path) {
      this.$router.push({ path: path })
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
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.icon-item {
  background-color: white;
  width: 40px;
  height: 40px;
  font-size: 16px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  margin-right: 10px;
  border-radius: 10px;
  box-shadow: 0 0 4px #0000001a;
}
.icon-item a {
  color: black;
}
.header-right .el-dropdown {
  font-size: 16px;
}
.header-right .icon-item {
  cursor: pointer;
}
.el-dropdown-link,
a {
  color: #343a40;
}
</style>
