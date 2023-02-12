<template>
  <div class="">
    <SDialog
      :onCancel="callback"
      :title="$t('import')"
      width="400px"
      :onOK="onSubmit"
    >
      <div>
        <div class="ml-6">
          <el-radio v-model="importToItemId" :label="item_id">{{
            $t('import_into_cur_item')
          }}</el-radio>
          <el-radio v-model="importToItemId" label="0">{{
            $t('import_into_new_item')
          }}</el-radio>
        </div>
        <br />
        <div>
          <el-upload
            drag
            name="file"
            :action="uploadUrl"
            :data="{
              item_id: this.importToItemId,
              user_token: this.user_token
            }"
            :on-success="uploadCallback"
            :show-file-list="false"
          >
            <i class="el-icon-upload"></i>
            <div class="el-upload__text">
              <span class="tips-text" v-html="$t('import_file_tips2')"></span>
            </div>
          </el-upload>
        </div>
        <br />
        <div>
          <span class="tips-text" v-html="$t('import_file_tips1')"></span>
        </div>
        <br />
        <div class="tips-text">{{ $t('import_into_cur_item_tips') }}</div>
      </div>
    </SDialog>
  </div>
</template>

<script>
import { getUserInfoFromStorage } from '@/models/user.js'
export default {
  name: 'Login',
  components: {},
  props: {
    callback: () => {},
    item_id: 0
  },
  data() {
    return {
      importToItemId: 0,
      uploadUrl: DocConfig.server + '/api/import/auto',
      user_token: ''
    }
  },

  methods: {
    uploadCallback(data) {
      if (this.importToItemId > 0) {
      } else {
        this.$router.push({ path: '/item/index' })
        window.location.reload()
      }
    }
  },
  mounted() {
    const userInfo = getUserInfoFromStorage()
    this.user_token = userInfo.user_token
    this.importToItemId = this.item_id
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: center;
  width: 400px;
}

.markdown-tips {
  text-align: left;
  margin-left: 25px;
  font-size: 11px;
}
</style>
