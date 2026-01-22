<template>
  <div class="item-card-list">
    <div class="card-container">
      <a-row :gutter="[24, 24]" class="draggable-container">
          <a-col
            :xs="24"
            :sm="12"
            :md="12"
            :lg="8"
            :xl="8"
            v-for="item in itemList"
            :key="item.item_id"
            :data-key="item.item_id"
          >
            <!-- 单层卡片设计 -->
            <div 
              class="item-card" 
              :class="{ 'is-starred': item.is_star > 0 }"
              @click="toOneItem(item)"
            >
              <!-- 星标切角 -->
              <div v-if="item.is_star > 0" class="star-corner" @click.stop></div>
              
              <!-- 卡片头部：图标 + 标题 + 操作 -->
              <div class="item-card-header">
                <div class="item-icon-wrapper">
                  <i v-if="item.item_type == '2'" class="item-icon fas fa-file"></i>
                  <i v-else-if="item.item_type == '4'" class="item-icon fas fa-table"></i>
                  <i v-else-if="item.item_type == '3'" class="item-icon fas fa-terminal"></i>
                  <i v-else-if="item.item_type == '5'" class="item-icon fas fa-chalkboard"></i>
                  <i v-else class="item-icon fas fa-book"></i>
                </div>
                <div class="item-card-title">{{ item.item_name }}</div>
                
                <!-- 操作按钮 -->
                <div class="item-card-actions" @click.stop>
                  <CommonDropdownMenu
                    :list="getMenuItems(item)"
                    trigger="hover"
                    placement="right"
                    :offsetX="20"
                    @select="(menuItem) => handleMenuSelect(item, menuItem.value)"
                  >
                    <span class="action-trigger">
                      <i class="fas fa-ellipsis"></i>
                    </span>
                  </CommonDropdownMenu>
                </div>
              </div>
              
              <!-- 卡片底部：元数据 -->
              <div class="item-card-footer">
                <div class="item-meta">
                  <span class="meta-item">
                    <i class="far fa-clock"></i>
                    {{ formatTime(getLatestTime(item.last_update_time, item.addtime)) }}
                  </span>
                </div>
                <div class="item-type-badge" :class="`type-${item.item_type}`">
                  {{ getItemTypeName(item.item_type) }}
                </div>
              </div>
            </div>
          </a-col>
        </a-row>
    </div>

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
import AttornModal from '@/views/modals/item/AttornModal'
import CopyItemModal from '@/views/modals/item/CopyItemModal'
import DeleteModal from '@/views/modals/item/DeleteModal'
import ArchiveModal from '@/views/modals/item/ArchiveModal'
import OpenApiModal from '@/views/modals/item/OpenApiModal'
import AiKnowledgeBaseModal from '@/views/modals/item/AiKnowledgeBaseModal'
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
const opItemRow = ref<any>({})

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

// 获取两个时间中较新的时间
const getLatestTime = (time1: number | undefined, time2: number | undefined): number => {
  // 如果两个时间都不存在，返回 0
  if (!time1 && !time2) return 0
  
  // 如果其中一个不存在，返回另一个
  if (!time1) return time2!
  if (!time2) return time1
  
  // 返回较大的时间戳
  return Math.max(time1, time2)
}

// 格式化时间
const formatTime = (timestamp: number) => {
  if (!timestamp) return ''
  
  const now = Date.now() / 1000
  const diff = now - timestamp
  
  // 1小时内
  if (diff < 3600) {
    const minutes = Math.floor(diff / 60)
    return minutes <= 1 ? t('time.just_now') : t('time.minutes_ago', { n: minutes })
  }
  
  // 24小时内
  if (diff < 86400) {
    const hours = Math.floor(diff / 3600)
    return t('time.hours_ago', { n: hours })
  }
  
  // 7天内
  if (diff < 604800) {
    const days = Math.floor(diff / 86400)
    return t('time.days_ago', { n: days })
  }
  
  // 显示日期
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  
  // 今年内不显示年份
  const currentYear = new Date().getFullYear()
  if (year === currentYear) {
    return `${month}-${day}`
  }
  
  return `${year}-${month}-${day}`
}

// 获取项目类型名称
const getItemTypeName = (itemType: string | number) => {
  const typeMap: Record<string, string> = {
    '1': t('item.type_regular'),
    '2': t('item.type_single'),
    '3': t('item.type_runapi'),
    '4': t('item.type_table'),
    '5': t('item.type_whiteboard')
  }
  return typeMap[String(itemType)] || t('item.type_regular')
}

// 拖动排序结束
const onDropEnd = async ({ oldIndex, newIndex }: { oldIndex: number, newIndex: number }) => {
  // 如果位置没变，不处理
  if (oldIndex === newIndex) return

  // 获取拖动后的元素顺序（从 DOM 读取）
  const container = document.querySelector('.draggable-container') as HTMLElement
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
  const listContainer = document.querySelector('.draggable-container') as HTMLElement
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
.draggable-container {
  :deep(.sortable-ghost) {
    opacity: 0.4;
    background-color: var(--color-bg-secondary);
  }

  :deep(.sortable-drag) {
    opacity: 1;
    background-color: var(--color-bg-secondary);
    cursor: move;
  }

  :deep(.sortable-chosen) {
    opacity: 1;
  }
}

:deep(.ant-col) {
  cursor: move;
  transition: all 0.15s ease;
}
</style>

<style scoped lang="scss">
.item-card-list {
  margin-bottom: 30px;
  width: 100%;
  overflow: hidden;
}

.card-container {
  margin-top: 20px;
  width: 100%;
}

.draggable-container {
  width: 100%;
}

// 卡片设计（克制设计）
.item-card {
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.15s ease;
  position: relative;
  height: auto;
  min-height: 140px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  overflow: hidden;
  box-shadow: var(--shadow-xs);

  &:hover {
    border-color: var(--color-active);
    box-shadow: var(--shadow-sm);

    .item-card-actions {
      opacity: 1;
      visibility: visible;
    }
  }
}

// 星标项目高亮边框（克制设计）
.item-card.is-starred {
  border-color: rgba(244, 193, 80, 0.2);
  
  &:hover {
    border-color: var(--color-active);
  }
}

// 星标右上角切角
.star-corner {
  position: absolute;
  top: 0;
  right: 0;
  width: 36px;
  height: 36px;
  background: var(--icon-tag-color);
  clip-path: polygon(100% 0, 100% 100%, 0 0);
  z-index: 5;

  &::before {
    content: '\f005';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 4px;
    right: 4px;
    color: #fff;
    font-size: 10px;
  }
}

// 卡片头部：图标 + 标题 + 操作按钮
.item-card-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
  position: relative;
}

// 图标容器 - 左上角
.item-icon-wrapper {
  flex-shrink: 0;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-secondary);
  border-radius: 8px;
  transition: all 0.15s ease;

  .item-icon {
    font-size: 18px;
    color: var(--color-text-secondary);
  }
}

.item-card:hover .item-icon-wrapper {
  background: var(--hover-overlay);
}

// 标题 - 左对齐，多行显示
.item-card-title {
  flex: 1;
  font-size: 15px;
  font-weight: 500;
  line-height: 1.5;
  color: var(--color-text-primary);
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-word;
  margin-right: 8px;
  padding-top: 2px;
}

// 操作按钮 - 右上角
.item-card-actions {
  position: absolute;
  top: 0;
  right: 0;
  opacity: 0;
  visibility: hidden;
  transition: all 0.15s ease;
  z-index: 3;
}

.action-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.15s ease;

  &:hover {
    background: var(--color-bg-secondary);
    color: var(--color-text-primary);
  }

  i {
    font-size: 16px;
  }
}

// 卡片底部：元数据
.item-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px solid var(--color-border-light);
}

.item-meta {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
  min-width: 0;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--color-text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  
  i {
    font-size: 12px;
    opacity: 0.7;
  }
}

// 项目类型徽章
.item-type-badge {
  flex-shrink: 0;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  line-height: 1;
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  transition: all 0.15s ease;
  
  &.type-1 {
    background: rgba(0, 123, 255, 0.1);
    color: var(--color-active);
  }
  
  &.type-2 {
    background: rgba(40, 167, 69, 0.1);
    color: var(--color-green);
  }
  
  &.type-3 {
    background: rgba(253, 126, 20, 0.1);
    color: var(--color-orange);
  }
  
  &.type-4 {
    background: rgba(220, 53, 69, 0.1);
    color: var(--color-red);
  }
  
  &.type-5 {
    background: rgba(108, 117, 125, 0.1);
    color: var(--color-text-secondary);
  }
}

// 暗黑模式优化
[data-theme='dark'] {
  .item-card {
    box-shadow: var(--shadow-sm);

    &:hover {
      box-shadow: var(--shadow-base);
    }

    &:active {
      box-shadow: var(--shadow-sm);
    }
  }
  
  .item-card.is-starred {
    border-color: rgba(244, 193, 80, 0.25);
    
    &:hover {
      border-color: rgba(244, 193, 80, 0.4);
    }
  }
  
  .item-type-badge {
    &.type-1 {
      background: rgba(74, 158, 255, 0.15);
    }
    
    &.type-2 {
      background: rgba(63, 185, 80, 0.15);
    }
    
    &.type-3 {
      background: rgba(255, 140, 46, 0.15);
    }
    
    &.type-4 {
      background: rgba(248, 81, 73, 0.15);
    }
    
    &.type-5 {
      background: rgba(140, 152, 163, 0.15);
    }
  }
}

// 响应式优化 - 小屏幕下调整内边距
@media (max-width: 768px) {
  .item-card {
    padding: 16px;
    min-height: 120px;
  }
  
  .item-card-header {
    gap: 10px;
    margin-bottom: 12px;
  }
  
  .item-icon-wrapper {
    width: 36px;
    height: 36px;
    
    .item-icon {
      font-size: 16px;
    }
  }
  
  .item-card-title {
    font-size: 14px;
  }
}
</style>


