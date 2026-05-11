export interface ApiResponse<T = any> {
  code: number
  msg: string
  data: T
}

export interface PageResult<T = any> {
  list: T[]
  total: number
  page: number
  pageSize: number
}

export interface PageParams {
  page: number
  page_size: number
  [key: string]: any
}
