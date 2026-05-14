<template>
  <svg v-if="svgIcon" aria-hidden="true" :style="styleObj" class="svg-icon">
    <use :xlink:href="symbolId" />
  </svg>
  <component :is="resolvedType" v-else-if="resolvedType" v-bind="$attrs" :style="styleObj" />
</template>

<script lang="ts">
import * as antIcons from '@ant-design/icons-vue'
import { computed, defineComponent, unref } from 'vue'

export default defineComponent({
  name: 'SIcon',
  components: {
    ...antIcons,
    Icon: antIcons.default
  },
  props: {
    type: {
      type: String,
      required: true
    },
    size: {
      type: [String, Number],
      default: ''
    },
    color: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const svgIcon = computed(() => props.type?.startsWith('svg:'))

    const symbolId = computed(() => {
      return unref(svgIcon) ? `#icon-${props.type.split('svg:')[1]}` : props.type
    })

    const resolvedType = computed(() => {
      if (!props.type) return undefined
      if (props.type.startsWith('ant-design:')) {
        return props.type.replace('ant-design:', '')
      }
      return props.type
    })

    const styleObj = computed(() => {
      let size = ''
      if (props.size) {
        size = typeof props.size === 'number' ? `${props.size}px` : props.size
      }
      return {
        fontSize: size,
        color: props.color
      }
    })

    return {
      svgIcon,
      symbolId,
      resolvedType,
      styleObj
    }
  }
})
</script>

<style scoped>
.svg-icon {
  width: 1em;
  height: 1em;
  overflow: hidden;
  fill: currentColor;
}
</style>
