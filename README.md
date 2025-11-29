# Bestkit -- 使用 Bestkit 构建网站。
<g><text fill="#FFFFFF" transform="matrix(1.945,0,0,2.368,0,31)">Bestkit</text></g>

- 使用Symfony组件构建的模块化和轻量级CMS
- A modular and lightweight CMS built with Symfony components

- 一个灵活、快速的内容管理框架，使用 Symfony 组件和 Vuejs 构建。
- A flexible and fast content management framework built with Symfony components and Vuejs.

- 这个项目取自 Pagekit。由于官方已放弃了该项目的开发，因此粉丝们以 Bestkit 的名义与您分享。
- This project is taken from Pagekit. Since the development of the project has been abandoned officially, fans share it with you under the name of Bestkit.

- Bestkit是一个模块化、轻量级的内容管理开发框架。



## 如何使用

在根目录下执行如下命令
```bash
composer install           # 安装php依赖项
yarn install               # 安装js依赖项
yarn gulp                  # 运行默认任务 (gulp 5.0.1)

- 运行其他任务：
yarn gulp lint              # 检查代码质量
yarn gulp watch             # 监视 LESS 资产
yarn gulp cldr              # 编译 CLDR 数据
```

## 启动项目

```bash
php -S localhost:8000
```

注意：由于gulp 5.0.1版本的变化，直接使用gulp命令可能需要全局安装gulp-cli或使用npx gulp
```

### 如果运行composer install命令时提示有不安全的依赖项，您可以在composer.json文件中添加如下配置：
```json
"config": {
    "vendor-dir": "app/vendor",
    "audit": {
        "block-insecure": false
    }
}
```