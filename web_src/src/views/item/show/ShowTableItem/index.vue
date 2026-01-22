<template>
  <div class="table-item-container">
    <!-- 动态加载 xspreadsheet CSS - 通过 script 加载 -->
    <ItemHeader :item-info="itemInfo">
      <template #right>
        <ItemHeaderRight
          :item-info="itemInfo"
          @save="handleSave"
          @export="handleExport"
          @import="handleImportFile"
          @reload="handleReload"
        />
      </template>
    </ItemHeader>

    <!-- 表格容器 -->
    <div id="table-item"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import { useAppStore } from '@/store/app'
import { useUserStore } from '@/store/user'
import request from '@/utils/request'
import { getStaticPath, getServerHost } from '@/utils/system'
import { unescapeHTML } from '@/utils/tools'
import ItemHeader from '@/views/item/components/ItemHeader.vue'
import ItemHeaderRight from './HeaderRight.vue'

interface Props {
  itemInfo: any
}

const props = defineProps<Props>()
const emit = defineEmits<{
  reload: []
}>()

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()

// 响应式数据
const spreadsheetObj = ref<any>(null)
const spreadsheetData = ref<any>({})
const pageId = ref(0)
const isLock = ref(0)
const isEditable = ref(0)
const intervalId = ref(0)
const dependenciesLoaded = ref(false)
const originalTheme = ref<'light' | 'dark'>('light') // 保存原始主题

// 全局变量声明
declare const x_spreadsheet: any
declare const XLSX: any

// HTML 转义解码
const fetchPageContent = async (pid?: number) => {
  const targetPageId = pid || pageId.value

  const response = await request('/api/page/info', {
    page_id: targetPageId
  })

  if (response.data && response.data.page_content) {
    let objData: any
    try {
      // unescapeHTML 解码
      objData = JSON.parse(unescapeHTML(response.data.page_content))
    } catch (error) {
      objData = {}
    }
    spreadsheetData.value = objData

    // 先检查草稿
    if (props.itemInfo.item_edit == 1) {
      const hasDraft = checkDraft()
      if (hasDraft) {
        return // 如果有草稿，等待用户选择是否恢复
      }
    }

    // 根据编辑权限设置初始值
    isEditable.value = props.itemInfo.item_edit == 1 ? 1 : 0

    // 初始化表格
    await initSheet()

    if (props.itemInfo.item_edit == 1) {
      // 开始定时保存
      startAutoSave()
    }
  }
}

// 初始化表格
const initSheet = async () => {
  if (!x_spreadsheet) return false

  const mode = isEditable.value ? 'edit' : 'read'
  const tableElement = document.getElementById('table-item')

  if (tableElement) {
    tableElement.innerHTML = '' // 清空原来的东西
  }

  spreadsheetObj.value = null

  await nextTick()

  // 初始化表格
  spreadsheetObj.value = x_spreadsheet('#table-item', {
    mode: mode, // edit | read
    showToolbar: true,
    row: {
      len: 800,
      height: 25
    },
    view: {
      height: () => document.documentElement.clientHeight - 90
    }
  }).loadData(spreadsheetData.value) // load data

  spreadsheetObj.value.on('cell-edited', (text: string, ri: number, ci: number) => {
    handleSave()
  })
}

// 保存
const handleSave = async () => {
  if (!spreadsheetObj.value) return

  try {
    await request('/api/page/save', {
      page_id: pageId.value,
      page_title: props.itemInfo.item_name,
      item_id: props.itemInfo.item_id,
      is_urlencode: 1,
      page_content: encodeURIComponent(
        JSON.stringify(spreadsheetObj.value.getData())
      )
    })

    autoSaveTips()
    deleteDraft()
  } catch (error) {
    console.error('保存失败:', error)
  }
}

// 检查是否有草稿
const checkDraft = (): boolean => {
  const pkey = 'page_content_' + pageId.value
  const draftContent = localStorage.getItem(pkey)

  if (!draftContent) {
    return false
  }

  try {
    const draftData = JSON.parse(draftContent)
    // 比较草稿数据和服务端数据是否不同
    if (JSON.stringify(draftData) !== JSON.stringify(spreadsheetData.value)) {
      // 提示用户是否恢复草稿
      const shouldRestore = confirm(t('page.draftTips'))
      if (shouldRestore) {
        spreadsheetData.value = draftData
        initSheet()
      }
      // 无论是否恢复，都清除草稿
      localStorage.removeItem(pkey)
      return true
    } else {
      // 数据相同，清除草稿
      localStorage.removeItem(pkey)
      return false
    }
  } catch (error) {
    console.error('解析草稿数据失败:', error)
    localStorage.removeItem(pkey)
    return false
  }
}

// 开始定时保存
const startAutoSave = () => {
  const pkey = 'page_content_' + pageId.value

  // 定时保存文本内容到 localStorage
  setInterval(() => {
    if (spreadsheetObj.value) {
      const content = JSON.stringify(spreadsheetObj.value.getData())
      localStorage.setItem(pkey, content)
    }
  }, 30 * 1000)
}

// 删除草稿
const deleteDraft = () => {
  for (let i = 0; i < localStorage.length; i++) {
    const name = localStorage.key(i)
    if (name && name.indexOf('page_content_') > -1) {
      localStorage.removeItem(name)
    }
  }
}

// 锁定
const setLock = async () => {
  if (pageId.value > 0) {
    await request('/api/page/setLock', {
      page_id: pageId.value,
      item_id: props.itemInfo.item_id
    })
    isLock.value = 1
  }
}

// 解除锁定
const unlock = async () => {
  if (!isLock.value) {
    return // 本来处于未锁定中的话，不发起请求
  }
  await request('/api/page/setLock', {
    page_id: pageId.value,
    item_id: props.itemInfo.item_id,
    lock_to: 1000
  })
  isLock.value = 0
}

// 心跳保持锁定
const heartBeatLock = () => {
  intervalId.value = window.setInterval(() => {
    if (isLock.value) {
      setLock()
    }
  }, 3 * 60 * 1000)
}

// 判断页面是否被锁定编辑
const remoteIsLock = async () => {
  const res = await request('/api/page/isLock', {
    page_id: pageId.value
  })

  // 判断已经锁定了不
  if (res.data.lock > 0) {
    if (res.data.is_cur_user > 0) {
      isLock.value = 1
      isEditable.value = 1
      initSheet()
      heartBeatLock()
    } else {
      message.error(t('item.locking') + res.data.lock_username)
      props.itemInfo.item_edit = false
      clearInterval(intervalId.value)
      deleteDraft()
    }
  } else {
    setLock() // 如果没有被别人锁定，则进编辑页面后自己锁定。
    isEditable.value = 1
    initSheet()
    heartBeatLock()
  }
}

// 导出文件
const handleExport = () => {
  if (!spreadsheetObj.value) return

  // 定义转换函数
  const xtos = (sdata: any) => {
    const out = XLSX.utils.book_new()
    sdata.forEach((xws: any) => {
      const aoa: any[][] = [[]]
      const rowobj = xws.rows
      for (let ri = 0; ri < rowobj.len; ++ri) {
        const row = rowobj[ri]
        if (!row) continue
        aoa[ri] = []
        Object.keys(row.cells).forEach((k) => {
          const idx = +k
          if (isNaN(idx)) return
          aoa[ri][idx] = row.cells[k].text
        })
      }
      const ws = XLSX.utils.aoa_to_sheet(aoa)
      XLSX.utils.book_append_sheet(out, ws, xws.name)
    })
    return out
  }

  // 构建工作簿
  const newWb = xtos(spreadsheetObj.value.getData())
  XLSX.writeFile(newWb, 'showdoc.xlsx')
}

// 导入文件（支持单个文件）
const handleImportFile = (file: File) => {
  if (!file) return

  const stox = (wb: any) => {
    const out: any[] = []
    wb.SheetNames.forEach((name: string) => {
      const o = { name: name, rows: {} }
      const ws = wb.Sheets[name]
      const aoa = XLSX.utils.sheet_to_json(ws, { raw: false, header: 1 })
      aoa.forEach((r: any, i: number) => {
        const cells: any = {}
        ;(r as any[]).forEach((c: any, j: number) => {
          cells[j] = { text: c }
        })
        o.rows[i] = { cells: cells }
      })
      out.push(o)
    })
    return out
  }

  const reader = new FileReader()
  reader.onload = (e) => {
    const data = e.target?.result
    if (!data) return
    const mdata = stox(XLSX.read(data, { type: 'array' }))

    if (mdata && spreadsheetObj.value) {
      spreadsheetObj.value.loadData(mdata)
      setTimeout(() => {
        handleSave()
      }, 500)
    }
  }
  reader.readAsArrayBuffer(file)
}

// 导入文件（支持多个文件 - 兼容旧版）
const handleImport = (files: FileList) => {
  handleImportFile(files[0])
}

// 页面关闭时解锁
const unLockOnClose = () => {
  const analyticsData = new URLSearchParams({
    page_id: pageId.value.toString(),
    item_id: props.itemInfo.item_id.toString(),
    lock_to: '1000',
    user_token: userStore.userToken
  })

  const url = getServerHost() + '/api/page/setLock'

  if ('sendBeacon' in navigator) {
    navigator.sendBeacon(url, analyticsData)
  } else {
    const client = new XMLHttpRequest()
    client.open('POST', url, false)
    client.send(analyticsData)
  }
}

// 自动保存提示
const autoSaveTips = () => {
  const s = localStorage.getItem('table_item_auto_save_tips')
  if (!s) {
    message.info(t('item.tableItemAutoSaveTips'))
    localStorage.setItem('table_item_auto_save_tips', '1')
  }
}

// 重新加载
const handleReload = () => {
  emit('reload')
}

// 动态加载脚本
const loadScript = (src: string): Promise<void> => {
  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = src
    script.onload = () => resolve()
    script.onerror = reject
    document.head.appendChild(script)
  })
}

// 动态加载 CSS
const loadCSS = (href: string): void => {
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = href
  document.head.appendChild(link)
}

// 动态加载依赖脚本
const loadDependencies = async () => {
  try {
    const staticPath = getStaticPath()

    // 加载 xspreadsheet.js
    await loadScript(`${staticPath}xspreadsheet/xspreadsheet.js`)

    // 加载语言包
    await Promise.all([
      loadScript(`${staticPath}xspreadsheet/locale/zh-cn.js`),
      loadScript(`${staticPath}xspreadsheet/locale/en.js`)
    ])

    // 加载 xlsx
    await loadScript(`${staticPath}xspreadsheet/xlsx.full.min.js`)

    // 初始化
    dependenciesLoaded.value = true

    if (x_spreadsheet) {
      if (locale.value === 'en-US') {
        x_spreadsheet.locale('en')
      } else {
        x_spreadsheet.locale('zh-cn')
      }

      // 加载页面内容
      if (props.itemInfo.menu && props.itemInfo.menu.pages && props.itemInfo.menu.pages[0]) {
        pageId.value = props.itemInfo.menu.pages[0].page_id
        fetchPageContent()

        if (props.itemInfo.item_edit == 1) {
          remoteIsLock()
        }
      }
    }
  } catch (error) {
    console.error('加载依赖失败:', error)
  }
}

onMounted(() => {
  // 保存原始主题
  originalTheme.value = appStore.theme

  // 如果当前是暗黑主题，切换到亮色主题
  if (appStore.theme === 'dark') {
    appStore.setTheme('light')
  }

  // 加载静态资源
  const staticPath = getStaticPath()
  loadCSS(`${staticPath}xspreadsheet/xspreadsheet.css`)

  // 加载依赖脚本
  loadDependencies()

  window.addEventListener('beforeunload', unLockOnClose)
})

onBeforeUnmount(() => {
  clearInterval(intervalId.value)
  unlock()
  window.removeEventListener('beforeunload', unLockOnClose)

  // 恢复原始主题
  if (originalTheme.value !== appStore.theme) {
    appStore.setTheme(originalTheme.value)
  }

  setTimeout(() => {
    handleReload()
  }, 500)
})
</script>

<style lang="scss" scoped>
.table-item-container {
  min-height: 100vh;
}

#table-item {
  margin-top: 90px;
}

.text-center {
  text-align: center;

  code {
    background: var(--color-bg-tertiary);
    padding: 2px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
  }

  p {
    margin: 12px 0;
  }
}
</style>
