<template>
  <div class="ai-collab-illustration">
    <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="aiCollabGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#6366f1" />
          <stop offset="100%" style="stop-color:#4338ca" />
        </linearGradient>

        <filter id="aiCollabShadow">
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

        <filter id="aiGlow">
          <feGaussianBlur stdDeviation="4" result="coloredBlur"/>
          <feMerge>
            <feMergeNode in="coloredBlur"/>
            <feMergeNode in="SourceGraphic"/>
          </feMerge>
        </filter>

        <marker id="arrowIndigo" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
          <polygon points="0 0, 10 3, 0 6" fill="#6366f1"/>
        </marker>
      </defs>

      <!-- 背景装饰 -->
      <circle cx="480" cy="100" r="90" :fill="bgCircleColor" opacity="0.3"/>
      <circle cx="100" cy="330" r="70" :fill="bgCircleColor" opacity="0.2"/>

      <!-- 左侧文档面板 -->
      <g class="doc-panel" filter="url(#aiCollabShadow)">
        <rect x="50" y="100" width="200" height="220" rx="12" :fill="documentBg" :stroke="borderColor" stroke-width="2"/>

        <!-- 文档标题栏 -->
        <rect x="50" y="100" width="200" height="30" rx="12" fill="url(#aiCollabGradient)" opacity="0.15"/>
        <text x="150" y="120" font-size="11" :fill="textColor" text-anchor="middle" font-weight="600">API Docs</text>

        <!-- 文档内容 -->
        <g>
          <text x="65" y="155" font-size="11" :fill="textColor" font-weight="600">POST /api/login</text>
          <text x="65" y="172" font-size="9" :fill="textSecondary">User Login API</text>

          <!-- 参数表格 -->
          <rect x="65" y="185" width="170" height="18" rx="4" :fill="tableBg"/>
          <text x="73" y="197" font-size="8" :fill="textColor" font-weight="600">Parameters</text>

          <rect x="65" y="203" width="170" height="18" :fill="tableRowBg" :stroke="borderColor" stroke-width="0.5"/>
          <text x="73" y="215" font-size="8" :fill="textSecondary">username</text>
          <text x="180" y="215" font-size="8" :fill="textSecondary">string</text>

          <rect x="65" y="221" width="170" height="18" :fill="documentBg" :stroke="borderColor" stroke-width="0.5"/>
          <text x="73" y="233" font-size="8" :fill="textSecondary">password</text>
          <text x="180" y="233" font-size="8" :fill="textSecondary">string</text>

          <!-- 返回示例 -->
          <text x="65" y="258" font-size="9" :fill="textColor" font-weight="600">Response</text>
          <rect x="65" y="263" width="170" height="30" rx="4" :fill="codeBg"/>
          <text x="73" y="278" font-size="8" fill="#28a745" font-family="monospace">{ "code": 200,</text>
          <text x="73" y="288" font-size="8" fill="#007bff" font-family="monospace">  "token": "..." }</text>

          <!-- 更新标记 -->
          <g class="update-badge">
            <rect x="150" y="296" width="85" height="16" rx="8" fill="#28a745" opacity="0.15"/>
            <circle cx="162" cy="304" r="3" fill="#28a745"/>
            <text x="170" y="307" font-size="8" fill="#28a745" font-weight="600">Updated</text>
          </g>
        </g>
      </g>

      <!-- 中央 MCP 连接区域 -->
      <g class="mcp-connection">
        <!-- 连接圆环 -->
        <g class="magic-circle">
          <circle cx="300" cy="210" r="50" fill="url(#aiCollabGradient)" opacity="0.1" filter="url(#aiGlow)" class="magic-pulse"/>
          <circle cx="300" cy="210" r="40" fill="none" stroke="url(#aiCollabGradient)" stroke-width="2" opacity="0.5" class="magic-rotate"/>
          <circle cx="300" cy="210" r="30" fill="none" stroke="url(#aiCollabGradient)" stroke-width="2" opacity="0.3" class="magic-rotate-reverse"/>
        </g>

        <!-- MCP 图标 -->
        <g transform="translate(300, 210)" class="mcp-icon">
          <circle r="16" fill="url(#aiCollabGradient)" filter="url(#aiGlow)"/>
          <text y="4" font-size="9" fill="white" text-anchor="middle" font-weight="bold" font-family="monospace">MCP</text>
        </g>

        <!-- 节点小图标 -->
        <g class="node-icons">
          <circle cx="268" cy="150" r="12" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="tool-icon-1"/>
          <circle cx="340" cy="160" r="12" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="tool-icon-2"/>
          <circle cx="322" cy="285" r="12" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="tool-icon-3"/>
        </g>

        <!-- 流动粒子 -->
        <g class="particles">
          <circle r="3" fill="#6366f1" opacity="0.8" filter="url(#aiGlow)">
            <animateMotion dur="2s" repeatCount="indefinite" path="M 250 210 L 300 210"/>
          </circle>
          <circle r="3" fill="#4338ca" opacity="0.8" filter="url(#aiGlow)">
            <animateMotion dur="2s" repeatCount="indefinite" begin="0.5s" path="M 250 210 L 300 210"/>
          </circle>
          <circle r="3" fill="#6366f1" opacity="0.8" filter="url(#aiGlow)">
            <animateMotion dur="2s" repeatCount="indefinite" begin="1s" path="M 300 210 L 350 210"/>
          </circle>
        </g>

        <!-- 箭头 -->
        <path d="M 250 210 L 280 210" stroke="#6366f1" stroke-width="2" marker-end="url(#arrowIndigo)" opacity="0.6"/>
        <path d="M 320 210 L 350 210" stroke="#6366f1" stroke-width="2" marker-end="url(#arrowIndigo)" opacity="0.6"/>
      </g>

      <!-- 右侧 AI 编辑器面板 -->
      <g class="ai-editor" filter="url(#aiCollabShadow)">
        <rect x="370" y="100" width="200" height="220" rx="12" :fill="codeBg" :stroke="borderColor" stroke-width="2"/>

        <!-- 编辑器标题栏 -->
        <rect x="370" y="100" width="200" height="30" rx="12" :fill="editorHeaderBg"/>
        <circle cx="385" cy="115" r="4" fill="#ff5f57"/>
        <circle cx="400" cy="115" r="4" fill="#febc2e"/>
        <circle cx="415" cy="115" r="4" fill="#28c840"/>
        <text x="470" y="120" font-size="10" :fill="textSecondary" text-anchor="middle">AI Editor</text>

        <!-- 代码内容 -->
        <g font-family="monospace" font-size="9">
          <text x="380" y="150" fill="#8b949e">1</text>
          <text x="395" y="150" fill="#7ee787">// read latest docs via MCP</text>

          <text x="380" y="164" fill="#8b949e">2</text>
          <text x="395" y="164" fill="#d2a8ff">const</text>
          <text x="425" y="164" :fill="textColor">docs =</text>
          <text x="462" y="164" fill="#ffa657"> await</text>
          <text x="500" y="164" fill="#79c0ff"> mcp</text>

          <text x="380" y="178" fill="#8b949e">3</text>
          <text x="395" y="178" :fill="textColor">  .getDoc(</text>
          <text x="450" y="178" fill="#a5d6ff">'login'</text>
          <text x="488" y="178" :fill="textColor">)</text>

          <text x="380" y="192" fill="#8b949e">4</text>
          <text x="395" y="192" :fill="textColor">{</text>

          <text x="380" y="206" fill="#8b949e">5</text>
          <text x="410" y="206" fill="#d2a8ff">const</text>
          <text x="445" y="206" :fill="textColor">res =</text>
          <text x="480" y="206" fill="#ffa657"> await</text>

          <text x="380" y="220" fill="#8b949e">6</text>
          <text x="410" y="220" fill="#79c0ff">  api</text>
          <text x="432" y="220" :fill="textColor">.</text>
          <text x="437" y="220" fill="#d2a8ff">post</text>
          <text x="462" y="220" :fill="textColor">(</text>
          <text x="470" y="220" fill="#a5d6ff">'/api/login'</text>

          <text x="380" y="234" fill="#8b949e">7</text>
          <text x="410" y="234" :fill="textColor">    body: docs.</text>
          <text x="483" y="234" fill="#79c0ff">params</text>

          <text x="380" y="248" fill="#8b949e">8</text>
          <text x="410" y="248" :fill="textColor">  )</text>

          <text x="380" y="262" fill="#8b949e">9</text>
          <text x="395" y="262" :fill="textColor">}</text>
        </g>

        <!-- AI 补全提示 -->
        <g class="ai-hint">
          <rect x="385" y="275" width="140" height="34" rx="6" fill="url(#aiCollabGradient)" opacity="0.12"/>
          <rect x="385" y="275" width="3" height="34" rx="1.5" fill="url(#aiCollabGradient)"/>
          <text x="397" y="290" font-size="8" :fill="textColor" font-weight="600">AI suggestion</text>
          <text x="397" y="302" font-size="7" :fill="textSecondary" font-family="monospace">based on latest docs ✓</text>
        </g>
      </g>

      <!-- 机器人图标 -->
      <g class="robot-icon float-icon" filter="url(#aiCollabShadow)">
        <circle cx="540" cy="340" r="22" :fill="iconBg" :stroke="borderColor" stroke-width="2"/>
        <!-- 简笔机器人头 -->
        <rect x="530" y="332" width="20" height="14" rx="4" fill="none" stroke="#6366f1" stroke-width="2"/>
        <circle cx="536" cy="339" r="2" fill="#6366f1"/>
        <circle cx="544" cy="339" r="2" fill="#6366f1"/>
        <line x1="540" y1="328" x2="540" y2="332" stroke="#6366f1" stroke-width="2"/>
        <circle cx="540" cy="326" r="2" fill="#6366f1"/>
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
const bgCircleColor = computed(() => isDark.value ? 'rgba(99, 102, 241, 0.1)' : 'rgba(99, 102, 241, 0.1)')
</script>

<style lang="scss" scoped>
.ai-collab-illustration {
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

.mcp-icon {
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

.doc-panel {
  animation: slideInLeft 1s ease-out;
}

.ai-editor {
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
