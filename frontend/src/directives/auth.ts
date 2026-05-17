/**
 * 权限指令
 * v-auth="'permission_code'" - 单个权限
 * v-auth="['perm1', 'perm2']" - 多个权限（满足任一）
 * v-auth:role="'role_code'" - 角色判断
 * v-auth:role="['role1', 'role2']" - 多角色判断（满足任一）
 *
 * @description 通过 watch 监听 userStore 中 permissions/roleList 的变化，
 *              在权限数据异步加载完成后自动更新元素可见性，解决页面刷新后
 *              permissions 尚未恢复时指令误隐藏按钮的问题
 */

import type { Directive, DirectiveBinding } from 'vue'
import { watch } from 'vue'
import { useUserStore } from '@/stores/user'

function checkPermission(binding: DirectiveBinding): boolean {
  const userStore = useUserStore()
  const value = binding.value
  const arg = binding.arg

  if (!value) return true

  if (arg === 'role') {
    if (Array.isArray(value)) {
      return userStore.hasAnyRoles(value)
    }
    return userStore.hasRole(value)
  }

  if (Array.isArray(value)) {
    return userStore.hasAnyPermission(value)
  }

  return userStore.hasPermission(value)
}

function applyAuth(el: HTMLElement, binding: DirectiveBinding): void {
  if (!checkPermission(binding)) {
    el.style.display = 'none'
    el.setAttribute('aria-hidden', 'true')
  } else {
    el.style.display = ''
    el.removeAttribute('aria-hidden')
  }
}

function applyAuthDisabled(el: HTMLElement, binding: DirectiveBinding): void {
  if (!checkPermission(binding)) {
    el.setAttribute('disabled', 'true')
    el.style.opacity = '0.5'
    el.style.cursor = 'not-allowed'
    el.style.pointerEvents = 'none'
  } else {
    el.removeAttribute('disabled')
    el.style.opacity = ''
    el.style.cursor = ''
    el.style.pointerEvents = ''
  }
}

export const authDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    applyAuth(el, binding)

    const userStore = useUserStore()
    const stop = watch(
      () => [userStore.permissions, userStore.roleList],
      () => applyAuth(el, binding),
      { deep: true }
    )

    ;(el as HTMLElement & { __authWatchStop?: () => void }).__authWatchStop = stop
  },

  updated(el: HTMLElement, binding: DirectiveBinding) {
    applyAuth(el, binding)
  },

  unmounted(el: HTMLElement) {
    const stop = (el as HTMLElement & { __authWatchStop?: () => void }).__authWatchStop
    if (stop) {
      stop()
    }
  }
}

export const authDisabledDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    applyAuthDisabled(el, binding)

    const userStore = useUserStore()
    const stop = watch(
      () => [userStore.permissions, userStore.roleList],
      () => applyAuthDisabled(el, binding),
      { deep: true }
    )

    ;(el as HTMLElement & { __authDisabledWatchStop?: () => void }).__authDisabledWatchStop = stop
  },

  updated(el: HTMLElement, binding: DirectiveBinding) {
    applyAuthDisabled(el, binding)
  },

  unmounted(el: HTMLElement) {
    const stop = (el as HTMLElement & { __authDisabledWatchStop?: () => void }).__authDisabledWatchStop
    if (stop) {
      stop()
    }
  }
}

export default authDirective
