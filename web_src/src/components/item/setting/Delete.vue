<template>
  <div class="">
    <SDialog
      :title="$t('delete')"
      :onCancel="callback"
      :onOK="deleteItem"
      width="500px"
    >
      <el-form>
        <el-form-item label>
          <el-input
            type="password"
            :placeholder="$t('input_login_password')"
            v-model="deleteForm.password"
            >></el-input
          >
        </el-form-item>
      </el-form>

      <p class="tips">
        <el-tag type="danger">{{ $t('delete_tips') }}</el-tag>
      </p>
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
      dialogDeleteVisible: true,
      deleteForm: {
        password: ''
      }
    }
  },
  methods: {
    deleteItem() {
      var loading = this.$loading()
      this.request('/api/item/delete', {
        item_id: this.item_id,
        password: this.deleteForm.password
      }).then(data => {
        this.callback()
        loading.close()
      })
      // 设置一个最长关闭时间
      setTimeout(() => {
        loading.close()
      }, 5000)
    }
  },

  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
