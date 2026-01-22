<template>
  <div class="recommend-management">
    <!-- 统计信息 -->
    <div class="stats-section">
      <div class="stats-card">
        <div class="stats-icon">
          <i class="fas fa-star"></i>
        </div>
        <div class="stats-content">
          <div class="stats-label">{{ $t('admin.recommend_count') }}</div>
          <div class="stats-value">{{ itemList.length }}</div>
        </div>
      </div>
    </div>

    <!-- 推荐列表表格 -->
    <div class="table-section">
      <CommonTable
        :table-header="tableHeader"
        :table-data="itemList"
        :loading="loading"
        :pagination="false"
        row-key="item_id"
        max-height="calc(100vh - 280px)"
      >
        <!-- 访问链接列 -->
        <template #cell-item_id="{ row }">
          <span class="link-btn" @click="handleViewItem(row)">
            {{ $t('admin.view') }}
          </span>
        </template>

        <!-- 操作列 -->
        <template #cell-action="{ row }">
          <div class="table-action-buttons">
            <span class="table-action-btn delete" @click="handleDelete(row)">
              <i class="fas fa-trash-alt"></i>
              {{ $t('common.delete') }}
            </span>
          </div>
        </template>
      </CommonTable>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import { useRouter } from 'vue-router'
import ConfirmModal from '@/components/ConfirmModal'
import CommonTable from '@/components/CommonTable.vue'
import { getRecommendList, deleteRecommendItem } from '@/models/admin'

const { t } = useI18n()
const router = useRouter()

// 数据状态
const itemList = ref<any[]>([])
const loading = ref(false)

// 表格头部配置
const tableHeader = computed(() => [
  { title: t('admin.item_name'), key: 'item_name', width: 160 },
  { title: t('admin.item_desc'), key: 'item_description', width: 200 },
  { title: t('admin.access_link'), key: 'item_id', width: 100, center: true },
  { title: t('admin.owner'), key: 'username', width: 140 },
  { title: t('admin.create_time'), key: 'addtime', width: 160 },
  { title: t('common.operation'), key: 'action', width: 140, center: true }
])

// 方法
const fetchList = async () => {
  loading.value = true
  try {
    const res: any = await getRecommendList({ page: 1, count: 1000000 })
    itemList.value = res.data || []
  } catch (error) {
    console.error('获取推荐列表失败:', error)
  } finally {
    loading.value = false
  }
}

const handleViewItem = (record: any) => {
  // 使用 path 而不是 name+params，避免参数解析问题
  const url = router.resolve({
    path: `/${record.item_id}`
  }).href
  window.open(url, '_blank')
}

const handleDelete = async (record: any) => {
  const confirmed = await ConfirmModal(t('common.confirm_delete'))
  if (confirmed) {
    try {
      await deleteRecommendItem({ item_id: record.item_id })
      message.success(t('admin.delete_success'))
      fetchList()
    } catch (error) {
      message.error(t('common.delete_failed'))
    }
  }
}

onMounted(() => {
  fetchList()
})
</script>

<style lang="scss" scoped>
.recommend-management {
  .stats-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;

    .stats-card {
      display: flex;
      align-items: center;
      gap: 16px;

      .stats-icon {
        width: 60px;
        height: 60px;
        background: var(--color-active);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: var(--color-obvious);
      }

      .stats-content {
        flex: 1;

        .stats-label {
          font-size: 14px;
          color: var(--color-primary);
          font-weight: 500;
          margin-bottom: 4px;
        }

        .stats-value {
          font-size: 28px;
          font-weight: 700;
          color: var(--color-active);
        }
      }
    }
  }

  .table-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
  }

  .link-btn {
    color: var(--color-active);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;

    &:hover {
      background: var(--hover-overlay);
    }
  }

  .delete-btn {
    color: var(--color-red);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 4px 12px;
    border-radius: 6px;

    &:hover {
      background: var(--hover-overlay);
    }
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .recommend-management {
    .stats-section,
    .table-section {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .stats-section {
      .stats-card {
        .stats-icon {
          background: var(--color-active);
          color: var(--color-obvious);
        }

        .stats-content {
          .stats-label {
            color: var(--color-primary);
          }

          .stats-value {
            color: var(--color-active);
          }
        }
      }
    }

    .link-btn {
      color: var(--color-active);
    }

    .delete-btn {
      color: var(--color-red);

      &:hover {
        background: var(--hover-overlay);
      }
    }
  }
}
</style>
