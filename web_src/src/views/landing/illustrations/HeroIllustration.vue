<template>
  <div class="hero-illustration">
    <svg viewBox="0 0 700 500" xmlns="http://www.w3.org/2000/svg">
      <!-- 定义渐变和滤镜 -->
      <defs>
        <!-- 主渐变 - 蓝绿渐变 -->
        <linearGradient id="heroGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" :style="`stop-color:${primaryColor};stop-opacity:1`" />
          <stop offset="100%" :style="`stop-color:${successColor};stop-opacity:1`" />
        </linearGradient>

        <!-- 发光效果 -->
        <filter id="glow">
          <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
          <feMerge>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>

        <!-- 阴影效果 -->
        <filter id="heroShadow">
          <feDropShadow dx="0" dy="8" stdDeviation="12" :flood-color="shadowColor" flood-opacity="0.3"/>
        </filter>

        <!-- 背景网格图案 -->
        <pattern id="gridPattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
          <circle cx="2" cy="2" r="1.5" :fill="gridDotColor" opacity="0.3"/>
        </pattern>

        <!-- 代码高亮渐变 -->
        <linearGradient id="codeGlow" x1="0%" y1="0%" x2="100%" y2="0%">
          <stop offset="0%" style="stop-color:#28a745;stop-opacity:0" />
          <stop offset="50%" style="stop-color:#28a745;stop-opacity:0.8" />
          <stop offset="100%" style="stop-color:#28a745;stop-opacity:0" />
        </linearGradient>
      </defs>

      <!-- 背景网格 -->
      <rect x="0" y="0" width="700" height="500" fill="url(#gridPattern)" opacity="0.4"/>

      <!-- 背景装饰圆 - 更大更多 -->
      <circle cx="550" cy="100" r="180" :fill="primaryColor" opacity="0.05" class="bg-circle-1"/>
      <circle cx="120" cy="400" r="150" :fill="successColor" opacity="0.05" class="bg-circle-2"/>
      <circle cx="650" cy="450" r="100" :fill="warningColor" opacity="0.05" class="bg-circle-3"/>

      <!-- ========== 左侧：代码编辑器 ========== -->
      <g class="code-editor" filter="url(#heroShadow)">
        <!-- 编辑器窗口 -->
        <rect x="30" y="120" width="220" height="280" rx="16" :fill="editorBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 编辑器头部 -->
        <rect x="30" y="120" width="220" height="35" rx="16" :fill="editorHeaderBg"/>
        <rect x="30" y="140" width="220" height="16" :fill="editorHeaderBg"/>
        <circle cx="48" cy="137" r="5" fill="#ff5f57"/>
        <circle cx="65" cy="137" r="5" fill="#ffbd2e"/>
        <circle cx="82" cy="137" r="5" fill="#28c840"/>

        <!-- 代码内容 - 有语法高亮效果 -->
        <g class="code-content">
          <!-- 注释行 -->
          <text x="45" y="180" font-size="10" fill="#6c757d" font-family="Monaco, monospace">// API Definition</text>
          
          <!-- 函数定义 - 带高亮动画 -->
          <rect x="40" y="190" width="190" height="16" fill="url(#codeGlow)" opacity="0" class="code-highlight-1"/>
          <text x="45" y="200" font-size="11" :fill="successColor" font-family="Monaco, monospace" font-weight="600">function</text>
          <text x="100" y="200" font-size="11" :fill="textColor" font-family="Monaco, monospace"> getUserInfo()</text>
          
          <!-- 代码块内容 -->
          <text x="45" y="220" font-size="10" :fill="textSecondary" font-family="Monaco, monospace">{</text>
          
          <rect x="40" y="228" width="190" height="16" fill="url(#codeGlow)" opacity="0" class="code-highlight-2"/>
          <text x="60" y="238" font-size="10" :fill="warningColor" font-family="Monaco, monospace">const</text>
          <text x="95" y="238" font-size="10" :fill="textColor" font-family="Monaco, monospace"> url =</text>
          <text x="130" y="238" font-size="10" fill="#28a745" font-family="Monaco, monospace">'/api/user'</text>
          
          <rect x="40" y="248" width="190" height="16" fill="url(#codeGlow)" opacity="0" class="code-highlight-3"/>
          <text x="60" y="258" font-size="10" :fill="warningColor" font-family="Monaco, monospace">return</text>
          <text x="105" y="258" font-size="10" :fill="primaryColor" font-family="Monaco, monospace"> fetch</text>
          <text x="140" y="258" font-size="10" :fill="textColor" font-family="Monaco, monospace">(url)</text>
          
          <text x="45" y="278" font-size="10" :fill="textSecondary" font-family="Monaco, monospace">}</text>

          <!-- Swagger 注释 -->
          <text x="45" y="300" font-size="9" fill="#6c757d" font-family="Monaco, monospace">@swagger</text>
          
          <!-- HTTP 方法标签 -->
          <g class="method-badge">
            <rect x="45" y="315" width="40" height="20" rx="6" :fill="successColor"/>
            <text x="65" y="329" font-size="10" fill="white" text-anchor="middle" font-weight="bold">GET</text>
          </g>
          
          <g class="method-badge">
            <rect x="92" y="315" width="45" height="20" rx="6" :fill="primaryColor"/>
            <text x="114" y="329" font-size="10" fill="white" text-anchor="middle" font-weight="bold">POST</text>
          </g>

          <!-- 光标闪烁 -->
          <rect x="170" y="252" width="2" height="12" :fill="primaryColor" class="cursor-blink"/>
        </g>

        <!-- 代码编辑器底部状态栏 -->
        <rect x="30" y="385" width="220" height="15" :fill="editorHeaderBg"/>
        <text x="40" y="396" font-size="8" :fill="textSecondary">main.js</text>
        <text x="200" y="396" font-size="8" :fill="successColor" text-anchor="end">✓ Saved</text>
      </g>

      <!-- ========== 中央：文档展示 ========== -->
      <g class="main-document" filter="url(#heroShadow)">
        <!-- 主文档容器 -->
        <rect x="280" y="80" width="260" height="340" rx="20" :fill="documentBg" :stroke="borderColor" stroke-width="3" class="doc-pulse"/>
        
        <!-- 顶部装饰条 -->
        <rect x="280" y="80" width="260" height="45" rx="20" fill="url(#heroGradient)" opacity="0.12"/>
        <rect x="280" y="110" width="260" height="16" fill="url(#heroGradient)" opacity="0.08"/>
        
        <!-- ShowDoc Logo 区域 -->
        <text x="420" y="108" font-size="18" fill="url(#heroGradient)" text-anchor="middle" font-weight="bold">ShowDoc</text>
        
        <!-- 文档标题 -->
        <rect x="295" y="140" width="230" height="35" rx="10" :fill="cardBg"/>
        <text x="310" y="163" font-size="14" :fill="textColor" font-weight="600">User Management API Docs</text>
        <circle cx="500" cy="157" r="4" :fill="successColor" class="status-dot"/>
        
        <!-- API 接口列表 -->
        <!-- 接口 1 -->
        <g class="api-item">
          <rect x="295" y="190" width="230" height="40" rx="8" :fill="cardBg" :stroke="borderColor" stroke-width="1"/>
          <rect x="305" y="200" width="40" height="20" rx="5" :fill="successColor" opacity="0.15"/>
          <text x="325" y="214" font-size="10" :fill="successColor" text-anchor="middle" font-weight="bold">GET</text>
          <text x="355" y="210" font-size="11" :fill="textColor" font-weight="500">/api/users</text>
          <text x="355" y="223" font-size="9" :fill="textSecondary">Get Users List</text>
          <circle cx="508" cy="210" r="3" :fill="successColor"/>
        </g>

        <!-- 接口 2 -->
        <g class="api-item">
          <rect x="295" y="240" width="230" height="40" rx="8" :fill="cardBg" :stroke="borderColor" stroke-width="1"/>
          <rect x="305" y="250" width="45" height="20" rx="5" :fill="primaryColor" opacity="0.15"/>
          <text x="327" y="264" font-size="10" :fill="primaryColor" text-anchor="middle" font-weight="bold">POST</text>
          <text x="360" y="260" font-size="11" :fill="textColor" font-weight="500">/api/users</text>
          <text x="360" y="273" font-size="9" :fill="textSecondary">Create User</text>
          <circle cx="508" cy="260" r="3" :fill="primaryColor"/>
        </g>

        <!-- 接口 3 -->
        <g class="api-item">
          <rect x="295" y="290" width="230" height="40" rx="8" :fill="cardBg" :stroke="borderColor" stroke-width="1"/>
          <rect x="305" y="300" width="40" height="20" rx="5" :fill="warningColor" opacity="0.15"/>
          <text x="325" y="314" font-size="10" :fill="warningColor" text-anchor="middle" font-weight="bold">PUT</text>
          <text x="355" y="310" font-size="11" :fill="textColor" font-weight="500">/api/users/:id</text>
          <text x="355" y="323" font-size="9" :fill="textSecondary">Update User</text>
          <circle cx="508" cy="310" r="3" :fill="warningColor"/>
        </g>

        <!-- 底部统计信息 -->
        <g class="stats-bar">
          <rect x="295" y="350" width="230" height="55" rx="10" fill="url(#heroGradient)" opacity="0.08"/>
          
          <!-- 接口数量 -->
          <text x="310" y="370" font-size="10" :fill="textSecondary">Total APIs</text>
          <text x="310" y="390" font-size="20" fill="url(#heroGradient)" font-weight="bold">24</text>
          
          <!-- 团队成员 -->
          <text x="390" y="368" font-size="10" :fill="textSecondary">Team Members</text>
          <g transform="translate(390, 382)">
            <circle cx="0" cy="0" r="8" :fill="primaryColor"/>
            <circle cx="14" cy="0" r="8" :fill="successColor"/>
            <circle cx="28" cy="0" r="8" :fill="warningColor"/>
            <text x="42" y="4" font-size="12" :fill="textColor" font-weight="600">+5</text>
          </g>
          
          <!-- 状态指示 -->
          <circle cx="500" cy="380" r="5" :fill="successColor" class="pulse-dot"/>
          <text x="510" y="384" font-size="9" :fill="successColor">Real-time</text>
        </g>
      </g>

      <!-- ========== 右侧：应用展示 ========== -->
      <!-- 移动端预览 -->
      <g class="mobile-preview float-device-1">
        <rect x="580" y="150" width="90" height="160" rx="12" :fill="deviceBg" :stroke="borderColor" stroke-width="2" filter="url(#heroShadow)"/>
        <rect x="585" y="155" width="80" height="140" rx="8" :fill="screenBg"/>
        <rect x="605" y="158" width="40" height="3" rx="2" :fill="borderColor"/>
        
        <!-- 屏幕内容 -->
        <rect x="590" y="170" width="70" height="8" rx="2" fill="url(#heroGradient)" opacity="0.3"/>
        <rect x="590" y="185" width="60" height="4" rx="2" :fill="textSecondary" opacity="0.3"/>
        <rect x="590" y="193" width="65" height="4" rx="2" :fill="textSecondary" opacity="0.3"/>
        <rect x="590" y="210" width="70" height="30" rx="6" :fill="cardBg"/>
        <circle cx="605" cy="225" r="8" :fill="successColor" opacity="0.2"/>
      </g>

      <!-- 桌面端浏览器 -->
      <g class="browser-preview float-device-2">
        <rect x="565" y="280" width="120" height="100" rx="10" :fill="deviceBg" :stroke="borderColor" stroke-width="2" filter="url(#heroShadow)"/>
        <rect x="565" y="280" width="120" height="18" rx="10" :fill="editorHeaderBg"/>
        <rect x="565" y="293" width="120" height="6" :fill="editorHeaderBg"/>
        <circle cx="575" cy="289" r="3" fill="#ff5f57"/>
        <circle cx="585" cy="289" r="3" fill="#ffbd2e"/>
        <circle cx="595" cy="289" r="3" fill="#28c840"/>
        
        <!-- 浏览器内容 -->
        <rect x="572" y="305" width="106" height="10" rx="3" fill="url(#heroGradient)" opacity="0.2"/>
        <rect x="572" y="322" width="90" height="6" rx="2" :fill="textSecondary" opacity="0.3"/>
        <rect x="572" y="332" width="95" height="6" rx="2" :fill="textSecondary" opacity="0.3"/>
        <rect x="572" y="345" width="50" height="20" rx="5" :fill="successColor" opacity="0.2"/>
        <text x="597" y="359" font-size="10" :fill="successColor" text-anchor="middle" font-weight="600">200</text>
      </g>

      <!-- 团队成员头像组 -->
      <g class="team-avatars">
        <g class="avatar float-avatar-1" transform="translate(570, 80)">
          <circle r="22" :fill="primaryColor" opacity="0.2" filter="url(#glow)"/>
          <circle r="18" :fill="primaryColor"/>
          <text y="6" font-size="14" fill="white" text-anchor="middle" font-weight="bold">A</text>
        </g>

        <g class="avatar float-avatar-2" transform="translate(620, 100)">
          <circle r="22" :fill="successColor" opacity="0.2" filter="url(#glow)"/>
          <circle r="18" :fill="successColor"/>
          <text y="6" font-size="14" fill="white" text-anchor="middle" font-weight="bold">B</text>
        </g>

        <g class="avatar float-avatar-3" transform="translate(655, 65)">
          <circle r="22" :fill="warningColor" opacity="0.2" filter="url(#glow)"/>
          <circle r="18" :fill="warningColor"/>
          <text y="6" font-size="14" fill="white" text-anchor="middle" font-weight="bold">C</text>
        </g>
      </g>

      <!-- ========== 数据流动效果 ========== -->
      <!-- 从代码到文档的数据流 -->
      <g class="data-flow">
        <!-- 连接线 -->
        <path d="M 250 250 Q 265 240 280 240" :stroke="primaryColor" stroke-width="2" stroke-dasharray="4 4" opacity="0.4" class="flow-line"/>
        <path d="M 540 240 Q 555 200 580 180" :stroke="successColor" stroke-width="2" stroke-dasharray="4 4" opacity="0.4" class="flow-line"/>
        <path d="M 540 300 Q 555 310 570 320" :stroke="warningColor" stroke-width="2" stroke-dasharray="4 4" opacity="0.4" class="flow-line"/>

        <!-- 流动的数据包 -->
        <g class="data-packet-1" filter="url(#glow)">
          <circle r="4" :fill="primaryColor">
            <animateMotion dur="2.5s" repeatCount="indefinite"
              path="M 250 250 Q 265 240 280 240"/>
          </circle>
        </g>

        <g class="data-packet-2" filter="url(#glow)">
          <circle r="4" :fill="successColor">
            <animateMotion dur="2.8s" repeatCount="indefinite"
              path="M 540 240 Q 555 200 580 180"/>
          </circle>
        </g>

        <g class="data-packet-3" filter="url(#glow)">
          <circle r="4" :fill="warningColor">
            <animateMotion dur="3s" repeatCount="indefinite"
              path="M 540 300 Q 555 310 570 320"/>
          </circle>
        </g>

        <!-- 从文档到团队的连接 -->
        <path d="M 520 150 Q 545 130 570 105" :stroke="primaryColor" stroke-width="2" stroke-dasharray="4 4" opacity="0.3"/>
        <circle r="3" :fill="primaryColor" opacity="0.8">
          <animateMotion dur="2s" repeatCount="indefinite"
            path="M 520 150 Q 545 130 570 105"/>
        </circle>
      </g>

      <!-- ========== 浮动装饰图标 ========== -->
      <!-- API 标签 -->
      <g class="float-tag float-tag-1">
        <rect x="70" y="50" width="60" height="28" rx="14" :fill="primaryColor" opacity="0.15" :stroke="primaryColor" stroke-width="1.5"/>
        <text x="100" y="68" font-size="11" :fill="primaryColor" text-anchor="middle" font-weight="600">API</text>
      </g>

      <!-- Markdown 标签 -->
      <g class="float-tag float-tag-2">
        <rect x="30" y="420" width="80" height="28" rx="14" :fill="successColor" opacity="0.15" :stroke="successColor" stroke-width="1.5"/>
        <text x="70" y="438" font-size="11" :fill="successColor" text-anchor="middle" font-weight="600">Markdown</text>
      </g>

      <!-- Real-time 标签 -->
      <g class="float-tag float-tag-3">
        <rect x="600" y="410" width="80" height="28" rx="14" :fill="warningColor" opacity="0.15" :stroke="warningColor" stroke-width="1.5"/>
        <circle cx="615" cy="424" r="4" :fill="warningColor" class="pulse-dot-2"/>
        <text x="650" y="428" font-size="11" :fill="warningColor" text-anchor="middle" font-weight="600">Real-time</text>
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app'

const appStore = useAppStore()
const isDark = computed(() => appStore.theme === 'dark')

// 主题颜色系统
const primaryColor = computed(() => isDark.value ? '#4a9eff' : '#007bff')
const successColor = computed(() => isDark.value ? '#46c93a' : '#28a745')
const warningColor = computed(() => isDark.value ? '#ffa940' : '#fd7e14')

// 背景和容器
const documentBg = computed(() => isDark.value ? '#1e1e1e' : '#ffffff')
const editorBg = computed(() => isDark.value ? '#1e1e1e' : '#282c34')
const editorHeaderBg = computed(() => isDark.value ? '#2d2d30' : '#21252b')
const cardBg = computed(() => isDark.value ? '#252525' : '#f8f9fa')
const deviceBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const screenBg = computed(() => isDark.value ? '#000000' : '#f5f5f5')

// 文字颜色
const textColor = computed(() => isDark.value ? '#e0e0e0' : '#2c3e50')
const textSecondary = computed(() => isDark.value ? '#8b949e' : '#6c757d')

// 边框和装饰
const borderColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.1)')
const shadowColor = computed(() => isDark.value ? '#000000' : '#000000')
const gridDotColor = computed(() => isDark.value ? '#4a9eff' : '#007bff')
</script>

<style lang="scss" scoped>
.hero-illustration {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;

  svg {
    width: 100%;
    height: auto;
    max-height: 500px;
    display: block;
  }
}

// ========== 背景装饰动画 ==========
.bg-circle-1 {
  animation: float 8s ease-in-out infinite;
}

.bg-circle-2 {
  animation: float 10s ease-in-out infinite;
  animation-delay: 1s;
}

.bg-circle-3 {
  animation: float 12s ease-in-out infinite;
  animation-delay: 2s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0);
  }
  33% {
    transform: translate(10px, -15px);
  }
  66% {
    transform: translate(-10px, 15px);
  }
}

// ========== 代码编辑器动画 ==========
.code-editor {
  animation: slideInLeft 1s ease-out, editorFloat 5s ease-in-out 1s infinite;
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes editorFloat {
  0%, 100% {
    transform: translateY(0px) translateX(0px);
  }
  50% {
    transform: translateY(-12px) translateX(5px);
  }
}

// 代码高亮动画
.code-highlight-1 {
  animation: codeHighlight 4s ease-in-out infinite;
}

.code-highlight-2 {
  animation: codeHighlight 4s ease-in-out infinite;
  animation-delay: 1.3s;
}

.code-highlight-3 {
  animation: codeHighlight 4s ease-in-out infinite;
  animation-delay: 2.6s;
}

@keyframes codeHighlight {
  0%, 100% {
    opacity: 0;
  }
  10%, 20% {
    opacity: 0.3;
  }
  30%, 100% {
    opacity: 0;
  }
}

// 光标闪烁
.cursor-blink {
  animation: blink 1s step-end infinite;
}

@keyframes blink {
  0%, 50% {
    opacity: 1;
  }
  51%, 100% {
    opacity: 0;
  }
}

// 方法标签动画 - 移除了，跟随整个编辑器一起动
// .method-badge {
//   animation: badgePulse 3s ease-in-out infinite;
// }

// ========== 主文档动画 ==========
.main-document {
  animation: slideInCenter 1s ease-out;
}

@keyframes slideInCenter {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.doc-pulse {
  animation: docGlow 4s ease-in-out infinite;
}

@keyframes docGlow {
  0%, 100% {
    filter: drop-shadow(0 8px 16px rgba(0, 123, 255, 0.1));
  }
  50% {
    filter: drop-shadow(0 12px 24px rgba(0, 123, 255, 0.2));
  }
}

// API 项目动画
.api-item {
  animation: itemSlideIn 0.5s ease-out;
  
  &:nth-child(1) {
    animation-delay: 0.2s;
  }
  &:nth-child(2) {
    animation-delay: 0.4s;
  }
  &:nth-child(3) {
    animation-delay: 0.6s;
  }
}

@keyframes itemSlideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

// 状态点动画
.status-dot {
  animation: statusBlink 2s ease-in-out infinite;
}

@keyframes statusBlink {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.3;
  }
}

// 脉冲点
.pulse-dot, .pulse-dot-2 {
  animation: pulse 2s ease-out infinite;
}

.pulse-dot-2 {
  animation-delay: 1s;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 currentColor;
  }
  70% {
    box-shadow: 0 0 0 10px rgba(0, 0, 0, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(0, 0, 0, 0);
  }
}

// ========== 右侧设备动画 ==========
.mobile-preview, .browser-preview {
  animation: slideInRight 1s ease-out;
}

.float-device-1 {
  animation: floatDevice 4s ease-in-out infinite;
}

.float-device-2 {
  animation: floatDevice 5s ease-in-out infinite;
  animation-delay: 1s;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes floatDevice {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-12px) rotate(2deg);
  }
}

// ========== 团队头像动画 ==========
.avatar {
  animation: avatarFloat 3s ease-in-out infinite;
  transform-origin: center;
}

.float-avatar-1 {
  animation-delay: 0s;
}

.float-avatar-2 {
  animation-delay: 0.7s;
}

.float-avatar-3 {
  animation-delay: 1.4s;
}

@keyframes avatarFloat {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-8px);
  }
}

// ========== 标签动画 ==========
.float-tag {
  animation: tagFloat 4s ease-in-out infinite;
}

.float-tag-1 {
  animation-delay: 0s;
}

.float-tag-2 {
  animation-delay: 1.3s;
}

.float-tag-3 {
  animation-delay: 2.6s;
}

@keyframes tagFloat {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-10px) rotate(-2deg);
  }
}

// ========== 数据流动画 ==========
.flow-line {
  stroke-dasharray: 300;
  animation: flowDash 3s linear infinite;
}

@keyframes flowDash {
  to {
    stroke-dashoffset: -300;
  }
}
</style>

