<template>
  <div class="hello">
    <el-container>
      <el-header>
        <div class="header_title">ShowDoc</div>
        <router-link class="goback" to="/item/index">{{
          $t('goback')
        }}</router-link>
      </el-header>
      <el-container>
        <el-aside width="150px">
          <el-menu
            default-active="1"
            class="el-menu-vertical-demo"
            background-color="#545c64"
            text-color="#fff"
            @select="selectMenu"
            active-text-color="#ffd04b"
          >
            <el-menu-item index="1">
              <i class="el-icon-info"></i>
              <span slot="title">{{ $t('user_manage') }}</span>
            </el-menu-item>
            <el-menu-item index="2">
              <i class="el-icon-tickets"></i>
              <span slot="title">{{ $t('item_manage') }}</span>
            </el-menu-item>
            <el-menu-item index="5">
              <i class="el-icon-tickets"></i>
              <span slot="title">{{ $t('attachment_manage') }}</span>
            </el-menu-item>
            <el-menu-item index="7">
              <i class="el-icon-tickets"></i>
              <span slot="title">{{ $t('ext_login') }}</span>
            </el-menu-item>
            <el-menu-item index="3">
              <i class="el-icon-tickets"></i>
              <span slot="title">{{ $t('web_setting') }}</span>
            </el-menu-item>
            <el-menu-item index="6" v-show="$lang == 'zh-cn'">
              <i class="el-icon-tickets"></i>
              <span slot="title"
                ><el-badge :value="isUpdate ? 'new' : ''"
                  >{{ $t('about_site') }}
                </el-badge></span
              >
            </el-menu-item>
          </el-menu>
        </el-aside>
        <el-container>
          <el-main>
            <User v-if="open_menu_index == 1"></User>
            <Item v-if="open_menu_index == 2"></Item>
            <Setting v-if="open_menu_index == 3"></Setting>
            <Attachment v-if="open_menu_index == 5"></Attachment>
            <About v-if="open_menu_index == 6"></About>
            <ExtLogin v-if="open_menu_index == 7"></ExtLogin>
          </el-main>
          <el-footer>
            <!-- something -->
          </el-footer>
        </el-container>
      </el-container>
    </el-container>
  </div>
</template>

<style scoped>
.el-header {
  color: #333;
  text-align: center;
  line-height: 60px;
  border-bottom: 1px solid #ddd;
  padding-left: 0px;
}

.el-footer {
  color: #333;
  text-align: center;
  line-height: 60px;
}

.el-aside {
  background-color: rgb(84, 92, 100);
  color: #333;
  text-align: center;
  line-height: 200px;
  height: calc(100% - 60px);
  position: fixed;
}

.el-menu {
  border-right: 0px;
}

.el-main {
  margin-left: 200px;
  overflow: visible;
}

body > .el-container {
  position: absolute;
  height: 100%;
  width: 100%;
}

.el-container:nth-child(5) .el-aside,
.el-container:nth-child(6) .el-aside {
  line-height: 260px;
}

.el-container:nth-child(7) .el-aside {
  line-height: 320px;
}

.goback {
  float: right;
  margin-right: 20px;
}

.header_title {
  float: left;
  width: 150px;
  font-size: 20px;
  background-color: rgb(84, 92, 100);
  color: #fff;
  position: fixed;
}
</style>

<script>
import Item from '@/components/admin/item/Index'
import User from '@/components/admin/user/Index'
import Setting from '@/components/admin/setting/Index'
import Attachment from '@/components/admin/attachment/Index'
import ExtLogin from '@/components/admin/extLogin/Index'
import About from '@/components/admin/about/Index'
export default {
  data() {
    return {
      open_menu_index: 1,
      isUpdate: false
    }
  },
  components: {
    Item,
    User,
    Setting,
    Attachment,
    ExtLogin,
    About
  },
  methods: {
    selectMenu(index, indexPath) {
      this.open_menu_index = 0
      this.$nextTick(() => {
        this.open_menu_index = index
      })
    },
    checkUpadte() {
      this.request('/api/adminUpdate/checkUpdate', {}).then(data => {
        if (data && data.data && data.data.url) {
          this.isUpdate = true
        }
      })
    }
  },
  mounted() {
    this.checkUpadte()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
