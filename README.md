# Niva - PHP项目打包工具

Niva 是一个基于 Laravel Zero 开发的命令行工具，用于将 PHP 项目打包为 ZIP 文件。它能够自动识别常见的 PHP 框架，智能排除不必要的文件和目录，并支持自定义配置。

## 功能特性

- 🚀 **自动框架识别**: 支持 Laravel、Lumen、Symfony、CodeIgniter、Yii、ThinkPHP、Webman、Laminas、Slim 等主流框架
- 📁 **智能排除**: 自动排除 `.git`、`node_modules`、`vendor`、`logs` 等常见忽略目录
- ⚙️ **配置灵活**: 支持从 `.env` 文件读取自定义排除配置
- 🗜️ **压缩可控**: 支持 0-9 级压缩等级设置
- 📦 **输出自定义**: 可指定输出目录和文件名

## 安装

1. 克隆项目：
```bash
git clone <repository-url>
cd niva
```

2. 安装依赖：
```bash
composer install
```

3. 使用可执行文件：
```bash
chmod +x niva
./niva --help
```

## 使用方法

### 基本用法

```bash
# 打包指定目录
./niva build /path/to/your/project

# 打包当前目录
./niva build .

# 显示帮助信息
./niva build --help
```

### 高级选项

```bash
# 指定输出目录
./niva build /path/to/project --output=/path/to/output

# 设置压缩等级 (0-9, 默认为6)
./niva build /path/to/project --level=9

# 额外排除文件或目录
./niva build /path/to/project --exclude="temp,*.log,debug"
```

### .env 配置支持

在项目根目录的 `.env` 文件中添加 `EXCLUDE` 配置：

```env
# JSON 数组格式
EXCLUDE=["temp", "*.tmp", "debug.log", "uploads"]

# 或逗号分隔格式
EXCLUDE="temp,*.tmp,debug.log,uploads"
```

## 支持的框架

Niva 能够自动识别以下 PHP 框架并应用相应的排除规则：

### Laravel
- 检测文件：`artisan` + `composer.json` (包含 laravel/framework)
- 额外排除：`storage/app/public`, `storage/debugbar`, `storage/logs`, `bootstrap/cache`, `public/storage`

### Lumen
- 检测文件：`artisan` + `composer.json` (包含 laravel/lumen-framework)
- 额外排除：`storage/logs`, `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/views`, `bootstrap/cache`

### Symfony
- 检测文件：`bin/console` + `composer.json` (包含 symfony/framework-bundle 或 symfony/symfony)
- 额外排除：`var/cache`, `var/log`, `var/sessions`

### Webman
- 检测文件：`start.php` + `composer.json` (包含 workerman/webman 或 workerman/webman-framework)
- 额外排除：`runtime`, `storage/logs`

### Laminas (Zend Framework)
- 检测文件：`composer.json` (包含 laminas/laminas-mvc 或 zendframework/zend-mvc)
- 额外排除：`data/cache`, `data/logs`, `data/sessions`

### Slim Framework
- 检测文件：`composer.json` (包含 slim/slim)
- 额外排除：`logs`, `cache`

### CodeIgniter
- 检测文件：`index.php` + `application/config/config.php`
- 额外排除：`application/cache`, `application/logs`

### Yii
- 检测文件：`yii` 或 `protected/yiic.php`
- 额外排除：`runtime`, `assets`

### ThinkPHP
- 检测文件：`think` 或 `ThinkPHP` 目录
- 额外排除：`runtime`, `data`

## 默认排除列表

Niva 默认会排除以下文件和目录：

```
.git, .svn, .hg
node_modules, vendor
.idea, .vscode
.DS_Store, Thumbs.db
*.log, logs, cache
.env, .env.local, .env.*.local
composer.lock, package-lock.json, yarn.lock
tests, phpunit.xml, phpunit.xml.dist
.phpunit.result.cache, coverage, .nyc_output
build, dist, tmp, temp
storage/logs, storage/framework/cache
storage/framework/sessions, storage/framework/views
bootstrap/cache
```

## 示例

### 打包 Laravel 项目
```bash
./niva build /path/to/laravel-project --output=/home/user/releases --level=9
```

输出：
```
开始打包项目...
源目录: /path/to/laravel-project
输出目录: /home/user/releases
检测到框架: laravel
已添加 156 个文件到压缩包
打包完成: /home/user/releases/www.zip
文件大小: 2.34 MB
```

### 使用自定义排除
```bash
./niva build /path/to/project --exclude="uploads,temp,*.cache"
```

### 从 .env 读取配置
在项目的 `.env` 文件中：
```env
EXCLUDE=["node_modules", "*.log", "temp", "uploads"]
```

然后运行：
```bash
./niva build /path/to/project
```

## 开发

### 运行测试
```bash
./vendor/bin/pest
```

### 代码格式化
```bash
./vendor/bin/pint
```

## 许可证

MIT License
