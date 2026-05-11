<template>
  <slot />
</template>

<script setup lang="ts">
import { watchEffect } from 'vue'
import { theme } from 'ant-design-vue'
import { registerTokenToCSSVar } from './token-to-cssvar'

const { token } = theme.useToken()

watchEffect(() => {
  registerTokenToCSSVar(token.value)
})
</script>
