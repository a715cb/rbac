import { get, post, put, del } from '@/utils/request'
import type { ApiResponse } from '@/types/api'

export interface DictTypeInfo {
  id: number
  name: string
  code: string
  type: string
  status: number
  sort: number
  remark: string
  create_time: string
  update_time: string
}

export interface DictDataInfo {
  id: number
  dict_type_id: number
  label: string
  value: string
  sort: number
  status: number
  remark: string
  create_time: string
  update_time: string
}

export interface DictTypeForm {
  id?: number
  name: string
  code: string
  type?: string
  status?: number
  sort?: number
  remark?: string
}

export interface DictDataForm {
  id?: number
  dict_type_id: number
  label: string
  value: string
  status?: number
  sort?: number
  remark?: string
}

export function getDictTypeList(params?: {
  keyword?: string
  status?: number
}): Promise<ApiResponse<DictTypeInfo[]>> {
  return get('/admin/dict/types', params)
}

export function getDictTypeDetail(id: number): Promise<ApiResponse<DictTypeInfo>> {
  return get(`/admin/dict/types/${id}`)
}

export function createDictType(data: DictTypeForm): Promise<ApiResponse<{ id: number }>> {
  return post('/admin/dict/types', data)
}

export function updateDictType(id: number, data: DictTypeForm): Promise<ApiResponse<void>> {
  return put(`/admin/dict/types/${id}`, data)
}

export function deleteDictType(id: number): Promise<ApiResponse<void>> {
  return del(`/admin/dict/types/${id}`)
}

export function changeDictTypeStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return put(`/admin/dict/types/${id}/status`, { status })
}

export function getDictDataList(params: {
  dict_type_id: number
  keyword?: string
  status?: number
}): Promise<ApiResponse<DictDataInfo[]>> {
  return get('/admin/dict/data', params)
}

export function getDictDataDetail(id: number): Promise<ApiResponse<DictDataInfo>> {
  return get(`/admin/dict/data/${id}`)
}

export function createDictData(data: DictDataForm): Promise<ApiResponse<{ id: number }>> {
  return post('/admin/dict/data', data)
}

export function updateDictData(id: number, data: DictDataForm): Promise<ApiResponse<void>> {
  return put(`/admin/dict/data/${id}`, data)
}

export function deleteDictData(id: number): Promise<ApiResponse<void>> {
  return del(`/admin/dict/data/${id}`)
}

export function changeDictDataStatus(id: number, status: number): Promise<ApiResponse<void>> {
  return put(`/admin/dict/data/${id}/status`, { status })
}

export function updateDictDataSort(
  data: Array<{ id: number; sort: number }>
): Promise<ApiResponse<void>> {
  return post('/admin/dict/data/sort', data)
}

export function getDictByCode(
  code: string,
  limit?: number
): Promise<ApiResponse<Array<{ value: string; label: string }>>> {
  const params: Record<string, number> = {}
  if (limit !== undefined) {
    params.limit = limit
  }
  return get(`/admin/dict/code/${code}`, params)
}
