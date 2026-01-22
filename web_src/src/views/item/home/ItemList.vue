<template>
  <div class="item-list">
    <a-spin :spinning="loading" style="width: 100%">
      <div class="item-list-container">
        <div
          v-for="item in itemList"
          :key="item.item_id"
          :data-key="item.item_id"
          @click="toOneItem(item)"
          class="item-list-one"
          :class="item.item_type == '1' || item.item_type == '3' ? 'shadow-hover' : ''"
        >
        <div
          class="item-list-one-block"
          :class="item.item_type == '1' || item.item_type == '3' ? '' : 'shadow-hover'"
        >
          <div class="left float-left">
            <i v-if="item.item_type == '2'" class="item-icon fas fa-file"></i>
            <i v-else-if="item.item_type == '4'" class="item-icon fas fa-table"></i>
            <i v-else-if="item.item_type == '3'" class="item-icon fas fa-terminal"></i>
            <i v-else class="item-icon fas fa-notes"></i>
            <i
              v-if="item.is_star > 0"
              class="star-flag fas fa-star v3-color-yellow"
            ></i>
            {{ item.item_name }}
          </div>
          <div class="right show-more float-right" @click.stop="() => {}">
            <CommonDropdownMenu
              :list="getMenuItems(item)"
              trigger="hover"
              placement="right"
              :offsetX="20"
              @select="(menuItem) => handleMenuSelect(item, menuItem.value)"
            >
              <span class="el-dropdown-link">
                <i class="item-icon-more fas fa-ellipsis"></i>
              </span>
            </CommonDropdownMenu>
          </div>
        </div>
        <div
          class="item-list-one-block-bg"
          :class="item.item_type == '1' || item.item_type == '3'
            ? ''
            : 'item-list-one-block-bg-none'"
        ></div>
        </div>
      </div>
    </a-spin>

    <!-- 项目成员&团队的弹窗由 MemberModal 动态创建 -->

    <!-- 归档项目由 ArchiveModal 动态创建 -->

    <!-- 删除项目由 DeleteModal 动态创建 -->
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Sortable from 'sortablejs'
import { starItem, unstarItem, exitItem, sortItem } from '@/models/item'
import ShareModal from '@/views/modals/item/ShareModal'
import CreateItemModal from '@/views/modals/item/CreateItemModal'
import MemberModal from '@/views/modals/item/MemberModal'
import RecycleModal from '@/views/modals/item/RecycleModal'
import ArchiveModal from '@/views/modals/item/ArchiveModal'
import AttornModal from '@/views/modals/item/AttornModal'
import CopyItemModal from '@/views/modals/item/CopyItemModal'
import DeleteModal from '@/views/modals/item/DeleteModal'
import AiKnowledgeBaseModal from '@/views/modals/item/AiKnowledgeBaseModal'
import OpenApiModal from '@/views/modals/item/OpenApiModal'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import CommonDropdownMenu from '@/components/CommonDropdownMenu.vue'
import type { DropdownMenuItem } from '@/components/CommonDropdownMenu.vue'

const { t } = useI18n()
const router = useRouter()

// Props
const props = defineProps<{
  itemList: any[]
  getItemList: () => Promise<void> | void
  itemGroupId: number
}>()

// 数据状态
const loading = ref(false)
const opItemRow = ref<any>({ item_domain: '', item_id: 0 })

// 生成菜单项
const getMenuItems = (item: any): DropdownMenuItem[] => {
  const menuItems: DropdownMenuItem[] = [
    {
      icon: ['fas', 'fa-right-to-bracket'],
      text: t('item.open_item'),
      value: 'open'
    },
    {
      icon: ['fas', 'fa-share-nodes'],
      text: t('item.share_project'),
      value: 'share'
    },
    item.is_star > 0
      ? {
          icon: ['fas', 'fa-star'],
          text: t('item.unstar_item'),
          value: 'unstar'
        }
      : {
          icon: ['far', 'fa-star'],
          text: t('item.star_item'),
          value: 'star'
        }
  ]

  // 非管理员的退出选项
  if (item.manage != 1) {
    menuItems.push({
      icon: ['fas', 'fa-trash'],
      text: t('item.item_exit'),
      value: 'exit'
    })
  }

  // 管理员专属选项
  if (item.manage == 1) {
    menuItems.push(
      {
        icon: ['fas', 'fa-edit'],
        text: t('item.update_base_info'),
        value: 'update'
      },
      {
        icon: ['far', 'fa-users'],
        text: t('item.member_manage'),
        value: 'member'
      },
      {
        icon: ['fas', 'fa-plug'],
        text: t('item.open_api'),
        value: 'open_api'
      }
    )

    // AI 知识库（仅常规项目）
    if (item.item_type == '1' || item.item_type == 1) {
      menuItems.push({
        icon: ['fas', 'fa-brain'],
        text: t('ai.ai_knowledge_base'),
        value: 'ai_knowledge_base'
      })
    }

    menuItems.push(
      {
        icon: ['fas', 'fa-trash'],
        text: t('item.recycle'),
        value: 'recycle'
      },
      {
        icon: ['fas', 'fa-recycle'],
        text: t('item.attorn'),
        value: 'attorn'
      },
      {
        icon: ['fas', 'fa-copy'],
        text: t('item.copy_item'),
        value: 'copy'
      },
      {
        icon: ['fas', 'fa-box-archive'],
        text: t('item.archive'),
        value: 'archive'
      },
      {
        icon: ['fas', 'fa-trash-can'],
        text: t('item.delete_item'),
        value: 'delete'
      }
    )
  }

  return menuItems
}

// 处理菜单选择
const handleMenuSelect = async (item: any, menuValue: string) => {
  switch (menuValue) {
    case 'open':
      toOneItem(item)
      break
    case 'share':
      handleShare(item)
      break
    case 'star':
    case 'unstar':
      handleStar(item)
      break
    case 'exit':
      handleExit(item.item_id)
      break
    case 'update':
      handleUpdate(item)
      break
    case 'member':
      handleMember(item)
      break
    case 'open_api':
      handleOpenApi(item)
      break
    case 'ai_knowledge_base':
      handleAiKnowledgeBase(item)
      break
    case 'recycle':
      handleRecycle(item)
      break
    case 'attorn':
      handleAttorn(item)
      break
    case 'copy':
      handleCopy(item)
      break
    case 'archive':
      handleArchive(item)
      break
    case 'delete':
      handleDelete(item)
      break
  }
}

// 跳转到项目
const toOneItem = (item: any) => {
  const to = '/' + (item.item_domain ? item.item_domain : item.item_id)
  router.push({ path: to })
}

// 星标或者取消星标
const handleStar = async (item: any) => {
  const is_star = item.is_star
  const item_id = item.item_id
  // 如果is_star > 0 ,即已经标星了，那么本次点击就是 取消星标 的意思
  if (is_star > 0) {
    await unstarItem(item_id)
    item.is_star = 0
    Message.success(t('common.op_success'))
  } else {
    await starItem(item_id)
    item.is_star = 1
    Message.success(t('common.op_success'))
  }
}

// 分享项目
const handleShare = async (item: any) => {
  await ShareModal({
    item_domain: item.item_domain,
    item_id: item.item_id
  })
}

// 更新项目
const handleUpdate = async (item: any) => {
  const result = await CreateItemModal({ item_id: item.item_id })
  if (result) {
    await props.getItemList()
  }
}

// 成员管理
const handleMember = async (item: any) => {
  opItemRow.value = item
  await MemberModal(item.item_id)
  await props.getItemList()
}

// 开放API
const handleOpenApi = async (item: any) => {
  await OpenApiModal(item.item_id)
}

// AI知识库
const handleAiKnowledgeBase = async (item: any) => {
  await AiKnowledgeBaseModal(item.item_id)
  // AI 配置后需要刷新页面，确保配置（包括展开状态）立即生效
  window.location.reload()
}

// 回收站
const handleRecycle = async (item: any) => {
  await RecycleModal(item.item_id)
}

// 归档
const handleArchive = async (item: any) => {
  const result = await ArchiveModal(item.item_id)
  if (result) {
    await props.getItemList()
  }
}

// 转让
const handleAttorn = async (item: any) => {
  const result = await AttornModal(item.item_id)
  if (result) {
    await props.getItemList()
  }
}

// 复制
const handleCopy = async (item: any) => {
  const result = await CopyItemModal(item.item_id)
  if (result) {
    await props.getItemList()
  }
}

// 删除
const handleDelete = async (item: any) => {
  const result = await DeleteModal(item.item_id)
  if (result) {
    await props.getItemList()
  }
}

// 退出项目
const handleExit = async (item_id: number) => {
  await exitItem(String(item_id))
  Message.success(t('common.op_success'))
}

// 拖动排序结束
const onDropEnd = async ({ oldIndex, newIndex }: { oldIndex: number, newIndex: number }) => {
  // 如果位置没变，不处理
  if (oldIndex === newIndex) return

  // 获取拖动后的元素顺序（从 DOM 读取）
  const container = document.querySelector('.item-list-container') as HTMLElement
  const children = container?.children
  if (!children) return

  // 按 DOM 顺序构建排序数据：{ item_id: 序号 }
  const data: Record<string, number> = {}
  for (let i = 0; i < children.length; i++) {
    const child = children[i] as HTMLElement
    const itemId = child.dataset.key
    if (itemId) {
      data[itemId] = i + 1
    }
  }

  // 调用保存排序接口
  try {
    await sortItem(data, props.itemGroupId)
    Message.success(t('common.op_success'))
    // 刷新列表以获取服务器端排序
    await props.getItemList()
  } catch (error) {
    console.error('Sort item failed:', error)
    await AlertModal(t('common.op_failed'))
    // 刷新列表恢复顺序
    await props.getItemList()
  }
}

// 初始化拖拽排序
let sortableInstance: any = null

onMounted(() => {
  const listContainer = document.querySelector('.item-list-container') as HTMLElement
  if (listContainer) {
    // 直接对容器本身进行拖拽排序
    sortableInstance = Sortable.create(listContainer, {
      animation: 200,
      ghostClass: 'sortable-ghost',
      dragClass: 'sortable-drag',
      chosenClass: 'sortable-chosen',
      onEnd: (evt: any) => {
        const { newIndex, oldIndex } = evt
        if (oldIndex !== newIndex && newIndex !== null) {
          onDropEnd({ oldIndex, newIndex })
        }
      }
    })
  }
})

onBeforeUnmount(() => {
  if (sortableInstance) {
    sortableInstance.destroy()
  }
})
</script>

<style scoped lang="scss">
.item-list-container {
  :deep(.sortable-ghost) {
    opacity: 0.4;
    background-color: var(--color-bg-secondary);
  }

  :deep(.sortable-drag) {
    opacity: 1;
    background-color: var(--color-bg-secondary);
    cursor: pointer;
  }

  :deep(.sortable-chosen) {
    opacity: 1;
  }

  :deep(.item-list-one) {
    cursor: pointer;
    transition: all 0.15s ease;
  }
}
</style>

<style scoped lang="scss">
.el-dropdown-link {
  cursor: pointer;
  color: var(--color-text-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  min-width: 60px;
  min-height: 60px;
  border-radius: 4px;
  transition: all 0.15s ease;
}

a {
  color: var(--color-text-primary);
}

.item-list {
  padding-left: 5px;
}

.item-list-one {
  margin-top: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  border-radius: 8px;
}

/* 定义一个类 ".shadow-hover"，指定 mouse-hover 时添加阴影 */
.shadow-hover {
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;
}

/* 当鼠标悬浮在元素上时，让阴影逐渐出现 */
.shadow-hover:hover {
  box-shadow: var(--shadow-sm);
}

/* 当离开元素时，让阴影逐渐消失 */
.shadow-hover:hover:after {
  opacity: 0;
  transition: 0.15s opacity linear 0.15s;
}

.item-list-one-block {
  width: 600px;
  height: 60px;
  background-color: var(--color-obvious);
  color: var(--color-text-primary);
  border-radius: 8px;
  box-shadow: 0 0 2px var(--color-border);
  border: 1px solid var(--color-border);
  float: left;
  opacity: 1;
  position: relative;
  bottom: 5px;
  right: 5px;
  overflow: hidden;
}

.item-list-one-block-bg {
  width: 600px;
  height: 60px;
  background-color: var(--color-obvious);
  color: var(--color-text-primary);
  border-radius: 8px;
  box-shadow: 0 0 2px var(--color-border);
  border: 1px solid var(--color-border);
}

.item-list-one-block-bg-none {
  visibility: hidden;
}

.item-list-one .left {
  position: relative;
  top: 50%;
  transform: translateY(-50%);
  padding-left: 20px;
  max-width: 550px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-list-one .right {
  position: relative;
  top: 50%;
  transform: translateY(-50%);
  padding-right: 20px;
}

.item-list-one .show-more {
  display: none;
}

.item-list-one:hover .show-more {
  display: block;
}

.item-list-one .item-icon {
  margin-right: 10px;
  color: var(--color-text-secondary);
  font-size: 16px;
}

.item-list-one .item-icon-more {
  color: var(--color-text-primary);
  font-size: 16px;
}

/* 列表模式星标小图标 */
.star-flag {
  margin-right: 8px;
}
</style>


