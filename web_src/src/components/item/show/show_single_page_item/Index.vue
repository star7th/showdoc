<template>
  <div class="hello grey-bg">
    <Header id="header" :item_info="item_info">
      <HeaderRight :item_info="item_info" :page_id="page_id"></HeaderRight>
    </Header>
    <div class="container doc-container" id="doc-container">
      <div class="doc-title-box">
        <span id="doc-title-span" class="dn"></span>
        <h2 id="doc-title">{{ page_title }}</h2>
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

    <SDialog
      v-if="dialogVisible"
      :title="$t('share')"
      :onCancel="
        () => {
          dialogVisible = false
        }
      "
      :showCancel="false"
      :onOK="
        () => {
          dialogVisible = false
        }
      "
      width="500px"
    >
      <div class="text-center">
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
      </div>
    </SDialog>

    <el-backtop></el-backtop>
    <Toc v-if="page_id"></Toc>
    <Footer></Footer>
    <div class></div>
  </div>
</template>

<style scoped>
#page_md_content {
  padding: 10px 10px 90px 10px;
  overflow: hidden;
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
  margin-top: 110px;
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
}
</style>

<script>
import Editormd from '@/components/common/Editormd'
import Toc from '@/components/common/Toc'
import Header from '../Header'
import HeaderRight from './HeaderRight'
import { rederPageContent } from '@/models/page'

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
    Toc,
    Header,
    HeaderRight
  },
  methods: {
    getPageContent(page_id) {
      if (!page_id) {
        page_id = this.page_id
      }
      this.request('/api/page/info', {
        page_id: page_id
      }).then(data => {
        const json = data.data
        this.content = rederPageContent(json.page_content)
        this.page_title = json.page_title
      })
    },

    editPage() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      var url = '/page/edit/' + this.item_info.item_id + '/' + page_id
      this.$router.push({ path: url })
    },
    shareItem() {
      let path = this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
      this.share_item_link = this.getRootPath() + '#/' + path
      this.qr_item_link =
        DocConfig.server +
        '/api/common/qrcode&size=3&url=' +
        encodeURIComponent(this.share_item_link)
      this.dialogVisible = true
      this.copyText =
        this.item_info.item_name + '  -- ShowDoc \r\n' + this.share_item_link
    },
    adaptToMobile() {
      var doc_container = document.getElementById('doc-container')
      doc_container.style.width = '95%'
      doc_container.style.padding = '5px'
      doc_container.style.marginTop = '10px'
      var header = document.getElementById('header')
      header.style.display = 'none'
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    }
  },
  mounted() {
    this.menu = this.item_info.menu
    this.page_id = this.menu.pages[0].page_id
    this.getPageContent()

    // 根据屏幕宽度进行响应(应对移动设备的访问)
    if (this.isMobile() || window.screen.width < 1000) {
      this.$nextTick(() => {
        this.adaptToMobile()
      })
    }
  }
}
</script>
