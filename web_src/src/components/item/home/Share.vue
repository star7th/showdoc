<template>
  <!-- 分享项目的弹窗 -->
  <SDialog
    :onCancel="callback"
    :title="$t('share')"
    width="400px"
    :onOK="callback"
    :showCancel="false"
  >
    <p>
      {{ $t('item_address') }} : <code>{{ shareItemLink }}</code>
      <i
        class="el-icon-document-copy cursor-pointer"
        v-clipboard:copy="shareItemLink"
        v-clipboard:success="onCopy"
      ></i>
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
    onCopy() {
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
