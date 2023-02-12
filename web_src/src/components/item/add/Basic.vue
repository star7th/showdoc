<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="item_id ? $t('update_base_info') : $t('create_new_item')"
      width="500px"
      :onOK="formSubmit"
    >
      <div class="p-6">
        <div v-show="false">
          <el-radio-group v-model="infoForm.item_type">
            <el-radio label="1">{{ $t('regular_item') }}</el-radio>
            <el-radio label="4">{{ $t('table') }}</el-radio>
            <el-radio label="2">
              {{ $t('single_item') }}
              <el-tooltip
                effect="dark"
                :content="$t('single_item_tips')"
                placement="top"
              >
                <i class="el-icon-question"></i>
              </el-tooltip>
            </el-radio>
          </el-radio-group>
        </div>
        <el-row class="leading-10 mb-4">
          <el-col :span="4">&nbsp;&nbsp;{{ $t('item_name') }} : </el-col>
          <el-col :span="20">
            <el-input
              type="text"
              auto-complete="off"
              v-model="infoForm.item_name"
            ></el-input
          ></el-col>
        </el-row>
        <el-row class="leading-10 mb-4">
          <el-col :span="4">{{ $t('item_description') }} : </el-col>
          <el-col :span="20">
            <el-input
              type="text"
              auto-complete="off"
              v-model="infoForm.item_description"
            ></el-input
          ></el-col>
        </el-row>
        <el-row class="leading-10 mb-4">
          <el-col :span="4">{{ $t('accessibility') }} : </el-col>
          <el-col :span="20">
            <el-select class="w-full" v-model="isOpenItem" placeholder="">
              <el-option :value="true" :label="$t('Open_item')"> </el-option>
              <el-option :value="false" :label="$t('private_item')">
              </el-option>
            </el-select>
          </el-col>
        </el-row>
        <el-row class="leading-10 mb-4" v-show="!isOpenItem">
          <el-col :span="4">&nbsp;</el-col>
          <el-col :span="20">
            <el-input
              type="password"
              auto-complete="off"
              v-model="infoForm.password"
              :placeholder="$t('visit_password')"
            ></el-input
          ></el-col>
        </el-row>
      </div>
    </SDialog>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    },
    defaultItemType: {
      type: String || Number,
      required: false,
      default: '1'
    },
    item_id: {
      type: String || Number,
      required: false,
      default: 0
    }
  },
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
    formSubmit() {
      if (!this.isOpenItem && !this.infoForm.password) {
        this.$alert(this.$t('private_item_passwrod'))
        return false
      }
      if (this.isOpenItem) {
        this.infoForm.password = ''
      }

      if (this.item_id) {
        this.request('/api/item/update', {
          item_id: this.item_id,
          item_name: this.infoForm.item_name,
          item_description: this.infoForm.item_description,
          item_domain: this.infoForm.item_domain,
          password: this.infoForm.password
        }).then(data => {
          this.$message.success(this.$t('modify_success'))
          this.callback()
        })
      } else {
        this.request(
          '/api/item/add',
          {
            item_type: this.infoForm.item_type,
            item_name: this.infoForm.item_name,
            item_description: this.infoForm.item_description,
            item_domain: this.infoForm.item_domain,
            password: this.infoForm.password
          },
          'post',
          false
        ).then(data => {
          if (data.error_code === 0) {
            this.callback()
          } else {
            this.$alert(data.error_message)
          }
        })
      }
    },
    getItemDetail(item_id) {
      this.request('/api/item/detail', {
        item_id: item_id
      }).then(data => {
        const json = data.data
        if (json.password) {
          this.isOpenItem = false
        } else {
          this.isOpenItem = true
        }
        this.infoForm.item_name = json.item_name
        this.infoForm.item_description = json.item_description
        this.infoForm.item_domain = json.item_domain
        this.infoForm.password = json.password
        this.infoForm.item_type = json.item_type
      })
    }
  },

  mounted() {
    this.infoForm.item_type = this.defaultItemType
    if (this.item_id) {
      this.getItemDetail(this.item_id)
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
