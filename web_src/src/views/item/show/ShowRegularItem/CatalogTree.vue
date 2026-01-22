<template>
  <div class="catalog-tree">
    <!-- 搜索框 -->
    <a-input
      v-model:value="keyword"
      :placeholder="$t('common.input_keyword')"
      allow-clear
      size="large"
      @pressEnter="handleSearch"
      @clear="handleClearSearch"
      class="search-input"
    >
      <template #prefix>
        <i class="far fa-search"></i>
      </template>
    </a-input>

    <!-- 目录树 -->
    <a-tree
      v-if="treeData.length > 0"
      ref="treeRef"
      :selectedKeys="selectedKeys"
      :expandedKeys="expandedKeys"
      :tree-data="treeData"
      :draggable="isItemEditable"
      :selectable="false"
      :block-node="true"
      @expand="handleExpand"
      @right-click="handleRightClick"
      @drop="handleDrop"
      class="tree-container"
    >
      <!-- 展开/收起图标 -->
      <template #switcherIcon="{ switcherCls }">
        <div :class="switcherCls">
          <i class="far fa-angle-down"></i>
        </div>
      </template>

      <!-- 节点内容 -->
      <template #title="{ key, title, type, children }">
        <div
          :class="[
            'tree-node-content',
            { 'node-selected': selectedKeys.includes(key) },
          ]"
          :data-selected="selectedKeys.includes(key)"
          :id="`node-${key}`"
          @click="handleNodeClick($event, key, type, children)"
        >
          <!-- 节点图标 -->
          <span class="node-icon">
            <template v-if="type === 'folder'">
              <i
                v-if="expandedKeys.includes(key) && children?.length"
                class="far fa-folder-open"
              />
              <i v-else class="far fa-folder-closed" />
            </template>
            <i v-else class="far fa-file-lines" />
          </span>
          <!-- 节点标题 -->
          <span :class="['node-title', `node-${type}`]" :title="title">{{
            title
          }}</span>
          <!-- 更多操作按钮（hover时显示，只读模式隐藏） -->
          <div
            v-if="isItemEditable"
            class="node-more"
            @click.stop="handleShowContextMenu($event, key, type)"
          >
            <i class="far fa-ellipsis"></i>
          </div>
        </div>
      </template>
    </a-tree>

    <!-- 右键菜单 -->
    <ContextmenuModal
      v-if="contextMenu.show"
      :x="contextMenu.x"
      :y="contextMenu.y"
      :list="contextMenuList"
      @close="closeContextMenu"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, nextTick, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import request from '@/utils/request'
import ContextmenuModal from '@/components/ContextmenuModal/index'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import PromptModal from '@/components/PromptModal/index'
import ConfirmModal from '@/components/ConfirmModal/index'
import {
  itemMenuDataToTreeData,
  getParentIds,
  getAllCatKeys,
} from '@/models/itemTree'
import CopyCatalogModal from '@/views/modals/catalog/CopyCatalogModal'
import EditPageModal from '@/views/modals/page/EditPageModal/index'
import HistoryModal from '@/views/modals/page/HistoryModal/index'

// Props
interface Props {
  itemInfo?: any
  keyword?: string
  getPageContent?: (pageId: number) => void
  searchItem?: (keyword: string) => void
}

const props = withDefaults(defineProps<Props>(), {
  itemInfo: () => ({}),
  keyword: '',
  getPageContent: () => {},
  searchItem: () => {},
})

// Emits
const emit = defineEmits<{
  reloadItem: []
}>()

// Composables
const router = useRouter()
const route = useRoute()
const { t } = useI18n()

// Refs
const keyword = ref('')
const selectedKeys = ref<string[]>([])
const expandedKeys = ref<string[]>([])
const treeData = ref<any[]>([])
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  node: null as any,
})

// 当前正在操作的节点（用于更多按钮）
const currentNode = ref<any>(null)

// Computed
const isItemEditable = computed(() => {
  // 使用弱等于判断，因为后端可能返回字符串
  return props.itemInfo?.item_edit == 1
})

const contextMenuList = computed(() => {
  const node = contextMenu.value.node
  if (!node) return []

  if (node.type === 'folder') {
    return [
      {
        icon: ['far', 'fa-plus'],
        text: t('page.new_page'),
        onclick: () => handleAddSubPage(node),
      },
      {
        icon: ['far', 'fa-folder-tree'],
        text: t('catalog.add_sub_cat'),
        onclick: () => handleAddSubCatalog(node),
      },
      {
        icon: ['far', 'fa-folder-plus'],
        text: t('catalog.add_sibling_cat'),
        onclick: () => handleAddSiblingCatalog(node),
      },
      {
        icon: ['far', 'fa-edit'],
        text: t('catalog.edit_cat'),
        onclick: () => handleEditCatalog(node),
      },
      {
        icon: ['far', 'fa-clone'],
        text: t('catalog.clone_move'),
        onclick: () => handleCloneCatalog(node),
      },
      {
        icon: ['far', 'fa-trash-can'],
        text: t('common.delete'),
        onclick: () => handleDeleteCatalog(node),
      },
    ]
  } else {
    return [
      {
        icon: ['far', 'fa-edit'],
        text: t('page.edit_page'),
        onclick: () => handleEditPage(node),
      },
      {
        icon: ['far', 'fa-copy'],
        text: t('page.copy_page'),
        onclick: () => handleCopyPage(node),
      },
      {
        icon: ['far', 'fa-circle-info'],
        text: t('page.page_info'),
        onclick: () => handlePageInfo(node),
      },
      {
        icon: ['far', 'fa-clock-rotate-left'],
        text: t('page.history_version'),
        onclick: () => handlePageHistory(node),
      },
      {
        icon: ['far', 'fa-trash-can'],
        text: t('page.delete_page'),
        onclick: () => handleDeletePage(node),
      },
    ]
  }
})

// Methods
const initTreeData = () => {
  if (!props.itemInfo?.menu) return

  treeData.value = itemMenuDataToTreeData(props.itemInfo.menu)

  // 默认展开第一个页面
  const defaultPageId = props.itemInfo.default_page_id || 0
  if (defaultPageId) {
    const parentIds = getParentIds(treeData.value, defaultPageId)
    if (parentIds) {
      expandedKeys.value = parentIds
      selectedKeys.value = [`page_${defaultPageId}`]

      // 延迟滚动到选中节点
      nextTick(() => {
        setTimeout(() => {
          const nodeElement = document.querySelector(
            `[data-node-key="page_${defaultPageId}"]`
          )
          if (nodeElement) {
            nodeElement.scrollIntoView({ block: 'center', behavior: 'smooth' })
          }
          props.getPageContent(defaultPageId)
        }, 500)
      })
    }
  }
}

// 处理展开/收起
const handleExpand = (keys: string[]) => {
  expandedKeys.value = keys
}

// 处理节点点击（目录展开/折叠，页面加载内容）
const handleNodeClick = (
  _event: MouseEvent,
  key: string,
  type: string,
  _children: any[]
) => {
  // 保存当前节点信息供更多按钮使用
  const treeDataList = flattenTreeData(treeData.value)
  currentNode.value = treeDataList.find((n) => n.key === key)

  // 如果是目录节点，展开/折叠
  if (type === 'folder') {
    const currentKeys = [...expandedKeys.value]
    const index = currentKeys.indexOf(key)

    if (index > -1) {
      currentKeys.splice(index, 1)
    } else {
      currentKeys.push(key)
    }

    expandedKeys.value = currentKeys
  } else {
    // 点击页面，加载内容
    const node = currentNode.value
    if (node && node.page_id) {
      selectedKeys.value = [key]
      props.getPageContent(node.page_id)

      // 更新URL (使用Hash模式)
      const domain = props.itemInfo.item_domain || props.itemInfo.item_id
      const newPath = `/${domain}/${node.page_id}`
      if (route.path !== newPath) {
        router.replace(newPath)
      }
    }
  }
}

// 显示右键菜单（从更多按钮或右键）
const handleShowContextMenu = (
  event?: MouseEvent,
  key?: string,
  type?: string
) => {
  if (!isItemEditable.value) return

  let x, y
  let targetNode: any = null

  if (event && event.type === 'click') {
    // 更多按钮点击
    const treeDataList = flattenTreeData(treeData.value)
    if (key) {
      targetNode = treeDataList.find((n) => n.key === key)
    } else {
      // 如果没有key，使用currentNode（兼容右键点击）
      targetNode = currentNode.value
    }

    if (!targetNode) return

    const target = event.currentTarget as HTMLElement
    const rect = target.getBoundingClientRect()
    x = rect.left
    y = rect.bottom + 5
  } else if (
    event &&
    (event.type === 'contextmenu' || event.type === 'mousedown')
  ) {
    // 右键点击，直接使用鼠标位置
    targetNode = currentNode.value
    x = event.clientX
    y = event.clientY
  } else {
    // 其他情况（默认位置）
    targetNode = currentNode.value
    x = contextMenu.value.x || 0
    y = contextMenu.value.y || 0
  }

  if (!targetNode) return

  contextMenu.value = {
    show: true,
    x,
    y,
    node: targetNode,
  }
}

// 扁平化树数据以便查找节点
const flattenTreeData = (data: any[]): any[] => {
  const result: any[] = []
  const flatten = (items: any[]) => {
    items.forEach((item) => {
      result.push(item)
      if (item.children && item.children.length > 0) {
        flatten(item.children)
      }
    })
  }
  flatten(data)
  return result
}

const handleRightClick = ({ event, node }: any) => {
  if (!isItemEditable.value) return

  event.preventDefault()
  event.stopPropagation()

  currentNode.value = node

  // 直接调用显示菜单，传递事件对象
  handleShowContextMenu(event)
}

const closeContextMenu = () => {
  contextMenu.value.show = false
  contextMenu.value.node = null
}

const handleDrop = async (info: any) => {
  if (keyword.value) return // 搜索模式下不允许拖拽

  const { dragNode, node, dropPosition, dropToGap } = info

  // 不允许拖动到页面节点内部
  if (!dropToGap && node.type !== 'folder') {
    return
  }

  // 递归查找和操作节点
  const loop = (data: any[], key: string, callback: Function) => {
    for (let i = 0; i < data.length; i++) {
      if (data[i].key === key) {
        return callback(data[i], i, data)
      }
      if (data[i].children) {
        loop(data[i].children, key, callback)
      }
    }
  }

  // 复制 treeData 避免直接修改
  const data = [...treeData.value]

  // 找到并移除拖动节点
  let dragObj: any
  loop(data, dragNode.key, (item: any, index: number, arr: any[]) => {
    arr.splice(index, 1)
    dragObj = item
  })

  if (!dragObj) return

  // 插入到新位置
  if (!dropToGap) {
    // 拖动到节点内部
    loop(data, node.key, (item: any) => {
      item.children = item.children || []
      item.children.push(dragObj)
    })
  } else if (
    (node.children || []).length > 0 &&
    node.expanded &&
    dropPosition === 1
  ) {
    // 拖动到展开节点的底部，插入到其子节点数组头部
    loop(data, node.key, (item: any) => {
      item.children = item.children || []
      item.children.unshift(dragObj)
    })
  } else {
    // 拖动到节点的前面或后面
    let ar: any[] | undefined
    let i: number | undefined
    loop(data, node.key, (_item: any, index: number, arr: any[]) => {
      ar = arr
      i = index
    })

    if (i !== undefined && ar) {
      if (dropPosition === -1) {
        // 插入到前面
        ar.splice(i, 0, dragObj)
      } else {
        // 插入到后面
        ar.splice(i + 1, 0, dragObj)
      }
    }
  }

  // 更新树数据
  treeData.value = data

  // 保存到后端
  await saveDragData(data)
}

// 保存拖动数据到后端
const saveDragData = async (data: any[]) => {
  // 将树数据降维
  const treeData2: any[] = []
  const pushTreeData = (
    oneData: any,
    parentCatId: number,
    level: number,
    index: number
  ) => {
    // 对于目录节点，使用 cat_id；对于页面节点，使用 page_id
    const catId = oneData.type === 'folder' ? oneData.cat_id || 0 : 0

    treeData2.push({
      cat_id: catId,
      cat_name: oneData.title || '',
      page_id: oneData.page_id || 0,
      parent_cat_id: parentCatId || 0,
      page_cat_id: parentCatId || 0,
      level,
      s_number: index + 1,
    })

    if (oneData.children) {
      oneData.children.forEach((child: any, i: number) => {
        // 子节点的 parent_cat_id 应该是当前节点的 cat_id（对于目录）
        const childParentCatId = catId
        pushTreeData(child, childParentCatId, level + 1, i)
      })
    }
  }

  data.forEach((item: any, index: number) => {
    pushTreeData(item, 0, 2, index)
  })

  // 获取拖动元素信息（用于后端记录）
  // 注意：这里使用修改后的数据结构
  const result = await request(
    '/api/catalog/batUpdate',
    {
      item_id: props.itemInfo.item_id,
      cats: JSON.stringify(treeData2),
    },
    'post'
  )

  if (result.error_code !== 0) {
    await AlertModal(result.error_message || t('common.op_failed'))
  }
}

const handleSearch = () => {
  props.searchItem(keyword.value)
}

const handleClearSearch = () => {
  keyword.value = ''
  props.searchItem('')
}

const handleAddSubPage = async (_node: any) => {
  try {
    const result = await EditPageModal({
      itemId: props.itemInfo.item_id,
      editPageId: 0,
      copyPageId: 0,
      catId: _node.cat_id, // 传入目录ID，在当前目录下创建页面
    })

    if (result) {
      emit('reloadItem')
    }
  } catch (error) {
    console.error('添加子页面失败:', error)
  }
}

const handleAddSubCatalog = async (node: any) => {
  const catName = await PromptModal(
    t('catalog.add_sub_cat'),
    '',
    t('catalog.cat_name')
  )

  if (!catName || !catName.trim()) return

  await createCatalog(node.cat_id, catName.trim())
}

const handleAddSiblingCatalog = async (node: any) => {
  const catName = await PromptModal(
    t('catalog.add_sibling_cat'),
    '',
    t('catalog.cat_name')
  )

  if (!catName || !catName.trim()) return

  await createCatalog(node.parent_cat_id, catName.trim())
}

const createCatalog = async (parentCatId: number, catName: string) => {
  try {
    const result = await request(
      '/api/catalog/save',
      {
        item_id: props.itemInfo.item_id,
        cat_id: 0,
        parent_cat_id: parentCatId,
        cat_name: catName,
      },
      'post',
      false
    )

    if (result.error_code === 0) {
      Message.success(t('common.save_success'))
      emit('reloadItem')
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('创建目录失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

const handleEditCatalog = async (node: any) => {
  const catName = await PromptModal(
    t('catalog.edit_cat'),
    node.title,
    t('catalog.cat_name')
  )

  if (catName === null || catName === node.title || !catName.trim()) return

  await saveCatalog(node.cat_id, node.parent_cat_id, catName.trim())
}

const saveCatalog = async (
  catId: number,
  parentCatId: number,
  catName: string
) => {
  try {
    const result = await request(
      '/api/catalog/save',
      {
        item_id: props.itemInfo.item_id,
        cat_id: catId,
        parent_cat_id: parentCatId,
        cat_name: catName,
      },
      'post',
      false
    )

    if (result.error_code === 0) {
      Message.success(t('common.save_success'))
      emit('reloadItem')
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('编辑目录失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

const handleCloneCatalog = async (node: any) => {
  const result = await CopyCatalogModal({
    catId: node.cat_id,
    itemId: props.itemInfo.item_id,
  })

  if (result) {
    emit('reloadItem')
  }
}

const handleDeleteCatalog = async (node: any) => {
  const result = await ConfirmModal({
    msg: t('catalog.confirm_cat_delete'),
    title: t('common.tips'),
  })

  if (result) {
    await deleteCatalog(node.cat_id)
  }
}

const deleteCatalog = async (catId: number) => {
  try {
    const result = await request(
      '/api/catalog/delete',
      {
        item_id: props.itemInfo.item_id,
        cat_id: catId,
      },
      'post',
      false
    )

    if (result.error_code === 0) {
      Message.success(t('common.op_success'))
      emit('reloadItem')
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('删除目录失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

const handleEditPage = async (node: any) => {
  try {
    const result = await EditPageModal({
      itemId: props.itemInfo.item_id,
      editPageId: node.page_id,
      copyPageId: 0,
      catId: node.page_cat_id, // 传入页面所在的目录ID，编辑时选中该目录
    })

    if (result) {
      emit('reloadItem')
    }
  } catch (error) {
    console.error('编辑页面失败:', error)
  }
}

const handleCopyPage = async (node: any) => {
  try {
    const result = await EditPageModal({
      itemId: props.itemInfo.item_id,
      editPageId: 0,
      copyPageId: node.page_id, // 传入要复制的页面ID
    })

    if (result) {
      emit('reloadItem')
    }
  } catch (error) {
    console.error('复制页面失败:', error)
  }
}

const handlePageInfo = async (node: any) => {
  try {
    const result = await request(
      '/api/page/info',
      {
        page_id: node.page_id,
      },
      'post',
      false
    )

    if (result.error_code === 0 && result.data) {
      const html = `本页面由 ${result.data.author_username} 于 ${result.data.addtime} 更新`
      await AlertModal(html, { dangerouslyUseHTMLString: true })
    }
  } catch (error) {
    console.error('获取页面信息失败:', error)
  }
}

const handlePageHistory = async (node: any) => {
  try {
    await HistoryModal({ pageId: node.page_id })
    // 不需要刷新整个item，历史版本查看不影响目录
  } catch (error) {
    console.error('打开历史版本失败:', error)
  }
}

const handleDeletePage = async (node: any) => {
  const result = await ConfirmModal({
    msg: t('common.confirm_delete'),
    title: t('common.tips'),
  })

  if (result) {
    await deletePage(node.page_id)
  }
}

const deletePage = async (pageId: number) => {
  try {
    const result = await request(
      '/api/page/delete',
      {
        page_id: pageId,
      },
      'post',
      false
    )

    if (result.error_code === 0) {
      Message.success(t('common.op_success'))
      emit('reloadItem')
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('删除页面失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

// Public methods
const expandAll = () => {
  expandedKeys.value = getAllCatKeys(treeData.value)
}

const collapseAll = () => {
  expandedKeys.value = []
}

// 滚动到指定页面
const scrollToPage = (pageId: number) => {
  if (!pageId || !treeData.value.length) return

  const key = `page_${pageId}`

  // 获取父节点 ID 并展开
  const parentIds = getParentIds(treeData.value, pageId)
  if (parentIds) {
    expandedKeys.value = parentIds
  }

  // 选中节点
  selectedKeys.value = [key]

  // 延迟滚动到选中节点
  nextTick(() => {
    setTimeout(() => {
      const nodeElement = document.querySelector(`#node-${key}`)
      if (nodeElement) {
        nodeElement.scrollIntoView({ block: 'center', behavior: 'smooth' })
      }
    }, 500)
  })
}

// Expose
defineExpose({
  expandAll,
  collapseAll,
  scrollToPage,
})

// Watchers
watch(
  () => props.itemInfo,
  (newItemInfo) => {
    if (newItemInfo && newItemInfo.menu) {
      initTreeData()
    }
  },
  { immediate: true, deep: true }
)

watch(
  () => props.keyword,
  (newKeyword) => {
    if (newKeyword !== undefined) {
      keyword.value = newKeyword
    }
  }
)

// 防抖处理：停止输入 500ms 后自动触发搜索
let searchDebounceTimer: any = null
watch(keyword, (newKeyword) => {
  if (searchDebounceTimer) {
    clearTimeout(searchDebounceTimer)
  }
  searchDebounceTimer = setTimeout(() => {
    if (props.searchItem) {
      props.searchItem(newKeyword)
    }
  }, 500)
})

// 监听选中状态变化，更新 .ant-tree-node-content-wrapper 的选中样式
watch(selectedKeys, (newKeys) => {
  nextTick(() => {
    // 移除所有节点的选中状态类
    const allWrappers = document.querySelectorAll(
      '.ant-tree-node-content-wrapper'
    )
    allWrappers.forEach((wrapper) => {
      wrapper.classList.remove('node-content-wrapper-selected')
    })

    // 为选中的节点添加选中状态类
    if (newKeys.length > 0) {
      // 找到包含 data-selected="true" 的节点
      const treeNodes = document.querySelectorAll('.ant-tree-treenode')
      treeNodes.forEach((node) => {
        const content = node.querySelector('.tree-node-content')
        if (content && content.getAttribute('data-selected') === 'true') {
          const wrapper = node.querySelector('.ant-tree-node-content-wrapper')
          if (wrapper) {
            wrapper.classList.add('node-content-wrapper-selected')
          }
        }
      })
    }
  })
})

// Lifecycle
onMounted(() => {
  initTreeData()
})
</script>

<style scoped lang="scss">
.catalog-tree {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 12px;
  scroll-behavior: smooth;

  // 滚动条样式已移至全局样式（styles/index.scss）
  // 所有滚动条统一使用全局样式，无需重复定义
  // overflow 由父组件 Index.vue 通过 :deep() 控制
}

.search-input {
  margin-bottom: 10px;
  width: 100%;

  :deep(.ant-input-affix-wrapper) {
    border-radius: 6px;
    border: 1px solid var(--color-border);
    background: var(--color-bg-primary);
    transition: all 0.15s ease;

    &:hover {
      border-color: var(--color-active);
    }

    &:focus-within {
      border-color: var(--color-active);
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);

      [data-theme='dark'] & {
        box-shadow: 0 0 0 2px rgba(74, 158, 255, 0.15);
      }
    }
  }

  :deep(.ant-input) {
    height: 32px;
    font-size: 13px;
    padding: 0 12px;
    background: transparent;
    border: none;

    &::placeholder {
      color: var(--color-text-secondary);
    }
  }

  // 搜索图标
  :deep(.ant-input-prefix) {
    color: var(--color-text-secondary);
    margin-right: 6px;

    i {
      font-size: 13px;
    }
  }

  // 聚焦时图标变色
  &:focus-within {
    :deep(.ant-input-prefix) {
      color: var(--color-active);
    }
  }
}

.tree-container {
  flex: 1;
}

:deep(.ant-tree) {
  background: transparent;
  color: var(--color-text-primary);

  .ant-tree-treenode {
    padding: 1px 0;
    width: max-content;
    min-width: 100%;

    &:hover {
      .node-title {
        color: var(--color-active);
      }

      // 显示更多按钮
      .node-more {
        opacity: 1;
      }
    }
  }

  .ant-tree-node-content-wrapper {
    border-radius: 6px;
    transition: all 0.15s ease;
    flex: 1;
    min-width: 0;
    min-height: 32px;
    display: flex;
    align-items: center;
    position: relative;
    padding-right: 0;

    &:hover {
      background: var(--hover-overlay);
    }

    // 选中状态
    &.node-content-wrapper-selected {
      background: rgba(0, 123, 255, 0.08);

      [data-theme='dark'] & {
        background: rgba(74, 158, 255, 0.12);
      }

      // 左侧指示条
      &::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 20px;
        background: var(--color-active);
        border-radius: 2px;
      }
    }
  }

  // 隐藏默认图标
  .ant-tree-iconEle {
    display: none;
  }

  // 展开/收起图标
  .ant-tree-switcher {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 32px;
    color: var(--color-text-secondary);
    transition: all 0.15s ease;
    cursor: pointer;

    &:hover {
      color: var(--color-active);
    }

    i {
      font-size: 10px;
      transition: transform 0.15s ease;
    }

    &.ant-tree-switcher_open i {
      transform: rotate(0deg);
    }

    &.ant-tree-switcher_close i {
      transform: rotate(-90deg);
    }
  }

  // 叶子节点的占位
  .ant-tree-switcher-leaf-line,
  .ant-tree-switcher-noop {
    width: 20px;
    height: 32px;
  }
}

// 树节点内容
.tree-node-content {
  display: flex;
  align-items: center;
  width: 100%;
  min-width: 0;
  min-height: 32px;
  padding-right: 40px;
  padding-left: 4px;
  position: relative;

  // 选中状态下的样式
  &.node-selected {
    .node-title {
      color: var(--color-active);
      font-weight: 500;
    }
  }
}

.node-icon {
  flex-shrink: 0;
  width: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-secondary);
  margin-left: 4px;
  margin-right: 8px;

  i {
    font-size: 14px;
  }

  // 文件夹图标颜色 - 温暖的橙色系
  .fa-folder-open,
  .fa-folder-closed {
    color: var(--color-orange);
  }

  // 文件图标颜色 - 使用主色
  .fa-file-lines {
    color: var(--color-active);
  }
}

.node-title {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--color-text-primary);
  transition: color 0.15s ease;
  font-size: 13px;
  cursor: pointer;
  line-height: 1.5;
  padding-right: 8px;
}

.node-more {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  opacity: 0;
  transition: opacity 0.15s ease;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  font-size: 12px;
  color: var(--color-text-secondary);
  border-radius: 4px;
  background-color: var(--color-bg-secondary);
  z-index: 10;
  box-shadow: 0 0 0 1px var(--color-border);

  &:hover {
    background-color: var(--hover-overlay);
    color: var(--color-active);
  }
}
</style>
