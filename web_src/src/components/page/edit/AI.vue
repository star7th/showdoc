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
            <el-button :loading="loading" type="primary" @click="createContent"
              >生成</el-button
            >
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
import { MessageBox } from 'element-ui'
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
      ;(async () => {
        const jsonBody = {
          content: this.content
        }

        const userinfostr = localStorage.getItem('userinfo')
        if (userinfostr) {
          const userinfo = JSON.parse(userinfostr)
          if (userinfo && userinfo.user_token) {
            jsonBody.user_token = userinfo.user_token
          }
        }

        let result = ''
        const url = DocConfig.server + '/api/ai/create'
        const answer = async isContinue => {
          const res = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(jsonBody),
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          })
          // Create a reader for the response body
          const reader = res.body.getReader()
          // Create a decoder for UTF-8 encoded text
          const decoder = new TextDecoder('utf-8')
          let render = 0
          // Function to read chunks of the response body
          const readChunk = async () => {
            return reader.read().then(({ value, done }) => {
              if (!done) {
                const dataString = decoder.decode(value)
                dataString
                  .toString()
                  .trim()
                  .split('data: ')
                  .forEach(async line => {
                    if (line != '') {
                      const text = line.replace('data: ', '')
                      try {
                        // Parse the chunk as a JSON object
                        const data = JSON.parse(text)
                        if (data.choices[0].delta.content) {
                          result += data.choices[0].delta.content
                          if (render++ > 5) {
                            this.outputContent += data.choices[0].delta.content
                            render = 0
                          } else {
                            this.outputContent = result
                          }
                          // 收到第一个内容响应就关闭loading状态
                          this.loading = false
                        }
                        if (data.choices[0].finish_reason === 'length') {
                          await answer(true)
                        } else if (data.choices[0].finish_reason === 'stop') {
                          this.outputContent = result
                          return
                        }
                        return readChunk()
                      } catch (error) {
                        // End the stream but do not send the error, as this is likely the DONE message from createCompletion
                        console.error(error)

                        if (text.trim() === '[DONE]') {
                          this.outputContent = result
                          return
                        }

                        // 如果返回的行是usage的情况，则return，以免执行下方的弹窗报错逻辑
                        if (text.indexOf('usage') > -1) {
                          return
                        }

                        try {
                          const obj = JSON.parse(text.trim())
                          if (obj.error_code !== 0) {
                            MessageBox.alert(obj.error_message)
                          }
                          this.loading = false
                        } catch (error) {}
                      }
                    }
                  })
              } else {
                // 这里是完成
                this.loading = false
              }
            })
          }

          await readChunk()
        }

        await answer()
      })()
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
