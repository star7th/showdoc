<template>
  <div class="page-diff-page">
    <div class="container-narrow">
      <!-- 隐藏的文本区域，用于存储内容供 difflib 使用 -->
      <textarea
        id="baseText"
        v-model="content"
        style="display:none;"
      ></textarea>
      <textarea
        id="newText"
        v-model="historyContent"
        style="display:none;"
      ></textarea>

      <!-- 切换视图类型 -->
      <div class="view-type-wrapper">
        <CommonButton
          :theme="viewType === 0 ? 'dark' : 'light'"
          @click="switchView(0)"
        >
          {{ $t('page.side_by_side') }}
        </CommonButton>
        <CommonButton
          :theme="viewType === 1 ? 'dark' : 'light'"
          @click="switchView(1)"
        >
          {{ $t('page.inline') }}
        </CommonButton>
      </div>

      <!-- 差异输出区域 -->
      <div id="diffoutput" v-loading="loading"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import CommonButton from '@/components/CommonButton.vue'

// Composables
const route = useRoute()
const { t } = useI18n()

// Refs
const content = ref('')
const historyContent = ref('')
const viewType = ref(0) // 0: 并排, 1: 行内
const loading = ref(true)

// 切换视图
const switchView = (type: number) => {
  viewType.value = type
  diffUsingJS(type)
}

// 执行对比
const diffUsingJS = (viewTypeNum: number) => {
  // 确保全局变量存在
  if (typeof (window as any).difflib === 'undefined' || typeof (window as any).diffview === 'undefined') {
    console.error('difflib 或 diffview 未加载')
    return
  }

  const byId = (id: string) => document.getElementById(id)
  const base = (window as any).difflib.stringAsLines((byId('baseText') as HTMLTextAreaElement).value)
  const newtxt = (window as any).difflib.stringAsLines((byId('newText') as HTMLTextAreaElement).value)
  const sm = new (window as any).difflib.SequenceMatcher(base, newtxt)
  const opcodes = sm.get_opcodes()
  const diffoutputdiv = byId('diffoutput')

  if (diffoutputdiv) {
    diffoutputdiv.innerHTML = ''

    diffoutputdiv.appendChild(
      (window as any).diffview.buildView({
        baseTextLines: base,
        newTextLines: newtxt,
        opcodes: opcodes,
        baseTextName: t('page.cur_page_content'),
        newTextName: t('page.history_version'),
        viewType: viewTypeNum
      })
    )
  }
}

// 获取对比数据
const fetchDiffData = async () => {
  const pageId = route.params.page_id as string
  const pageHistoryId = route.params.page_history_id as string

  if (!pageId || !pageHistoryId) {
    console.error('缺少参数: page_id 或 page_history_id')
    return
  }

  loading.value = true

  try {
    const result = await request('/api/page/diff', {
      page_id: pageId,
      page_history_id: pageHistoryId
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      content.value = result.data.page.page_content || ''
      historyContent.value = result.data.history_page.page_content || ''

      // 等待 DOM 更新后执行对比
      await nextTick()
      diffUsingJS(viewType.value)
    } else {
      console.error('获取对比数据失败:', result.error_message)
    }
  } catch (error) {
    console.error('获取对比数据失败:', error)
  } finally {
    loading.value = false
  }
}

// 加载必要的脚本
const loadDiffScripts = async () => {
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

  // 加载链接 CSS
  const link = document.createElement('link')
  link.href = '/diff/diffview.css'
  link.rel = 'stylesheet'
  document.head.appendChild(link)

  // 加载 jsdifflib 和 diffview
  try {
    await loadScript('/diff/difflib.js')
    await loadScript('/diff/diffview.js')
  } catch (error) {
    console.error('加载 diff 脚本失败:', error)
  }
}

// 设置页面标题
document.title = `${t('page.version_comparison')}--ShowDoc`

// Lifecycle
onMounted(async () => {
  // 先加载脚本，再获取数据
  await loadDiffScripts()
  await fetchDiffData()
})
</script>

<style lang="scss" scoped>
.page-diff-page {
  min-height: 100vh;
  background-color: var(--color-bg-secondary);
  padding: 20px;
}

.container-narrow {
  max-width: 1200px;
  margin: 0 auto;
}

.view-type-wrapper {
  margin-bottom: 20px;
  display: flex;
  justify-content: center;
  gap: 10px;

  .ant-btn {
    min-width: 100px;
  }
}

#diffoutput {
  background-color: var(--color-bg-primary);
  padding: 20px;
  border-radius: 8px;
  box-shadow: var(--shadow-base);
  min-height: 400px;
}
</style>
