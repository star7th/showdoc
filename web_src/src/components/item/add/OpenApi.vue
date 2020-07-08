<template>
  <div class="hello">
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
</template>

<script>
export default {
  name: 'Login',
  components: {},
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
<style scoped>
.hello {
  text-align: left;
  margin-left: 20px;
  margin-top: 50px;
}
</style>
