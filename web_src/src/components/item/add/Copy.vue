<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="$t('copy_item')"
      width="450px"
      :onOK="formSubmit"
    >
      <div class="text-center">
        <p class="tips">{{ $t('copy_item_tips1') }}</p>
        <el-form
          status-icon
          label-width="10px"
          class="infoForm"
          v-model="infoForm"
        >
          <el-form-item label class="text-left">
            <el-select
              style="width:100%;"
              v-model="copy_item_id"
              :placeholder="$t('please_choose')"
              @change="chooseCopyItem"
            >
              <el-option
                v-for="item in itemList"
                :key="item.item_id"
                :label="item.item_name"
                :value="item.item_id"
              ></el-option>
            </el-select>
          </el-form-item>

          <el-form-item>
            <el-tooltip
              effect="dark"
              :content="$t('copy_item_tips2')"
              placement="right"
            >
              <el-input
                type="text"
                auto-complete="off"
                v-model="item_name"
                :placeholder="$t('copy_item_tips2')"
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
              type="text"
              auto-complete="off"
              v-model="password"
              :placeholder="$t('visit_password')"
            ></el-input>
          </el-form-item>
        </el-form>
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
    }
  },
  data() {
    return {
      infoForm: {},
      isOpenItem: true,
      itemList: {},
      copy_item_id: '',
      item_name: '',
      item_description: '',
      password: ''
    }
  },
  methods: {
    getItemList() {
      this.request('/api/item/myList', {}).then(data => {
        const json = data.data
        this.itemList = json
      })
    },
    chooseCopyItem(item_id) {
      for (var i = 0; i < this.itemList.length; i++) {
        if (item_id == this.itemList[i].item_id) {
          this.item_name = this.itemList[i].item_name + '--copy'
          this.item_description = this.itemList[i].item_description
        }
      }
    },
    formSubmit() {
      if (!this.isOpenItem && !this.password) {
        this.$alert(this.$t('private_item_passwrod'))
        return false
      }
      if (this.isOpenItem) {
        this.password = ''
      }

      this.request('/api/item/add', {
        copy_item_id: this.copy_item_id,
        item_name: this.item_name,
        password: this.password,
        item_description: this.item_description
      }).then(() => {
        this.callback()
      })
    }
  },

  mounted() {
    this.getItemList()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
