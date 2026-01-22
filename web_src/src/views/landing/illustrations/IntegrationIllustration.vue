<template>
  <div class="integration-illustration">
    <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="runApiGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#6f42c1" />
          <stop offset="100%" style="stop-color:#5a32a3" />
        </linearGradient>

        <linearGradient id="showDocGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#007bff" />
          <stop offset="100%" style="stop-color:#0056b3" />
        </linearGradient>

        <linearGradient id="integrationGradient" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" style="stop-color:#6f42c1" />
          <stop offset="50%" style="stop-color:#007bff" />
          <stop offset="100%" style="stop-color:#6f42c1" />
        </linearGradient>

        <filter id="intShadow">
          <feGaussianBlur in="SourceAlpha" stdDeviation="3"/>
          <feOffset dx="0" dy="3"/>
          <feComponentTransfer>
            <feFuncA type="linear" slope="0.15"/>
          </feComponentTransfer>
          <feMerge> 
            <feMergeNode/>
            <feMergeNode in="SourceGraphic"/> 
          </feMerge>
        </filter>

        <filter id="glow">
          <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
          <feMerge>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>
      </defs>

      <!-- 背景装饰 -->
      <circle cx="90" cy="90" r="80" :fill="purpleCircleColor" opacity="0.3"/>
      <circle cx="510" cy="320" r="70" :fill="blueCircleColor" opacity="0.3"/>

      <!-- 左侧 RunAPI -->
      <g class="runapi-panel" filter="url(#intShadow)">
        <rect x="50" y="90" width="220" height="240" rx="16" :fill="cardBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 标题栏 -->
        <rect x="60" y="100" width="200" height="35" rx="8" fill="url(#runApiGradient)" opacity="0.15"/>
        <text x="160" y="123" font-size="14" :fill="textColor" text-anchor="middle" font-weight="700">RunAPI</text>

        <!-- 调试界面 -->
        <g class="debug-panel">
          <!-- HTTP Method -->
          <rect x="70" y="150" width="50" height="24" rx="6" fill="#6f42c1"/>
          <text x="95" y="166" font-size="11" fill="white" text-anchor="middle" font-weight="bold">POST</text>

          <!-- URL 输入 -->
          <rect x="130" y="150" width="120" height="24" rx="6" :fill="inputBg" :stroke="borderColor" stroke-width="1"/>
          <text x="140" y="166" font-size="9" :fill="textSecondary" font-family="monospace">/api/users</text>

          <!-- 请求参数 -->
          <text x="70" y="195" font-size="10" :fill="textColor" font-weight="600">Request Body</text>
          <rect x="70" y="200" width="180" height="50" rx="6" :fill="codeBg"/>
          <text x="80" y="218" font-size="8" fill="#c792ea" font-family="monospace">{</text>
          <text x="90" y="230" font-size="8" fill="#82aaff" font-family="monospace">"name": "test",</text>
          <text x="90" y="240" font-size="8" fill="#c3e88d" font-family="monospace">"age": 25</text>
          <text x="80" y="248" font-size="8" fill="#c792ea" font-family="monospace">}</text>

          <!-- 调试按钮 -->
          <rect x="70" y="265" width="180" height="32" rx="8" fill="url(#runApiGradient)" class="debug-button"/>
          <text x="160" y="285" font-size="12" fill="white" text-anchor="middle" font-weight="600">Send Request</text>

          <!-- 响应状态 -->
          <rect x="70" y="305" width="55" height="20" rx="4" fill="#28a745" opacity="0.2"/>
          <text x="97" y="318" font-size="9" fill="#28a745" text-anchor="middle" font-weight="bold">200 OK</text>
        </g>
      </g>

      <!-- 右侧 ShowDoc -->
      <g class="showdoc-panel" filter="url(#intShadow)">
        <rect x="330" y="90" width="220" height="240" rx="16" :fill="cardBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 标题栏 -->
        <rect x="340" y="100" width="200" height="35" rx="8" fill="url(#showDocGradient)" opacity="0.15"/>
        <text x="440" y="123" font-size="14" :fill="textColor" text-anchor="middle" font-weight="700">ShowDoc</text>

        <!-- 文档界面 -->
        <g class="doc-panel">
          <!-- API 标题 -->
          <text x="350" y="160" font-size="12" :fill="textColor" font-weight="600">POST /api/users</text>
          <text x="350" y="178" font-size="9" :fill="textSecondary">Create User API</text>

          <!-- 参数文档 -->
          <rect x="350" y="190" width="180" height="20" rx="4" :fill="tableBg"/>
          <text x="360" y="204" font-size="9" :fill="textColor" font-weight="600">Parameters</text>

          <rect x="350" y="210" width="180" height="18" :fill="tableRowBg" :stroke="borderColor" stroke-width="0.5"/>
          <text x="360" y="222" font-size="8" :fill="textSecondary">name</text>
          <text x="420" y="222" font-size="8" :fill="textSecondary">string</text>
          <text x="475" y="222" font-size="8" :fill="textSecondary">Username</text>

          <rect x="350" y="228" width="180" height="18" :fill="cardBg" :stroke="borderColor" stroke-width="0.5"/>
          <text x="360" y="240" font-size="8" :fill="textSecondary">age</text>
          <text x="420" y="240" font-size="8" :fill="textSecondary">number</text>
          <text x="475" y="240" font-size="8" :fill="textSecondary">Age</text>

          <!-- 示例代码 -->
          <text x="350" y="265" font-size="9" :fill="textColor" font-weight="600">Example Code</text>
          <rect x="350" y="270" width="180" height="40" rx="6" :fill="codeBg"/>
          <text x="360" y="286" font-size="8" fill="#82aaff" font-family="monospace">POST /api/users</text>
          <text x="360" y="297" font-size="8" fill="#c3e88d" font-family="monospace">{"name":"test"}</text>

          <!-- 自动同步标识 -->
          <g class="sync-badge">
            <rect x="480" y="148" width="45" height="18" rx="4" fill="#28a745" opacity="0.2"/>
            <circle cx="488" cy="157" r="3" fill="#28a745" class="sync-dot"/>
            <text x="510" y="160" font-size="8" fill="#28a745" text-anchor="middle" font-weight="bold">Sync</text>
          </g>
        </g>
      </g>

      <!-- 中央连接区域 -->
      <g class="integration-connection">
        <!-- 双向箭头背景 -->
        <rect x="260" y="195" width="80" height="40" rx="20" :fill="connectionBg" opacity="0.9" filter="url(#intShadow)"/>
        
        <!-- 左箭头 -->
        <path d="M 280 215 L 295 215 M 282 210 L 280 215 L 282 220" 
              stroke="url(#integrationGradient)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"
              class="arrow-flow-1"/>
        
        <!-- 右箭头 -->
        <path d="M 305 215 L 320 215 M 318 210 L 320 215 L 318 220" 
              stroke="url(#integrationGradient)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"
              class="arrow-flow-2"/>

        <!-- 同步图标 -->
        <g transform="translate(300, 215)" class="sync-icon-center">
          <circle r="8" fill="url(#integrationGradient)" opacity="0.2"/>
          <path d="M 3 -5 A 5 5 0 1 1 -3 0" fill="none" stroke="url(#integrationGradient)" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M 2 -6 L 3 -5 L 4 -6" stroke="url(#integrationGradient)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
          <path d="M -3 5 A 5 5 0 1 1 3 0" fill="none" stroke="url(#integrationGradient)" stroke-width="1.5" stroke-linecap="round"/>
          <path d="M -2 6 L -3 5 L -4 6" stroke="url(#integrationGradient)" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        </g>

        <!-- 流动数据粒子 -->
        <g class="data-particles">
          <circle r="2.5" fill="#6f42c1" opacity="0.8" filter="url(#glow)">
            <animateMotion dur="2s" repeatCount="indefinite" path="M 270 215 L 330 215"/>
          </circle>
          <circle r="2.5" fill="#007bff" opacity="0.8" filter="url(#glow)">
            <animateMotion dur="2s" repeatCount="indefinite" begin="1s" path="M 330 215 L 270 215"/>
          </circle>
        </g>
      </g>

      <!-- 底部特性标签 -->
      <g class="feature-badges">
        <rect x="150" y="350" width="80" height="24" rx="12" :fill="badgeBg" :stroke="borderColor" stroke-width="1"/>
        <text x="190" y="366" font-size="10" :fill="textColor" text-anchor="middle" font-weight="600">Real-time Sync</text>

        <rect x="260" y="350" width="80" height="24" rx="12" :fill="badgeBg" :stroke="borderColor" stroke-width="1"/>
        <text x="300" y="366" font-size="10" :fill="textColor" text-anchor="middle" font-weight="600">Seamless</text>

        <rect x="370" y="350" width="80" height="24" rx="12" :fill="badgeBg" :stroke="borderColor" stroke-width="1"/>
        <text x="410" y="366" font-size="10" :fill="textColor" text-anchor="middle" font-weight="600">Efficient</text>
      </g>

      <!-- 装饰图标 -->
      <g class="deco-icons">
        <!-- 前端图标 -->
        <circle cx="120" cy="50" r="20" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="float-icon-1"/>
        <text x="120" y="57" font-size="12" fill="#007bff" text-anchor="middle" font-weight="bold">Client</text>

        <!-- 后端图标 -->
        <circle cx="480" cy="50" r="20" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="float-icon-2"/>
        <text x="480" y="57" font-size="12" fill="#6f42c1" text-anchor="middle" font-weight="bold">Web</text>
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app'

const appStore = useAppStore()
const isDark = computed(() => appStore.theme === 'dark')

const cardBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const iconBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const inputBg = computed(() => isDark.value ? '#1e1e1e' : '#f8f9fa')
const codeBg = computed(() => isDark.value ? '#1e1e1e' : '#0d1117')
const tableBg = computed(() => isDark.value ? '#1e1e1e' : '#f0f7ff')
const tableRowBg = computed(() => isDark.value ? '#252525' : '#f9f9f9')
const connectionBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const badgeBg = computed(() => isDark.value ? '#2d2d2d' : '#f8f9fa')
const textColor = computed(() => isDark.value ? '#e0e0e0' : '#343a40')
const textSecondary = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const borderColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)')
const purpleCircleColor = computed(() => 'rgba(111, 66, 193, 0.15)')
const blueCircleColor = computed(() => 'rgba(0, 123, 255, 0.15)')
</script>

<style lang="scss" scoped>
.integration-illustration {
  width: 100%;
  height: 100%;
  overflow: hidden;

  svg {
    width: 100%;
    height: auto;
    display: block;
  }
}

.debug-button {
  cursor: pointer;
  transition: all 0.3s ease;

  &:hover {
    opacity: 0.9;
  }
}

.sync-icon-center {
  animation: rotate 4s linear infinite;
  transform-origin: 300px 215px;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.sync-dot {
  animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.3; }
}

.arrow-flow-1 {
  animation: flowRight 2s ease-in-out infinite;
}

.arrow-flow-2 {
  animation: flowLeft 2s ease-in-out infinite;
  animation-delay: 1s;
}

@keyframes flowRight {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

@keyframes flowLeft {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

.runapi-panel {
  animation: slideInLeft 1s ease-out;
}

.showdoc-panel {
  animation: slideInRight 1s ease-out;
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.float-icon-1,
.float-icon-2 {
  animation: float 3s ease-in-out infinite;
}

.float-icon-2 {
  animation-delay: 1s;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.feature-badges {
  animation: fadeIn 1s ease-out;
  animation-delay: 0.5s;
  animation-fill-mode: both;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>

