<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatMediaTable extends Migration
{
    protected $tableName = 'wechat_media';

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('wechat_id')->index()->default(0)->comment('公众号id');
                $table->string('title')->default('')->comment('图文消息标题');
                $table->string('command')->default('')->comment('关键词');
                $table->string('author')->default('')->comment('作者');
                $table->unsignedTinyInteger('is_show')->default(0)->comment('是否显示封面，1为显示，0为不显示');
                $table->string('digest')->default('')->comment('图文消息的描述');
                $table->text('content')->nullable()->default('')->comment('图文消息页面的内容，支持HTML标签');
                $table->string('link')->default('')->comment('点击图文消息跳转链接');
                $table->string('file')->default('')->comment('图片链接');
                $table->unsignedInteger('size')->default(0)->comment('媒体文件上传后，获取时的唯一标识');
                $table->string('file_name')->default('')->comment('媒体文件上传时间戳');
                $table->string('thumb')->default('')->comment('缩略图');
                $table->unsignedInteger('add_time')->default(0)->comment('添加时间');
                $table->unsignedInteger('edit_time')->default(0)->comment('编辑时间');
                $table->string('type')->nullable()->default('');
                $table->string('article_id')->nullable()->default('');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
            });
        }
    }

    /**
     * 回滚数据库迁移
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable($this->tableName)) {
            Schema::drop($this->tableName);
        }
    }
}
