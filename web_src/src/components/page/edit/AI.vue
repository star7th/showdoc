<template>
  <div>
    <SDialog
      width="90%"
      top="10vh"
      title="AI"
      :onCancel="callback"
      :onOK="
        () => {
          callback(outputContent)
        }
      "
      okText="插入到编辑器中"
      cancelText="关闭"
    >
      <el-row :gutter="40">
        <el-col :span="12">
          <h3>输入区</h3>
          <el-input
            type="textarea"
            class="dialoContent"
            placeholder="你可以在此输入描述，告诉AI你想生成什么，然后右边的输出区可以看到结果。更多使用说明可点击下方的帮助说明按钮"
            :rows="20"
            v-model="content"
          ></el-input>

          <p>
            <el-button type="primary" @click="createContent">生成</el-button>
            &nbsp; &nbsp;
            <a
              href="https://www.showdoc.com.cn/p/b910aa406c168054994aa9250a23e398"
              target="_blank"
              >帮助说明</a
            >
          </p>
        </el-col>
        <el-col :span="12">
          <h3>输出区</h3>
          <el-input
            v-loading="loading"
            type="textarea"
            class="dialoContent"
            placeholder=" "
            :rows="20"
            v-model="outputContent"
          ></el-input>

          <!-- <div class="preview-content" v-html="outputContent"></div> -->
        </el-col>
      </el-row>
    </SDialog>
  </div>
</template>

<script>
import { unescapeHTML } from '@/models/page'
export default {
  name: 'Mock',
  props: {
    callback: '',
    page_id: '',
    item_id: ''
  },
  data() {
    return {
      content: '',
      outputContent: '',
      loading: false
    }
  },
  methods: {
    createContent() {
      this.outputContent = ''
      this.loading = true
      this.request('/api/ai/create', {
        content: this.content
      })
        .then(data => {
          this.outputContent = data.data.choices[0].message.content
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    }
  },
  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.preview-content {
  white-space: pre-line;
}
</style>
