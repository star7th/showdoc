<template>
  <div class="hello">
    <p class="tips">{{ $t('copy_item_tips1') }}</p>
    <el-form status-icon label-width="10px" class="infoForm" v-model="infoForm">
      <el-form-item label class="text-left">
        <el-select
          style="width:100%;"
          v-model="copy_item_id"
          :placeholder="$t('please_choose')"
          @change="choose_copy_item"
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
      infoForm: {},
      isOpenItem: true,
      itemList: {},
      copy_item_id: '',
      item_name: '',
      item_description: ''
    }
  },
  methods: {
    get_item_list() {
      var that = this
      var url = DocConfig.server + '/api/item/myList'

      var params = new URLSearchParams()

      that.axios.get(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          // that.$message.success("加载成功");
          var json = response.data.data
          that.itemList = json
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    choose_copy_item(item_id) {
      for (var i = 0; i < this.itemList.length; i++) {
        if (item_id == this.itemList[i].item_id) {
          this.item_name = this.itemList[i].item_name + '--copy'
          this.item_description = this.itemList[i].item_description
        }
      }
    },
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
      params.append('copy_item_id', this.copy_item_id)
      params.append('item_name', this.item_name)
      params.append('item_description', this.item_description)

      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.$router.push({ path: '/item/index' })
        } else {
          that.$alert(response.data.error_message)
        }
      })
    }
  },

  mounted() {
    this.get_item_list()
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
