# 团队开发规范文档

> RBAC 项目团队开发统一标准与最佳实践

---

## 📚 文档索引

| 文档 | 描述 | 维护人 |
|------|-------|--------|
| [CODE_STYLE_GUIDE.md](./CODE_STYLE_GUIDE.md) | 代码风格指南 | 开发团队 |
| [DIRECTORY_STRUCTURE.md](./DIRECTORY_STRUCTURE.md) | 目录组织结构规范 | 开发团队 |
| [NAMING_CONVENTIONS.md](./NAMING_CONVENTIONS.md) | 命名约定规范 | 开发团队 |
| [VERSION_CONTROL.md](./VERSION_CONTROL.md) | 版本控制流程 | 开发团队 |
| [COLLABORATION.md](./COLLABORATION.md) | 团队协作规范 | 开发团队 |

---

## 🎯 快速开始

### 新成员加入

1. 阅读本 README 了解项目规范
2. 阅读 [CODE_STYLE_GUIDE.md](./CODE_STYLE_GUIDE.md) 了解代码风格
3. 阅读 [NAMING_CONVENTIONS.md](./NAMING_CONVENTIONS.md) 了解命名约定
4. 阅读 [VERSION_CONTROL.md](./VERSION_CONTROL.md) 了解 Git 工作流
5. 阅读 [COLLABORATION.md](./COLLABORATION.md) 了解团队协作

### 开发前检查清单

- [ ] Git 配置完成（用户名、邮箱）
- [ ] 开发环境配置完成（Node.js, PHP, MySQL）
- [ ] IDE/编辑器配置完成（ESLint, Prettier）
- [ ] 已阅读代码风格指南
- [ ] 已理解分支命名规范
- [ ] 了解 Pull Request 流程

---

## 📖 文档详情

### 1. 代码风格指南

**文件**: [CODE_STYLE_GUIDE.md](./CODE_STYLE_GUIDE.md)

**内容**:
- TypeScript/Vue 代码风格
- PHP/ThinkPHP 代码风格
- CSS/LESS 命名规范 (BEM)
- API 接口规范
- Git 提交信息规范
- 测试代码规范
- 文档注释规范
- 代码审查检查清单

### 2. 目录组织结构规范

**文件**: [DIRECTORY_STRUCTURE.md](./DIRECTORY_STRUCTURE.md)

**内容**:
- 项目根目录结构
- 前端目录详细规范
- 后端目录详细规范
- 测试目录规范
- 配置文件规范
- 文档目录规范
- 资源文件规范

### 3. 命名约定规范

**文件**: [NAMING_CONVENTIONS.md](./NAMING_CONVENTIONS.md)

**内容**:
- 通用命名原则
- 前端命名约定 (TypeScript/Vue)
- 后端命名约定 (PHP/ThinkPHP)
- API 命名约定
- Git 命名约定
- 测试命名约定
- 配置命名约定
- 常见错误对照表

### 4. 版本控制流程

**文件**: [VERSION_CONTROL.md](./VERSION_CONTROL.md)

**内容**:
- Git 工作流程 (Git Flow)
- 分支管理规范
- 提交规范 (Conventional Commits)
- Pull Request 流程
- 版本发布流程
- Tag 管理规范
- .gitignore 规范
- Git 配置规范

### 5. 团队协作规范

**文件**: [COLLABORATION.md](./COLLABORATION.md)

**内容**:
- 角色与职责 (RACI 矩阵)
- 沟通规范（渠道、频率）
- Issue 管理
- 协作流程（功能开发、Bug 修复、紧急问题）
- Code Review 规范
- 文档协作规范
- 权限与安全规范
- CI/CD 规范

---

## 🔧 工具配置

### ESLint 配置

项目使用 ESLint 进行代码检查，配置见：
- 前端: `frontend/.eslintrc.cjs`

### Prettier 配置

项目使用 Prettier 进行代码格式化，配置见：
- 前端: `frontend/.prettierrc.json`

### Playwright 配置

测试配置见：
- 统一配置: `.agents/tools/playwright/config.js`
- 测试报告目录: `.agents/tools/playwright/test-reports/`

---

## 📊 规范更新记录

| 版本 | 日期 | 更新内容 | 更新人 |
|------|------|---------|--------|
| v1.0 | 2026-05-10 | 初始版本，包含所有基础规范 | 开发团队 |

---

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request 来改进这些规范。

### 规范变更流程

1. 在 GitHub Issues 中创建规范改进建议
2. 团队讨论并达成共识
3. 更新相关文档
4. 通知团队成员

---

## 📞 联系方式

- **GitHub Issues**: [项目 Issues](https://github.com/your-org/rbac/issues)
- **技术负责人**: [Tech Lead 邮箱]
- **项目管理**: [PM 邮箱]

---

**最后更新**: 2026-05-10
**维护团队**: RBAC 开发团队