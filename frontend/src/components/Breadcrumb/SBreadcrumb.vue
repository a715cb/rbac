<template>
  <div class="s-breadcrumb">
    <transition-group name="breadcrumb">
      <breadcrumb-item v-for="(item, index) in breadList" :key="item.path" :separator="separator">
        <span
          v-if="item.redirect === 'noRedirect' || index == breadList.length - 1"
          class="no-redirect"
        >
          {{ item.meta.title }}
        </span>
        <span v-else>{{ item.meta.title }}</span>
      </breadcrumb-item>
    </transition-group>
  </div>
</template>

<script lang="ts" setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import BreadcrumbItem from './BreadcrumbItem.vue'
import type { RouteLocationMatched } from 'vue-router'

const route = useRoute()

const breadList = ref<RouteLocationMatched[]>([])

defineProps({
  separator: {
    type: String,
    default: '/'
  }
})

watch(route, () => {
  getBreadcrumb()
})

const getBreadcrumb = () => {
  breadList.value = []
  route.matched.forEach((item: RouteLocationMatched) => {
    if (item.meta.breadcrumb !== false) {
      breadList.value.push(item)
    }
  })
}

onMounted(() => {
  getBreadcrumb()
})
</script>

<style lang="less" scoped>
.s-breadcrumb {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
  white-space: nowrap;
  font-size: 14px;
  line-height: 1.5;
  color: rgba(0, 0, 0, 0.45);

  .no-redirect {
    color: rgba(0, 0, 0, 0.88);
  }
}
</style>
