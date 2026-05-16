import { describe, it, expect, vi, beforeEach } from 'vitest'

const mockGet = vi.fn()

vi.mock('@/utils/request', () => ({
  get: mockGet,
  post: vi.fn(),
  put: vi.fn(),
  del: vi.fn(),
  default: {
    get: mockGet,
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() }
    }
  }
}))

describe('getApiList 参数传递', () => {
  beforeEach(() => {
    mockGet.mockReset()
    mockGet.mockResolvedValue({
      code: 200,
      msg: '获取成功',
      data: {
        list: [],
        groups: [],
        pagination: { page: 1, page_size: 10, total: 0, total_pages: 0 }
      }
    })
  })

  it('应将查询参数正确包装在 { params } 中传递', async () => {
    const { getApiList } = await import('@/api/api')

    const params = {
      page: 1,
      limit: 10,
      keyword: 'test',
      method: 'GET',
      group: 'user',
      status: 1
    }

    await getApiList(params)

    expect(mockGet).toHaveBeenCalledWith('/api/admin/apis', { params })
  })

  it('应正确处理 undefined 参数', async () => {
    const { getApiList } = await import('@/api/api')

    const params = {
      page: 1,
      limit: 10,
      keyword: '',
      method: undefined,
      group: undefined,
      status: undefined
    }

    await getApiList(params)

    expect(mockGet).toHaveBeenCalledWith('/api/admin/apis', { params })
  })

  it('应正确处理空参数调用', async () => {
    const { getApiList } = await import('@/api/api')

    await getApiList()

    expect(mockGet).toHaveBeenCalledWith('/api/admin/apis', { params: undefined })
  })

  it('不应将 params 直接作为 AxiosRequestConfig 传递', async () => {
    const { getApiList } = await import('@/api/api')

    const params = { page: 2, limit: 20 }

    await getApiList(params)

    const callArgs = mockGet.mock.calls[0]
    const secondArg = callArgs[1]

    expect(secondArg).not.toBe(params)
    expect(secondArg).toEqual({ params })
    expect(secondArg).toHaveProperty('params')
  })
})

describe('getApiList 响应数据处理', () => {
  beforeEach(() => {
    mockGet.mockReset()
  })

  it('应正确返回分页数据中的 total', async () => {
    const { getApiList } = await import('@/api/api')

    mockGet.mockResolvedValue({
      code: 200,
      msg: '获取成功',
      data: {
        list: [
          {
            id: 1,
            name: '接口1',
            code: 'api_1',
            method: 'GET',
            path: '/api/1',
            status: 1,
            menu_id: 1,
            menu_name: '菜单1',
            group: '系统',
            create_time: '2024-01-01'
          },
          {
            id: 2,
            name: '接口2',
            code: 'api_2',
            method: 'POST',
            path: '/api/2',
            status: 1,
            menu_id: 1,
            menu_name: '菜单1',
            group: '系统',
            create_time: '2024-01-01'
          }
        ],
        groups: ['系统', '用户'],
        pagination: { page: 1, page_size: 10, total: 25, total_pages: 3 }
      }
    })

    const result = await getApiList({ page: 1, limit: 10 })

    expect(result.data.pagination.total).toBe(25)
    expect(result.data.list).toHaveLength(2)
    expect(result.data.groups).toEqual(['系统', '用户'])
  })

  it('应正确处理筛选后的 total 与未筛选的 total 不同', async () => {
    const { getApiList } = await import('@/api/api')

    mockGet.mockResolvedValue({
      code: 200,
      msg: '获取成功',
      data: {
        list: [
          {
            id: 1,
            name: 'GET接口',
            code: 'api_1',
            method: 'GET',
            path: '/api/1',
            status: 1,
            menu_id: 1,
            menu_name: '菜单1',
            group: '系统',
            create_time: '2024-01-01'
          }
        ],
        groups: ['系统'],
        pagination: { page: 1, page_size: 10, total: 5, total_pages: 1 }
      }
    })

    const result = await getApiList({ page: 1, limit: 10, method: 'GET' })

    expect(result.data.pagination.total).toBe(5)
    expect(mockGet).toHaveBeenCalledWith('/api/admin/apis', {
      params: { page: 1, limit: 10, method: 'GET' }
    })
  })

  it('total 为 0 时应正确返回', async () => {
    const { getApiList } = await import('@/api/api')

    mockGet.mockResolvedValue({
      code: 200,
      msg: '获取成功',
      data: {
        list: [],
        groups: [],
        pagination: { page: 1, page_size: 10, total: 0, total_pages: 0 }
      }
    })

    const result = await getApiList({ page: 1, limit: 10 })

    expect(result.data.pagination.total).toBe(0)
  })
})
