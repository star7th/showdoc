<template>
  <div class="api-illustration">
    <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="apiPrimaryGradient" x1="0%" y1="0%" x2="100%" y2="100%">
          <stop offset="0%" style="stop-color:#007bff" />
          <stop offset="100%" style="stop-color:#0056b3" />
        </linearGradient>

        <filter id="apiShadow">
          <feGaussianBlur in="SourceAlpha" stdDeviation="3"/>
          <feOffset dx="0" dy="3" result="offsetblur"/>
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
      <circle cx="480" cy="100" r="100" :fill="bgCircleColor" opacity="0.3"/>
      <circle cx="80" cy="320" r="60" :fill="bgCircleColor" opacity="0.2"/>

      <!-- 左侧 API 请求部分 -->
      <g class="api-request" filter="url(#apiShadow)">
        <rect x="50" y="80" width="240" height="260" rx="16" :fill="cardBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 标题 -->
        <text x="170" y="110" font-size="16" :fill="textColor" text-anchor="middle" font-weight="600">API Request</text>
        
        <!-- HTTP Method 标签 -->
        <g class="method-tags">
          <rect x="70" y="130" width="45" height="24" rx="6" fill="#28a745"/>
          <text x="92" y="147" font-size="11" fill="white" text-anchor="middle" font-weight="bold">GET</text>
          
          <rect x="125" y="130" width="50" height="24" rx="6" fill="#007bff"/>
          <text x="150" y="147" font-size="11" fill="white" text-anchor="middle" font-weight="bold">POST</text>
          
          <rect x="185" y="130" width="45" height="24" rx="6" fill="#fd7e14"/>
          <text x="207" y="147" font-size="11" fill="white" text-anchor="middle" font-weight="bold">PUT</text>
          
          <rect x="240" y="130" width="35" height="24" rx="6" fill="#dc3545"/>
          <text x="257" y="147" font-size="10" fill="white" text-anchor="middle" font-weight="bold">DEL</text>
        </g>

        <!-- URL 输入框 -->
        <rect x="70" y="170" width="210" height="30" rx="6" :fill="inputBg" :stroke="borderColor" stroke-width="1"/>
        <text x="80" y="191" font-size="11" :fill="textSecondary" font-family="monospace">/api/users/login</text>

        <!-- 参数表格 -->
        <g class="params-table">
          <text x="70" y="220" font-size="12" :fill="textColor" font-weight="600">Parameters</text>
          
          <!-- 表格行 -->
          <rect x="70" y="230" width="210" height="22" :fill="tableBg" :stroke="borderColor" stroke-width="1"/>
          <text x="80" y="245" font-size="10" :fill="textSecondary">username</text>
          <text x="180" y="245" font-size="10" :fill="textSecondary">string</text>
          
          <rect x="70" y="252" width="210" height="22" :fill="cardBg" :stroke="borderColor" stroke-width="1"/>
          <text x="80" y="267" font-size="10" :fill="textSecondary">password</text>
          <text x="180" y="267" font-size="10" :fill="textSecondary">string</text>
        </g>

        <!-- 代码示例 -->
        <rect x="70" y="290" width="210" height="35" rx="6" :fill="codeBg"/>
        <text x="80" y="307" font-size="9" fill="#28a745" font-family="monospace">curl -X POST \</text>
        <text x="80" y="318" font-size="9" fill="#007bff" font-family="monospace">  /api/users/login</text>
      </g>

      <!-- 右侧文档界面 -->
      <g class="api-document" filter="url(#apiShadow)">
        <rect x="320" y="80" width="240" height="260" rx="16" :fill="cardBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 标题 -->
        <text x="440" y="110" font-size="16" :fill="textColor" text-anchor="middle" font-weight="600">Documentation</text>
        
        <!-- 文档内容 -->
        <rect x="340" y="130" width="200" height="10" rx="5" :fill="textLineColor" opacity="0.6"/>
        <rect x="340" y="150" width="180" height="8" rx="4" :fill="textLineColor" opacity="0.4"/>
        <rect x="340" y="166" width="190" height="8" rx="4" :fill="textLineColor" opacity="0.5"/>
        
        <!-- Response 示例 -->
        <text x="340" y="195" font-size="12" :fill="textColor" font-weight="600">Response</text>
        <rect x="340" y="205" width="200" height="50" rx="6" :fill="codeBg"/>
        <text x="350" y="223" font-size="9" fill="#28a745" font-family="monospace">{</text>
        <text x="360" y="234" font-size="9" fill="#007bff" font-family="monospace">"status": "success",</text>
        <text x="360" y="245" font-size="9" fill="#fd7e14" font-family="monospace">"token": "abc123"</text>
        <text x="350" y="253" font-size="9" fill="#28a745" font-family="monospace">}</text>

        <!-- 状态码 -->
        <rect x="340" y="270" width="60" height="22" rx="6" fill="#28a745" opacity="0.2"/>
        <text x="370" y="285" font-size="11" fill="#28a745" text-anchor="middle" font-weight="bold">200 OK</text>
        
        <rect x="410" y="270" width="60" height="22" rx="6" fill="#dc3545" opacity="0.2"/>
        <text x="440" y="285" font-size="11" fill="#dc3545" text-anchor="middle" font-weight="bold">401</text>

        <!-- 复制按钮图标 -->
        <g class="copy-icon">
          <rect x="530" y="135" width="18" height="18" rx="3" fill="none" :stroke="textSecondary" stroke-width="1.5"/>
          <rect x="533" y="138" width="18" height="18" rx="3" :fill="iconBg" :stroke="textSecondary" stroke-width="1.5"/>
        </g>
      </g>

      <!-- 连接箭头 -->
      <g :stroke="arrowColor" stroke-width="2" fill="none" class="arrow-animate">
        <path d="M 290 200 L 320 200" marker-end="url(#arrowhead)"/>
      </g>

      <defs>
        <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
          <polygon points="0 0, 10 3, 0 6" :fill="arrowColor"/>
        </marker>
      </defs>

      <!-- 装饰图标 -->
      <g class="float-icons">
        <circle cx="160" cy="50" r="20" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="icon-float-1"/>
        <text x="160" y="58" font-size="16" fill="#007bff" text-anchor="middle" font-weight="bold">API</text>
        
        <circle cx="450" cy="360" r="18" :fill="iconBg" :stroke="borderColor" stroke-width="1.5" class="icon-float-2"/>
        <path d="M 445 360 L 455 360 M 450 355 L 450 365" stroke="#28a745" stroke-width="2.5" stroke-linecap="round"/>
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
const inputBg = computed(() => isDark.value ? '#1e1e1e' : '#f8f9fa')
const tableBg = computed(() => isDark.value ? '#252525' : '#f9f9f9')
const codeBg = computed(() => isDark.value ? '#1e1e1e' : '#f5f5f5')
const iconBg = computed(() => isDark.value ? '#2d2d2d' : '#ffffff')
const textColor = computed(() => isDark.value ? '#e0e0e0' : '#343a40')
const textSecondary = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const textLineColor = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const borderColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)')
const bgCircleColor = computed(() => isDark.value ? 'rgba(74, 158, 255, 0.1)' : 'rgba(0, 123, 255, 0.1)')
const arrowColor = computed(() => isDark.value ? '#4a9eff' : '#007bff')
</script>

<style lang="scss" scoped>
.api-illustration {
  width: 100%;
  height: 100%;
  overflow: hidden;

  svg {
    width: 100%;
    height: auto;
    display: block;
  }
}

.icon-float-1 {
  animation: float 3s ease-in-out infinite;
}

.icon-float-2 {
  animation: float 4s ease-in-out infinite;
  animation-delay: 1s;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

.arrow-animate {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 0.8; }
  50% { opacity: 0.4; }
}

.method-tags rect {
  transition: transform 0.3s ease;
}

.copy-icon {
  cursor: pointer;
  transition: transform 0.2s ease;
  
  &:hover {
    transform: scale(1.1);
  }
}
</style>

