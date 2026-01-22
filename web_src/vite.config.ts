import { defineConfig, splitVendorChunkPlugin } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// ========================================
// ShowDoc 开源版前端构建配置
// ========================================
//
// 环境配置：
// 1. 开发环境（npm run dev）：base: '/' - Vite dev server
// 2. 生产环境（npm run build）：base: '/' - 相对路径，支持二级目录部署
//
// 部署策略：
// - 构建输出目录：../web/（相对于 web_src/）
// - 所有资源路径使用相对路径，无需 CDN
// - 支持 Nginx 无需重写配置即可在二级目录部署
//
// 缓存策略：
// - emptyOutDir: true 每次构建清空输出目录
// ========================================

export default defineConfig(({ mode }) => {
  const isDev = mode === 'development'

  return {
    // 开源版：base 路径始终为 '/'，使用相对路径部署
    base: './',

    // Vue Router base 路径始终为 '/'
    define: {
      'import.meta.env.BASE_URL': '"./"',
    },

    plugins: [vue(), splitVendorChunkPlugin()],

    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src'),
        '@renderer': path.resolve(__dirname, 'src'),
      },
    },

    css: {
      preprocessorOptions: {
        scss: {
          additionalData: `@use "@/styles/variables.scss" as *;`,
        },
      },
    },

    server: {
      port: 8080,
      proxy: {
        '/server': {
          target: 'http://127.0.0.1/showdoc',
          changeOrigin: true,
        },
      },
    },

    build: {
      // 开源版：构建输出到 ../web 目录
      outDir: path.resolve(__dirname, '../web'),
      emptyOutDir: true,
      chunkSizeWarningLimit: 1000,
      rollupOptions: {
        output: {
          // 静态资源命名（图片、字体等）
          assetFileNames: 'assets/[name]-[hash][extname]',
          // chunk 文件命名
          chunkFileNames: 'assets/[name]-[hash].js',
          // 入口文件命名
          entryFileNames: 'assets/[name]-[hash].js',
          // 手动代码分割策略 - 减少文件数量
          manualChunks: (id) => {
            // node_modules 分组：按依赖类型分离
            if (id.includes('node_modules')) {
              // Vue 核心库
              if (id.match(/vue|pinia|vue-router/)) {
                return 'vendor-vue'
              }
              // UI 库
              if (id.match(/ant-design-vue|@kangc\/v-md-editor/)) {
                return 'vendor-ui'
              }
            // 编辑器库
            if (id.match(/highlight\.js/)) {
              return 'vendor-editor'
            }
            // 其他第三方库
              return 'vendor'
            }

            // 组件层
            if (id.includes('src/components')) {
              return 'components'
            }

            // 页面层
            if (id.includes('src/views')) {
              return 'views'
            }

            // 核心层（router/store/models/i18n/utils）
            if (
              id.includes('src/router') ||
              id.includes('src/store') ||
              id.includes('src/models') ||
              id.includes('src/i18n') ||
              id.includes('src/utils')
            ) {
              return 'core'
            }

            // 其他资源（styles/assets）
            if (id.includes('src/styles') || id.includes('src/assets')) {
              return 'assets'
            }

            // 默认返回 null，让 Rollup 自动处理
            return null
          },
        },
      },
    },
  }
})
