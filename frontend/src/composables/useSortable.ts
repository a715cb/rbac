import { nextTick, unref, ref, onUnmounted } from 'vue'
import type { Ref } from 'vue'
import type { Options } from 'sortablejs'
import Sortable from 'sortablejs'

export function useSortable(el: HTMLElement | Ref<HTMLElement>, options?: Options) {
  const sortableInstance = ref<Sortable | null>(null)

  nextTick(() => {
    if (!el) return
    sortableInstance.value = Sortable.create(unref(el), {
      animation: 500,
      ...options
    })
  })

  onUnmounted(() => {
    sortableInstance.value?.destroy()
  })

  return { sortableInstance }
}
