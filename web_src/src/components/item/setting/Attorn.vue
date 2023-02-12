<template>
  <div class="">
    <SDialog
      :title="$t('attorn')"
      :onCancel="callback"
      :onOK="attorn"
      width="400px"
    >
      <el-form>
        <el-form-item label>
          <el-input
            :placeholder="$t('attorn_username')"
            auto-complete="new-password"
            v-model="attornForm.username"
          ></el-input>
        </el-form-item>
        <el-form-item label>
          <el-input
            type="password"
            auto-complete="new-password"
            :placeholder="$t('input_login_password')"
            v-model="attornForm.password"
          ></el-input>
        </el-form-item>
      </el-form>
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
      attornForm: {
        username: '',
        password: ''
      }
    }
  },
  methods: {
    attorn() {
      this.request('/api/item/attorn', {
        item_id: this.item_id,
        username: this.attornForm.username,
        password: this.attornForm.password
      }).then(data => {
        this.callback()
      })
    }
  },

  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
