/**
 * 权限指令
 * v-auth="'permission_code'" - 单个权限
 * v-auth="['perm1', 'perm2']" - 多个权限（满足任一）
 * v-auth:role="'role_code'" - 角色判断
 * v-auth:role="['role1', 'role2']" - 多角色判断（满足任一）
 */

import type { Directive, DirectiveBinding } from 'vue'
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

export const authDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    if (!checkPermission(binding)) {
      el.style.display = 'none'
      el.setAttribute('aria-hidden', 'true')
    }
  },

  updated(el: HTMLElement, binding: DirectiveBinding) {
    if (!checkPermission(binding)) {
      el.style.display = 'none'
      el.setAttribute('aria-hidden', 'true')
    } else {
      el.style.display = ''
      el.removeAttribute('aria-hidden')
    }
  }
}

export const authDisabledDirective: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    if (!checkPermission(binding)) {
      el.setAttribute('disabled', 'true')
      el.style.opacity = '0.5'
      el.style.cursor = 'not-allowed'
      el.style.pointerEvents = 'none'
    }
  },

  updated(el: HTMLElement, binding: DirectiveBinding) {
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
}

export default authDirective
