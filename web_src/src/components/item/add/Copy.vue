<template>
  <div class="hello">
    <p class="tips">{{ $t('copy_item_tips1') }}</p>
    <el-form status-icon label-width="10px" class="infoForm" v-model="infoForm">
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
          class="item"
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
      this.request('/api/item/myList', {
        item_id: this.item_id
      }).then(data => {
        this.itemList = data.data
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
      var that = this
      if (!this.isOpenItem && !this.password) {
        that.$alert(that.$t('private_item_passwrod'))
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
        that.$router.push({ path: '/item/index' })
      })
    }
  },

  mounted() {
    this.getItemList()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.infoForm {
  width: 380px;
  margin-top: 50px;
}

.tips {
  margin-left: 10px;
  color: #9ea1a6;
}
</style>
