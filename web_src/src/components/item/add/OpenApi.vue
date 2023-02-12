<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('open_api')"
      width="500px"
      :onOK="callback"
    >
      <div class="ml-8">
        <p>
          <span v-html="$t('open_api_tips1')"></span>
        </p>
        <p>
          <span v-html="$t('open_api_tips2')"></span>
        </p>
        <p>
          <span v-html="$t('open_api_tips3')"></span>
        </p>
        <p>
          <span v-html="$t('open_api_tips4')"></span>
        </p>
      </div>
    </SDialog>
  </div>
</template>

<script>
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
      api_token: ''
    }
  },
  methods: {},

  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
