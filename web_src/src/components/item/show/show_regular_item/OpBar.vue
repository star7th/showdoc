<template>
  <div class="hello">
    <div v-if="show_menu_btn" id="header-right-btn">
      <el-dropdown trigger="click" @command="handleCommand">
        <span class="el-dropdown-link">
          <i class="el-icon-caret-bottom el-icon--right"></i>
        </span>
        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item command="goback">{{
            $t('goback')
          }}</el-dropdown-item>
          <el-dropdown-item command="share">{{ $t('share') }}</el-dropdown-item>
          <el-dropdown-item v-if="item_info.item_edit" command="new_page">{{
            $t('new_page')
          }}</el-dropdown-item>
          <el-dropdown-item v-if="item_info.item_edit" command="new_catalog">{{
            $t('new_catalog')
          }}</el-dropdown-item>
          <el-dropdown-item v-if="item_info.item_edit" command="edit_page">{{
            $t('edit_page')
          }}</el-dropdown-item>
          <el-dropdown-item v-if="item_info.item_edit" command="copy">{{
            $t('copy')
          }}</el-dropdown-item>
          <el-dropdown-item
            v-if="item_info.item_edit"
            command="ShowHistoryVersion"
            >{{ $t('history_version') }}</el-dropdown-item
          >
          <el-dropdown-item v-if="item_info.item_edit" command="export">{{
            $t('export')
          }}</el-dropdown-item>
          <el-dropdown-item v-if="item_info.item_edit" command="delete_page">{{
            $t('delete_interface')
          }}</el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>

    <div class="op-bar" v-if="show_op_bar">
      <span v-if="!item_info.is_login">
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('index_login_or_register')"
          placement="top"
        >
          <router-link to="/user/login">
            <i class="el-icon-user"></i>
          </router-link>
        </el-tooltip>
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('history_version')"
          placement="top"
        >
          <i class="el-icon-goods" @click="ShowHistoryVersion"></i>
        </el-tooltip>
        <el-tooltip
          class="item"
          effect="dark"
          v-if="lang == 'zh-cn'"
          :content="$t('about_showdoc')"
          placement="top"
        >
          <a href="https://www.showdoc.cc/help" target="_blank">
            <i class="el-icon-arrow-right"></i>
          </a>
        </el-tooltip>
      </span>

      <span v-if="item_info.is_login">
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('goback')"
          placement="left"
        >
          <router-link to="/item/index">
            <i class="el-icon-back"></i>
          </router-link>
        </el-tooltip>

        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('share')"
          placement="top"
        >
          <i class="el-icon-share" @click="share_page"></i>
        </el-tooltip>

        <el-tooltip
          v-if="!item_info.item_edit"
          class="item"
          effect="dark"
          :content="$t('detail')"
          placement="top"
        >
          <i class="el-icon-info" @click="show_page_info"></i>
        </el-tooltip>
      </span>

      <span v-if="item_info.item_edit">
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('new_page')"
          placement="top"
        >
          <i class="el-icon-plus" @click="new_page"></i>
        </el-tooltip>
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('new_catalog')"
          placement="left"
        >
          <i class="el-icon-folder" @click="mamage_catalog"></i>
        </el-tooltip>
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('edit_page')"
          placement="top"
        >
          <i class="el-icon-edit" @click="edit_page"></i>
        </el-tooltip>

        <el-tooltip
          v-show="!showMore"
          class="item"
          effect="dark"
          :content="$t('more')"
          placement="top"
        >
          <i class="el-icon-caret-top" @click="showMoreAction"></i>
        </el-tooltip>
        <el-tooltip
          v-show="showMore"
          class="item"
          effect="dark"
          :content="$t('more')"
          placement="top"
        >
          <i class="el-icon-caret-bottom" @click="hideMoreAction"></i>
        </el-tooltip>

        <span v-show="showMore">
          <el-tooltip
            class="item"
            effect="dark"
            :content="$t('copy')"
            placement="left"
          >
            <router-link
              :to="'/page/edit/' + item_id + '/0?copy_page_id=' + page_id"
            >
              <i class="el-icon-document"></i>
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            :content="$t('history_version')"
            placement="top"
          >
            <i class="el-icon-goods" @click="ShowHistoryVersion"></i>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            :content="$t('detail')"
            placement="top"
          >
            <i class="el-icon-info" @click="show_page_info"></i>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            :content="$t('export')"
            placement="left"
          >
            <router-link
              :to="'/item/export/' + item_info.item_id"
              v-if="item_info.item_edit"
            >
              <i class="el-icon-download"></i>
            </router-link>
          </el-tooltip>
          <el-tooltip
            class="item"
            effect="dark"
            :content="$t('delete_interface')"
            placement="top"
          >
            <i class="el-icon-delete" @click="delete_page"></i>
          </el-tooltip>

          <span v-if="item_info.item_manage">
            <el-tooltip
              class="item"
              effect="dark"
              :content="$t('item_setting')"
              placement="left"
            >
              <router-link
                :to="'/item/setting/' + item_info.item_id"
                v-if="item_info.item_manage"
              >
                <i class="el-icon-setting"></i>
              </router-link>
            </el-tooltip>
          </span>
        </span>
      </span>
    </div>

    <el-dialog
      :title="$t('share_page')"
      :visible.sync="dialogVisible"
      width="600px"
      :close-on-click-modal="false"
    >
      <p>
        {{ $t('item_page_address') }} :
        <code>{{ share_page_link }}</code>
        <i
          class="el-icon-document-copy"
          v-clipboard:copy="share_page_link"
          v-clipboard:success="onCopy"
        ></i>
      </p>
      <p v-if="false" style="border-bottom: 1px solid #eee;">
        <img
          id="qr-page-link"
          style="width:114px;height:114px;"
          :src="qr_page_link"
        />
      </p>

      <div v-show="item_info.item_edit">
        <el-checkbox
          v-model="isCreateSiglePage"
          @change="checkCreateSiglePage"
          >{{ $t('create_sigle_page') }}</el-checkbox
        >

        <p v-if="isCreateSiglePage">
          {{ $t('single_page_address') }} :
          <code>{{ share_single_link }}</code>
          <i
            class="el-icon-document-copy"
            v-clipboard:copy="share_single_link"
            v-clipboard:success="onCopy"
          ></i>
        </p>
        <p></p>
        <p>{{ $t('create_sigle_page_tips') }}</p>
      </div>

      <span slot="footer" class="dialog-footer">
        <el-button type="primary" @click="dialogVisible = false">{{
          $t('confirm')
        }}</el-button>
      </span>
    </el-dialog>

    <!-- 历史版本 -->
    <HistoryVersion
      :page_id="page_id"
      :is_show_recover_btn="false"
      :is_modal="false"
      callback="insertValue"
      ref="HistoryVersion"
    ></HistoryVersion>
  </div>
</template>

<style scoped>
.op-bar {
  color: #333;
  position: fixed;
  top: 110px;
  margin-left: 840px;
  max-width: 250px;
}
.op-bar i {
  cursor: pointer;
  font-size: 16px;
  margin-right: 55px;
  margin-bottom: 30px;
}

.icon-folder {
  width: 15px;
  height: 12px;
  cursor: pointer;
  margin-right: 55px;
}

a {
  color: #333;
}

#header-right-btn {
  font-size: 20px;
  top: 15px;
  right: 5%;
  cursor: pointer;
  position: fixed;
}

.el-dropdown-link {
  color: #000;
  font-size: 18px;
  font-weight: bolder;
}
.el-icon-document-copy {
  cursor: pointer;
}
</style>

<script>
import HistoryVersion from '@/components/page/edit/HistoryVersion'
export default {
  props: {
    item_id: '',
    item_domain: '',
    page_id: '',
    item_info: '',
    page_info: {}
  },
  data() {
    return {
      menu: [],
      dialogVisible: false,
      qr_page_link: '#',
      qr_single_link: '#',
      share_page_link: '',
      share_single_link: '',
      copyText1: '',
      copyText2: '',
      isCreateSiglePage: false,
      showMore: false,
      lang: '',
      show_menu_btn: false,
      show_op_bar: true
    }
  },
  components: {
    HistoryVersion
  },
  methods: {
    edit_page() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      var url = '/page/edit/' + this.item_id + '/' + page_id
      this.$router.push({ path: url })
    },
    share_page() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      let path = this.item_domain ? this.item_domain : this.item_id
      this.share_page_link = this.getRootPath() + '#/' + path + '/' + page_id
      // this.share_single_link= this.getRootPath()+"/page/"+page_id ;
      this.qr_page_link =
        DocConfig.server +
        '/api/common/qrcode&size=3&url=' +
        encodeURIComponent(this.share_page_link)
      // this.qr_single_link = DocConfig.server +'/api/common/qrcode&size=3&url='+encodeURIComponent(this.share_single_link);
      this.dialogVisible = true
      this.copyText1 =
        this.item_info.item_name +
        ' - ' +
        this.page_info.page_title +
        '\r\n' +
        this.share_page_link
      this.copyText2 =
        this.page_info.page_title + '\r\n' + this.share_single_link
    },
    dropdown_callback(data) {
      if (data) {
        data()
      }
    },
    show_page_info() {
      var html =
        '本页面由 ' +
        this.page_info.author_username +
        ' 于 ' +
        this.page_info.addtime +
        ' 更新'
      this.$alert(html)
    },

    // 展示历史版本
    ShowHistoryVersion() {
      let childRef = this.$refs.HistoryVersion // 获取子组件
      childRef.show()
    },

    delete_page() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      var that = this
      var url = DocConfig.server + '/api/page/delete'

      this.$confirm(that.$t('comfirm_delete'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var params = new URLSearchParams()
        params.append('page_id', page_id)
        that.axios.post(url, params).then(function(response) {
          if (response.data.error_code === 0) {
            window.location.reload()
          } else {
            that.$alert(response.data.error_message)
          }
        })
      })
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    },
    checkCreateSiglePage(newvalue) {
      if (newvalue) {
        this.CreateSiglePage()
      } else {
        this.$confirm(this.$t('cancelSingle'), ' ', {
          confirmButtonText: this.$t('cancelSingleYes'),
          cancelButtonText: this.$t('cancelSingleNo'),
          type: 'warning'
        }).then(
          () => {
            this.CreateSiglePage()
          },
          () => {
            this.isCreateSiglePage = true
          }
        )
      }
    },
    CreateSiglePage() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      var that = this
      var url = DocConfig.server + '/api/page/createSinglePage'
      var params = new URLSearchParams()
      params.append('page_id', page_id)
      params.append('isCreateSiglePage', this.isCreateSiglePage)

      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          var unique_key = response.data.data.unique_key
          if (unique_key) {
            that.share_single_link = that.getRootPath() + '#/p/' + unique_key
          } else {
            that.share_single_link = ''
          }
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    new_page() {
      var url = '/page/edit/' + this.item_info.item_id + '/0'
      this.$router.push({ path: url })
    },

    mamage_catalog() {
      var url = '/catalog/' + this.item_info.item_id
      this.$router.push({ path: url })
    },
    showMoreAction() {
      this.showMore = true
      var element = document
        .getElementById('page_md_content')
        .getElementsByClassName('open-list')
      element[0].style.top = '330px'
    },
    hideMoreAction() {
      this.showMore = false
      var element = document
        .getElementById('page_md_content')
        .getElementsByClassName('open-list')
      element[0].style.top = '230px'
    },
    handleCommand(command) {
      switch (command) {
        case 'goback':
          this.$router.push({ path: '/item/index' })
          break
        case 'share':
          this.share_page()
          break
        case 'new_page':
          this.new_page()
          break
        case 'new_catalog':
          this.mamage_catalog()
          break
        case 'edit_page':
          this.edit_page()
          break
        case 'ShowHistoryVersion':
          this.ShowHistoryVersion()
          break
        case 'copy':
          this.$router.push({
            path:
              '/page/edit/' +
              this.item_info.item_id +
              '/0?copy_page_id=' +
              this.page_id
          })
          break
        case 'export':
          this.$router.push({ path: '/item/export/' + this.item_info.item_id })
          break
        case 'delete_page':
          this.delete_page()
          break
      }
    }
  },
  mounted() {
    var that = this
    this.lang = DocConfig.lang
    if (this.page_info.unique_key) {
      this.isCreateSiglePage = true
      this.share_single_link =
        this.getRootPath() + '#/p/' + this.page_info.unique_key
    }
    document.onkeydown = function(e) {
      // 对整个页面文档监听 其键盘快捷键
      var keyNum = window.event ? e.keyCode : e.which // 获取被按下的键值
      if (keyNum == 69 && e.ctrlKey) {
        // Ctrl +e 为编辑
        that.edit_page()
        e.preventDefault()
      }
    }

    if (
      this.isMobile() ||
      (window.innerWidth < 1300 && !this.item_info.is_login)
    ) {
      this.show_menu_btn = false
      this.show_op_bar = false
    }
    if (
      this.isMobile() ||
      (window.innerWidth < 1300 && this.item_info.is_login)
    ) {
      this.show_menu_btn = true
      this.show_op_bar = false
    }
  },
  watch: {
    page_info: function() {
      if (this.page_info.unique_key) {
        this.isCreateSiglePage = true
        this.share_single_link =
          this.getRootPath() + '#/p/' + this.page_info.unique_key
      } else {
        this.isCreateSiglePage = false
        this.share_single_link = ''
      }
    }
  },
  destroyed() {
    document.onkeydown = undefined
  }
}
</script>
