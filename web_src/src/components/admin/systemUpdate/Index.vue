<template>
  <div class="">
    <div>
      <br />
      你当前使用的版本是<b>{{ cur_version }}</b
      >，showdoc最新版本是<b>{{ new_version }}</b>
      <br />
      <br />
      <el-button type="primary" @click="clickToUpdate">点此更新</el-button>
    </div>
    <br />
    <div>
      如点击上面按钮更新失败，<a
        href="https://www.showdoc.com.cn/help?page_id=13732"
        target="_blank"
        >点击这里</a
      >参考文档进行手动升级
    </div>
  </div>
</template>

<style scoped></style>

<script>
export default {
  data() {
    return {
      isUpdate: false,
      cur_version: '',
      new_version: '',
      url: '',
      file_url: ''
    }
  },
  methods: {
    check_upadte() {
      this.request('/api/adminUpdate/checkUpdate', {}).then(data => {
        if (data && data.data && data.data.url) {
          this.url = data.data.url
          this.file_url = data.data.file_url
          this.new_version = data.data.new_version
          this.cur_version = data.data.version
        }
      })
    },
    clickToUpdate() {
      let loading = this.$loading({
        text: '正在下载更新包...'
      })
      this.request('/api/adminUpdate/download', {
        new_version: this.new_version,
        file_url: this.file_url
      }).then(
        data => {
          loading.close()
          loading = this.$loading({
            text: '正在更新文件...'
          })
          this.request('/api/adminUpdate/updateFiles', {}).then(
            () => {
              loading.close()
              loading = this.$loading({
                text:
                  '升级文件成功，准备刷新页面...如果页面缓存迟迟不能自动更新，请手动刷新页面以更新浏览器缓存'
              })
              this.request('/api/update/checkDb')
              // this.axios.get('./')
              setTimeout(() => {
                window.location.reload()
              }, 7000)
            },
            () => {
              loading.close()
            }
          )
        },
        () => {
          loading.close()
        }
      )
    }
  },
  mounted() {
    this.check_upadte()
  },
  beforeDestroy() {}
}
</script>
