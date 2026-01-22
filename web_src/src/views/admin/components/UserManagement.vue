<template>
  <div class="user-management">
    <!-- 搜索表单区域 -->
    <div class="search-section">
      <div class="search-row">
        <div class="search-input">
          <CommonInput
            v-model="username"
            :placeholder="$t('admin.username_search_placeholder')"
          />
        </div>
        <CommonButton
          theme="dark"
          :text="$t('common.search')"
          :leftIcon="['fas', 'search']"
          @click="handleSearch"
        />
        <CommonButton
          theme="light"
          :text="$t('admin.add_user')"
          :leftIcon="['fas', 'plus']"
          @click="showAddModal"
        />
      </div>
    </div>

    <!-- 用户列表表格 -->
    <div class="table-section">
      <CommonTable
        :table-header="tableHeader"
        :table-data="userList"
        :pagination="pagination"
        :loading="loading"
        row-key="uid"
        max-height="calc(100vh - 280px)"
        @page-change="handleTableChange"
      >
        <!-- 用户角色列 -->
        <template #cell-groupid="{ row }">
          <span class="role-badge" :class="getRoleClass(row)">
            {{ formatGroup(row) }}
          </span>
        </template>

        <!-- 操作列 -->
        <template #cell-action="{ row }">
          <div class="table-action-buttons">
            <span class="table-action-btn edit" @click="handleEdit(row)">
              <i class="fas fa-edit"></i>
              {{ $t('common.edit') }}
            </span>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import ConfirmModal from '@/components/ConfirmModal'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTable from '@/components/CommonTable.vue'
import {
  getUserList,
  deleteUser
} from '@/models/admin'
import editUserModal from '../modals/EditUserModal'

const { t } = useI18n()

// 数据状态
const username = ref('')
const userList = ref<any[]>([])
const loading = ref(false)

// 分页配置
const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
})

// 表格头部配置
const tableHeader = computed(() => [
  { title: t('admin.username'), key: 'username', width: 140 },
  { title: t('user.name'), key: 'name', width: 100 },
  { title: t('admin.user_role'), key: 'groupid', width: 90, center: true },
  { title: t('admin.mobile'), key: 'mobile', width: 130 },
  { title: t('admin.register_time'), key: 'reg_time', width: 160 },
  { title: t('admin.last_login_time'), key: 'last_login_time', width: 170 },
  { title: t('common.operation'), key: 'action', width: 200, center: true }
])

// 方法
const fetchUserList = async () => {
  loading.value = true
  try {
    const res: any = await getUserList({
      username: username.value,
      page: pagination.current,
      count: pagination.pageSize
    })
    userList.value = res.data.users || []
    pagination.total = res.data.total || 0
  } catch (error) {
    console.error('获取用户列表失败:', error)
  } finally {
    loading.value = false
  }
}

const getRoleClass = (row: any) => {
  if (Number(row.groupid) === 1) return 'admin'
  if (Number(row.groupid) === 2) return 'normal'
  return 'unknown'
}

const formatGroup = (row: any) => {
  if (Number(row.groupid) === 1) {
    return t('admin.admin')
  } else if (Number(row.groupid) === 2) {
    return t('admin.normal_user')
  } else {
    return t('admin.unknown')
  }
}

const handleSearch = () => {
  pagination.current = 1
  fetchUserList()
}

const handleTableChange = (page: number, pageSize: number) => {
  pagination.current = page
  pagination.pageSize = pageSize
  fetchUserList()
}

const showAddModal = async () => {
  const result = await editUserModal()
  if (result) {
    fetchUserList()
  }
}

const handleEdit = async (record: any) => {
  const result = await editUserModal({ user: record })
  if (result) {
    fetchUserList()
  }
}

const handleDelete = async (record: any) => {
  const confirmed = await ConfirmModal(t('common.confirm_delete'))
  if (confirmed) {
    try {
      await deleteUser({ uid: record.uid })
      message.success(t('admin.delete_success'))
      fetchUserList()
    } catch (error) {
      message.error(t('common.delete_failed'))
    }
  }
}

onMounted(() => {
  fetchUserList()
})
</script>

<style lang="scss" scoped>
.user-management {
  .search-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;

    .search-row {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;

      .search-input {
        flex: 1;
        max-width: 300px;
      }
    }
  }

  .table-section {
    background: var(--color-obvious);
    padding: 20px;
    border-radius: 8px;
    box-shadow: var(--shadow-sm);
  }

  .role-badge {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;

    &.admin {
      background: var(--color-inactive);
      color: var(--color-primary);
    }

    &.normal {
      background: var(--hover-overlay);
      color: var(--color-primary);
    }

    &.unknown {
      background: var(--color-grey);
      color: var(--color-obvious);
    }
  }

  .status-badge {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;

    &.success {
      background: var(--color-green);
      color: var(--color-obvious);
    }

    &.default {
      background: var(--color-inactive);
      color: var(--color-primary);
    }
  }

  .modal-form {
    .form-group {
      margin-bottom: 20px;

      .form-label {
        display: block;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 600;
        color: var(--color-primary);
      }

      .password-input {
        width: 100%;
        height: 40px;
        padding: 0 12px;
        border: 1px solid var(--color-inactive);
        border-radius: 4px;
        background: var(--color-default);
        color: var(--color-primary);
        font-size: 14px;
        outline: none;
        transition: all 0.15s ease;

        &:focus {
          border-color: var(--color-active);
          background: var(--color-obvious);
        }

        &::placeholder {
          color: var(--color-inactive);
        }
      }
    }
  }

  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  .truncate-text {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

// 暗黑主题适配
[data-theme="dark"] {
  .user-management {
    .search-section,
    .table-section {
      background: var(--color-secondary);
      box-shadow: var(--shadow-sm);
    }

    .role-badge {
      &.admin {
        background: var(--color-default);
        color: var(--color-primary);
      }

      &.normal {
        background: var(--hover-overlay);
        color: var(--color-primary);
      }

      &.unknown {
        background: var(--color-grey);
        color: var(--color-primary);
      }
    }

    .status-badge {
      &.success {
        background: var(--color-green);
        color: var(--color-obvious);
      }

      &.default {
        background: var(--color-default);
        color: var(--color-primary);
      }
    }

    .action-buttons {
      .action-btn {
        background: var(--color-default);
        color: var(--color-primary);
        border-color: var(--color-inactive);

        &:hover {
          background: var(--hover-overlay);
        }
      }
    }

    .modal-form {
      .form-group {
        .form-label {
          color: var(--color-primary);
        }

        .password-input {
          background: var(--color-default);
          border-color: var(--color-inactive);
          color: var(--color-primary);

          &:focus {
            border-color: var(--color-active);
            background: var(--color-secondary);
          }

          &::placeholder {
            color: var(--color-inactive);
          }
        }
      }
    }
  }
}
</style>
