import { computed } from 'vue'
import { useSetting } from './useSetting'

export function useHeaderSetting() {
  const {
    layoutConfig,
    showBreadcrumb: showBreadcrumbSetting,
    navTheme,
    layout,
    isMobile,
    reduceWidth
  } = useSetting()

  const width = computed(() => {
    let reduce = reduceWidth.value
    if (layout.value === 'left') reduce = layoutConfig.leftNavWidth
    if (['top', 'mix'].includes(layout.value) || isMobile.value) reduce = 0
    return `calc(100% - ${reduce}px)`
  })

  const height = computed(() => {
    return `${layoutConfig.headerHeight}px`
  })

  const showTopMenu = computed(() => {
    return (layout.value === 'top' || layout.value === 'mix') && !isMobile.value
  })

  const sideOrMobile = computed(() => {
    return layout.value === 'side' || isMobile.value
  })

  const showBreadcrumb = computed(() => {
    if (isMobile.value) return false
    if (['side', 'left'].includes(layout.value) && showBreadcrumbSetting.value) {
      return true
    }
    return false
  })

  return {
    width,
    height,
    showBreadcrumb,
    showTopMenu,
    sideOrMobile,
    navTheme
  }
}
