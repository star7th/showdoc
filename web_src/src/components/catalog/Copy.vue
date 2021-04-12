<template>
  <el-dialog :visible="true" :close-on-click-modal="false" width="350px">
    <el-form>
      <el-form-item label class="text-left">
        <el-select
          style="width:100%;"
          v-model="is_del"
          :placeholder="$t('please_choose')"
          @change="selectItem"
        >
          <el-option key="0" :label="$t('copy_to')" value="0"></el-option>
          <el-option key="1" :label="$t('move_to')" value="1"></el-option>
        </el-select>
      </el-form-item>
      <el-form-item label class="text-left">
        <el-select
          style="width:100%;"
          v-model="to_item_id"
          :placeholder="$t('please_choose')"
          @change="selectItem"
        >
          <el-option
            v-for="item in itemList"
            :key="item.item_id"
            :label="item.item_name"
            :value="item.item_id"
          ></el-option>
        </el-select>
      </el-form-item>

      <el-form-item label class="text-left">
        <el-select
          style="width:100%;"
          v-model="new_p_cat_id"
          :placeholder="$t('please_choose')"
        >
          <el-option
            v-for="item in catalogs"
            :key="item.cat_id"
            :label="item.cat_name"
            :value="item.cat_id"
          ></el-option>
        </el-select>
      </el-form-item>
    </el-form>
    <div slot="footer" class="dialog-footer">
      <el-button @click="closeDialog">{{ $t('cancel') }}</el-button>
      <el-button type="primary" @click="copy">{{ $t('confirm') }}</el-button>
    </div>
  </el-dialog>
</template>
<script>
export default {
  props: ['cat_id', 'item_id', 'callback'],
  data() {
    return {
      itemList: [],
      to_item_id: '0',
      is_del: '0',
      catalogs: [{ cat_id: '0', cat_name: '/' }],
      new_p_cat_id: '0'
    }
  },
  methods: {
    getItemList() {
      this.request('/api/item/myList', {}).then(data => {
        this.itemList = data.data
        this.to_item_id = this.item_id
      })
    },
    selectItem(item_id) {
      this.get_catalog(item_id)
    },
    get_catalog(item_id) {
      var that = this
      that
        .request('/api/catalog/catListName', {
          item_id: item_id
        })
        .then(data => {
          this.new_p_cat_id = '0'
          var Info = data.data
          Info.unshift({ cat_id: '0', cat_name: '/' })
          that.catalogs = Info
        })
    },
    copy() {
      var that = this
      that
        .request('/api/catalog/copy', {
          cat_id: this.cat_id,
          new_p_cat_id: this.new_p_cat_id,
          to_item_id: this.to_item_id,
          is_del: this.is_del
        })
        .then(data => {
          this.closeDialog()
        })
    },
    closeDialog() {
      if (this.callback) this.callback()
    }
  },
  mounted() {
    this.getItemList()
    this.get_catalog(this.item_id)
  }
}
</script>
<style scoped></style>
