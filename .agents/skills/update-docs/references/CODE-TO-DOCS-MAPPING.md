# 源代码到文档映射

此文件定义了源代码和文档之间的映射关系。它告诉AI当某个代码文件发生变化时，应该去更新哪个文档文件。

## 映射规则

### 通用映射模式

| 源代码路径模式 | 对应文档路径 |
|--------------|------------|
| `src/components/**/*.tsx` | `docs/components/{component-name}.md` |
| `src/hooks/**/*.ts` | `docs/hooks/{hook-name}.md` |
| `src/utils/**/*.ts` | `docs/utils/{util-name}.md` |
| `src/pages/**/*.tsx` | `docs/pages/{page-name}.md` |
| `src/api/**/*.ts` | `docs/api/{api-name}.md` |
| `src/services/**/*.ts` | `docs/services/{service-name}.md` |

### Next.js项目映射

| 源代码路径模式 | 对应文档路径 |
|--------------|------------|
| `app/**/page.tsx` | `docs/app/{route}.md` |
| `app/**/layout.tsx` | `docs/app/{route}-layout.md` |
| `app/api/**/route.ts` | `docs/api/{endpoint}.md` |
| `middleware.ts` | `docs/middleware.md` |
| `next.config.*` | `docs/configuration.md` |

### 配置文件映射

| 配置文件 | 对应文档路径 |
|---------|------------|
| `package.json` (scripts/dependencies) | `docs/getting-started.md` |
| `tsconfig.json` | `docs/typescript.md` |
| `tailwind.config.*` | `docs/styling.md` |
| `.env.example` | `docs/environment-variables.md` |

## 使用方法

1. 当代码文件变更时，查找匹配的路径模式
2. 根据映射确定需要更新的文档文件
3. 如果文档文件不存在，标记为需要创建
4. 按照DOC-CONVENTIONS.md中的规范更新或创建文档

## 自定义

项目应根据自身结构扩展此映射文件。添加项目特有的映射关系时，请保持格式一致。
