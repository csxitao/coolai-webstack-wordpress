# CoolAI 导航系统 v2.4.5

WordPress 网址导航系统，支持分类管理、书签收藏、云端同步。

## 功能特性

### 核心功能
- **分类管理**：创建/重命名/删除分类，拖拽排序
- **书签管理**：添加/编辑/删除网站，支持批量操作
- **智能获取**：自动获取网站标题和图标
- **搜索**：实时搜索，支持历史记录

### 交互体验
- **右键菜单**：分类和网站支持右键快捷操作
- **拖拽排序**：分类和网站均可拖拽调整顺序
- **快捷键**：Ctrl+V 快速粘贴添加网址
- **视图切换**：标准视图 / 紧凑视图

### 数据管理
- **本地存储**：数据保存在浏览器 localStorage
- **云端同步**：登录用户支持服务器同步
- **导入导出**：支持 JSON 格式备份

## 文件结构

```
mu-plugins/
├── coolai-integration.php    # WordPress REST API 后端
themes/justnews/
├── coolai-nav.html           # 前端页面（独立运行）
```

## 技术栈

- 前端：原生 HTML/CSS/JavaScript（无框架依赖）
- 后端：WordPress REST API (PHP)
- 存储：localStorage + WordPress 数据库

## 浏览器支持

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
