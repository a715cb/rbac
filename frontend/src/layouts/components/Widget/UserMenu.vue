<template>
  <div class="user-menu">
    <!-- 图标工具栏：设置与主题切换按钮 -->
    <a-space :size="4" class="icon-toolbar">
      <s-button
        v-if="setPosition == 'header'"
        shape="circle"
        :icon-size="16"
        class="user-menu-icon"
        icon="setting-outlined"
        @click="openSetting"
      />
      <s-button
        shape="circle"
        :icon-size="16"
        class="user-menu-icon"
        :icon="theme == 'realDark' ? 'svg:sun' : 'svg:moon'"
        @click="onChangeTheme($event)"
      />
    </a-space>
    <a-dropdown class="user-dropdown">
      <span class="action ant-dropdown-link user-dropdown-menu">
        <a-avatar class="avatar" size="small" :src="userInfo?.avatar" />
        <span class="account-name line-feed-1">{{ userInfo?.username }}</span>
      </span>
      <template #overlay>
        <a-menu>
          <a-menu-item @click="toAccount">
            <s-icon type="user-outlined" class="icon" />
            <span>个人中心</span>
          </a-menu-item>
          <a-menu-item @click="logout">
            <a href="javascript:;">
              <s-icon type="logout-outlined" class="icon" />
              <span>
                退出登录
                <a-spin :spinning="spinning" :indicator="indicator"></a-spin>
              </span>
            </a>
          </a-menu-item>
        </a-menu>
      </template>
    </a-dropdown>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, h } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { useSetting } from '@/layouts/composables'
import { storeToRefs } from 'pinia'
import { LoadingOutlined } from '@ant-design/icons-vue'

const indicator = h(LoadingOutlined, {
  style: {
    fontSize: '12px',
    color: '#aaa'
  },
  spin: true
})

const store = useUserStore()
const { userInfo } = storeToRefs(store)
const router = useRouter()

const { theme, changeTheme, setPosition } = useSetting()

const openSetting = inject<() => void>('openSetting', () => {})

const spinning = ref(false)
const logout = async () => {
  spinning.value = true
  try {
    await store.logout()
  } catch (error) {
    if (import.meta.env.DEV) console.error('退出登录失败:', error)
  } finally {
    spinning.value = false
    router.push('/login')
  }
}

const toAccount = () => {
  router.push({ path: '/account' })
}

const localPrevTheme = ref<'light' | 'dark'>('light')

const themeAnimation = (event: MouseEvent, isDark: boolean, callback: () => void) => {
  if (!(document as any).startViewTransition) return callback()
  const x = event.clientX
  const y = event.clientY
  const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y))
  const transition = (document as any).startViewTransition(callback)
  transition.ready.then(() => {
    const clipPath = [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`]
    document.documentElement.animate(
      { clipPath: isDark ? [...clipPath].reverse() : clipPath },
      {
        duration: 400,
        easing: 'ease-in',
        pseudoElement: isDark ? '::view-transition-old(root)' : '::view-transition-new(root)'
      }
    )
  })
}

const onChangeTheme = (event: MouseEvent) => {
  if (theme.value !== 'realDark') {
    localPrevTheme.value = theme.value
  }
  const value = theme.value !== 'realDark' ? 'realDark' : localPrevTheme.value
  const isDark = value === 'realDark'
  themeAnimation(event, isDark, () => {
    changeTheme(value as 'light' | 'dark' | 'realDark')
  })
}
</script>

<style lang="less" scoped>
.action {
  padding: 0 12px;
  &:hover {
    background: @layout-user-menu-action-hover;
  }
}

.icon {
  min-width: 12px;
  margin-right: 8px;
}

.user-menu {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.icon-toolbar {
  display: inline-flex;
  align-items: center;
  vertical-align: middle;
  // a-space :size="4" controls spacing, no CSS gap needed
}

.user-menu-icon {
  border: none;
  box-shadow: none;
  width: @layout-user-menu-icon-size;
  height: @layout-user-menu-icon-size;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  margin: 0;
  vertical-align: middle;
  outline: none;
  transform: translateY(0);
  transition: none;
  position: relative;

  &:hover {
    background: @layout-user-menu-icon-hover-bg;
    color: initial;
  }

  &:active {
    transform: translateY(0);
    border: none;
    box-shadow: none;
  }

  &:focus {
    outline: none;
    border: none;
    box-shadow: none;
  }

  // 覆盖 Ant Design Vue 按钮默认的点击动画
  &::after {
    display: none;
  }
}

.user-dropdown-menu {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: @layout-user-menu-dropdown-height;
  cursor: pointer;
  gap: @layout-user-menu-account-gap;

  span {
    user-select: none;
  }
}

.account-name {
  font-size: @layout-user-menu-account-font-size;
  transform: none;
  color: @layout-header-light-account-text;
  text-transform: none;
}

// Dark header theme
.header-dark {
  .user-menu-icon {
    background: @layout-user-menu-dark-icon-bg;
    color: @layout-user-menu-account-color;

    &:hover {
      background: @layout-user-menu-dark-icon-hover-bg;
      color: #fff;
    }
  }

  .account-name {
    color: @layout-user-menu-account-color;
  }
}

// Global dark mode
html[data-theme='dark'] {
  .user-menu-icon {
    background: transparent;
    color: @layout-user-menu-account-color;

    &:hover {
      background: @layout-user-menu-dark-hover-bg;
      color: @layout-user-menu-account-color;
    }
  }

  .account-name {
    color: @layout-user-menu-account-color;
  }
}

// Responsive: hide username on small screens
@media screen and (max-width: 576px) {
  .user-menu {
    gap: 4px;
  }

  .account-name {
    display: none;
  }

  .action {
    padding: 0 8px;
  }
}
</style>
