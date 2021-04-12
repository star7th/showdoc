<template>
  <div class="hello">
    <el-form status-icon label-width="10px" class="infoForm" v-model="infoForm">
      <el-form-item>
        <el-radio-group v-model="infoForm.item_type">
          <el-radio label="1">{{ $t('regular_item') }}</el-radio>
          <el-radio label="4">{{ $t('table') }}</el-radio>
          <el-radio label="2">
            {{ $t('single_item') }}
            <el-tooltip
              class="item"
              effect="dark"
              :content="$t('single_item_tips')"
              placement="top"
            >
              <i class="el-icon-question"></i>
            </el-tooltip>
          </el-radio>
        </el-radio-group>
      </el-form-item>
      <el-form-item>
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('item_name')"
          placement="right"
        >
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_name"
            :placeholder="$t('item_name')"
          ></el-input>
        </el-tooltip>
      </el-form-item>

      <el-form-item>
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('item_description')"
          placement="right"
        >
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_description"
            :placeholder="$t('item_description')"
          ></el-input>
        </el-tooltip>
      </el-form-item>

      <el-form-item label>
        <el-radio v-model="isOpenItem" :label="true">{{
          $t('Open_item')
        }}</el-radio>
        <el-radio v-model="isOpenItem" :label="false">{{
          $t('private_item')
        }}</el-radio>
      </el-form-item>

      <el-form-item v-show="!isOpenItem">
        <el-input
          type="password"
          auto-complete="off"
          v-model="infoForm.password"
          :placeholder="$t('visit_password')"
        ></el-input>
      </el-form-item>

      <el-form-item label>
        <el-button type="primary" style="width:100%;" @click="FormSubmit">{{
          $t('submit')
        }}</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
  data() {
    return {
      infoForm: {
        item_name: '',
        item_description: '',
        item_domain: '',
        password: '',
        item_type: '1'
      },
      isOpenItem: true
    }
  },
  methods: {
    FormSubmit() {
      var that = this
      var url = DocConfig.server + '/api/item/add'
      if (!this.isOpenItem && !this.infoForm.password) {
        that.$alert(that.$t('private_item_passwrod'))
        return false
      }
      if (this.isOpenItem) {
        this.infoForm.password = ''
      }
      var params = new URLSearchParams()
      params.append('item_type', this.infoForm.item_type)
      params.append('item_name', this.infoForm.item_name)
      params.append('item_description', this.infoForm.item_description)
      params.append('item_domain', this.infoForm.item_domain)
      params.append('password', this.infoForm.password)

      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.$router.push({ path: '/item/index' })
        } else {
          that.$alert(response.data.error_message)
        }
      })
    }
  },

  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.infoForm {
  width: 380px;
  margin-top: 30px;
}
</style>
