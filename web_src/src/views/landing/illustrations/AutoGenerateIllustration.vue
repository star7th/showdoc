<template>
  <div class="auto-generate-illustration">
    <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="autoGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#ffc107" />
          <stop offset="100%" style="stop-color:#ff8c2e" />
        </linearGradient>

        <filter id="autoShadow">
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
      <circle cx="480" cy="100" r="90" :fill="bgCircleColor" opacity="0.3"/>
      <circle cx="100" cy="330" r="70" :fill="bgCircleColor" opacity="0.2"/>

      <!-- 左侧代码编辑器 -->
      <g class="code-editor" filter="url(#autoShadow)">
        <rect x="50" y="100" width="200" height="220" rx="12" :fill="codeBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 编辑器标题栏 -->
        <rect x="50" y="100" width="200" height="30" rx="12" :fill="editorHeaderBg"/>
        <circle cx="65" cy="115" r="4" fill="#ff5f57"/>
        <circle cx="80" cy="115" r="4" fill="#febc2e"/>
        <circle cx="95" cy="115" r="4" fill="#28c840"/>
        <text x="150" y="120" font-size="10" :fill="textSecondary" text-anchor="middle">main.js</text>

        <!-- 代码内容 -->
        <g font-family="monospace" font-size="9">
          <text x="60" y="150" fill="#8b949e">1</text>
          <text x="75" y="150" fill="#ff7b72">/**</text>
          
          <text x="60" y="164" fill="#8b949e">2</text>
          <text x="75" y="164" fill="#7ee787"> * @api {POST} /api/login</text>
          
          <text x="60" y="178" fill="#8b949e">3</text>
          <text x="75" y="178" fill="#7ee787"> * @param {string} username</text>
          
          <text x="60" y="192" fill="#8b949e">4</text>
          <text x="75" y="192" fill="#7ee787"> * @param {string} password</text>
          
          <text x="60" y="206" fill="#8b949e">5</text>
          <text x="75" y="206" fill="#ff7b72"> */</text>
          
          <text x="60" y="220" fill="#8b949e">6</text>
          <text x="75" y="220" fill="#d2a8ff">function</text>
          <text x="118" y="220" fill="#ffa657"> login</text>
          <text x="143" y="220" :fill="textColor">(username, pwd)</text>
          
          <text x="60" y="234" fill="#8b949e">7</text>
          <text x="75" y="234" :fill="textColor">{</text>
          
          <text x="60" y="248" fill="#8b949e">8</text>
          <text x="85" y="248" fill="#ff7b72">return</text>
          <text x="120" y="248" fill="#79c0ff"> api</text>
          <text x="138" y="248" :fill="textColor">.</text>
          <text x="143" y="248" fill="#d2a8ff">post</text>
          <text x="168" y="248" :fill="textColor">(...)</text>
          
          <text x="60" y="262" fill="#8b949e">9</text>
          <text x="75" y="262" :fill="textColor">}</text>
        </g>
      </g>

      <!-- 中央转换魔法区域 -->
      <g class="transformation-magic">
        <!-- 魔法粒子圆圈 -->
        <g class="magic-circle">
          <circle cx="300" cy="210" r="50" fill="url(#autoGradient)" opacity="0.1" filter="url(#glow)" class="magic-pulse"/>
          <circle cx="300" cy="210" r="40" fill="none" stroke="url(#autoGradient)" stroke-width="2" opacity="0.5" class="magic-rotate"/>
          <circle cx="300" cy="210" r="30" fill="none" stroke="url(#autoGradient)" stroke-width="2" opacity="0.3" class="magic-rotate-reverse"/>
        </g>

        <!-- 闪电/魔法棒图标 -->
        <g transform="translate(300, 210)" class="magic-icon">
          <path d="M -8 -15 L 0 -5 L -5 -5 L 5 10 L -2 0 L 3 0 Z" fill="url(#autoGradient)" filter="url(#glow)"/>
        </g>

        <!-- 工具图标 -->
        <g class="tool-icons">
          <!-- Swagger -->
          <circle cx="280" cy="140" r="18" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="tool-icon-1"/>
          <text x="280" y="146" font-size="8" fill="#85ea2d" text-anchor="middle" font-weight="bold">Swagger</text>

          <!-- Postman -->
          <circle cx="340" cy="150" r="18" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="tool-icon-2"/>
          <text x="340" y="156" font-size="8" fill="#ff6c37" text-anchor="middle" font-weight="bold">Postman</text>

          <!-- Markdown -->
          <circle cx="320" cy="290" r="18" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="tool-icon-3"/>
          <text x="320" y="296" font-size="9" :fill="textColor" text-anchor="middle" font-weight="bold">MD</text>
        </g>

        <!-- 流动粒子 -->
        <g class="particles">
          <circle r="3" fill="#ffc107" opacity="0.8" filter="url(#glow)">
            <animateMotion dur="2s" repeatCount="indefinite" path="M 250 210 L 300 210"/>
          </circle>
          <circle r="3" fill="#ff8c2e" opacity="0.8" filter="url(#glow)">
            <animateMotion dur="2s" repeatCount="indefinite" begin="0.5s" path="M 250 210 L 300 210"/>
          </circle>
          <circle r="3" fill="#ffc107" opacity="0.8" filter="url(#glow)">
            <animateMotion dur="2s" repeatCount="indefinite" begin="1s" path="M 250 210 L 300 210"/>
          </circle>
        </g>

        <!-- 箭头 -->
        <defs>
          <marker id="arrowGold" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
            <polygon points="0 0, 10 3, 0 6" fill="#ffc107"/>
          </marker>
        </defs>
        <path d="M 250 210 L 280 210" stroke="#ffc107" stroke-width="2" marker-end="url(#arrowGold)" opacity="0.6"/>
        <path d="M 320 210 L 350 210" stroke="#ffc107" stroke-width="2" marker-end="url(#arrowGold)" opacity="0.6"/>
      </g>

      <!-- 右侧生成的文档 -->
      <g class="generated-doc" filter="url(#autoShadow)">
        <rect x="370" y="100" width="200" height="220" rx="12" :fill="documentBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 文档标题 -->
        <rect x="380" y="110" width="180" height="30" rx="8" fill="url(#autoGradient)" opacity="0.15"/>
        <text x="470" y="130" font-size="12" :fill="textColor" text-anchor="middle" font-weight="600">API Docs</text>

        <!-- 文档内容 -->
        <g>
          <text x="390" y="160" font-size="11" :fill="textColor" font-weight="600">POST /api/login</text>
          
          <text x="390" y="180" font-size="9" :fill="textSecondary">User Login API</text>
          
          <!-- 参数表格 -->
          <rect x="390" y="195" width="160" height="20" rx="4" :fill="tableBg"/>
          <text x="400" y="209" font-size="9" :fill="textColor" font-weight="600">Request Parameters</text>
          
          <rect x="390" y="215" width="160" height="18" :fill="tableRowBg" :stroke="borderColor" stroke-width="0.5"/>
          <text x="400" y="227" font-size="8" :fill="textSecondary">username</text>
          <text x="490" y="227" font-size="8" :fill="textSecondary">string</text>
          
          <rect x="390" y="233" width="160" height="18" :fill="documentBg" :stroke="borderColor" stroke-width="0.5"/>
          <text x="400" y="245" font-size="8" :fill="textSecondary">password</text>
          <text x="490" y="245" font-size="8" :fill="textSecondary">string</text>

          <!-- 返回示例 -->
          <text x="390" y="270" font-size="9" :fill="textColor" font-weight="600">Response Example</text>
          <rect x="390" y="275" width="160" height="30" rx="4" :fill="codeBg"/>
          <text x="400" y="290" font-size="8" fill="#28a745" font-family="monospace">{ "code": 200,</text>
          <text x="400" y="300" font-size="8" fill="#007bff" font-family="monospace">  "token": "..." }</text>
        </g>
      </g>

      <!-- 成功图标 -->
      <g class="success-icon float-icon" filter="url(#autoShadow)">
        <circle cx="540" cy="340" r="22" :fill="iconBg" :stroke="borderColor" stroke-width="2"/>
        <circle cx="540" cy="340" r="14" fill="none" stroke="#28a745" stroke-width="2"/>
        <path d="M 534 340 L 538 344 L 547 333" stroke="#28a745" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
      </g>
    </svg>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/store/app'

const appStore = useAppStore()
const isDark = computed(() => appStore.theme === 'dark')

const codeBg = computed(() => isDark.value ? '#1e1e1e' : '#0d1117')
const editorHeaderBg = computed(() => isDark.value ? '#2d2d2d' : '#161b22')
const documentBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const iconBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const tableBg = computed(() => isDark.value ? '#1e1e1e' : '#f8f9fa')
const tableRowBg = computed(() => isDark.value ? '#252525' : '#f9f9f9')
const textColor = computed(() => isDark.value ? '#e0e0e0' : '#343a40')
const textSecondary = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const borderColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)')
const bgCircleColor = computed(() => isDark.value ? 'rgba(255, 193, 7, 0.1)' : 'rgba(255, 193, 7, 0.1)')
</script>

<style lang="scss" scoped>
.auto-generate-illustration {
  width: 100%;
  height: 100%;
  overflow: hidden;

  svg {
    width: 100%;
    height: auto;
    display: block;
  }
}

.magic-pulse {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { 
    transform: scale(1);
    opacity: 0.1;
  }
  50% { 
    transform: scale(1.2);
    opacity: 0.2;
  }
}

.magic-rotate {
  animation: rotate 4s linear infinite;
  transform-origin: 300px 210px;
}

.magic-rotate-reverse {
  animation: rotateReverse 3s linear infinite;
  transform-origin: 300px 210px;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@keyframes rotateReverse {
  from { transform: rotate(360deg); }
  to { transform: rotate(0deg); }
}

.magic-icon {
  animation: bounce 1.5s ease-in-out infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.tool-icon-1 {
  animation: float 3s ease-in-out infinite;
}

.tool-icon-2 {
  animation: float 3s ease-in-out infinite;
  animation-delay: 0.5s;
}

.tool-icon-3 {
  animation: float 3s ease-in-out infinite;
  animation-delay: 1s;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.float-icon {
  animation: floatSlow 4s ease-in-out infinite;
}

@keyframes floatSlow {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.code-editor {
  animation: slideInLeft 1s ease-out;
}

.generated-doc {
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
</style>

