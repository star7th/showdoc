<template>
  <div class="">
    <div>
      <br />
      <p>
        本站基于开源版<b>showdoc {{ cur_version }}</b
        >搭建
      </p>
      <p>
        如有问题或者建议，请到github提issue：<a
          href="https://github.com/star7th/showdoc/issues"
          target="_blank"
          >https://github.com/star7th/showdoc/issues</a
        >
        <br />如果你觉得showdoc好用，不妨去<a
          href="https://github.com/star7th/showdoc"
          target="_blank"
          >github</a
        >给开源项目点一个star。良好的关注度和参与度有助于开源项目的长远发展。
      </p>
      <p>
        <a href="https://www.showdoc.com.cn/" target="_blank">showdoc官方</a>
        拥有程序的版权和相应权利，在保留版权信息和链接的前提下可免费使用或者二次开发
      </p>
    </div>

    <div v-if="isUpdate">
      <hr />
      <p>
        检测到showdoc有新版更新({{
          new_version
        }})，点击下方按钮将自动升级到最新稳定版。<a
          href="https://www.showdoc.com.cn/help/13733"
          target="_blank"
          >更新日志</a
        >
      </p>
      <p>
        升级到最新版有助于及时得到官方的安全修复和功能更新，以获得更安全更稳定的使用体验。
      </p>
      <el-badge value="new"
        ><el-button type="primary" @click="clickToUpdate"
          >点此更新</el-button
        ></el-badge
      >
      <br />
      <br />
      <div>
        如点击上面按钮更新失败，<a
          href="https://www.showdoc.com.cn/help?page_id=13732"
          target="_blank"
          >点击这里</a
        >参考文档进行手动升级
      </div>
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
      url: ''
    }
  },
  methods: {
    getCurVersion() {
      this.request('/api/common/version', {}).then(data => {
        if (data && data.data && data.data.version) {
          this.cur_version = data.data.version
        }
      })
    },
    checkUpadte() {
      this.request('/api/adminUpdate/checkUpdate', {}).then(data => {
        if (data && data.data && data.data.url) {
          this.url = data.data.url
          this.new_version = data.data.new_version
          this.cur_version = data.data.version
          this.isUpdate = true
        }
      })
    },
    clickToUpdate() {
      let loading = this.$loading({
        text: '正在下载更新包...'
      })
      this.request('/api/adminUpdate/download', {}).then(
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
    this.checkUpadte()
    this.getCurVersion()
  },
  beforeDestroy() {}
}
</script>
