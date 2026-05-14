import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    icon?: string
    hidden?: boolean
    active_key?: string
    namePath?: string[]
    affix?: boolean
    requiresAuth?: boolean
  }
}
