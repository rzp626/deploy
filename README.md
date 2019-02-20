本项目是基于Laravel-admin + Magephp构建的简易上线发布系统

注意事项：
1. 检查配置生成的.mage.yml文件权限；
2. 检查宿主主机的所需log目录；
3. 配置文件，确认好权限用户，分发主机，对应使用的ssh端口；
4. 自测自定义命令的执行结果，待测；
5. 发布、回滚跑异步redis队列, 由supervisor调度

to-do-list
1. model整理
2. 静态文件瘦身
3. 表单项值校验: 是否为空，是否为0
4. 异步发布、回滚时，回调通知待修改


参考链接：
  Larval-admin手册：https://laravel-admin.org/docs/zh
  Magallanes V4：https://www.magephp.com/
  Laravel数据迁移： https://laravel-china.org/docs/laravel/5.5/migrations/1329#generating-migrations
