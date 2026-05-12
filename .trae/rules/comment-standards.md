# Vue 组件注释规范

## 1. 文件头部注释

所有 `.vue` 文件必须在 `<template>` 标签之前添加文件头部注释，使用 HTML 注释块：

```vue
<!--
  @文件: 文件名.vue
  @用途: 一句话概述组件用途
  @描述: 详细描述组件功能、交互流程、核心逻辑
         可换行继续描述
  @核心逻辑:
    1. 逻辑要点一
    2. 逻辑要点二
    3. 逻辑要点三
-->
```

**规范要点：**
- `@文件` 必须与实际文件名一致
- `@用途` 控制在一句话内，简洁明确
- `@描述` 可多行，说明组件的完整功能和使用场景
- `@核心逻辑` 用编号列表列出关键流程，3-5 条为宜
- 弹窗组件需在 `@描述` 中说明新增/编辑模式的区分方式

## 2. 模板注释

### 2.1 区域注释
对模板中的主要区域使用 HTML 注释标注：

```html
<!-- 统计卡片区域：响应式栅格布局 -->
<a-row :gutter="[16, 16]">
  ...
</a-row>

<!-- 登录表单：垂直布局，校验规则绑定 -->
<a-form ...>
  ...
</a-form>
```

### 2.2 复杂结构注释
对条件渲染、循环、复杂属性绑定添加行内注释：

```html
<!-- 移动端隐藏左侧区域 -->
<div class="login-left" v-if="!isMobile">

<!-- 验证码组件：支持刷新和客户端校验 -->
<Captcha ref="captchaRef" v-model:captcha="formState.captcha" />

<!-- 编辑模式下编码不可修改 -->
<a-input :disabled="isEdit" />
```

### 2.3 注释原则
- 不注释显而易见的结构（如 `<a-button>确定</a-button>`）
- 注释"为什么"而非"是什么"
- 条件渲染（v-if/v-else）必须注释分支含义

## 3. 脚本注释

### 3.1 变量注释

使用 `/** */` JSDoc 风格注释关键变量：

```typescript
/** 表格加载状态 */
const loading = ref(false)

/** 部门树形数据：与 tableData 同步，供 DeptFormModal 的上级部门选择器使用 */
const treeData = ref<DeptInfo[]>([])

/** 搜索表单，字段与后端 Query 接口对齐 */
const searchForm = reactive<ApiQuery>({
  keyword: '',       // 搜索关键词
  method: undefined, // HTTP 方法筛选
  status: undefined  // 状态筛选
})
```

**规范要点：**
- 简单变量用单行 `/** */`
- 复杂变量可在行内添加字段说明
- 不注释类型定义中已自描述的属性

### 3.2 方法注释

使用 JSDoc 格式，包含功能描述、参数和关键流程：

```typescript
/**
 * 获取部门列表数据
 * @description 调用 API 获取部门树形数据，同步更新表格数据和树形选择器数据，
 *              并默认展开所有节点
 */
const fetchData = async () => { ... }

/**
 * 删除部门
 * @param record - 待删除的部门记录
 * @description 调用删除 API，成功后刷新列表数据
 */
const handleDelete = async (record: DeptInfo) => { ... }
```

**规范要点：**
- 简单方法（如 `handleAdd`）用单行 `/** 新增顶级部门 */`
- 复杂方法必须用 `@description` 说明执行流程
- 参数用 `@param` 标注，格式为 `@param name - 描述`
- 不需要 `@returns`（Vue 组件方法通常无返回值）

### 3.3 computed 属性注释

```typescript
/** 是否为编辑模式：根据 record 是否存在判断 */
const isEdit = computed(() => !!props.record)

/**
 * 最终可见列配置
 * @description 在基础列配置上增强特定列的渲染逻辑：
 *   - name 列：添加自定义筛选下拉、搜索高亮渲染
 *   - status 列：渲染为 Switch 开关
 *   - action 列：渲染操作按钮组
 */
const visibleColumns = computed(() => { ... })
```

### 3.4 watch 监听器注释

```typescript
/**
 * 监听弹窗可见状态变化
 * @param val - 弹窗是否可见
 * @description 弹窗打开时加载成员数据，关闭时清空成员列表
 */
watch(
  () => props.visible,
  (val) => { ... }
)
```

### 3.5 Props 和 Emits 注释

```typescript
/** 组件属性定义 */
interface Props {
  visible: boolean       // 弹窗是否可见，支持 v-model:visible 双向绑定
  record: DeptInfo | null  // 编辑时传入的部门记录，null 表示新增模式
  parentId?: number      // 新增子部门时指定的上级部门ID
}

/** 组件事件定义 */
const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void  // 更新弹窗可见状态
  (e: 'success'): void                          // 表单提交成功后触发
}>()
```

## 4. 样式注释

### 4.1 区域注释
对主要样式块添加功能说明：

```less
/* 错误页面容器：全屏居中布局，浅灰背景 */
.error-page { ... }

/* 统计卡片样式 */
.stat-card { ... }
```

### 4.2 关键属性注释
对非显而易见的样式值添加说明：

```less
.login-left {
  flex: 0 0 300px; /* 固定左侧面板宽度 300px */
  border-right: 1px solid var(--ant-color-border-secondary, #f0f0f0);
}
```

### 4.3 暗色主题注释
暗色主题适配块必须添加说明：

```less
/* 暗色主题适配 */
[data-theme='dark'] { ... }
```

## 5. 注释禁区

以下情况**禁止**添加注释：

1. **自描述代码**：`const userName = '张三'` 不需要注释
2. **重复代码含义**：`// 设置 loading 为 true` 在 `loading.value = true` 之前是多余的
3. **过时注释**：代码修改后必须同步更新注释
4. **注释掉的代码**：使用版本控制而非注释保留废弃代码
5. **分隔线注释**：禁止使用 `// ====================` 等装饰性分隔线

## 6. 语言规范

- 所有注释使用**中文**
- 技术术语保留英文原文（如 API、Token、Props、computed、watch）
- 注释语言简洁专业，避免口语化表达
- 使用第三人称描述（"获取数据"而非"我获取数据"）

## 7. 格式规范

- HTML 注释：`<!-- 注释内容 -->`
- 单行 JS 注释：`/** 注释内容 */`
- 多行 JS 注释：
  ```typescript
  /**
   * 注释内容第一行
   * @param name - 参数描述
   * @description 详细描述
   */
  ```
- 行内注释：`const x = 1 // 简短说明`
- 注释与代码之间空一行
- 注释内部不使用 emoji
