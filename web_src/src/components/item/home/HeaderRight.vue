<template>
  <div class="header-right float-right  mt-6 mr-10">
    <div>
      <el-tooltip effect="dark" :content="$t('feedback')" placement="top">
        <div @click="showFeedback = true" class="icon-item">
          <i class="fas fa-headphones"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('my_attachment')" placement="top">
        <div @click="showAttachment = true" class="icon-item">
          <i class="fas fa-folder-arrow-up"></i>
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
            <i class="fas fa-message"></i>
          </el-badge>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('team_mamage')" placement="top">
        <div @click="showTeam = true" class="icon-item">
          <i class="fas fa-users"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('user_center')" placement="top">
        <div @click="showUserSetting = true" class="icon-item">
          <i class="fas fa-user"></i>
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
          <i class="fas fa-laptop-arrow-down"></i>
        </div>
      </el-tooltip>

      <el-tooltip
        v-if="isAdmin"
        effect="dark"
        :content="$t('background')"
        placement="top"
      >
        <div @click="toPath('/admin/index')" class="icon-item">
          <i class="fas fa-gear"></i>
        </div>
      </el-tooltip>
      <div class="inline" v-if="$lang == 'zh-cn'">
        <SDropdown
          title="更多产品功能"
          titleIcon="fas fa-ellipsis"
          :menuList="menuList"
          width="270px"
        >
          <div class="icon-item">
            <span class="el-dropdown-link">
              <i class="fas fa-ellipsis"></i>
            </span>
          </div>
        </SDropdown>
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

    <!-- 反馈 -->
    <Feedback
      v-if="showFeedback"
      :callback="
        () => {
          showFeedback = false
        }
      "
    ></Feedback>

  </div>
</template>

<script>
import Team from '@/components/team/Index'
import Attachment from '@/components/attachment/Index'
import Message from '@/components/message/Index'
import UserSetting from '@/components/user/setting/Index'
import Feedback from '@/components/common/Feedback.vue'
import SDropdown from '@/components/common/SDropdown.vue'

export default {
  name: 'HeaderRight',
  components: {
    Team,
    Attachment,
    Message,
    UserSetting,
    Feedback,
    SDropdown
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
      showUserSetting: false,
      showFeedback: false,
      menuList: []
    }
  },
  methods: {
    toPath(path) {
      this.$router.push({ path: path })
    },
  },
  mounted() {
    this.menuList = [
      {
        title: 'RunApi',
        icon: 'fas fa-terminal',
        desc: '自动生成 API 接口文档',
        method: () => {
          this.toOutLink('https://www.showdoc.com.cn/runapi')
        }
      },
      {
        title: '推送服务',
        icon: 'fas fa-car-side',
        desc: '从服务器推送消息到手机的工具',
        method: () => {
          this.toOutLink('https://push.showdoc.com.cn')
        }
      },
      {
        title: '返回首页',
        icon: 'fas fa-backward',
        desc: '回到showdoc官网首页',
        method: () => {
          this.$router.push({ path: '/' })
        }
      }
    ]
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.icon-item {
  background-color: white;
  width: 40px;
  height: 40px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  margin-right: 10px;
  border-radius: 10px;
  box-shadow: 0 0 4px #0000001a;
}
.icon-item a {
  color: #343a40;
}
.header-right .el-dropdown {
}
.header-right .icon-item {
  cursor: pointer;
}
.el-dropdown-link,
a {
  color: #343a40;
}

.header-right >>> .el-popover__reference {
  display: inline;
}
</style>
