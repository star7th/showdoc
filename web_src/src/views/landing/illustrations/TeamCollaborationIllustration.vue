<template>
  <div class="team-collaboration-illustration">
    <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="teamGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#007bff" />
          <stop offset="100%" style="stop-color:#0056b3" />
        </linearGradient>

        <filter id="teamShadow">
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
      </defs>

      <!-- 背景装饰 -->
      <circle cx="500" cy="90" r="90" :fill="bgCircleColor" opacity="0.3"/>
      <circle cx="90" cy="330" r="70" :fill="bgCircleColor" opacity="0.2"/>

      <!-- 中央文档 -->
      <g class="central-document" filter="url(#teamShadow)">
        <rect x="230" y="140" width="140" height="180" rx="12" :fill="documentBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 文档标题栏 -->
        <rect x="240" y="150" width="120" height="25" rx="6" fill="url(#teamGradient)" opacity="0.15"/>
        <text x="300" y="167" font-size="11" :fill="textColor" text-anchor="middle" font-weight="600">Project Doc</text>

        <!-- 文档内容 -->
        <rect x="245" y="185" width="110" height="6" rx="3" :fill="textLineColor" opacity="0.6"/>
        <rect x="245" y="200" width="100" height="6" rx="3" :fill="textLineColor" opacity="0.4"/>
        <rect x="245" y="215" width="105" height="6" rx="3" :fill="textLineColor" opacity="0.5"/>

        <!-- 编辑区域 -->
        <rect x="245" y="235" width="110" height="60" rx="6" :fill="editAreaBg" :stroke="borderColor" stroke-width="1"/>
        
        <!-- 多个光标（表示多人编辑） -->
        <line x1="260" y1="245" x2="260" y2="255" :stroke="cursorColor1" stroke-width="2" class="cursor-blink"/>
        <line x1="295" y1="260" x2="295" y2="270" :stroke="cursorColor2" stroke-width="2" class="cursor-blink" style="animation-delay: 0.5s"/>
        <line x1="275" y1="280" x2="275" y2="290" :stroke="cursorColor3" stroke-width="2" class="cursor-blink" style="animation-delay: 1s"/>
      </g>

      <!-- 用户头像 - 左上 -->
      <g class="user-avatar user-1" filter="url(#teamShadow)">
        <circle cx="120" cy="120" r="35" :fill="avatarBg1" :stroke="borderColor" stroke-width="2"/>
        <circle cx="120" cy="115" r="10" :fill="avatarColor1"/>
        <path d="M 105 138 Q 120 130 135 138" :fill="avatarColor1"/>
        
        <!-- 权限图标 - 皇冠 -->
        <g transform="translate(140, 100)">
          <path d="M -4 0 L -2 -6 L 0 -2 L 2 -6 L 4 0 Z" fill="#ffc107"/>
          <rect x="-4" y="0" width="8" height="3" fill="#ffc107"/>
        </g>

        <!-- 连接线 -->
        <path d="M 145 135 L 230 170" :stroke="connectionColor" stroke-width="2" stroke-dasharray="4,4" opacity="0.5" class="connection-animate"/>
      </g>

      <!-- 用户头像 - 右上 -->
      <g class="user-avatar user-2" filter="url(#teamShadow)">
        <circle cx="480" cy="120" r="35" :fill="avatarBg2" :stroke="borderColor" stroke-width="2"/>
        <circle cx="480" cy="115" r="10" :fill="avatarColor2"/>
        <path d="M 465 138 Q 480 130 495 138" :fill="avatarColor2"/>
        
        <!-- 权限图标 - 笔 -->
        <g transform="translate(500, 100)">
          <path d="M -3 -3 L 3 3 M 3 3 L 1 5 L -1 3 Z" :stroke="editIconColor" stroke-width="1.5" fill="none" stroke-linecap="round"/>
        </g>

        <!-- 连接线 -->
        <path d="M 455 135 L 370 170" :stroke="connectionColor" stroke-width="2" stroke-dasharray="4,4" opacity="0.5" class="connection-animate"/>
      </g>

      <!-- 用户头像 - 下方 -->
      <g class="user-avatar user-3" filter="url(#teamShadow)">
        <circle cx="300" cy="360" r="35" :fill="avatarBg3" :stroke="borderColor" stroke-width="2"/>
        <circle cx="300" cy="355" r="10" :fill="avatarColor3"/>
        <path d="M 285 378 Q 300 370 315 378" :fill="avatarColor3"/>
        
        <!-- 权限图标 - 眼睛 -->
        <g transform="translate(320, 340)">
          <ellipse cx="0" cy="0" rx="5" ry="3" fill="none" :stroke="viewIconColor" stroke-width="1.5"/>
          <circle cx="0" cy="0" r="2" :fill="viewIconColor"/>
        </g>

        <!-- 连接线 -->
        <path d="M 300 325 L 300 320" :stroke="connectionColor" stroke-width="2" stroke-dasharray="4,4" opacity="0.5" class="connection-animate"/>
      </g>

      <!-- 评论气泡 -->
      <g class="comment-bubble float-element" filter="url(#teamShadow)">
        <rect x="400" y="240" width="100" height="50" rx="10" :fill="commentBg" :stroke="borderColor" stroke-width="1.5"/>
        <path d="M 430 290 L 425 300 L 440 290 Z" :fill="commentBg"/>
        <text x="450" y="260" font-size="9" :fill="textSecondary" text-anchor="middle">Need Change</text>
        <text x="450" y="273" font-size="9" :fill="textSecondary" text-anchor="middle">This part...</text>
      </g>

      <!-- 版本历史图标 -->
      <g class="history-icon float-element" filter="url(#teamShadow)">
        <circle cx="100" cy="250" r="28" :fill="iconBg" :stroke="borderColor" stroke-width="2"/>
        <circle cx="100" cy="250" r="15" fill="none" :stroke="historyIconColor" stroke-width="2"/>
        <path d="M 100 240 L 100 250 L 108 250" :stroke="historyIconColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M 90 235 L 85 240 L 90 245" :stroke="historyIconColor" stroke-width="2" fill="none" stroke-linecap="round"/>
      </g>

      <!-- 同步图标 -->
      <g class="sync-icon float-element" filter="url(#teamShadow)">
        <circle cx="500" cy="280" r="25" :fill="iconBg" :stroke="borderColor" stroke-width="2"/>
        <g class="sync-rotate">
          <path d="M 505 270 A 10 10 0 1 1 495 280" fill="none" :stroke="syncIconColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M 503 268 L 505 270 L 507 268" :fill="syncIconColor"/>
          <path d="M 495 290 A 10 10 0 1 1 505 280" fill="none" :stroke="syncIconColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M 497 292 L 495 290 L 493 292" :fill="syncIconColor"/>
        </g>
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app'

const appStore = useAppStore()
const isDark = computed(() => appStore.theme === 'dark')

const documentBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const iconBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const editAreaBg = computed(() => isDark.value ? '#1e1e1e' : '#f8f9fa')
const commentBg = computed(() => isDark.value ? '#2d2d2d' : '#fff3cd')
const textColor = computed(() => isDark.value ? '#e0e0e0' : '#343a40')
const textSecondary = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const textLineColor = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const borderColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)')
const bgCircleColor = computed(() => isDark.value ? 'rgba(74, 158, 255, 0.1)' : 'rgba(0, 123, 255, 0.1)')
const connectionColor = computed(() => isDark.value ? '#4a9eff' : '#007bff')

// 用户头像颜色
const avatarBg1 = computed(() => isDark.value ? '#2d2d2d' : '#e3f2fd')
const avatarBg2 = computed(() => isDark.value ? '#2d2d2d' : '#fff3e0')
const avatarBg3 = computed(() => isDark.value ? '#2d2d2d' : '#f3e5f5')
const avatarColor1 = computed(() => isDark.value ? '#4a9eff' : '#007bff')
const avatarColor2 = computed(() => isDark.value ? '#ff8c2e' : '#fd7e14')
const avatarColor3 = computed(() => isDark.value ? '#c77dff' : '#9c27b0')

// 光标颜色
const cursorColor1 = computed(() => '#007bff')
const cursorColor2 = computed(() => '#fd7e14')
const cursorColor3 = computed(() => '#9c27b0')

// 图标颜色
const editIconColor = computed(() => isDark.value ? '#ff8c2e' : '#fd7e14')
const viewIconColor = computed(() => isDark.value ? '#c77dff' : '#9c27b0')
const historyIconColor = computed(() => isDark.value ? '#4a9eff' : '#007bff')
const syncIconColor = computed(() => isDark.value ? '#3fb950' : '#28a745')
</script>

<style lang="scss" scoped>
.team-collaboration-illustration {
  width: 100%;
  height: 100%;
  overflow: hidden;

  svg {
    width: 100%;
    height: auto;
    display: block;
  }
}

.user-avatar {
  animation: float 3s ease-in-out infinite;

  &.user-1 { animation-delay: 0s; }
  &.user-2 { animation-delay: 0.5s; }
  &.user-3 { animation-delay: 1s; }
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.cursor-blink {
  animation: blink 1.5s ease-in-out infinite;
}

@keyframes blink {
  0%, 49% { opacity: 1; }
  50%, 100% { opacity: 0; }
}

.connection-animate {
  animation: dash 3s linear infinite;
  stroke-dasharray: 200;
}

@keyframes dash {
  to {
    stroke-dashoffset: -200;
  }
}

.float-element {
  animation: floatSlow 4s ease-in-out infinite;
}

@keyframes floatSlow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.sync-rotate {
  animation: rotate 3s linear infinite;
  transform-origin: 500px 280px;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.central-document {
  animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}
</style>

