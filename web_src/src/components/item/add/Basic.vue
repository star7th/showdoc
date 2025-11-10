<template>
  <div class="hello">
    <SDialog
      :onCancel="callback"
      :title="item_id ? $t('update_base_info') : $t('create_new_item')"
      width="550px"
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
          <el-col :span="6">&nbsp;&nbsp;{{ $t('item_name') }} : </el-col>
          <el-col :span="18">
            <el-input
              type="text"
              auto-complete="off"
              v-model="infoForm.item_name"
            ></el-input
          ></el-col>
        </el-row>
        <el-row
          class="leading-10 mb-4"
          v-if="itemGroupList && itemGroupList.length > 0"
        >
          <el-col :span="6">{{ $t('group') }} : </el-col>
          <el-col :span="18">
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
          </el-col>
        </el-row>
        <el-row class="leading-10 mb-4">
          <el-col :span="6">{{ $t('item_description') }} : </el-col>
          <el-col :span="18">
            <el-input
              type="text"
              auto-complete="off"
              v-model="infoForm.item_description"
            ></el-input
          ></el-col>
        </el-row>
        <el-row class="leading-10 mb-4">
          <el-col :span="6">{{ $t('accessibility') }} : </el-col>
          <el-col :span="18">
            <el-select class="w-full" v-model="isOpenItem" placeholder="">
              <el-option :value="true" :label="$t('Open_item')"> </el-option>
              <el-option :value="false" :label="$t('private_item')">
              </el-option>
            </el-select>
          </el-col>
        </el-row>
        <el-row class="leading-10 mb-4" v-show="!isOpenItem">
          <el-col :span="6">&nbsp;</el-col>
          <el-col :span="18">
            <el-input
              type="password"
              auto-complete="off"
              v-model="infoForm.password"
              :placeholder="$t('visit_password')"
            ></el-input
          ></el-col>
        </el-row>

        <!-- 互动功能（仅常规项目） -->
        <template v-if="infoForm.item_type == 1 || infoForm.item_type === 1 || infoForm.item_type === '1'">
          <el-row class="leading-10 mb-4">
            <el-col :span="6"
              >{{ $t('itemSetting.interactionTitle') }} :
            </el-col>
            <el-col :span="18">
              <el-checkbox v-model="infoForm.allow_comment">
                {{ $t('itemSetting.allowComment') }}
              </el-checkbox>
              <div class="form-item-desc">
                {{ $t('itemSetting.allowCommentDesc') }}
              </div>
              <el-checkbox
                v-model="infoForm.allow_feedback"
                style="margin-top: 10px;"
              >
                {{ $t('itemSetting.allowFeedback') }}
              </el-checkbox>
              <div class="form-item-desc">
                {{ $t('itemSetting.allowFeedbackDesc') }}
              </div>
            </el-col>
          </el-row>
        </template>
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
    },
    itemGroupId: 0
  },
  data() {
    return {
      infoForm: {
        item_name: '',
        item_description: '',
        item_domain: '',
        password: '',
        item_type: '1',
        allow_comment: false,
        allow_feedback: false
      },
      isOpenItem: true,
      itemGroupList: [],
      itemGroupIdsLocal: []
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
          password: this.infoForm.password,
          item_group_ids: (this.itemGroupIdsLocal || [])
            .map(v => Number(v))
            .filter(v => !isNaN(v)),
          // 提交时将布尔值转换为0/1
          allow_comment: this.infoForm.allow_comment ? 1 : 0,
          allow_feedback: this.infoForm.allow_feedback ? 1 : 0
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
            password: this.infoForm.password,
            item_group_ids: (this.itemGroupIdsLocal || [])
              .map(v => Number(v))
              .filter(v => !isNaN(v)),
            // 提交时将布尔值转换为0/1
            allow_comment: this.infoForm.allow_comment ? 1 : 0,
            allow_feedback: this.infoForm.allow_feedback ? 1 : 0
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
        // 确保allow_comment和allow_feedback有默认值，并转换为布尔值（checkbox需要布尔值）
        this.infoForm.allow_comment = (json.allow_comment === 1 || json.allow_comment === true || json.allow_comment === '1')
        this.infoForm.allow_feedback = (json.allow_feedback === 1 || json.allow_feedback === true || json.allow_feedback === '1')
        // 多分组：后端返回 group_ids
        this.itemGroupIdsLocal = Array.isArray(json.group_ids)
          ? json.group_ids.map(v => Number(v)).filter(v => !isNaN(v))
          : []
        // 兜底：父组件传入的单个分组
        if (
          (!this.itemGroupIdsLocal || this.itemGroupIdsLocal.length === 0) &&
          this.itemGroupId > 0
        ) {
          this.itemGroupIdsLocal = [Number(this.itemGroupId)]
        }
      })
    }
  },

  mounted() {
    this.infoForm.item_type = this.defaultItemType
    // 初始化分组选择：父组件传入的单个分组
    if (this.itemGroupId > 0) {
      this.itemGroupIdsLocal = [Number(this.itemGroupId)]
    }
    // 读取分组列表
    this.request('/api/itemGroup/getList', {}).then(data => {
      this.itemGroupList = data.data || []
    })
    if (this.item_id) {
      this.getItemDetail(this.item_id)
    }
  },
  onGroupChange(val) {
    const arr = (val || []).map(v => Number(v)).filter(v => !isNaN(v))
    if (arr.includes(0)) {
      this.itemGroupIdsLocal = [0]
    } else {
      this.itemGroupIdsLocal = arr
    }
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.form-item-desc {
  font-size: 12px;
  color: #999;
  margin-top: 5px;
}
</style>
