/// <reference types="vite/client" />
/// <reference types="vue/jsx" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}

declare module 'ant-design-vue/es/locale/*' {
  const locale: any
  export default locale
}

interface ImportMetaEnv {
  readonly VITE_APP_TITLE: string
  readonly VITE_APP_PORT: number
  readonly VITE_APP_BASE_API: string
  readonly VITE_APP_API_BASE_URL: string
  readonly VITE_APP_STORAGE_PREFIX: string
  readonly VITE_APP_VIEW_COMPONENT_PREFIX: string
  readonly VITE_APP_ERROR_COMPONENT_PATH: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
