<template>
  <!-- 分享项目的弹窗 -->
  <SDialog
    :onCancel="callback"
    :title="$t('share')"
    width="450px"
    :onOK="copy"
    okText="复制分享链接"
  >
    <p>
      {{ $t('item_address') }} : <code>{{ shareItemLink }}</code>

    </p>
  </SDialog>
</template>

<script>
export default {
  name: '',
  components: {},
  props: {
    item_info: '',
    callback: () => {}
  },
  data() {
    return {
      shareItemLink: ''
    }
  },

  methods: {
    async copy() {
      await this.$copyText(this.shareItemLink)
      this.$message(this.$t('copy_success'))
    }
  },
  mounted() {
    this.shareItemLink =
      this.getRootPath() +
      '#/' +
      (this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id)
  },
  beforeDestroy() {}
}
</script>
