<!--
  @文件: UserMenu.vue
  @用途: 用户菜单组件
  @描述: 渲染顶部导航栏右侧的用户操作区域，包含设置按钮、主题切换按钮和用户下拉菜单。
         - 设置按钮：打开设置抽屉（仅在 setPosition == 'header' 时显示）
         - 主题切换按钮：在亮色/暗黑模式间切换，支持 View Transition API 圆形扩散动画
         - 用户下拉菜单：显示用户头像和名称，提供"个人中心"和"退出登录"操作
  @核心逻辑:
    1. 通过 inject('openSetting') 获取设置抽屉的打开方法
    2. 主题切换使用 View Transition API 实现圆形扩散动画效果
    3. 退出登录调用 userStore.logout()，完成后跳转到登录页
-->
<template>
  <div class="user-menu">
    <!-- 图标工具栏：设置与主题切换按钮 -->
    <a-space :size="4" class="icon-toolbar">
      <!-- 设置按钮：仅在设置入口位于头部时显示 -->
      <s-button
        v-if="setPosition == 'header'"
        shape="circle"
        :icon-size="16"
        class="user-menu-icon"
        icon="setting-outlined"
        @click="openSetting"
      />
      <!-- 主题切换按钮：暗黑模式显示太阳图标，其他模式显示月亮图标 -->
      <s-button
        shape="circle"
        :icon-size="16"
        class="user-menu-icon"
        :icon="theme == 'realDark' ? 'svg:sun' : 'svg:moon'"
        @click="onChangeTheme($event)"
      />
    </a-space>
    <!-- 用户下拉菜单：头像 + 用户名 + 操作项 -->
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
                <!-- 退出登录加载指示器 -->
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
import { TokenManager } from '@/utils/token'
import { StorageManager } from '@/utils/storage'
import { AppConfig } from '@/config/app'
import { clearTabsStorage } from '@/layouts/components/TagsView/useTabs'
import { removeDynamicRoutes } from '@/router/dynamic'

/** 退出登录按钮的加载指示器 */
const indicator = h(LoadingOutlined, {
  style: {
    fontSize: '12px',
    color: '#aaa'
  },
  spin: true
})

const store = useUserStore()
/** 用户信息（头像、用户名），响应式绑定 */
const { userInfo } = storeToRefs(store)
const router = useRouter()

/** 全局设置：主题、主题切换方法、设置入口位置 */
const { theme, changeTheme, setPosition } = useSetting()

/** 通过 inject 获取父组件 provide 的打开设置抽屉方法 */
const openSetting = inject<() => void>('openSetting', () => {})

/** 退出登录加载状态 */
const spinning = ref(false)

/**
 * 退出登录
 * @description 调用 userStore.logout() 清除用户状态，
 *              失败时兜底清除本地认证状态，最终均跳转到登录页
 */
const logout = async () => {
  spinning.value = true
  try {
    await store.logout()
  } catch (error) {
    console.error('退出登录失败:', error)
    TokenManager.clearToken()
    StorageManager.removeItem('session', AppConfig.userInfoKey)
    StorageManager.removeItem('session', AppConfig.menusKey)
    StorageManager.removeItem('local', AppConfig.refreshTokenKey)
    StorageManager.removeItem('local', AppConfig.tokenExpiresKey)
    clearTabsStorage()
    removeDynamicRoutes()
  } finally {
    spinning.value = false
    router.push('/login')
  }
}

/** 跳转到个人中心页面 */
const toAccount = () => {
  router.push({ path: '/account' })
}

/** 记住切换暗黑模式前的主题，用于切换回非暗黑模式时恢复 */
const localPrevTheme = ref<'light' | 'dark'>('light')

/**
 * 检测浏览器是否支持 View Transition API
 * @description 通过检查 document.startViewTransition 方法是否存在来判断
 */
const supportsViewTransition = (): boolean =>
  'startViewTransition' in document && typeof (document as any).startViewTransition === 'function'

/**
 * 主题切换动画
 * @param event - 鼠标事件，用于获取点击坐标作为动画圆心
 * @param isDark - 是否切换到暗黑模式
 * @param callback - 实际的主题切换回调
 * @description 使用 View Transition API 实现从点击位置向外扩散的圆形动画。
 *              不支持 View Transition API 的浏览器直接执行回调。
 */
const themeAnimation = (event: MouseEvent, isDark: boolean, callback: () => void) => {
  if (!supportsViewTransition()) return callback()
  const x = event.clientX
  const y = event.clientY
  /* 计算动画结束半径：从点击位置到视口四角的最大距离 */
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

/**
 * 切换主题模式
 * @param event - 鼠标事件，传递给动画函数
 * @description 在暗黑模式和上一次非暗黑模式之间切换，
 *              通过 themeAnimation 实现圆形扩散过渡动画
 */
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
}

/* 工具栏图标按钮：无边框，统一尺寸 */
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

  /* 覆盖 Ant Design Vue 按钮默认的点击动画 */
  &::after {
    display: none;
  }
}

/* 用户下拉菜单触发区域 */
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

/* 用户名文本样式 */
.account-name {
  font-size: @layout-user-menu-account-font-size;
  transform: none;
  color: @layout-header-light-account-text;
  text-transform: none;
}

/* 暗色头部主题适配 */
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

/* 全局暗黑模式适配 */
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

/* 响应式：小屏幕隐藏用户名 */
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
