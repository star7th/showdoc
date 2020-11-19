<template>
  <div>
    <el-dialog title="Mock" :visible="true" :close-on-click-modal="false" @close="callback()">
      <el-form>
        <p v-if="mock_url" style=" margin-bottom:20px;font-size: 16px">
          Mock地址 :
          <code>{{mock_url}}</code>
          <i class="el-icon-document-copy" v-clipboard:copy="mock_url" v-clipboard:success="onCopy"></i>
          &nbsp;
          <el-button @click="callback(mock_url)" type="text">把地址插入文档中</el-button>
        </p>
        <el-input
          type="textarea"
          class="dialoContent"
          placeholder="这里填写的是Mock接口的返回结果。你可以直接编辑/粘贴一段json字符串，支持使用MockJs语法（关于MockJs语法,可以查看下方的帮助说明按钮）。输入完毕后，点击保存，就会自动生成Mock地址"
          :rows="20"
          v-model="content"
        ></el-input>
        <p>
          <el-button type="primary" @click="handleClick">{{$t('save')}}</el-button>&nbsp;
          <el-tooltip
            class="item"
            effect="dark"
            content="假如上面填写的是一段符合json语法的字符串，点此按钮可以对json字符串进行快速格式化（美化）"
            placement="top"
          >
            <el-button @click="beautifyJson">json快速美化</el-button>
          </el-tooltip>&nbsp;
          &nbsp;
          <a
            href="https://www.showdoc.com.cn/p/d952ed6b7b5fb454df13dce74d1b41f8"
            target="_blank"
          >帮助说明</a>
        </p>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="callback()">{{$t('goback')}}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { unescapeHTML } from '@/models/page'
export default {
  name: 'JsonBeautify',
  props: {
    formLabelWidth: '120px',
    callback: '',
    page_id: ''
  },
  data() {
    return {
      content: '',
      json_table_data: '',
      mock_url: ''
    }
  },
  methods: {
    add() {
      this.request('/api/mock/add', {
        'page_id': this.page_id,
        'template': this.content
      }).then((data) => {
        this.$message({
          showClose: true,
          message: '保存成功',
          type: 'success'
        })
        this.mock_url = this.getUrl(data.data.unique_key)
      })
    },
    infoByPageId() {
      if (this.page_id <= 0) {
        this.$alert('请先保存页面')
        this.callback()
        return
      }
      this.request('/api/mock/infoByPageId', {
        'page_id': this.page_id
      }).then((data) => {
        if (data.data && data.data.unique_key && data.data.template) {
          this.mock_url = this.getUrl(data.data.unique_key)
          this.content = unescapeHTML(data.data.template)
        }
      })
    },
    getUrl(unique_key) {
      if (DocConfig.server.indexOf('web') > -1) {
        let server = window.location.protocol + '//' + window.location.host + window.location.pathname + 'index.php?s='
        server = server.replace(/\/web/g, '/server')
        return server + '/mock-data/' + unique_key
      } else {
        return window.location.protocol + '//' + window.location.host + '/server/index.php?s=' + '/mock-data/' + unique_key
      }
    },
    handleClick() {
      this.add()
    },
    beautifyJson() {
      this.content = this.formatJson(this.content)
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    }
  },
  mounted() {
    this.infoByPageId()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.el-icon-document-copy {
  cursor: pointer;
}
</style>
