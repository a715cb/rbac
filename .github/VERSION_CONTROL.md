# 版本控制流程规范

## 1. Git 工作流程

### 1.1 分支策略

本项目采用 **Git Flow** 分支策略：

```
main (生产环境)
├── develop (开发分支)
│   ├── feature/* (功能分支)
│   ├── bugfix/* (缺陷修复分支)
│   ├── hotfix/* (紧急修复分支)
│   └── release/* (发布分支)
```

### 1.2 分支说明

| 分支类型 | 命名规则 | 生命周期 | 合并目标 |
|---------|---------|---------|---------|
| main | `main` | 永久 | - |
| develop | `develop` | 永久 | main |
| feature | `feature/{description}` | 功能开发 | develop |
| bugfix | `bugfix/{description}` | 缺陷修复 | develop |
| hotfix | `hotfix/{description}` | 紧急修复 | main + develop |
| release | `release/{version}` | 发布准备 | main + develop |

---

## 2. 分支管理规范

### 2.1 创建分支

```bash
# 从 develop 创建功能分支
git checkout develop
git pull origin develop
git checkout -b feature/user-authentication

# 从 main 创建热修复分支
git checkout main
git pull origin main
git checkout -b hotfix/security-vulnerability
```

### 2.2 分支命名规范

```
# ✅ 推荐
feature/user-authentication
feature/user-role-permission
bugfix/login-validation
bugfix/api-response-format
hotfix/security-vulnerability
hotfix/critical-bug
release/v1.0.0

# ❌ 避免
new-feature
fix
fix-bug
feature1
temp
test
```

### 2.3 分支更新

```bash
# 定期从 develop 拉取最新代码
git fetch origin
git rebase origin/develop

# 或者合并最新代码（保留分支历史）
git merge origin/develop
```

---

## 3. 提交规范

### 3.1 提交信息格式

```
<type>(<scope>): <subject>

<body>

<footer>
```

### 3.2 Type 类型

| 类型 | 说明 | 示例 |
|------|------|------|
| feat | 新功能 | `feat(user): 添加用户批量导入功能` |
| fix | 缺陷修复 | `fix(auth): 修复登录过期后无法自动刷新Token` |
| docs | 文档变更 | `docs: 更新API接口文档` |
| style | 代码格式（不影响功能） | `style: 格式化代码风格` |
| refactor | 重构（非新功能非修复） | `refactor(user): 重构用户权限验证逻辑` |
| perf | 性能优化 | `perf(api): 优化用户列表查询性能` |
| test | 测试相关 | `test: 添加用户CRUD单元测试` |
| chore | 构建/工具变更 | `chore: 升级Playwright到最新版本` |
| revert | 回退提交 | `revert: 回退feat/user-auth提交` |

### 3.3 Scope 范围

```
# 前端
feat(frontend): 前端相关
feat(backend): 后端相关
feat(api): API相关
feat(ui): UI/样式相关
feat(auth): 认证授权相关

# 后端模块
feat(user): 用户管理
feat(role): 角色管理
feat(menu): 菜单管理
feat(dept): 部门管理
feat(api): 接口管理
feat(dict): 字典管理
feat(log): 日志管理

# 全局
docs: 文档
chore: 工具/构建
test: 测试
```

### 3.4 提交示例

```bash
# ✅ 标准提交
git commit -m "feat(user): 添加用户头像上传功能

实现用户头像上传功能，支持以下特性：
- 支持 JPG、PNG 格式
- 最大文件大小 2MB
- 自动生成缩略图
- 支持裁剪功能

Closes #123"

# ✅ 仅修改的
git commit -m "fix(auth): 修复Token刷新时race condition问题"

# ✅ 包含breaking change
git commit -m "feat(api): 重构用户接口返回格式

BREAKING CHANGE: user 接口返回字段由 userName 改为 username
需要前端同步修改"

# ❌ 避免
git commit -m "fix bug"
git commit -m "update"
git commit -m "WIP"
git commit -m "asdf"
```

### 3.5 提交检查清单

- [ ] 提交信息清晰描述变更内容
- [ ] 包含相关的 Issue 编号（如果有）
- [ ] 已运行代码格式化和 lint 检查
- [ ] 已添加/更新相应的测试
- [ ] 变更的代码已通过本地测试

---

## 4. Pull Request 流程

### 4.1 创建 Pull Request

```bash
# 1. 确保分支是最新的
git checkout feature/user-authentication
git rebase origin/develop

# 2. 推送分支
git push origin feature/user-authentication

# 3. 在 GitHub/GitLab 创建 PR
```

### 4.2 PR 描述模板

```markdown
## 📋 概述
<!-- 简要描述这个 PR 的目的 -->

## 🎯 主要变更
<!-- 详细列出主要变更内容 -->
- 新增用户头像上传功能
- 添加图片裁剪组件
- 更新用户详情接口

## 📝 变更类型
- [ ] 新功能 (feat)
- [ ] 缺陷修复 (fix)
- [ ] 重构 (refactor)
- [ ] 文档更新 (docs)
- [ ] 其他 (chore)

## 🔗 相关 Issue
Closes #123

## ✅ 检查清单
- [ ] 代码已通过 ESLint 检查
- [ ] 已添加单元测试
- [ ] 本地测试通过
- [ ] 文档已更新
- [ ] API 接口文档已更新

## 📸 截图（UI 变更）
<!-- 如果有 UI 变更，添加截图 -->

## 🧪 测试结果
<!-- 本地测试结果 -->
```

### 4.3 Code Review 要求

**提交者要求**：
- 确保 PR 描述完整清晰
- 包含必要的截图或测试结果
- 响应审查意见并及时修改

**审查者要求**：
- 在 24 小时内进行审查
- 检查代码风格、功能实现、测试覆盖
- 提供建设性的反馈意见

### 4.4 PR 合并

```bash
# ✅ 合并方式
# 1. Squash and merge（推荐用于 feature 分支）
#   将多个提交合并为一个，保持历史整洁

# 2. Merge（保留完整历史）
#   用于需要保留完整提交历史的场景

# 3. Rebase and merge
#   用于需要保持线性历史的场景
```

---

## 5. 版本发布流程

### 5.1 版本号规则

```
{MAJOR}.{MINOR}.{PATCH}

- MAJOR: 主版本号，不兼容的重大变更
- MINOR: 次版本号，向后兼容的功能新增
- PATCH: 修订号，向后兼容的缺陷修复
```

### 5.2 发布流程

```bash
# 1. 从 develop 创建 release 分支
git checkout develop
git pull origin develop
git checkout -b release/v1.1.0

# 2. 更新版本号
# 修改 package.json, composer.json 等版本文件

# 3. 测试和修复
git commit -m "chore: bump version to v1.1.0"

# 4. 合并到 main
git checkout main
git merge release/v1.1.0 --no-ff
git tag -a v1.1.0 -m "Release version v1.1.0"
git push origin main --tags

# 5. 合并回 develop
git checkout develop
git merge release/v1.1.0 --no-ff
git push origin develop

# 6. 删除 release 分支
git branch -d release/v1.1.0
git push origin --delete release/v1.1.0
```

### 5.3 热修复流程

```bash
# 1. 从 main 创建 hotfix 分支
git checkout main
git pull origin main
git checkout -b hotfix/v1.0.1

# 2. 修复并提交
git commit -m "fix: 修复关键安全漏洞"

# 3. 合并到 main
git checkout main
git merge hotfix/v1.0.1 --no-ff
git tag -a v1.0.1 -m "Hotfix version v1.0.1"
git push origin main --tags

# 4. 合并回 develop
git checkout develop
git merge hotfix/v1.0.1 --no-ff
git push origin develop

# 5. 删除 hotfix 分支
git branch -d hotfix/v1.0.1
```

---

## 6. Tag 管理规范

### 6.1 创建 Tag

```bash
# 创建轻量 tag
git tag v1.0.0

# 创建附注 tag（推荐）
git tag -a v1.0.0 -m "Release version 1.0.0"

# 推送 tag
git push origin v1.0.0

# 推送所有 tag
git push origin --tags
```

### 6.2 查看 Tag

```bash
# 列出所有 tag
git tag

# 列出特定版本 tag
git tag -l "v1.*"

# 查看 tag 详细信息
git show v1.0.0
```

---

## 7. .gitignore 规范

### 7.1 必须忽略的文件

```
# 依赖
node_modules/
vendor/

# 构建产物
dist/
build/
*.pyc
__pycache__/

# 环境配置（包含敏感信息）
.env
.env.local
*.local

# IDE
.idea/
.vscode/
*.swp
*.swo

# 测试报告
test-reports/
test-screenshots/
*.log

# 系统文件
.DS_Store
Thumbs.db
```

### 7.2 应该保留的文件

```
# 保持目录结构
!.gitkeep
```

---

## 8. Git 配置规范

### 8.1 用户配置

```bash
# 设置用户名和邮箱
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# 每个仓库单独设置（项目内）
git config user.name "Your Name"
git config user.email "your.email@example.com"
```

### 8.2 别名配置

```bash
# 常用别名
git config --global alias.st status
git config --global alias.co checkout
git config --global alias.br branch
git config --global alias.ci commit
git config --global alias.lg "log --oneline --graph --decorate"

# 高级别名
git config --global alias.last "log -1 HEAD"
git config --global alias.unstage "reset HEAD --"
```

### 8.3 推送配置

```bash
# 设置默认推送行为
git config --global push.default current

# 设置 pull 策略
git config --global pull.rebase false  # 合并策略
git config --global pull.rebase true   # 变基策略（保持线性历史）
```

---

**版本**: v1.0
**最后更新**: 2026-05-10
**维护人**: 开发团队