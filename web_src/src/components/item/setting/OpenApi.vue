<template>
  <div class="hello">
    <el-form status-icon label-width="100px" class="infoForm">
      <el-form-item label="api_key：">
        <el-input
          type="text"
          auto-complete="off"
          :readonly="true"
          v-model="api_key"
          placeholder
        ></el-input>
      </el-form-item>

      <el-form-item label="api_token">
        <el-input
          type="text"
          auto-complete="off"
          :readonly="true"
          v-model="api_token"
          placeholder
        ></el-input>
      </el-form-item>

      <el-button type="primary" style="width:100%;" @click="resetKey">{{
        $t('reset_token')
      }}</el-button>
    </el-form>

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
  methods: {
    getKeyInfo() {
      this.request('/api/item/getKey', {
        item_id: this.$route.params.item_id
      }).then(data => {
        this.api_key = data.data.api_key
        this.api_token = data.data.api_token
      })
    },
    resetKey() {
      this.request('/api/item/resetKey', {
        item_id: this.$route.params.item_id
      }).then(data => {
        this.getKeyInfo()
      })
    }
  },

  mounted() {
    this.getKeyInfo()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: left;
  width: 600px;
  height: 500px;
}

.infoForm {
  width: 470px;
  margin-top: 30px;
}

.goback-btn {
  z-index: 999;
  margin-left: 500px;
}
</style>
