<template>
  <div class="hello">
    <el-form
      status-icon
      label-width="100px"
      class="infoForm"
      v-model="infoForm"
    >
      <el-form-item>
        <el-tooltip effect="dark" content="项目名" placement="right">
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_name"
            placeholder
          ></el-input>
        </el-tooltip>
      </el-form-item>

      <el-form-item>
        <el-tooltip effect="dark" content="项目描述" placement="right">
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_description"
            :placeholder="$t('item_description')"
          ></el-input>
        </el-tooltip>
      </el-form-item>

      <el-form-item>
        <el-tooltip
          effect="dark"
          content="假如你的个性域名设置为abc，则你的项目地址为www.showdoc.com.cn/abc"
          placement="top-end"
        >
          <el-input
            type="text"
            auto-complete="off"
            v-model="infoForm.item_domain"
            :placeholder="$t('info_item_domain')"
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
          type="password"
          auto-complete="off"
          v-model="infoForm.password"
          :placeholder="$t('visit_password')"
        ></el-input>
      </el-form-item>

      <el-form-item v-if="itemGroupList && itemGroupList.length > 0">
        <el-tooltip :content="$t('item_group_desc')" placement="top">
          <el-select
            class="w-full"
            v-model="itemGroupIdsLocal"
            multiple
            collapse-tags
            @change="onGroupChange"
            :placeholder="$t('item_group_desc')"
          >
            <el-option :value="0" :label="$t('all_items')"> </el-option>
            <el-option
              v-for="g in itemGroupList"
              :key="g.id"
              :value="Number(g.id)"
              :label="g.group_name"
            >
            </el-option>
          </el-select>
        </el-tooltip>
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
      itemGroupList: [],
      itemGroupIdsLocal: []
    }
  },
  methods: {
    getItemInfo() {
      this.request('/api/item/detail', {
        item_id: this.$route.params.item_id
      }).then(data => {
        const json = data.data
        if (json.password) {
          this.isOpenItem = false
        }
        this.infoForm = json
        // 默认分组：后端 group_ids
        if (Array.isArray(json.group_ids)) {
          this.itemGroupIdsLocal = json.group_ids
            .map(v => Number(v))
            .filter(v => !isNaN(v))
        }
      })
    },
    getItemGroupList() {
      this.request('/api/itemGroup/getList', {}).then(data => {
        this.itemGroupList = data.data || []
        this.setDefaultGroup()
      })
    },
    setDefaultGroup() {
      if (!this.infoForm || !this.infoForm.item_id || !this.itemGroupList.length) {
        return
      }
      if (this.itemGroupIdsLocal && this.itemGroupIdsLocal.length > 0) return
      const itemId = String(this.infoForm.item_id)
      const selected = []
      for (const g of this.itemGroupList) {
        if (!g || !g.item_ids) continue
        const ids = String(g.item_ids).split(',').filter(Boolean)
        if (ids.includes(itemId)) {
          selected.push(Number(g.id))
        }
      }
      if (selected.length > 0) {
        this.itemGroupIdsLocal = selected
      }
    },
    onGroupChange(val) {
      const arr = (val || []).map(v => Number(v)).filter(v => !isNaN(v))
      if (arr.includes(0)) {
        this.itemGroupIdsLocal = [0]
      } else {
        this.itemGroupIdsLocal = arr
      }
    },
    formSubmit() {
      if (!this.isOpenItem && !this.infoForm.password) {
        this.$alert(this.$t('private_item_passwrod'))
        return false
      }
      if (this.isOpenItem) {
        this.infoForm.password = ''
      }
      this.request('/api/item/update', {
        item_id: this.$route.params.item_id,
        item_name: this.infoForm.item_name,
        item_description: this.infoForm.item_description,
        item_domain: this.infoForm.item_domain,
        password: this.infoForm.password,
        item_group_ids: (this.itemGroupIdsLocal || [])
          .map(v => Number(v))
          .filter(v => !isNaN(v))
      }).then(data => {
        this.$message.success(this.$t('modify_success'))
      })
    }
  },

  mounted() {
    this.getItemInfo()
    this.getItemGroupList()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: center;
  width: 600px;
  height: 500px;
}

.infoForm {
  width: 350px;
  margin-left: 20px;
  margin-top: 60px;
}

.goback-btn {
  z-index: 999;
  margin-left: 500px;
}
</style>
