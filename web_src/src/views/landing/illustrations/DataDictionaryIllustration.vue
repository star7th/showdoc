<template>
  <div class="data-dictionary-illustration">
    <svg viewBox="0 0 600 400" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient id="dbGradient" x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" style="stop-color:#28a745" />
          <stop offset="100%" style="stop-color:#20833a" />
        </linearGradient>

        <filter id="dbShadow">
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
      <circle cx="100" cy="80" r="80" :fill="bgCircleColor" opacity="0.3"/>
      <circle cx="520" cy="330" r="70" :fill="bgCircleColor" opacity="0.2"/>

      <!-- 左侧数据库图标 -->
      <g class="database-icon" filter="url(#dbShadow)">
        <circle cx="150" cy="200" r="70" :fill="iconBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 数据库三层 -->
        <g transform="translate(150, 200)">
          <ellipse cx="0" cy="-25" rx="35" ry="10" fill="url(#dbGradient)"/>
          <rect x="-35" y="-25" width="70" height="15" fill="url(#dbGradient)"/>
          <ellipse cx="0" cy="-10" rx="35" ry="10" fill="#28a745"/>
          
          <rect x="-35" y="-10" width="70" height="15" fill="url(#dbGradient)"/>
          <ellipse cx="0" cy="5" rx="35" ry="10" fill="#28a745"/>
          
          <rect x="-35" y="5" width="70" height="20" fill="url(#dbGradient)"/>
          <ellipse cx="0" cy="25" rx="35" ry="10" fill="#28a745"/>
        </g>

        <!-- 数据库标签 -->
        <text x="150" y="290" font-size="12" :fill="textColor" text-anchor="middle" font-weight="600">MySQL</text>
      </g>

      <!-- 右侧数据字典表格 -->
      <g class="data-table" filter="url(#dbShadow)">
        <rect x="280" y="60" width="280" height="280" rx="16" :fill="cardBg" :stroke="borderColor" stroke-width="2"/>
        
        <!-- 表格标题 -->
        <text x="420" y="90" font-size="14" :fill="textColor" text-anchor="middle" font-weight="600">users Table Structure</text>
        
        <!-- 表头 -->
        <rect x="300" y="110" width="240" height="32" rx="6" :fill="tableHeaderBg"/>
        <text x="320" y="130" font-size="11" :fill="textColor" font-weight="600">Field</text>
        <text x="400" y="130" font-size="11" :fill="textColor" font-weight="600">Type</text>
        <text x="480" y="130" font-size="11" :fill="textColor" font-weight="600">Description</text>

        <!-- 表格行 -->
        <!-- 第1行 - 主键 -->
        <g class="table-row">
          <rect x="300" y="142" width="240" height="28" :fill="tableRowBg" :stroke="borderColor" stroke-width="1"/>
          <circle cx="310" cy="156" r="6" fill="#ffc107"/>
          <path d="M 307 156 L 310 153 L 313 156 L 310 159 Z M 310 153 L 310 158" stroke="white" stroke-width="1.5" fill="none"/>
          <text x="320" y="160" font-size="10" :fill="textColor">id</text>
          <text x="400" y="160" font-size="10" :fill="textSecondary">INT</text>
          <text x="480" y="160" font-size="9" :fill="textSecondary">User ID</text>
        </g>

        <!-- 第2行 -->
        <g class="table-row">
          <rect x="300" y="170" width="240" height="28" :fill="cardBg" :stroke="borderColor" stroke-width="1"/>
          <text x="320" y="188" font-size="10" :fill="textColor">username</text>
          <text x="400" y="188" font-size="10" :fill="textSecondary">VARCHAR</text>
          <text x="480" y="188" font-size="9" :fill="textSecondary">Username</text>
        </g>

        <!-- 第3行 -->
        <g class="table-row">
          <rect x="300" y="198" width="240" height="28" :fill="tableRowBg" :stroke="borderColor" stroke-width="1"/>
          <text x="320" y="216" font-size="10" :fill="textColor">email</text>
          <text x="400" y="216" font-size="10" :fill="textSecondary">VARCHAR</text>
          <text x="480" y="216" font-size="9" :fill="textSecondary">Email</text>
        </g>

        <!-- 第4行 -->
        <g class="table-row">
          <rect x="300" y="226" width="240" height="28" :fill="cardBg" :stroke="borderColor" stroke-width="1"/>
          <text x="320" y="244" font-size="10" :fill="textColor">password</text>
          <text x="400" y="244" font-size="10" :fill="textSecondary">VARCHAR</text>
          <text x="480" y="244" font-size="9" :fill="textSecondary">Password</text>
        </g>

        <!-- 第5行 -->
        <g class="table-row">
          <rect x="300" y="254" width="240" height="28" :fill="tableRowBg" :stroke="borderColor" stroke-width="1"/>
          <text x="320" y="272" font-size="10" :fill="textColor">created_at</text>
          <text x="400" y="272" font-size="10" :fill="textSecondary">DATETIME</text>
          <text x="480" y="272" font-size="9" :fill="textSecondary">Created At</text>
        </g>

        <!-- 索引标识 -->
        <g class="index-badges">
          <rect x="305" y="295" width="55" height="20" rx="4" fill="#28a745" opacity="0.2"/>
          <text x="332" y="308" font-size="9" fill="#28a745" text-anchor="middle" font-weight="bold">PRIMARY</text>
          
          <rect x="370" y="295" width="45" height="20" rx="4" fill="#007bff" opacity="0.2"/>
          <text x="392" y="308" font-size="9" fill="#007bff" text-anchor="middle" font-weight="bold">INDEX</text>
        </g>
      </g>

      <!-- 连接线 -->
      <g :stroke="lineColor" stroke-width="2" fill="none" opacity="0.5" class="connection-line">
        <path d="M 220 200 L 280 140" stroke-dasharray="4,4" class="line-animate"/>
      </g>

      <!-- 装饰元素 - SQL 查询图标 -->
      <g class="sql-icon float-icon">
        <circle cx="420" cy="30" r="22" :fill="iconBg" :stroke="borderColor" stroke-width="1.5"/>
        <text x="420" y="37" font-size="12" fill="#28a745" text-anchor="middle" font-weight="bold">SQL</text>
      </g>

      <!-- 装饰元素 - 表关系图标 -->
      <g class="relation-icon float-icon">
        <circle cx="180" cy="350" r="18" :fill="iconBg" :stroke="borderColor" stroke-width="1.5"/>
        <rect x="173" y="345" width="6" height="10" rx="1" fill="#007bff"/>
        <rect x="182" y="345" width="6" height="10" rx="1" fill="#fd7e14"/>
        <line x1="176" y1="350" x2="185" y2="350" stroke="#28a745" stroke-width="2"/>
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
const tableHeaderBg = computed(() => isDark.value ? '#1e1e1e' : '#f0f7ff')
const tableRowBg = computed(() => isDark.value ? '#252525' : '#f9f9f9')
const textColor = computed(() => isDark.value ? '#e0e0e0' : '#343a40')
const textSecondary = computed(() => isDark.value ? '#8b949e' : '#6c757d')
const borderColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)')
const bgCircleColor = computed(() => isDark.value ? 'rgba(40, 167, 69, 0.1)' : 'rgba(40, 167, 69, 0.1)')
const lineColor = computed(() => isDark.value ? '#3fb950' : '#28a745')
</script>

<style lang="scss" scoped>
.data-dictionary-illustration {
  width: 100%;
  height: 100%;
  overflow: hidden;

  svg {
    width: 100%;
    height: auto;
    display: block;
  }
}

.database-icon {
  animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.02); }
}

.table-row {
  transition: all 0.3s ease;
}

.connection-line {
  .line-animate {
    stroke-dasharray: 150;
    animation: dash 3s linear infinite;
  }
}

@keyframes dash {
  to {
    stroke-dashoffset: -150;
  }
}

.float-icon {
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
</style>

