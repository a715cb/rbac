# RBAC 权限管理系统 - API管理页交互分析报告

**文档版本**：1.0 | **生成日期**：2026-05-12 | **页面路径**：`src/pages/system/api/index.vue`

---

## 一、页面概述

### 1.1 页面定位

API管理页用于管理系统中的所有接口配置，支持按关键词、请求方法（GET/POST/PUT/DELETE）、分组、状态进行多维度筛选。每个接口可关联菜单，支持启用/禁用切换。

### 1.2 页面功能模块划分

| 模块 | 位置 | 功能 |
|------|------|------|
| 搜索表单区 | 页面顶部 | 4维筛选：关键词/方法/分组/状态 |
| 表格工具栏 | 表格上方 | 新增按钮 + 列设置 |
| 数据表格 | 页面主体 | 分页接口列表（含彩色方法标签） |
| 表单弹窗 | 模态层 | ApiFormModal 新增/编辑接口 |

### 1.3 依赖组件

| 组件 | 用途 |
|------|------|
| `ApiFormModal` | 接口表单弹窗 |
| `TableSetting` | 表格列设置 |
| `usePageTable` | 表格状态管理 |

---

## 二、用户操作流程

### 2.1 页面初始化

```
onMounted → fetchData()
    ├── loading = true
    ├── getApiList({ page, limit, keyword, method, group, status })
    │   ├── 成功 → tableData + pagination.total + groupList 更新
    │   └── 失败 → console.error(DEV) + 拦截器提示
    └── loading = false
```

### 2.2 筛选流程

| 筛选维度 | 组件类型 | 选项来源 |
|----------|----------|----------|
| 关键词 | a-input (Enter触发) | 接口名称/标识/路径模糊匹配 |
| 请求方法 | a-select | GET/POST/PUT/DELETE 静态选项 |
| 分组 | a-select | 后端返回的 groupList |
| 状态 | a-select | 正常(1)/禁用(0) 静态选项 |

点击"查询"→ pagination.current=1 → fetchData()
点击"重置"→ 清空所有条件 → pagination.current=1 → fetchData()

### 2.3 CRUD操作

| 操作 | 触发 | 确认 | 成功后 |
|------|------|------|--------|
| 新增 | currentRecord=null → 打开弹窗 | 无 | handleSearch() |
| 编辑 | currentRecord=record → 打开弹窗 | 无 | handleSearch() |
| 删除 | Popconfirm确认 | 二次确认 | fetchData()（最后一页回退处理） |
| 状态切换 | Switch点击 | 乐观更新 | 失败时回滚record.status |

---

## 三、交互触发条件与状态反馈

### 3.1 请求方法标签

| 方法 | 颜色 |
|------|------|
| GET | HTTP_METHODS中配置的颜色（通常绿色） |
| POST | HTTP_METHODS中配置的颜色（通常蓝色） |
| PUT | HTTP_METHODS中配置的颜色（通常橙色） |
| DELETE | HTTP_METHODS中配置的颜色（通常红色） |

通过 `HTTP_METHODS` 常量数组动态获取颜色映射。

### 3.2 接口路径样式

```html
<code class="api-path">{{ record.path }}</code>
```
路径以灰色代码块样式展示：
- 背景：var(--ant-color-fill-quaternary, #f5f5f5)
- 内边距：2px 8px
- 圆角：4px

### 3.3 状态切换乐观更新

```typescript
const handleStatusChange = async (record, checked) => {
    const oldStatus = record.status
    record.status = checked ? 1 : 0  // 先更新UI
    try {
        await changeApiStatus(record.id, checked ? 1 : 0)
        message.success(...)
    } catch {
        record.status = oldStatus  // 失败回滚
        console.error(DEV)
    }
}
```

### 3.4 删除后的分页回退

```
if (tableData.value.length <= 1 && (pagination.current ?? 1) > 1) {
    pagination.current = (pagination.current ?? 2) - 1
}
```

---

## 四、动画效果

| 元素 | 动画 |
|------|------|
| 弹窗 | Modal缩放淡入淡出 |
| Switch | 滑动切换 |
| 表格loading | 骨架屏 |
| 全屏模式 | position切换 |

---

## 五、响应式适配

| 屏幕 | 行为 |
|------|------|
| 桌面 | 正常表格 + 全屏模式 |
| ≤480px | 横向滚动 |

---

## 六、异常处理

| 异常 | 处理 |
|------|------|
| 列表加载失败 | console.error + 拦截器提示 |
| 删除失败 | console.error + 拦截器提示 |
| 状态切换失败 | 乐观回滚 record.status=oldStatus |
| 分组数据为空 | groupList=[]，下拉无选项 |

---

## 七、交互关联

| 关联 | 方式 |
|------|------|
| ApiFormModal | v-model:visible + @success |
| TableSetting | usePageTable |
| 后端API | getApiList, deleteApi, changeApiStatus |

---

**文档信息**

| 项目 | 内容 |
|------|------|
| 源文件 | `src/pages/system/api/index.vue` |
| 子组件 | ApiFormModal |
| 依赖API | getApiList, deleteApi, changeApiStatus |