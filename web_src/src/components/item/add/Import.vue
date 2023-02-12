<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('import_file')"
      width="450px"
      :onOK="callback"
    >
      <p class="tips">
        <span class="tips-text" v-html="$t('import_file_tips1')"></span>
      </p>
      <p class="text-center">
        <el-upload
          :data="{ user_token: user_token }"
          class="upload-demo"
          drag
          name="file"
          :action="upload_url"
          :on-success="success"
          :before-upload="beforeUpload"
          :show-file-list="false"
        >
          <i class="el-icon-upload"></i>
          <div class="el-upload__text">
            <span v-html="$t('import_file_tips2')"></span>
          </div>
        </el-upload>
      </p>
    </SDialog>
  </div>
</template>

<script>
import { getUserInfoFromStorage } from '@/models/user.js'

export default {
  name: 'Login',
  components: {},
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    }
  },
  data() {
    return {
      api_key: '',
      api_token: '',
      upload_url: DocConfig.server + '/api/import/auto',
      loading: '',
      user_token: ''
    }
  },
  methods: {
    success(data) {
      this.loading.close()
      if (data.error_code === 0) {
        this.callback()
      } else {
        this.$alert(data.error_message)
      }
    },
    beforeUpload() {
      this.loading = this.$loading()
    }
  },

  mounted() {
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
