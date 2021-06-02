<template>
  <div class="hello grey-bg">
    <Header></Header>

    <div id="header"></div>
    <div class="container doc-container" id="doc-container">
      <div class="doc-title-box">
        <span id="doc-title-span" class="dn"></span>
        <h2 id="doc-title">{{ page_title }}</h2>

        <div class="tool-bar pull-right">
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
            <i class="el-icon-share" @click="share_item"></i>
          </el-tooltip>
          <el-tooltip
            v-if="item_info.item_edit && item_info.is_archived < 1"
            class="item"
            effect="dark"
            :content="$t('edit_page')"
            placement="top"
          >
            <i class="el-icon-edit" @click="edit_page"></i>
          </el-tooltip>
          <el-dropdown v-if="item_info.item_edit">
            <span class="el-dropdown-link">
              <i class="el-icon-caret-bottom el-icon--right"></i>
            </span>
            <el-dropdown-menu slot="dropdown">
              <router-link :to="'/item/export/' + item_info.item_id">
                <el-dropdown-item>{{ $t('export') }}</el-dropdown-item>
              </router-link>
              <router-link :to="'/item/setting/' + item_info.item_id">
                <el-dropdown-item>{{ $t('item_setting') }}</el-dropdown-item>
              </router-link>
            </el-dropdown-menu>
          </el-dropdown>
        </div>
      </div>
      <div id="doc-body">
        <div id="page_md_content" class="page_content_main">
          <Editormd
            v-bind:content="content"
            v-if="content"
            type="html"
          ></Editormd>
        </div>
      </div>
    </div>
    <el-dialog
      :title="$t('share')"
      :visible.sync="dialogVisible"
      width="600px"
      :close-on-click-modal="false"
      class="text-center"
    >
      <p>
        {{ $t('item_address') }} :
        <code>{{ share_item_link }}</code>
      </p>
      <p>
        <a
          href="javascript:;"
          class="home-phone-butt"
          v-clipboard:copyhttplist="copyText"
          v-clipboard:success="onCopy"
          >{{ $t('copy_link') }}</a
        >
      </p>
      <p style="border-bottom: 1px solid #eee;">
        <img id style="width:114px;height:114px;" :src="qr_item_link" />
      </p>
      <span slot="footer" class="dialog-footer">
        <el-button type="primary" @click="dialogVisible = false">{{
          $t('confirm')
        }}</el-button>
      </span>
    </el-dialog>
    <BackToTop></BackToTop>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style scoped>
#page_md_content {
  padding: 10px 10px 90px 10px;
  overflow: hidden;
  font-size: 11pt;
  line-height: 1.7;
  color: #333;
}

.doc-container {
  position: static;
  -webkit-box-shadow: 0px 1px 6px #ccc;
  -moz-box-shadow: 0px 1px 6px #ccc;
  -ms-box-shadow: 0px 1px 6px #ccc;
  -o-box-shadow: 0px 1px 6px #ccc;
  box-shadow: 0px 1px 6px #ccc;
  background-color: #fff;
  border-bottom: 1px solid #d9d9d9;
  margin-bottom: 20px;
  width: 800px;
  min-height: 500px;
  margin-left: auto;
  margin-right: auto;
  padding: 20px;
}

#header {
  height: 80px;
}

#doc-body {
  width: 90%;
  margin: 0 auto;
  background-color: #fff;
}

.doc-title-box {
  height: auto;
  margin: 30px 100px 10px 100px;
  width: auto;
  border-bottom: 1px solid #ebebeb;
  padding-bottom: 10px;
  width: 90%;
  margin: 10px auto;
}
#doc-title {
  font-size: 1.8em;
}
#footer {
  margin: 0 auto;
  width: 180px;
  font-size: 8px;
  color: #959595;
}

pre ol {
  list-style: none;
}

.markdown-body pre {
  background-color: #f7f7f9;
  border: 1px solid #e1e1e8;
}
.hljs {
  background-color: #f7f7f9;
}
.tool-bar {
  margin-top: -55px;
  font-size: 1.1em;
}
.tool-bar i {
  margin-right: 15px;
}

.el-dropdown-link,
a {
  color: #333;
}
.editormd-html-preview,
.editormd-preview-container {
  padding: 0px;
  font-size: 14px;
}
</style>

<script>
import Editormd from '@/components/common/Editormd'
import BackToTop from '@/components/common/BackToTop'
import Toc from '@/components/common/Toc'

export default {
  props: {
    item_info: ''
  },
  data() {
    return {
      menu: '',
      content: '',
      page_title: '',
      page_id: '',
      dialogVisible: false,
      share_item_link: '',
      qr_item_link: '',
      copyText: ''
    }
  },
  components: {
    Editormd,
    BackToTop,
    Toc
  },
  methods: {
    get_page_content(page_id) {
      var that = this
      var url = DocConfig.server + '/api/page/info'
      if (!page_id) {
        page_id = that.page_id
      }
      var params = new URLSearchParams()
      params.append('page_id', page_id)
      that.axios
        .post(url, params)
        .then(function(response) {
          if (response.data.error_code === 0) {
            // that.$message.success("加载成功");
            that.content = response.data.data.page_content
            that.page_title = response.data.data.page_title
          } else {
            that.$alert(response.data.error_message)
          }
        })
        .catch(function(error) {
          console.log(error)
        })
    },

    edit_page() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      var url = '/page/edit/' + this.item_info.item_id + '/' + page_id
      this.$router.push({ path: url })
    },
    share_item() {
      this.share_item_link = this.getRootPath() + '#/' + this.item_info.item_id
      this.qr_item_link =
        DocConfig.server +
        '/api/common/qrcode&size=3&url=' +
        encodeURIComponent(this.share_item_link)
      this.dialogVisible = true
      this.copyText =
        this.item_info.item_name + '  -- ShowDoc \r\n' + this.share_item_link
    },
    AdaptToMobile() {
      var doc_container = document.getElementById('doc-container')
      doc_container.style.width = '95%'
      doc_container.style.padding = '5px'
      var header = document.getElementById('header')
      header.style.height = '10px'
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    }
  },
  mounted() {
    this.menu = this.item_info.menu
    this.page_id = this.menu.pages[0].page_id
    this.get_page_content()

    // 根据屏幕宽度进行响应(应对移动设备的访问)
    if (this.isMobile() || window.screen.width < 1000) {
      this.$nextTick(() => {
        this.AdaptToMobile()
      })
    }
  }
}
</script>
