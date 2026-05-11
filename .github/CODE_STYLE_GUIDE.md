# 代码风格指南

## 1. 通用原则

- **一致性优先**：团队所有成员遵循统一的代码风格
- **可读性第一**：代码应该易于阅读和理解
- **简洁为美**：避免不必要的复杂性
- **命名清晰**：变量、函数、类名应自解释其用途

---

## 2. 前端代码风格 (TypeScript/Vue)

### 2.1 TypeScript 规范

#### 变量命名
```typescript
// ✅ 推荐：使用 camelCase 命名变量
const userName = '张三';
const isAuthenticated = true;
const userList: User[] = [];

// ❌ 避免：使用下划线或匈牙利命名
const user_name = '张三';
const strUserName = '张三';
```

#### 函数命名
```typescript
// ✅ 推荐：使用 camelCase，动词前缀
function getUserInfo(): User { }
function validateForm(): boolean { }
function handleLogin(): void { }

// ❌ 避免：使用下划线或无意义名称
function user_info() { }
function doIt() { }
```

#### 类型/接口命名
```typescript
// ✅ 推荐：使用 PascalCase，使用 I 前缀（接口）或描述性名称
interface IUser {
  id: number;
  username: string;
}

interface UserInfo {
  id: number;
  username: string;
}

// ❌ 避免：使用不清晰的缩写
interface U { }
```

#### 组件命名
```typescript
// ✅ 推荐：使用 PascalCase，特征性前缀
export default defineComponent({
  name: 'UserFormModal',
  // 或
  name: 'TableSetting'
});

// ❌ 避免：使用无意义名称
name: 'component1'
```

### 2.2 Vue/React 代码风格

#### 组件文件命名
```
# ✅ 推荐：使用 PascalCase 或 kebab-case
UserFormModal.vue
user-form-modal.vue
TableSetting/
  index.vue
  ColumnSetting.vue

# ❌ 避免：混合使用或不规范的命名
userFormModal.vue
user_form_modal.vue
```

#### Props 定义
```typescript
// ✅ 推荐：详细定义 props，带类型和默认值
interface Props {
  title: string;
  isVisible: boolean;
  dataList: User[];
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
});

// ❌ 避免：无类型定义或缺少默认值
// props: ['title', 'isVisible']
```

### 2.3 CSS/LESS 命名 (BEM 规范)

```css
/* ✅ 推荐：使用 BEM 命名规范 */
.element__item--modifier { }
.sidebar__menu-item { }
.button__icon--primary { }

/* ❌ 避免：使用内联样式或无意义的类名 */
.myclass { }
.red-text { }
#special-id { }
```

---

## 3. 后端代码风格 (PHP/ThinkPHP)

### 3.1 PHP 命名规范

#### 类命名
```php
// ✅ 推荐：使用 PascalCase
class UserController extends BaseController { }
class JwtService { }
class AdminAuth { }

// ❌ 避免：使用下划线或小写
class user_controller { }
class jwt_service { }
```

#### 方法命名
```php
// ✅ 推荐：使用 camelCase，动词描述操作
public function getUserInfo() { }
public function validateLoginData() { }
public function createNewUser() { }

// ❌ 避免：使用下划线或无意义名称
public function get_user_info() { }
public function do_it() { }
```

#### 变量命名
```php
// ✅ 推荐：使用 camelCase
$userName = '张三';
$isValid = true;
$userList = [];

// ❌ 避免：使用匈牙利命名或下划线
$strUserName = '张三';
$user_name = '张三';
```

#### 常量命名
```php
// ✅ 推荐：使用全大写下划线分隔
const MAX_LOGIN_ATTEMPTS = 5;
const TOKEN_EXPIRE_TIME = 7200;

// ❌ 避免：使用小写或混合
const maxLoginAttempts = 5;
const tokenExpire = 7200;
```

#### 数据库表/字段命名
```sql
-- ✅ 推荐：使用下划线命名，小写
-- 表名：名词复数形式
CREATE TABLE user_depts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  dept_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ❌ 避免：驼峰命名或复数形式不统一
CREATE TABLE UserDepts { }
CREATE TABLE userDept { }
```

### 3.2 ThinkPHP 特定规范

#### 控制器命名
```php
// ✅ 推荐：继承 BaseController，使用资源操作方法
class UserController extends BaseController
{
    // RESTful 方法
    public function index() { }    // GET - 列表
    public function create() { }   // GET - 创建表单
    public function save() { }     // POST - 创建
    public function read() { }     // GET - 详情
    public function edit() { }     // GET - 编辑表单
    public function update() { }   // PUT - 更新
    public function delete() { }   // DELETE - 删除
}

// ❌ 避免：使用非 RESTful 方法名
public function getUsers() { }
public function addUser() { }
```

#### 模型命名
```php
// ✅ 推荐：继承基础模型，驼峰转下划线自动映射
class User extends BaseModel
{
    // 自动对应 users 表
    // 自动对应 user_name 字段
}

// ❌ 避免：显式指定表名（除非不遵循约定）
protected $name = 'users';
```

---

## 4. API 接口规范

### 4.1 URL 命名
```
# ✅ 推荐：RESTful 风格，小写，复数名词
GET    /admin/users          # 获取用户列表
GET    /admin/users/{id}     # 获取单个用户
POST   /admin/users          # 创建用户
PUT    /admin/users/{id}    # 更新用户
DELETE /admin/users/{id}    # 删除用户

# ❌ 避免：动词或驼峰命名
GET    /admin/getUsers
GET    /admin/GetUserInfo
POST   /admin/addUser
```

### 4.2 响应格式
```json
// ✅ 推荐：统一的响应格式
{
  "code": 200,
  "msg": "操作成功",
  "data": {
    "id": 1,
    "username": "admin"
  }
}

// 错误响应
{
  "code": 401,
  "msg": "未授权访问",
  "data": null
}

// ❌ 避免：不一致的响应格式
{
  "status": "success",
  "result": { }
}
```

---

## 5. Git 提交信息规范

### 5.1 提交信息格式
```
<type>(<scope>): <subject>

<body>

<footer>
```

### 5.2 Type 类型
```
feat:     新功能
fix:      缺陷修复
docs:     文档变更
style:    代码格式（不影响功能）
refactor: 重构（既不是新功能也不是修复）
perf:     性能优化
test:     测试相关
chore:    构建/工具变更
```

### 5.3 示例
```
feat(user): 添加用户批量导入功能

实现用户数据的 Excel 批量导入功能
- 支持 .xlsx 和 .csv 格式
- 支持字段映射配置
- 添加导入进度展示

Closes #123
```

---

## 6. 测试代码规范

### 6.1 测试文件命名
```
# ✅ 推荐
user.controller.spec.ts
UserService.test.ts
test_comprehensive.py

# ❌ 避免
test1.ts
UserTest.ts
TestUserService.ts
```

### 6.2 测试函数命名
```typescript
// ✅ 推荐：描述性名称，说明测试场景
describe('UserController', () => {
  it('should return 401 when token is invalid', () => { });
  it('should return user list when request is valid', () => { });
});

// ❌ 避免：无意义的名称
describe('UserController', () => {
  it('test1', () => { });
  it('case1', () => { });
});
```

---

## 7. 配置和环境变量规范

### 7.1 配置文件命名
```
# ✅ 推荐：环境特定配置使用 .env.* 格式
.env                 # 通用配置（不应提交）
.env.development    # 开发环境
.env.production     # 生产环境
.env.example        # 环境变量示例（应提交）

# ❌ 避免：自定义命名
config.dev.js
production.config.js
```

### 7.2 环境变量命名
```
# ✅ 推荐：使用下划线分隔，大写表示不公开值
DATABASE_HOST=localhost
DATABASE_PORT=3306
API_BASE_URL=http://localhost:8000
SECRET_KEY=your-secret-key

# ❌ 避免：驼峰或无意义名称
dbHost = "localhost"
APIURL = "http://localhost:8000"
```

---

## 8. 文档注释规范

### 8.1 函数/方法注释
```typescript
/**
 * 获取用户详细信息
 * @param userId - 用户ID
 * @returns 用户信息对象，包含用户名、邮箱、角色等
 * @throws {AppException} 当用户不存在时抛出异常
 */
function getUserInfo(userId: number): UserInfo {
  // ...
}
```

### 8.2 类文件注释
```php
<?php
/**
 * 用户控制器
 *
 * 负责处理用户相关的HTTP请求
 * 包括用户CRUD、权限验证等
 *
 * @package App\Admin\Controller
 * @author  开发团队
 */
class UserController extends BaseController {
  // ...
}
```

---

## 9. 代码审查检查清单

提交代码前，请确认：

- [ ] 代码符合上述命名规范
- [ ] 变量命名清晰，有意义
- [ ] 函数长度适中（建议不超过50行）
- [ ] 必要的注释已添加
- [ ] 无硬编码的配置值
- [ ] 错误处理已添加
- [ ] ESLint/代码格式化检查通过
- [ ] 单元测试已更新（如适用）

---

**版本**: v1.0
**最后更新**: 2026-05-10
**维护人**: 开发团队