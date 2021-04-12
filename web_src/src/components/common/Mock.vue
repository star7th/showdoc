<template>
  <div>
    <el-dialog
      width="1000px"
      title="Mock"
      :visible="true"
      :close-on-click-modal="false"
      @close="callback()"
    >
      <el-form>
        <el-input
          type="textarea"
          class="dialoContent"
          placeholder="这里填写的是Mock接口的返回结果。你可以直接编辑/粘贴一段json字符串，支持使用MockJs语法（关于MockJs语法,可以查看下方的帮助说明按钮）。输入完毕后，点击保存，就会自动生成Mock地址"
          :rows="20"
          v-model="content"
        ></el-input>
        <p>
          <el-row>
            <span>Mock Url和路径 &nbsp;:&nbsp;&nbsp; {{ mockUrlPre }}</span>
            <el-input class="path-input" v-model="path"></el-input>
            <i
              class="el-icon-document-copy"
              v-clipboard:copy="mock_url"
              v-clipboard:success="onCopy"
            ></i>
          </el-row>
        </p>
        <p>
          <el-button type="primary" @click="handleClick">{{
            $t('save')
          }}</el-button
          >&nbsp;
          <el-tooltip
            class="item"
            effect="dark"
            content="假如上面填写的是一段符合json语法的字符串，点此按钮可以对json字符串进行快速格式化（美化）"
            placement="top"
          >
            <el-button @click="beautifyJson"
              >json快速美化</el-button
            > </el-tooltip
          >&nbsp; &nbsp;
          <a
            href="https://www.showdoc.com.cn/p/d952ed6b7b5fb454df13dce74d1b41f8"
            target="_blank"
            >帮助说明</a
          >
        </p>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="callback()">{{ $t('goback') }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { unescapeHTML } from '@/models/page'
export default {
  name: 'Mock',
  props: {
    formLabelWidth: '120px',
    callback: '',
    page_id: '',
    item_id: ''
  },
  data() {
    return {
      content: '',
      mock_url: '',
      mockUrlPre: '',
      path: '/'
    }
  },
  methods: {
    add() {
      this.request('/api/mock/add', {
        page_id: this.page_id,
        template: this.content,
        path: this.path
      }).then(data => {
        this.$message({
          showClose: true,
          message: '保存成功',
          type: 'success'
        })
        this.infoByPageId()
      })
    },
    infoByPageId() {
      if (this.page_id <= 0) {
        this.$alert('请先保存页面')
        this.callback()
        return
      }
      this.request('/api/mock/infoByPageId', {
        page_id: this.page_id
      }).then(data => {
        if (data.data && data.data.unique_key && data.data.template) {
          // this.mock_url = this.getRootPath() + '/server/mock-data/' + data.data.unique_key
          this.mock_url = this.mockUrlPre + data.data.path
          this.content = unescapeHTML(data.data.template)
          this.path = data.data.path
        }
      })
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
    this.mockUrlPre = DocConfig.server + '/mock-path/' + this.item_id + '&path='
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.el-icon-document-copy {
  cursor: pointer;
}
.path-input {
  width: 200px;
}
</style>
