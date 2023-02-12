<template>
  <div class="create-item-btn-div">
    <el-dropdown trigger="hover" :show-timeout="0">
      <div class="el-dropdown-link">
        <div class="create-item-btn">
          <i class="el-icon-plus ml-6 mr-2"></i>
          <span>{{ $t('create_new_item') }}</span>
          <span class="line"></span>
          <i class=" ml-10 el-icon-more"></i>
        </div>
      </div>
      <el-dropdown-menu slot="dropdown">
        <el-dropdown-item @click.native="regularItem">
          <div class="mt-2 mb-2">
            <div class="inline-block align-middle">
              <i class="v3-font-size-lg el-icon-copy-document"></i>
            </div>
            <div class="inline-block align-middle">
              <div class="font-bold leading-6">{{ $t('regular_item') }}</div>
              <div class="v3-font-size-sm v3-color-aux leading-6">
                {{ $t('regular_item_desc') }}
              </div>
            </div>
          </div>
        </el-dropdown-item>
        <el-dropdown-item @click.native="singleItem">
          <div class="mb-2">
            <div class="inline-block align-middle">
              <i class="v3-font-size-lg el-icon-document"></i>
            </div>
            <div class="inline-block align-middle">
              <div class="font-bold leading-6">
                {{ $t('single_item') }}
              </div>
              <div class="v3-font-size-sm v3-color-aux leading-6">
                {{ $t('single_item_desc') }}
              </div>
            </div>
          </div>
        </el-dropdown-item>
        <el-dropdown-item @click.native="tableItem">
          <div class="mb-2">
            <div class="inline-block align-middle">
              <i class="v3-font-size-lg el-icon-bank-card"></i>
            </div>
            <div class="inline-block align-middle">
              <div class="font-bold leading-6">
                {{ $t('table_item') }}
              </div>
              <div class="v3-font-size-sm v3-color-aux leading-6">
                {{ $t('table_item_desc') }}
              </div>
            </div>
          </div>
        </el-dropdown-item>
        <el-dropdown-item @click.native="importFile">
          <div class="mb-2">
            <div class="inline-block align-middle">
              <i class="v3-font-size-lg el-icon-upload2"></i>
            </div>
            <div class="inline-block align-middle">
              <div class="font-bold leading-6">{{ $t('import_file') }}</div>
              <div class="v3-font-size-sm v3-color-aux leading-6">
                {{ $t('import_file_desc') }}
              </div>
            </div>
          </div>
        </el-dropdown-item>
        <el-dropdown-item @click.native="copyItem">
          <div class="mb-2">
            <div class="inline-block align-middle">
              <i class="v3-font-size-lg el-icon-document-copy"></i>
            </div>
            <div class="inline-block align-middle">
              <div class="font-bold leading-6">{{ $t('copy_item') }}</div>
              <div class="v3-font-size-sm v3-color-aux leading-6">
                {{ $t('copy_item_tips1') }}
              </div>
            </div>
          </div>
        </el-dropdown-item>
        <el-dropdown-item @click.native="autoCreate">
          <div class="mb-2">
            <div class="inline-block align-middle">
              <i class="v3-font-size-lg el-icon-magic-stick"></i>
            </div>
            <div class="inline-block align-middle">
              <div class="font-bold leading-6">{{ $t('auto_create') }}</div>
              <div class="v3-font-size-sm v3-color-aux leading-6">
                {{ $t('auto_create_desc') }}
              </div>
            </div>
          </div>
        </el-dropdown-item>
      </el-dropdown-menu>
    </el-dropdown>

    <Basic
      v-if="showBasic"
      :callback="
        () => {
          showBasic = false
          callback()
        }
      "
      :defaultItemType="defaultItemType"
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
    <Copy
      v-if="showCopy"
      :callback="
        () => {
          showCopy = false
          callback()
        }
      "
    >
    </Copy>
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
import Copy from '@/components/item/add/Copy'
import OpenApi from '@/components/item/add/OpenApi'
import Import from '@/components/item/add/Import'

export default {
  name: 'Login',
  components: {
    Basic,
    Copy,
    OpenApi,
    Import
  },
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    }
  },
  data() {
    return {
      showBasic: false,
      showImportFile: false,
      defaultItemType: '1',
      showCopy: false,
      showOpenApi: false
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
    copyItem() {
      this.showCopy = true
    },
    autoCreate() {
      this.showOpenApi = true
    }
  },

  mounted() {},
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
}

.create-item-btn-div .line {
  height: 60px;
  border-left: 1px solid rgba(0, 0, 0, 0.05);
  /* border-left: 5px solid; */
  position: relative;
  left: 15px;
}

.create-item-btn {
  width: 200px;
  background: #ffffff;
  border-radius: 10px;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  display: -webkit-flex;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  box-shadow: 0 0 8px #0000001a;
  font-weight: 600;
}

.el-dropdown-link,
a {
  color: #343a40;
}
</style>
