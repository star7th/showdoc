<template>
  <div class="hello">
    <p style="height:40px;"></p>
    <p>
      <el-tooltip :content="$t('attorn_tips')" placement="top-start">
        <el-button class="a_button" @click="dialogAttornVisible = true">{{
          $t('attorn')
        }}</el-button>
      </el-tooltip>
    </p>
    <p>
      <el-tooltip :content="$t('archive_tips')" placement="top-start">
        <el-button class="a_button" @click="dialogArchiveVisible = true">{{
          $t('archive')
        }}</el-button>
      </el-tooltip>
    </p>

    <p>
      <el-tooltip :content="$t('delete_tips')" placement="top-start">
        <el-button class="a_button" @click="dialogDeleteVisible = true">{{
          $t('delete')
        }}</el-button>
      </el-tooltip>
    </p>

    <el-dialog
      :visible.sync="dialogAttornVisible"
      :modal="false"
      width="300px"
      :close-on-click-modal="false"
    >
      <el-form>
        <el-form-item label>
          <el-input
            :placeholder="$t('attorn_username')"
            v-model="attornForm.username"
          ></el-input>
        </el-form-item>
        <el-form-item label>
          <el-input
            type="password"
            :placeholder="$t('input_login_password')"
            v-model="attornForm.password"
          ></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogAttornVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="attorn">{{ $t('attorn') }}</el-button>
      </div>
    </el-dialog>

    <el-dialog
      :visible.sync="dialogArchiveVisible"
      :modal="false"
      width="300px"
      :close-on-click-modal="false"
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

      <p class="tips">{{ $t('archive_tips2') }}</p>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogArchiveVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="archive">{{
          $t('archive')
        }}</el-button>
      </div>
    </el-dialog>

    <el-dialog
      :visible.sync="dialogDeleteVisible"
      :modal="false"
      width="300px"
      :close-on-click-modal="false"
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
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogDeleteVisible = false">{{
          $t('cancel')
        }}</el-button>
        <el-button type="primary" @click="deleteItem">{{
          $t('delete')
        }}</el-button>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
  data() {
    return {
      dialogAttornVisible: false,
      dialogArchiveVisible: false,
      dialogDeleteVisible: false,
      attornForm: {
        username: '',
        password: ''
      },
      archiveForm: {
        password: ''
      },
      deleteForm: {
        password: ''
      }
    }
  },
  methods: {
    deleteItem() {
      this.request('/api/item/delete', {
        item_id: this.$route.params.item_id,
        password: this.deleteForm.password
      }).then(data => {
        this.dialogDeleteVisible = false
        this.$message.success(this.$t('success_jump'))
        setTimeout(() => {
          this.$router.push({ path: '/item/index' })
        }, 2000)
      })
    },
    archive() {
      this.request('/api/item/archive', {
        item_id: this.$route.params.item_id,
        password: this.archiveForm.password
      }).then(data => {
        this.dialogArchiveVisible = false
        this.$message.success(this.$t('success_jump'))
        setTimeout(() => {
          this.$router.push({ path: '/item/index' })
        }, 2000)
      })
    },

    attorn() {
      this.request('/api/item/attorn', {
        item_id: this.$route.params.item_id,
        username: this.attornForm.username,
        password: this.attornForm.password
      }).then(data => {
        this.dialogAttornVisible = false
        this.$message.success(this.$t('success_jump'))
        setTimeout(() => {
          this.$router.push({ path: '/item/index' })
        }, 2000)
      })
    }
  },

  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.a_button {
  width: 30%;
}

.a_button:first-child {
  margin-top: 30px;
}
</style>
