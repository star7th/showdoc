<template>
  <div class="create-item-modal">
    <CommonModal
      :class="{ show }"
      :title="isCreateMode ? $t('item.create_new_item') : $t('item.update_base_info')"
      :icon="isCreateMode ? ['fa', 'fa-plus'] : ['fas', 'edit']"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form layout="horizontal" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
          <!-- 项目类型（创建模式下可选，编辑模式下禁用） -->
          <a-form-item :label="$t('item.item_type')">
            <a-select
              v-model:value="form.item_type"
              :disabled="!isCreateMode"
            >
              <a-select-option value="1">{{ $t('item.regular_item') }}</a-select-option>
              <a-select-option value="2">{{ $t('item.single_item') }}</a-select-option>
              <a-select-option value="4">{{ $t('item.table_item') }}</a-select-option>
              <a-select-option value="5">{{ $t('item.whiteboard_item') }}</a-select-option>
            </a-select>
          </a-form-item>

          <!-- 项目名称 -->
          <a-form-item :label="$t('item.item_name')">
            <CommonInput
              v-model="form.item_name"
              :placeholder="$t('item.item_name')"
              autocomplete="off"
            />
          </a-form-item>

          <!-- 分组选择 -->
          <a-form-item v-if="itemGroupList && itemGroupList.length > 0" :label="$t('item.group')">
            <a-select
              v-model:value="itemGroupIdsLocal"
              mode="multiple"
              :placeholder="$t('item.item_group_desc')"
              @change="onGroupChange"
            >
              <a-select-option :value="0">{{ $t('item.all_items') }}</a-select-option>
              <a-select-option
                v-for="g in itemGroupList"
                :key="g.id"
                :value="Number(g.id)"
              >
                {{ g.group_name }}
              </a-select-option>
            </a-select>
          </a-form-item>

          <!-- 项目描述 -->
          <a-form-item :label="$t('item.item_description')">
            <CommonInput
              v-model="form.item_description"
              :placeholder="$t('item.item_description')"
              autocomplete="off"
            />
          </a-form-item>

          <!-- 访问权限 -->
          <a-form-item :label="$t('item.accessibility')">
            <a-select v-model:value="isOpenItem" @change="handleAccessTypeChange">
              <a-select-option :value="true">{{ $t('item.public_item') }}</a-select-option>
              <a-select-option :value="false">{{ $t('item.private_item') }}</a-select-option>
            </a-select>
          </a-form-item>

          <!-- 访问密码(私密项目时显示) -->
          <a-form-item v-if="!isOpenItem" :label="$t('item.visit_password')">
            <CommonInput
              v-model="form.password"
              type="password"
              :placeholder="$t('item.visit_password')"
            />
          </a-form-item>

          <!-- 互动功能（仅常规项目） -->
          <template v-if="form.item_type == '1' || Number(form.item_type) === 1">
            <a-form-item :label="$t('itemSetting.interactionTitle')">
              <div class="switch-item">
                <CommonSwitch v-model="form.allow_comment" :label="$t('itemSetting.allowComment')" />
                <div class="form-item-desc">
                  {{ $t('itemSetting.allowCommentDesc') }}
                </div>
              </div>
              <div class="switch-item">
                <CommonSwitch v-model="form.allow_feedback" :label="$t('itemSetting.allowFeedback')" />
                <div class="form-item-desc">
                  {{ $t('itemSetting.allowFeedbackDesc') }}
                </div>
              </div>
            </a-form-item>
          </template>
        </a-form>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click.stop="handleClose(false)">{{ $t('common.cancel') }}</div>
        <div
          class="primary-button"
          :class="{ disabled: loading }"
          @click.stop="handleSubmit"
        >
          {{ loading ? $t('common.submiting') : $t('common.confirm') }}
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonSwitch from '@/components/CommonSwitch.vue'
import { addItem, getItemDetail, updateItem } from '@/models/item'
import { getGroupList } from '@/models/itemGroup'
import AlertModal from '@/components/AlertModal'
import Message from '@/components/Message'

const { t } = useI18n()

interface Props {
  item_id?: string | number
  item_type?: string
  item_group_id?: number
  onClose: (result: boolean) => void
}

const props = defineProps<Props>()

// 判断是否为创建模式
const isCreateMode = computed(() => !props.item_id)

// 数据状态
const show = ref(false)
const loading = ref(false)
const isOpenItem = ref(true) // 默认为公开项目
const itemGroupIdsLocal = ref<number[]>([])
const itemGroupList = ref<any[]>([])

const form = ref({
  item_name: '',
  item_description: '',
  password: '',
  item_type: '1',
  allow_comment: false,
  allow_feedback: false
})

// 分组变化处理
const onGroupChange = (val: number[]) => {
  const arr = (val || []).map((v: number) => Number(v)).filter((v: number) => !isNaN(v))
  if (arr.includes(0)) {
    itemGroupIdsLocal.value = [0]
  } else {
    itemGroupIdsLocal.value = arr
  }
}

// 访问权限变化处理
const handleAccessTypeChange = () => {
  if (isOpenItem.value) {
    form.value.password = ''
  }
}

// 获取项目详情
const loadItemDetail = async () => {
  try {
    const data = await getItemDetail(String(props.item_id))
    if (data && data.data) {
      const json = data.data
      if (json.password) {
        isOpenItem.value = false
      } else {
        isOpenItem.value = true
      }
      form.value.item_name = json.item_name || ''
      form.value.item_description = json.item_description || ''
      form.value.password = json.password || ''
      form.value.item_type = json.item_type || '1'
      // 转换为布尔值（checkbox需要布尔值）
      form.value.allow_comment =
        json.allow_comment === 1 ||
        json.allow_comment === true ||
        json.allow_comment === '1'
      form.value.allow_feedback =
        json.allow_feedback === 1 ||
        json.allow_feedback === true ||
        json.allow_feedback === '1'
      // 多分组：使用后端返回的 group_ids
      itemGroupIdsLocal.value = Array.isArray(json.group_ids)
        ? json.group_ids.map((v: number) => Number(v)).filter((v: number) => !isNaN(v))
        : []
    }
  } catch (error) {
    console.error('Load item detail failed:', error)
  }
}

// 初始化创建模式表单
const initCreateForm = () => {
  // 设置项目类型
  if (props.item_type) {
    form.value.item_type = String(props.item_type)
  }
  // 如果有分组ID，设置为默认分组
  if (props.item_group_id && props.item_group_id > 0) {
    itemGroupIdsLocal.value = [props.item_group_id]
  }
  // 创建模式默认为公开项目
  isOpenItem.value = true
}

// 获取分组列表
const loadGroupList = async () => {
  try {
    const data = await getGroupList()
    if (data && data.data) {
      itemGroupList.value = data.data || []
    }
  } catch (error) {
    console.error('Load group list failed:', error)
  }
}

// 提交表单
const handleSubmit = async () => {
  // 表单验证
  if (!form.value.item_name || form.value.item_name.trim() === '') {
    await AlertModal(t('item.item_name') + ' ' + t('common.required'))
    return
  }

  // 私密项目必须设置密码
  if (!isOpenItem.value && !form.value.password) {
    await AlertModal(t('item.private_item_password'))
    return
  }

  // 准备提交数据
  const submitData: any = {
    item_name: form.value.item_name,
    item_description: form.value.item_description,
    password: isOpenItem.value ? '' : form.value.password,
    item_group_ids: (itemGroupIdsLocal.value || [])
      .map((v: number) => Number(v))
      .filter((v: number) => !isNaN(v))
  }

  // 仅常规项目支持评论和反馈功能
  if (form.value.item_type == '1' || Number(form.value.item_type) === 1) {
    submitData.allow_comment = form.value.allow_comment ? 1 : 0
    submitData.allow_feedback = form.value.allow_feedback ? 1 : 0
  }

  try {
    loading.value = true

    if (isCreateMode.value) {
      // 创建项目
      submitData.item_type = Number(form.value.item_type)
      const res = await addItem(submitData, false) // 跳过 request 的自动弹窗
      if (res.error_code === 0) {
        Message.success(t('common.op_success'))
        handleClose(true)
      } else if (res.error_code === 10309) {
        // 项目数超限
        const msg = t('item.item_limit_exceeded_with_link')
        await AlertModal(msg, {
          dangerouslyUseHTMLString: true
        })
      } else if (res.error_code === 10313) {
        // 实名认证要求（创建公开项目需要先完成支付实名认证）
        const msg = t('item.realname_auth_required_for_public_item')
        await AlertModal(msg, {
          dangerouslyUseHTMLString: true
        })
      } else {
        // 其他错误情况
        await AlertModal(res.error_message || t('common.op_failed'))
      }
    } else {
      // 更新项目
      const res = await updateItem(String(props.item_id), submitData, false) // 跳过 request 的自动弹窗
      if (res.error_code === 0) {
        Message.success(t('common.modify_success'))
        handleClose(true)
      } else {
        await AlertModal(res.error_message || t('common.op_failed'))
      }
    }
  } catch (error) {
    console.error('Submit item failed:', error)
    await AlertModal(t('common.op_failed'))
  } finally {
    loading.value = false
  }
}

// 关闭弹窗
const handleClose = (result: boolean = false) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
  }, 300)
}

onMounted(async () => {
  // 延迟显示弹窗，让动画更流畅
  setTimeout(() => {
    show.value = true
  })

  await loadGroupList()

  if (isCreateMode.value) {
    // 创建模式：初始化表单
    initCreateForm()
  } else {
    // 编辑模式：加载项目详情
    await loadItemDetail()
  }
})
</script>

<style scoped lang="scss">
.modal-content {
  padding: 20px 30px;
}

// 覆盖 Ant Design Form 的默认样式，使用水平布局
:deep(.ant-form-item) {
  margin-bottom: 12px;

  .ant-form-item-label {
    font-size: 13px;
    line-height: 40px;
  }

  .ant-form-item-control {
    line-height: 40px;
  }
}

:deep(.ant-select-selector) {
  height: 40px;
  font-size: 13px;
}

.form-item-desc {
  font-size: 11px;
  color: var(--color-text-secondary);
  margin-top: 4px;
  line-height: 1.4;
}

.switch-item {
  margin-bottom: 12px;

  &:last-child {
    margin-bottom: 0;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 80px;
  border-top: 1px solid var(--color-interval);

  .secondary-button,
  .primary-button {
    width: 160px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: var(--font-size-m);
    font-weight: bold;
    cursor: pointer;
    margin: 0 7.5px;
    transition: all 0.15s ease;

    &.disabled {
      opacity: 0.3;
      pointer-events: none;
    }
  }

  .secondary-button {
    background-color: var(--color-obvious);
    color: var(--color-primary);
    white-space: nowrap;

    &:hover {
      background-color: var(--color-secondary);
    }
  }

  .primary-button {
    background-color: var(--color-primary);
    color: var(--color-obvious);
    white-space: nowrap;
  }
}

// 暗黑主题适配
[data-theme='dark'] .form-item-desc {
  color: var(--color-text-secondary);
}

[data-theme='dark'] .modal-footer {
  border-top-color: var(--color-interval);
}
</style>
