<template>
  <Transition name="guide-fade">
    <div v-if="visible" class="ai-guide-panel">
      <!-- 游客权限提示条 -->
      <div v-if="isGuest" class="guest-notice">
        <i class="fas fa-info-circle"></i>
        <span>基于公开文档的有限访问，<a href="#/user/login" target="_blank">登录</a>可获得更多功能</span>
      </div>

      <div class="guide-content">
        <div class="guide-icon">
          <i class="fas fa-robot"></i>
        </div>
        <div class="guide-title">AI 智能助手</div>
        <!-- 欢迎语：后端返回时显示，替换默认副标题 -->
        <div v-if="welcomeMessage" class="guide-welcome">{{ welcomeMessage }}</div>
        <div v-else class="guide-subtitle">有什么可以帮助你的？</div>

        <!-- 场景分组引导 -->
        <div class="guide-groups">
          <div
            v-for="(group, gIdx) in activeGroups"
            :key="gIdx"
            class="guide-group"
          >
            <div class="group-label">{{ group.icon }} {{ group.label }}</div>
            <div class="group-items">
              <div
                v-for="(item, iIdx) in group.items"
                :key="iIdx"
                class="group-item"
                @click="$emit('quick-send', item.prompt)"
              >
                {{ item.text }}
              </div>
            </div>
          </div>

          <!-- AI 接入引导项：点击打开「AI 接入」弹窗（不发 prompt；游客隐藏，避免未登录接口报错） -->
          <div v-if="!isGuest" class="guide-group">
            <div class="group-item ai-access-item" @click="handleAiAccess">
              <i class="fas fa-plug"></i>
              在用 AI 编辑器？让编辑器直接读写 ShowDoc 文档
            </div>
          </div>
        </div>

        <!-- 保留旧的 feature-card 作为 fallback（无分组时） -->
        <div v-if="activeGroups.length === 0" class="guide-features">
          <div
            v-for="(feature, index) in displayFeatures"
            :key="index"
            class="feature-card"
          >
            <i :class="feature.icon" class="feature-icon"></i>
            <div class="feature-info">
              <div class="feature-name">{{ feature.name }}</div>
              <div class="feature-desc">{{ feature.description }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AiTokenModal from '@/components/ai/AiTokenModal'

// 打开「AI 接入」弹窗
const handleAiAccess = async () => {
  await AiTokenModal()
}

interface FeatureItem {
  name: string
  description: string
  icon: string
}

interface QuickItem {
  text: string
  prompt: string
}

interface GuideGroup {
  icon: string
  label: string
  items: QuickItem[]
}

const props = withDefaults(defineProps<{
  visible: boolean
  features?: FeatureItem[]
  canEdit?: boolean
  globalMode?: boolean
  isGuest?: boolean
  welcomeMessage?: string
}>(), {
  features: () => [],
  canEdit: false,
  globalMode: false,
  isGuest: false,
  welcomeMessage: '',
})

defineEmits<{
  'quick-send': [prompt: string]
}>()

const defaultFeatures: FeatureItem[] = [
  {
    name: '搜索文档',
    description: '在当前项目中搜索相关文档内容',
    icon: 'fas fa-search',
  },
  {
    name: '创建页面',
    description: '帮你在项目中创建新的文档页面',
    icon: 'fas fa-file-alt',
  },
  {
    name: '编辑内容',
    description: '智能修改和优化编辑器中的内容',
    icon: 'fas fa-edit',
  },
  {
    name: '回答问题',
    description: '基于项目文档回答你的问题',
    icon: 'fas fa-comments',
  },
]

const displayFeatures = computed(() => {
  return props.features.length > 0 ? props.features : defaultFeatures
})

/** 项目编辑模式（admin/writer） */
const projectEditGroups: GuideGroup[] = [
  {
    icon: '📖',
    label: '问答',
    items: [
      { text: '"登录接口怎么调用？"', prompt: '登录接口怎么调用？' },
      { text: '"搜索关于权限相关的文档"', prompt: '搜索关于权限相关的文档' },
    ],
  },
  {
    icon: '✏️',
    label: '编辑',
    items: [
      { text: '"帮我把参数说明改成表格格式"', prompt: '帮我把参数说明改成表格格式' },
      { text: '"把这段文字翻译成英文"', prompt: '把这段文字翻译成英文' },
    ],
  },
  {
    icon: '📋',
    label: '整理',
    items: [
      { text: '"帮我总结这个项目的目录结构"', prompt: '帮我总结这个项目的目录结构' },
      { text: '"找出项目中重复的内容"', prompt: '找出项目中重复的内容' },
    ],
  },
  {
    icon: '🧪',
    label: '辅助',
    items: [
      { text: '"根据接口文档生成 JS 调用代码"', prompt: '根据接口文档生成 JS 调用代码' },
      { text: '"帮我写这个接口的测试用例"', prompt: '帮我写这个接口的测试用例' },
    ],
  },
]

/** 项目只读模式 */
const projectReadGroups: GuideGroup[] = [
  {
    icon: '📖',
    label: '问答',
    items: [
      { text: '"登录接口怎么调用？"', prompt: '登录接口怎么调用？' },
      { text: '"搜索关于权限相关的文档"', prompt: '搜索关于权限相关的文档' },
    ],
  },
  {
    icon: '📋',
    label: '整理',
    items: [
      { text: '"帮我总结这个项目的目录结构"', prompt: '帮我总结这个项目的目录结构' },
      { text: '"找出项目中重复的内容"', prompt: '找出项目中重复的内容' },
    ],
  },
  {
    icon: '🧪',
    label: '辅助',
    items: [
      { text: '"根据接口文档生成 JS 调用代码"', prompt: '根据接口文档生成 JS 调用代码' },
      { text: '"帮我写这个接口的测试用例"', prompt: '帮我写这个接口的测试用例' },
    ],
  },
]

/** 全局模式 */
const globalGroups: GuideGroup[] = [
  {
    icon: '🔍',
    label: '项目管理',
    items: [
      { text: '"列出我的所有项目"', prompt: '列出我的所有项目' },
      { text: '"帮我创建一个新项目"', prompt: '帮我创建一个新项目' },
    ],
  },
  {
    icon: '📋',
    label: '跨项目',
    items: [
      { text: '"搜索所有项目里关于部署的文档"', prompt: '搜索所有项目里关于部署的文档' },
      { text: '"帮我找出项目间不一致的接口定义"', prompt: '帮我找出项目间不一致的接口定义' },
    ],
  },
]

/** 根据场景选择分组 */
const activeGroups = computed((): GuideGroup[] => {
  if (props.globalMode) return globalGroups
  if (props.canEdit) return projectEditGroups
  return projectReadGroups
})
</script>

<style scoped lang="scss">
.guide-fade-enter-active,
.guide-fade-leave-active {
  transition: opacity 0.2s ease;
}
.guide-fade-enter-from,
.guide-fade-leave-to {
  opacity: 0;
}

.ai-guide-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px;
  overflow-y: auto;
}

.guest-notice {
  width: 100%;
  padding: 8px 12px;
  background: #fff7e6;
  border: 1px solid #ffe58f;
  border-radius: 6px;
  font-size: 12px;
  color: #ad6800;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 12px;
  flex-shrink: 0;

  [data-theme='dark'] & {
    background: #2b2111;
    border-color: #594214;
    color: #d4b106;
  }

  a {
    color: var(--color-active);
    text-decoration: underline;
  }
}

.guide-content {
  text-align: center;
  max-width: 320px;
  width: 100%;
}

.guide-icon {
  font-size: 40px;
  color: var(--color-active);
  margin-bottom: 12px;

  i {
    opacity: 0.8;
  }
}

.guide-title {
  font-size: 18px;
  font-weight: 600;
  color: var(--color-primary);
  margin-bottom: 4px;
}

.guide-subtitle {
  font-size: 13px;
  color: var(--color-text-secondary);
  margin-bottom: 24px;
}

.guide-welcome {
  font-size: 14px;
  color: var(--color-text-secondary);
  margin-bottom: 24px;
  line-height: 1.5;
}

.guide-groups {
  display: flex;
  flex-direction: column;
  gap: 14px;
  text-align: left;
}

.guide-group {
  .group-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 6px;
  }

  .group-items {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
}

.group-item {
  font-size: 12px;
  color: var(--color-text-secondary);
  padding: 6px 10px;
  border-radius: 6px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-border);
  cursor: pointer;
  transition: all 0.15s;

  &:hover {
    border-color: var(--color-active);
    color: var(--color-active);
  }

  [data-theme='dark'] & {
    background: var(--color-bg-secondary);
    border-color: var(--color-border);
  }
}

// AI 接入引导项（带图标）
.ai-access-item {
  display: flex;
  align-items: center;
  gap: 6px;

  i {
    font-size: 11px;
    color: var(--color-active);
  }
}

.guide-features {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.feature-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px;
  border-radius: 8px;
  border: 1px solid var(--color-border);
  background: var(--color-bg-secondary);
  text-align: left;
  transition: border-color 0.15s;

  &:hover {
    border-color: var(--color-active);
  }

  [data-theme='dark'] & {
    background: var(--color-bg-secondary);
    border-color: var(--color-border);
  }
}

.feature-icon {
  font-size: 18px;
  color: var(--color-active);
  margin-top: 2px;
  flex-shrink: 0;
}

.feature-info {
  min-width: 0;
}

.feature-name {
  font-size: 13px;
  font-weight: 500;
  color: var(--color-primary);
  margin-bottom: 2px;
}

.feature-desc {
  font-size: 12px;
  color: var(--color-text-secondary);
  line-height: 1.4;
}

/* ─── 移动端适配 ─── */
@media (max-width: 768px) {
  .ai-guide-panel {
    padding: 16px;
  }

  .guide-content {
    max-width: 100%;
  }

  .guide-icon {
    font-size: 32px;
    margin-bottom: 8px;
  }

  .guide-title {
    font-size: 16px;
  }

  /* 引导项触摸区更大 */
  .group-item {
    font-size: 13px;
    padding: 10px 12px;
  }

  .feature-card {
    padding: 10px;
  }
}
</style>
