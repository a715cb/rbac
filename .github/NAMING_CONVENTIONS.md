# 命名约定规范

## 1. 通用命名原则

### 1.1 基本规则

- **使用有意义的名称**：名称应清晰表达其用途
- **避免缩写**：除非是公认的标准缩写（如 `URL`、`API`、`ID`）
- **一致性**：在整个项目中保持命名风格一致
- **语言统一**：英文命名，避免中文拼音

### 1.2 命名对照表

| 类型 | 规则 | 示例 |
|------|------|------|
| 变量 | camelCase | `userName`, `isActive` |
| 常量 | UPPER_SNAKE_CASE | `MAX_RETRY_COUNT` |
| 函数/方法 | camelCase，动词前缀 | `getUserInfo()` |
| 类/接口 | PascalCase | `UserController` |
| 文件名 | kebab-case (前端)，PascalCase (PHP类) | `user-service.ts`, `UserController.php` |
| 数据库表 | snake_case，复数名词 | `user_depts` |
| 数据库字段 | snake_case | `user_name` |
| API 路径 | kebab-case，复数名词 | `/admin/users` |
| CSS 类名 | BEM 规范 | `sidebar__menu-item` |
| Git 分支 | kebab-case | `feature/user-auth` |

---

## 2. 前端命名约定 (TypeScript/Vue)

### 2.1 变量命名

```typescript
// ✅ 基本类型
const userName = '张三';
const isAuthenticated = true;
const count = 0;
const items: string[] = [];

// ✅ 布尔值使用 is/has/can 前缀
const isVisible = true;
const hasPermission = false;
const canEdit = true;

// ✅ 数组使用复数名词或 List/Array 后缀
const users = ['张三', '李四'];
const userList = [];
const productArray = [];

// ✅ 对象使用名词
const userInfo = { name: '张三', age: 25 };
const configOptions = { theme: 'dark' };

// ❌ 避免
const data = {};
const temp = {};
const temp1 = {};
```

### 2.2 函数命名

```typescript
// ✅ 行为动词前缀
function getUserInfo() {}
function setUserName() {}
function validateForm() {}
function handleClick() {}
function fetchData() {}
function updateUser() {}
function deleteUser() {}

// ✅ 布尔值函数 is/has/can 前缀
function isLoggedIn() {}
function hasPermission(permission: string) {}
function canEditUser() {}

// ❌ 避免
function userInfo() {}
function process() {}
function doIt() {}
```

### 2.3 接口/类型命名

```typescript
// ✅ 接口使用 I 前缀或描述性名称
interface IUser {
  id: number;
  username: string;
}

interface UserInfo {
  id: number;
  nickname: string;
}

interface ApiResponse<T> {
  code: number;
  msg: string;
  data: T;
}

// ✅ 类型别名使用 PascalCase
type UserId = number;
type Callback = () => void;
```

### 2.4 组件命名

```vue
<!-- ✅ 单文件组件使用 PascalCase -->
<!-- 文件名：UserFormModal.vue -->
<template>
  <UserFormModal />
</template>

<!-- ✅ 组件 name 使用 PascalCase -->
<script setup lang="ts">
defineOptions({
  name: 'UserFormModal'
})
</script>

<!-- ❌ 避免：混合命名 -->
<!-- userFormModal.vue 或 user_form_modal.vue -->
```

### 2.5 路由命名

```typescript
// ✅ 路由路径使用 kebab-case
const routes = [
  { path: '/system/user', component: UserIndex },
  { path: '/system/role', component: RoleIndex },
  { path: '/monitor/login-log', component: LoginLogIndex },
];

// ❌ 避免：驼峰或 PascalCase
// { path: '/system/User', ... }
// { path: '/system/userManagement', ... }
```

### 2.6 Store 命名

```typescript
// ✅ Store 文件名使用 kebab-case
// stores/user.ts
// stores/settings.ts

// ✅ Store state 使用 camelCase
const state = {
  userName: '张三',
  isLoggedIn: false,
  userPermissions: [],
};

// ✅ Action 使用 camelCase，动词前缀
const actions = {
  async login() {},
  async logout() {},
  setUserInfo() {},
};
```

---

## 3. 后端命名约定 (PHP/ThinkPHP)

### 3.1 类命名

```php
// ✅ 控制器：PascalCase，后缀 Controller
class UserController extends BaseController {}
class AuthController extends BaseController {}
class DashboardController extends BaseController {}

// ✅ 模型：PascalCase，后缀 Model（可省略）
class User extends BaseModel {}  // 对应 users 表
class Role extends BaseModel {}  // 对应 roles 表
class UserRole extends BaseModel {}

// ✅ 服务：PascalCase，后缀 Service
class JwtService {}
class AdminAuth {}

// ✅ 中间件：PascalCase，后缀 Middleware
class AuthCheckMiddleware {}
class ApiPermissionMiddleware {}

// ✅ 验证器：PascalCase，后缀 Validate
class UserValidate {}
class LoginValidate {}
```

### 3.2 方法命名

```php
// ✅ 使用 camelCase，动词前缀
public function getUserInfo() {}
public function createUser() {}
public function updateUser() {}
public function deleteUser() {}
public function validateLoginData() {}

// ✅ RESTful 方法名
public function index() {}     // 列表
public function create() {}    // 创建表单
public function save() {}      // 创建
public function read() {}      // 详情
public function edit() {}      // 编辑表单
public function update() {}    // 更新
public function delete() {}    // 删除
```

### 3.3 变量命名

```php
// ✅ camelCase
$userName = '张三';
$isValid = true;
$userList = [];

// ✅ 数组使用复数或 List 后缀
$users = User::select()->find();
$userList = User::select()->find();
$rolePermissions = [];

// ❌ 避免
$_userName = '张三';  // 不要使用下划线前缀
$u = User::find();    // 缩写
```

### 3.4 常量命名

```php
// ✅ 全大写，下划线分隔
const MAX_LOGIN_ATTEMPTS = 5;
const TOKEN_EXPIRE_TIME = 7200;
const DEFAULT_PAGE_SIZE = 20;

// ✅ 静态属性
public static $tableName = 'users';
```

### 3.5 数据库表和字段命名

```sql
-- ✅ 表名：snake_case，复数名词
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(50) NOT NULL,
    user_password VARCHAR(255) NOT NULL,
    user_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- ✅ 关联表：使用下划线连接，两个表名单数形式
-- user_role 表连接 users 和 roles 表
CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ✅ 字段命名
user_name          -- 用户名
user_password      -- 密码（加密存储）
user_email         -- 邮箱
role_name          -- 角色名
role_code          -- 角色代码
menu_path          -- 菜单路径
dept_leader        -- 部门负责人
```

---

## 4. API 命名约定

### 4.1 URL 路径命名

```
# ✅ RESTful 风格：kebab-case，复数名词
GET    /admin/users              -- 用户列表
GET    /admin/users/{id}        -- 用户详情
POST   /admin/users             -- 创建用户
PUT    /admin/users/{id}        -- 更新用户
DELETE /admin/users/{id}        -- 删除用户

GET    /admin/roles              -- 角色列表
GET    /admin/menus              -- 菜单列表
GET    /admin/departments        -- 部门列表

# ❌ 避免
GET    /admin/getUsers
GET    /admin/GetUserInfo
POST   /admin/addUser
POST   /admin/user/create
```

### 4.2 请求参数命名

```json
// ✅ 请求体：camelCase
{
  "userName": "admin",
  "password": "123456",
  "nickname": "管理员",
  "roleIds": [1, 2, 3]
}

// ✅ 查询参数：camelCase
GET /admin/users?pageNum=1&pageSize=20&keyword=admin

// ❌ 避免
{
  "user_name": "admin",
  "UserName": "admin",
  "USER_NAME": "admin"
}
```

### 4.3 响应参数命名

```json
// ✅ 统一响应格式：camelCase
{
  "code": 200,
  "msg": "操作成功",
  "data": {
    "userId": 1,
    "userName": "admin",
    "userEmail": "admin@example.com",
    "createdAt": "2026-05-10 10:00:00"
  }
}

// ✅ 分页响应
{
  "code": 200,
  "msg": "获取成功",
  "data": {
    "list": [],
    "total": 100,
    "pageNum": 1,
    "pageSize": 20
  }
}
```

---

## 5. Git 命名约定

### 5.1 分支命名

```
# ✅ 格式：{type}/{short-description}
feature/user-authentication     # 新功能
feature/user-role-permission   # 用户角色权限功能
bugfix/login-validation        # 登录验证 bug 修复
hotfix/security-vulnerability   # 安全漏洞修复
refactor/user-controller       # 重构用户控制器
docs/api-documentation         # 文档更新
test/playwright-integration     # 测试相关
chore/dependency-update        # 依赖更新

# ❌ 避免
Fix                           # 太笼统
new-feature                    # 驼峰命名
user-auth                      # 缺少类型前缀
```

### 5.2 Tag 标签命名

```
# ✅ 格式：v{version}
v1.0.0                        # 正式版本
v1.1.0                        # 次版本
v2.0.0-beta                   # 测试版本

# ❌ 避免
release-1.0.0                  # 不是标准格式
final                         # 不明确
```

---

## 6. 测试命名约定

### 6.1 测试文件命名

```
# ✅ 前端 (TypeScript)
user.service.spec.ts           # 用户服务测试
user.controller.spec.ts       # 用户控制器测试
auth.middleware.spec.ts        # 认证中间件测试

# ✅ Python
test_comprehensive.py          # 综合测试
test_user_api.py               # 用户 API 测试

# ❌ 避免
test1.ts                        # 无意义编号
TestUserService.ts             # 混用命名
```

### 6.2 测试函数命名

```typescript
// ✅ 描述性名称，说明测试场景
describe('UserService', () => {
  it('should return user info when user exists', () => { });
  it('should throw error when user does not exist', () => { });
  it('should validate email format correctly', () => { });
});

// ❌ 避免
describe('UserService', () => {
  it('test1', () => { });
  it('case1', () => { });
  it('should work', () => { });  // 太模糊
});
```

### 6.3 测试报告命名

```
# ✅ 格式：{prefix}_{test_type}_{timestamp}
test_report_comprehensive_20260510_143020.json
test_report_api_20260510_143020.json
test_report_ui_20260510_143020.json
test_report_regression_20260510_143020.json
```

---

## 7. 配置命名约定

### 7.1 环境变量命名

```
# ✅ 全大写，下划线分隔，不公开值
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=rbac
DATABASE_USER=root
DATABASE_PASSWORD=secret

API_BASE_URL=http://localhost:8000
FRONTEND_BASE_URL=http://localhost:5173

JWT_SECRET=your-secret-key
JWT_EXPIRE_TIME=7200

# ❌ 避免
db_host=localhost              # 混用大小写
APIURL=http://localhost:8000  # 非标准缩写
password=secret               # 缺少前缀
```

### 7.2 配置文件命名

```
# ✅ 环境特定配置
.env                          # 通用配置
.env.development              # 开发环境
.env.production               # 生产环境
.env.local                    # 本地覆盖（不提交）
.env.example                  # 配置示例（应提交）

# ❌ 避免
config.dev.js                  # 非标准格式
settings.json                  # 太笼统
production.config.yaml         # 混用命名
```

---

## 8. 常见错误对照表

| 错误示例 | 正确示例 | 说明 |
|---------|---------|------|
| `user_name` (变量) | `userName` | TypeScript/PHP 变量用 camelCase |
| `getuserinfo()` | `getUserInfo()` | 方法名用 camelCase |
| `userController` | `UserController` | PHP 类名用 PascalCase |
| `users` (表名) | `users` | ✅ 正确，表名用复数 |
| `UserRoles` (表名) | `user_roles` | 数据库表用 snake_case |
| `/admin/getUsers` | `/admin/users` | RESTful URL 用复数名词 |
| `test1.ts` | `user.service.spec.ts` | 测试文件应有描述性名称 |
| `fix bug` | `bugfix/login-validation` | 分支名应包含类型和描述 |

---

**版本**: v1.0
**最后更新**: 2026-05-10
**维护人**: 开发团队