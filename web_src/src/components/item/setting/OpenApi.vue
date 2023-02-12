<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('open_api')"
      width="500px"
      :onOK="callback"
      :showCancel="false"
    >
      <div class="text-center">
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

          <el-button style="width:90%;" @click="resetKey">{{
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
    </SDialog>
  </div>
</template>

<script>
export default {
  name: '',
  components: {},
  props: {
    callback: () => {},
    item_id: 0
  },
  data() {
    return {
      api_key: '',
      api_token: ''
    }
  },
  methods: {
    getKeyInfo() {
      this.request('/api/item/getKey', {
        item_id: this.item_id
      }).then(data => {
        const json = data.data
        this.api_key = json.api_key
        this.api_token = json.api_token
      })
    },
    resetKey() {
      this.request('/api/item/resetKey', {
        item_id: this.item_id
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
<style scoped></style>
