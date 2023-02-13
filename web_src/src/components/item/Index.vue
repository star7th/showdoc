<template>
  <!--准备弃用这个组件 -->
  <div class="hello">
    <Header></Header>

    <el-container class="container-narrow">
      <el-row class="masthead">
        <div class="logo-title">
          <h2 class="muted">
            <img
              src="static/logo/b_64.png"
              style="width:50px;height:50px;margin-bottom:-10px;"
              alt
            />ShowDoc
          </h2>
        </div>
        <div class="header-btn-group pull-right">
          <el-tooltip effect="dark" content="客户端" placement="top">
            <a target="_blank" href="/clients">
              <i class="el-icon-mobile-phone"></i>
            </a>
          </el-tooltip>

          <el-tooltip
            effect="dark"
            content="接口开发调试工具RunApi"
            placement="top"
          >
            <router-link to>
              <i @click="toRunApi" class="el-icon-connection"></i>
            </router-link>
          </el-tooltip>

          <el-tooltip
            effect="dark"
            :content="$t('team_mamage')"
            placement="top"
          >
            <router-link to="/team/index">
              <i class="el-icon-s-flag"></i>
            </router-link>
          </el-tooltip>

          <el-tooltip effect="dark" content="用户中心" placement="top">
            <router-link to="/user/setting">
              <i class="el-icon-user"></i>
            </router-link>
          </el-tooltip>

          <el-tooltip
            v-if="isAdmin"
            effect="dark"
            :content="$t('background')"
            placement="top"
          >
            <router-link to="/admin/index">
              <i class="el-icon-s-tools"></i>
            </router-link> </el-tooltip
          >&nbsp;&nbsp;
          <el-tooltip effect="dark" :content="$t('more')" placement="top">
            <el-dropdown @command="dropdownCallback" trigger="click">
              <span class="el-dropdown-link">
                <i class="el-icon-caret-bottom el-icon--right"></i>
              </span>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item :command="toUserSettingLink">
                  {{ $t('Logged') }}:{{ username }}
                </el-dropdown-item>
                <el-dropdown-item divided :command="toMessageLink">
                  {{ $t('my_notice') }}
                </el-dropdown-item>
                <el-dropdown-item :command="toAttachmentLink">
                  {{ $t('my_attachment') }}
                </el-dropdown-item>
                <el-dropdown-item :command="toDfyunLink">
                  CDN加速
                </el-dropdown-item>
                <el-dropdown-item :command="toPushLink">
                  推送服务
                </el-dropdown-item>

                <el-dropdown-item :command="logout">{{
                  $t('logout')
                }}</el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </el-tooltip>
        </div>
      </el-row>
    </el-container>

    <el-container class="container-narrow">
      <div class="container-thumbnails">
        <div class="search-box-div" v-if="itemList.length > 1">
          <div class="search-box el-input el-input--prefix">
            <el-input
              autocomplete="off"
              type="text"
              validateevent="true"
              :clearable="true"
              v-model="keyword"
            />
            <span class="el-input__prefix">
              <i class="el-input__icon el-icon-search"></i>
            </span>
          </div>
        </div>
        <Search
          v-if="showSearch"
          :keyword="keyword"
          :itemList="itemList"
        ></Search>
        <div
          class="group-bar"
          v-show="!showSearch && (itemGroupId > 0 || itemList.length > 5)"
        >
          <el-radio-group
            v-model="itemGroupId"
            @change="changeGroup"
            size="small"
          >
            <el-radio-button class="radio-button" label="0">{{
              $t('all_items')
            }}</el-radio-button>
            <el-radio-button
              v-for="element in itemGroupList"
              :key="element.id"
              class="radio-button"
              :label="element.id"
              >{{ element.group_name }}</el-radio-button
            >
          </el-radio-group>
          <router-link class="group-link" to="/item/group/index">
            {{ $t('manage_item_group') }}
          </router-link>
          <router-link class="group-link" to="/item/add">
            {{ $t('new_item') }}
          </router-link>
        </div>
        <ul class="thumbnails" id="item-list" v-if="!showSearch">
          <draggable
            v-model="itemList"
            tag="span"
            group="item"
            @end="endMove"
            ghostClass="sortable-chosen"
          >
            <li
              v-loading="loading"
              class="text-center"
              v-for="item in itemList"
              :key="item.item_id"
            >
              <router-link
                class="thumbnail item-thumbnail"
                :to="'/' + (item.item_domain ? item.item_domain : item.item_id)"
                :title="item.item_description"
              >
                <!-- 自己创建的话显示项目设置按钮 -->
                <span
                  class="item-setting"
                  @click.prevent="clickItemSetting(item.item_id)"
                  :title="$t('item_setting')"
                  v-if="item.creator"
                >
                  <i class="el-icon-setting"></i>
                </span>
                <!-- 如果是加入的项目的话，这里显示退出按钮 -->
                <span
                  class="item-exit"
                  @click.prevent="clickItemExit(item.item_id)"
                  :title="$t('item_exit')"
                  v-if="!item.creator"
                >
                  <i class="el-icon-close"></i>
                </span>
                <p class="my-item">{{ item.item_name }}</p>
                <!-- 如果是加密项目的话，这里显示一个加密图标 -->
                <span class="item-private" v-if="item.is_private">
                  <el-tooltip
                    effect="dark"
                    :content="$t('private_tips')"
                    placement="right"
                  >
                    <i class="el-icon-lock"></i>
                  </el-tooltip>
                </span>
              </router-link>
            </li>
          </draggable>
          <li class="text-center" v-if="itemGroupId <= 0">
            <router-link class="thumbnail item-thumbnail" to="/item/add" title>
              <p class="my-item">
                {{ $t('new_item') }}
                <i class="el-icon-plus"></i>
              </p>
            </router-link>
          </li>
        </ul>
      </div>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<style scoped>
.container-narrow {
  margin: 0 auto;
  max-width: 930px;
}

.masthead {
  width: 100%;
  margin-top: 30px;
}

.header-btn-group {
  margin-top: -38px;
  font-size: 18px;
}

.header-btn-group a {
  color: #333;
  margin-left: 25px;
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

.container-thumbnails {
  margin-top: 30px;
  max-width: 1000px;
}

.my-item {
  margin: 40px 5px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.thumbnails li {
  float: left;
  margin-bottom: 20px;
  margin-left: 20px;
}

.thumbnails li a {
  color: #444;
  font-weight: bold;
  height: 100px;
  width: 180px;
}
.thumbnails li a:hover,
.thumbnails li a:focus {
  border-color: #f2f5e9;
  -webkit-box-shadow: none;
  box-shadow: none;
  text-decoration: none;
  background-color: #f2f5e9;
}

.thumbnail {
  display: block;
  padding: 4px;
  line-height: 20px;
  border: 1px solid #ddd;
  -webkit-border-radius: 4px;
  -moz-border-radius: 4px;
  border-radius: 4px;
  -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.055);
  -moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.055);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.055);
  -webkit-transition: all 0.2s ease-in-out;
  -moz-transition: all 0.2s ease-in-out;
  -o-transition: all 0.2s ease-in-out;
  transition: all 0.2s ease-in-out;
  list-style: none;
  background-color: #ffffff;
}

.item-setting {
  float: right;
  margin-right: 15px;
  margin-top: 5px;
  display: none;
}

.item-exit {
  float: right;
  margin-right: 5px;
  margin-top: 5px;
  display: none;
}

.item-private {
  float: right;
  margin-right: 15px;
  margin-top: -20px;
  display: none;
  cursor: default;
}

.thumbnails li a i {
  color: #777;
  font-weight: bold;
  margin-left: 5px;
}

.item-thumbnail:hover .item-setting {
  display: block;
}
.item-thumbnail:hover .item-exit {
  display: block;
}
.item-thumbnail:hover .item-private {
  display: block;
}

.search-box-div {
  width: 190px;
  margin-left: 60px;
}
.sortable-chosen .item-thumbnail {
  color: #ffffff;
  background-color: #ffffff;
}

.group-bar {
  margin-top: 20px;
  margin-bottom: 20px;
  margin-left: 60px;
}
.group-link {
  margin-left: 10px;
  font-size: 12px;
}
</style>

<style>
.group-bar .el-radio-button__inner {
  border-radius: 30px !important;
  margin-right: 5px;
}
</style>

<script>
import draggable from 'vuedraggable'
import Search from './Search'
import { getUserInfo } from '@/models/user.js'
if (typeof window !== 'undefined') {
  var $s = require('scriptjs')
}
export default {
  components: {
    draggable,
    Search
  },
  data() {
    return {
      currentDate: new Date(),
      itemList: {},
      isAdmin: false,
      keyword: '',
      username: '',
      showSearch: false,
      itemGroupId: '',
      itemGroupList: [],
      loading: false
    }
  },
  watch: {
    // 监听搜索词的变化
    keyword: function(val) {
      if (val) {
        // 当输入的字符只有一个长度的时候，是中文才会搜索。英文或者数字不会搜索
        if (val && val.length == 1) {
          // 验证是否是中文
          var pattern = new RegExp('[\u4E00-\u9FA5]+')
          if (pattern.test(val)) {
            // alert('该字符串是中文')
            this.showSearch = true
          }
        } else {
          this.showSearch = true
        }
      } else {
        this.showSearch = false
      }
    }
  },
  methods: {
    getItemList() {
      this.loading = true
      const itemGroupId = this.itemGroupId
      this.request('/api/item/myList', {
        item_group_id: itemGroupId
      }).then(data => {
        this.loading = false
        this.itemList = data.data
      })
    },

    // 进入项目设置页
    clickItemSetting(item_id) {
      this.$router.push({ path: '/item/setting/' + item_id })
    },
    clickItemExit(item_id) {
      this.$confirm(this.$t('confirm_exit_item'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/item/exitItem', {
          item_id: item_id
        }).then(data => {
          window.location.reload()
        })
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

    dropdownCallback(data) {
      if (data) {
        data()
      }
    },

    sortItem(data) {
      this.request(
        '/api/item/sort',
        {
          data: JSON.stringify(data),
          item_group_id: this.itemGroupId
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.getItemList()
          // window.location.reload();
        } else {
          this.$alert(data.error_message, '', {
            callback: function() {
              window.location.reload()
            }
          })
        }
      })
    },
    endMove(evt) {
      let data = {}
      for (var i = 0; i < this.itemList.length; i++) {
        let key = this.itemList[i]['item_id']
        data[key] = i + 1
      }
      this.sortItem(data)
    },
    toRunApi() {
      window.open('https://www.showdoc.cc/runapi')
    },
    // 检测邮箱绑定情况
    checkEmail() {
      var that = this
      getUserInfo(function(response) {
        if (response.data.error_code === 0) {
          that.username = response.data.data.username
          if (response.data.data.groupid == 1) {
            that.isAdmin = true
          }
          if (response.data.data.email_verify < 1) {
            if (response.data.data.email.length > 0) {
              that.$message({
                showClose: true,
                duration: 10000,
                dangerouslyUseHTMLString: true,
                message:
                  '系统已发一封验证邮件到你的邮箱<br><br>请登录邮箱查看验证邮件<br><br>或者<a href="/user/setting">点此修改/重发邮件</a>'
              })
            } else {
              that.$message({
                showClose: true,
                duration: 10000,
                dangerouslyUseHTMLString: true,
                message:
                  '<a href="/user/setting">点此绑定邮箱，忘记密码时可通过邮箱重置密码</a>'
              })
            }
          }
        }
      })
    },
    getItemGroupList() {
      this.request('/api/itemGroup/getList', {}).then(data => {
        this.itemGroupList = data.data
        const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
        // 循环欧判断记住的id是否还存在列表中
        this.itemGroupList.map(element => {
          if (element.id == deaultItemGroupId) {
            this.itemGroupId = deaultItemGroupId
          }
        })
        this.getItemList()
      })
    },
    changeGroup() {
      localStorage.setItem('deaultItemGroupId', this.itemGroupId)
      this.getItemList()
    },
    toUserSettingLink() {
      this.$router.push({ path: '/user/setting' })
    },
    toMessageLink() {
      this.$router.push({ path: '/message/index' })
    },
    toAttachmentLink() {
      this.$router.push({ path: '/attachment/index' })
    },
    toPushLink() {
      window.open('https://push.showdoc.com.cn')
    },
    toDfyunLink() {
      window.open('https://www.dfyun.com.cn')
    }
  },
  created() {
    this.checkEmail()
  },
  mounted() {
    const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
    if (deaultItemGroupId === null) {
      this.getItemList()
      this.itemGroupId = '0'
    } else {
      this.itemGroupId = deaultItemGroupId
    }

    this.getItemGroupList()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
