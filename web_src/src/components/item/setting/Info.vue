<template>
  <div class="hello">
    <el-form
      status-icon
      label-width="100px"
      class="infoForm"
      v-model="infoForm"
    >
      <el-form-item>
        <el-tooltip effect="dark" content="项目名" placement="right">
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_name"
            placeholder
          ></el-input>
        </el-tooltip>
      </el-form-item>

      <el-form-item>
        <el-tooltip effect="dark" content="项目描述" placement="right">
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_description"
            :placeholder="$t('item_description')"
          ></el-input>
        </el-tooltip>
      </el-form-item>

      <el-form-item>
        <el-tooltip
          effect="dark"
          content="假如你的个性域名设置为abc，则你的项目地址为www.showdoc.com.cn/abc"
          placement="top-end"
        >
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_domain"
            :placeholder="$t('info_item_domain')"
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
        <el-button type="primary" style="width:100%;" @click="formSubmit">{{
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
      infoForm: {},
      isOpenItem: true
    }
  },
  methods: {
    getItemInfo() {
      this.request('/api/item/detail', {
        item_id: this.$route.params.item_id
      }).then(data => {
        const json = data.data
        if (json.password) {
          this.isOpenItem = false
        }
        this.infoForm = json
      })
    },
    formSubmit() {
      if (!this.isOpenItem && !this.infoForm.password) {
        this.$alert(this.$t('private_item_passwrod'))
        return false
      }
      if (this.isOpenItem) {
        this.infoForm.password = ''
      }
      this.request('/api/item/update', {
        item_id: this.$route.params.item_id,
        item_name: this.infoForm.item_name,
        item_description: this.infoForm.item_description,
        item_domain: this.infoForm.item_domain,
        password: this.infoForm.password
      }).then(data => {
        this.$message.success(this.$t('modify_success'))
      })
    }
  },

  mounted() {
    this.getItemInfo()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: center;
  width: 600px;
  height: 500px;
}

.infoForm {
  width: 350px;
  margin-left: 20px;
  margin-top: 60px;
}

.goback-btn {
  z-index: 999;
  margin-left: 500px;
}
</style>
