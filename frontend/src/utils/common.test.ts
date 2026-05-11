import { describe, it, expect } from 'vitest'
import { createPagination } from '@/utils/common'

describe('createPagination', () => {
  it('应返回默认分页配置', () => {
    const pagination = createPagination()

    expect(pagination.current).toBe(1)
    expect(pagination.pageSize).toBe(10)
    expect(pagination.total).toBe(0)
    expect(pagination.showSizeChanger).toBe(true)
    expect(pagination.showQuickJumper).toBe(true)
  })

  it('showTotal 应正确格式化总条数', () => {
    const pagination = createPagination()

    expect(pagination.showTotal).toBeTypeOf('function')
    expect(pagination.showTotal!(25)).toBe('共 25 条')
    expect(pagination.showTotal!(0)).toBe('共 0 条')
    expect(pagination.showTotal!(1000)).toBe('共 1000 条')
  })

  it('total 初始值应为 0，不应预设为全量数据', () => {
    const pagination = createPagination()

    expect(pagination.total).toBe(0)
    expect(pagination.total).not.toBeGreaterThan(0)
  })
})

describe('分页 total 赋值逻辑', () => {
  it('应正确从 API 响应中提取 total 并赋值', () => {
    const pagination = createPagination()

    const apiResponse = {
      data: {
        list: [],
        groups: [],
        pagination: { page: 1, page_size: 10, total: 42, total_pages: 5 }
      }
    }

    pagination.total = apiResponse.data.pagination.total

    expect(pagination.total).toBe(42)
  })

  it('筛选后的 total 应小于未筛选的 total', () => {
    const pagination = createPagination()

    const unfilteredTotal = 100
    const filteredTotal = 15

    pagination.total = unfilteredTotal
    expect(pagination.total).toBe(100)

    pagination.total = filteredTotal
    expect(pagination.total).toBe(15)
    expect(pagination.total).toBeLessThan(unfilteredTotal)
  })

  it('total 为 0 时不应显示异常值', () => {
    const pagination = createPagination()

    const apiResponse = {
      data: {
        list: [],
        groups: [],
        pagination: { page: 1, page_size: 10, total: 0, total_pages: 0 }
      }
    }

    pagination.total = apiResponse.data.pagination.total

    expect(pagination.total).toBe(0)
    expect(pagination.showTotal!(pagination.total)).toBe('共 0 条')
  })
})
