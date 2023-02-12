<template>
  <div class="">
    <SDialog
      :title="$t('archive')"
      :onCancel="callback"
      :onOK="archive"
      width="400px"
    >
      <el-form>
        <el-form-item label>
          <el-input
            type="password"
            :placeholder="$t('input_login_password')"
            v-model="archiveForm.password"
          ></el-input>
        </el-form-item>
      </el-form>

      <p class="tips-text">{{ $t('archive_tips2') }}</p>
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
      dialogArchiveVisible: true,
      archiveForm: {
        password: ''
      }
    }
  },
  methods: {
    archive() {
      this.request('/api/item/archive', {
        item_id: this.item_id,
        password: this.archiveForm.password
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
