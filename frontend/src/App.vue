<template>
  <a-config-provider :locale="zhCN" :theme="themeSetting">
    <token-provider>
      <router-view />
    </token-provider>
  </a-config-provider>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import zhCN from 'ant-design-vue/es/locale/zh_CN'
import { useSetting } from '@/layouts/composables'
import { theme as antdTheme } from 'ant-design-vue'
import { TokenProvider } from '@/components/TokenProvider'
import { getCssVar } from '@/utils/dom'

const { theme, primaryColor, borderRadius } = useSetting()

const themeSetting = computed(() => {
  const isDark = theme.value === 'realDark'
  return {
    algorithm: isDark ? antdTheme.darkAlgorithm : antdTheme.defaultAlgorithm,
    token: {
      colorPrimary: primaryColor.value,
      colorBgLayout: isDark ? '#000000' : getCssVar('--spe-layout-bg-color', '#f1f3f6'),
      borderRadius: borderRadius.value
    },
    components: isDark
      ? {
          Menu: {
            colorItemBg: 'rgb(20 20 20)',
            colorSubItemBg: 'rgb(36 37 37)',
            menuSubMenuBg: 'rgb(36 37 37)'
          }
        }
      : {}
  }
})
</script>
