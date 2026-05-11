import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueJsx from '@vitejs/plugin-vue-jsx'
import { createSvgIconsPlugin } from 'vite-plugin-svg-icons'
import { resolve } from 'path'

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [
      vue(),
      vueJsx(),
      createSvgIconsPlugin({
        iconDirs: [resolve(process.cwd(), 'src/assets/icons')],
        symbolId: 'icon-[name]'
      })
    ],
    resolve: {
      alias: {
        '@': resolve(__dirname, 'src')
      }
    },
    server: {
      host: '0.0.0.0',
      port: Number(env.VITE_APP_PORT) || 5173,
      open: true,
      proxy: env.VITE_APP_BASE_API
        ? {
            [env.VITE_APP_BASE_API]: {
              target: env.VITE_APP_API_BASE_URL,
              changeOrigin: true,
              rewrite: (path) => path.replace(new RegExp(`^${env.VITE_APP_BASE_API}`), '')
            }
          }
        : {}
    },
    build: {
      outDir: 'dist',
      assetsDir: 'static',
      sourcemap: false,
      minify: 'esbuild',
      rollupOptions: {
        output: {
          manualChunks: {
            'vue-vendor': ['vue', 'vue-router', 'pinia'],
            'ant-design-vue': ['ant-design-vue'],
            'utils-vendor': ['axios', '@vueuse/core', 'dayjs']
          }
        }
      }
    },
    css: {
      preprocessorOptions: {
        less: {
          javascriptEnabled: true,
          modifyVars: {
            'primary-color': '#4073fa',
            'link-color': '#4073fa'
          },
          additionalData: `@import "${resolve(__dirname, 'src/styles/global/variables.less').replace(/\\/g, '/')}";`
        }
      }
    },
    optimizeDeps: {
      include: ['vue', 'vue-router', 'pinia', 'ant-design-vue', 'axios', '@vueuse/core']
    }
  }
})
