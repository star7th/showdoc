<template>
  <div>
    <SDialog
      :title="$t('feedback')"
      :onCancel="callback"
      :onOK="toLink"
      titleIcon="fas fa-info-circle"
      okText="去github开源仓库"
      width="450px"
    >
      <p>你正在使用免费开源版showdoc，如有问题或者建议，请到github提issue：</p>
      <p><a href='https://github.com/star7th/showdoc/issues' target='_blank'>https://github.com/star7th/showdoc/issues</a></p>
      <p>如果你觉得showdoc好用，不妨给开源项目点一个star。良好的关注度和参与度有助于开源项目的长远发展。</p>
    </SDialog>
  </div>
</template>

<script>
export default {
  name: 'Feedback',
  props: {
    callback: ''
  },
  data() {
    return {
      msg: '反馈'
    }
  },
  methods: {
    async toLink() {
      this.toOutLink('https://github.com/star7th/showdoc/issues')
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
