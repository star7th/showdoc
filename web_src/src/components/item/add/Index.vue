<template>
  <div class="create-item-btn-div">
    <div class="left">
      <div @click="regularItem" class="create-item-left-btn">
        <i class="mr-3 fas fa-plus"></i>
        <span>{{ $t('create_new_item') }}</span>
      </div>
    </div>
    <div class="right">
      <SDropdown
        :title="$t('create_new_item')"
        titleIcon="fas fa-plus"
        :menuListGroup="menuListGroup"
        placement="top-start"
      >
        <div class="create-item-right-btn">
          <i class="fas fa-ellipsis"></i>
        </div>
      </SDropdown>
    </div>

    <Basic
      v-if="showBasic"
      :callback="
        () => {
          showBasic = false
          callback()
        }
      "
      :defaultItemType="defaultItemType"
      :itemGroupId="itemGroupId"
    >
    </Basic>
    <Import
      v-if="showImportFile"
      :callback="
        () => {
          showImportFile = false
          callback()
        }
      "
    >
    </Import>

    <OpenApi
      v-if="showOpenApi"
      :callback="
        () => {
          showOpenApi = false
          callback()
        }
      "
    >
    </OpenApi>
  </div>
</template>

<script>
import Basic from '@/components/item/add/Basic'
import OpenApi from '@/components/item/add/OpenApi'
import Import from '@/components/item/add/Import'
import SDropdown from '@/components/common/SDropdown.vue'

export default {
  name: 'Login',
  components: {
    Basic,
    OpenApi,
    Import,
    SDropdown
  },
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {},
    },
    itemGroupId: 0

  },
  data() {
    return {
      showBasic: false,
      showImportFile: false,
      defaultItemType: '1',
      showOpenApi: false,
      showPopover: false,
      menuListGroup: []
    }
  },
  methods: {
    regularItem() {
      this.defaultItemType = '1'
      this.showBasic = true
    },
    singleItem() {
      this.defaultItemType = '2'
      this.showBasic = true
    },
    tableItem() {
      this.defaultItemType = '4'
      this.showBasic = true
    },
    importFile() {
      this.showImportFile = true
    },
    autoCreate() {
      this.showOpenApi = true
    }
  },

  mounted() {
    this.menuListGroup = [
      {
        group_name: this.$t('create'),
        listMenu: [
          {
            title: this.$t('regular_item'),
            icon: 'fas fa-notes',
            desc: this.$t('regular_item_desc'),
            method: this.regularItem
          },
          {
            title: this.$t('single_item'),
            icon: 'fas fa-file',
            desc: this.$t('single_item_desc'),
            method: this.singleItem
          },
          {
            title: this.$t('table_item'),
            icon: 'fas fa-table',
            desc: this.$t('table_item_desc'),
            method: this.tableItem
          }
        ]
      },
      {
        group_name: this.$t('import'),
        listMenu: [
          {
            title: this.$t('import_file'),
            icon: 'fas fa-upload',
            desc: this.$t('import_file_desc'),
            method: this.importFile
          },
          {
            title: this.$t('auto_create'),
            icon: 'fas fa-terminal',
            desc: this.$t('auto_create_desc'),
            method: this.autoCreate
          }
        ]
      }
    ]
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.create-item-btn-div {
  margin-left: 250px;
  width: 200px;
  height: 60px;
  bottom: 20px;
  position: fixed;
  box-shadow: 0 0 8px #0000001a;
  border-radius: 10px;
  background: #ffffff;
  font-weight: 600;
}

.create-item-btn-div .left,
.create-item-btn-div .right {
  height: 60px;
  display: inline-block;
}

.create-item-left-btn {
  width: 135px;
  height: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  cursor: pointer;
}

/* >>> 符号表示对子组件生效 */
.create-item-btn-div >>> .create-item-right-btn {
  width: 60px;
  height: 60px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.el-dropdown-link,
a {
  color: #343a40;
}
</style>
