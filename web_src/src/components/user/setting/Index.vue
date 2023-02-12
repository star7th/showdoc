<template>
  <div class="hello">
    <Info :callback="callback"></Info>
  </div>
</template>

<script>
import Info from './Info'
export default {
  name: 'Login',
  components: { Info },
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    }
  },
  data() {
    return {}
  },
  computed: {},
  methods: {},

  mounted() {},

  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
