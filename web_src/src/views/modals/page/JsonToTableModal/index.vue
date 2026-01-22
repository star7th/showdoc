<template>
  <CommonModal
    :show="show"
    :title="$t('page.json_to_table')"
    width="700px"
    @close="handleClose"
  >
    <div class="json-to-table">
      <div class="description">
        {{ $t('page.json_to_table_description') }}
      </div>
      <CommonTextarea
        v-model="jsonContent"
        :placeholder="$t('page.json_content_placeholder')"
        :rows="10"
      />
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton type="primary" @click="handleConvert">{{ $t('common.confirm') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'

interface Props {
  onClose: () => void
  onInsert: (table: string) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const jsonContent = ref('')

// Methods
const handleClose = () => {
  props.onClose()
}

const handleConvert = () => {
  if (!jsonContent.value.trim()) {
    AlertModal(t('common.required'))
    return
  }

  try {
    const jsonData = JSON.parse(jsonContent.value)
    const table = jsonToMarkdownTable(jsonData)
    props.onInsert(table)
    props.onClose()
  } catch (error) {
    console.error('JSON解析失败:', error)
    AlertModal(t('page.invalid_json'))
  }
}

/**
 * 将嵌套 JSON 转化为参数表格（API 参数说明格式）
 * 表格格式：| 参数 | 类型 | 描述 |
 * 支持嵌套结构，使用点号连接（如 data.user.name）
 */
const jsonToMarkdownTable = (data: any): string => {
  let params: Array<{
    name: string
    type: string
    require: string
    remark: string
    value: any
  }> = []
  const uniqueKeys: string[] = []

  /**
   * 递归获取所有字段
   * @param data - 当前层级的对象
   * @param parentKey - 父级键名
   * @param level - 当前层级
   */
  const getKeys = (data: any, parentKey: string = '', level: number = 1): void => {
    if (!data || typeof data !== 'object') {
      return
    }

    const keys = Object.keys(data)
    keys.forEach(key => {
      let name = key

      // 如果是第3层及以上，或者第2层且 showLevel1 为 true，使用点号连接
      if (level > 2 || (level > 1 && parentKey && !isNaN(Number(parentKey)))) {
        if (isNaN(Number(key))) {
          name = parentKey + '.' + key
        } else {
          name = parentKey
        }
      } else if (level > 1 && parentKey && isNaN(Number(parentKey))) {
        // 第2层的非数组元素，也使用点号连接
        if (isNaN(Number(key))) {
          name = parentKey + '.' + key
        }
      }

      // 去重判断
      if (uniqueKeys.indexOf(name) > -1) {
        return
      }

      if (typeof data[key] === 'object' && data[key] !== null) {
        // 对象或数组类型
        if (isNaN(Number(key))) {
          params.push({
            name: name,
            type: Array.isArray(data[key]) ? 'array' : 'object',
            require: '1',
            remark: '',
            value: ''
          })
        }
        // 递归处理嵌套结构
        if (data[key]) {
          getKeys(data[key], name, level + 1)
        }
      } else {
        // 基础类型
        const typeValue = typeof data[key]
        params.push({
          name: name,
          type: typeValue === 'boolean' ? 'boolean' : (typeValue === 'number' ? 'number' : 'string'),
          require: '1',
          remark: '',
          value: data[key]
        })
      }

      uniqueKeys.push(name)
    })
  }

  try {
    getKeys(data)

    if (params && params.length > 0) {
      // 去重
      const unique = (arr: any[]): any[] => {
        const seen = new Map()
        return arr.filter(item => {
          const key = item.name
          if (seen.has(key)) {
            return false
          }
          seen.set(key, true)
          return true
        })
      }

      params = unique(params)

      // 构建表格
      let table = '| 参数 | 类型 | 描述 |\n'
      table += '| :------- | :------- | :------- |\n'

      params.forEach(param => {
        table += `| ${param.name} | ${param.type} | |\n`
      })

      return table
    } else {
      // 空对象或无法解析
      return '| 参数 | 类型 | 描述 |\n| :------- | :------- | :------- |\n'
    }
  } catch (e) {
    console.error('JSON 解析错误:', e)
    return '| 参数 | 类型 | 描述 |\n| :------- | :------- | :------- |\n'
  }
}

// Lifecycle
onMounted(() => {
  show.value = true
})
</script>

<style scoped lang="scss">
.json-to-table {
  padding: 10px 0;

  .description {
    margin-bottom: 12px;
    padding: 12px;
    background-color: var(--color-bg-secondary);
    border-left: 3px solid var(--color-primary);
    border-radius: 4px;
    color: var(--color-text-primary);
    font-size: 13px;
    line-height: 1.6;
  }
}
</style>
