<template>
  <div>
    <SDialog
      :onCancel="callback"
      :title="$t('select_catalog')"
      width="400px"
      :onOK="
        () => {
          callback(new_p_cat_id)
        }
      "
      :btn1Text="$t('new_catalog')"
      btn1Icon="el-icon-plus"
      :btn1Medthod="
        () => {
          showCatalog = true
        }
      "
    >
      <el-form>
        <el-form-item :label="$t('select_catalog')" class="text-left">
          <el-select
            style="width:100%;"
            v-model="new_p_cat_id"
            :placeholder="$t('select_catalog')"
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
    </SDialog>

    <Catalog
      v-if="showCatalog"
      :item_id="item_id"
      :callback="
        () => {
          showCatalog = false
          getCatalog(item_id)
        }
      "
    ></Catalog>
  </div>
</template>
<script>
import Catalog from '@/components/catalog/Index'

export default {
  props: ['cat_id', 'item_id', 'callback'],
  components: { Catalog },
  data() {
    return {
      to_item_id: '0',
      is_del: '0',
      catalogs: [{ cat_id: '0', cat_name: '/' }],
      new_p_cat_id: '0',
      showCatalog: false
    }
  },
  methods: {
    getCatalog(item_id) {
      this.request('/api/catalog/catListName', {
        item_id: item_id
      }).then(data => {
        var Info = data.data
        Info.unshift({ cat_id: '0', cat_name: '/' })
        this.catalogs = Info
      })
    }
  },
  mounted() {
    this.getCatalog(this.item_id)
    this.new_p_cat_id = this.cat_id
  }
}
</script>
<style scoped></style>
