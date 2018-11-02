<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatTemplateTable extends Migration
{
    protected $tableName = 'wechat_template';

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
                $table->string('template_id')->default('')->comment('模板id');
                $table->string('code')->default('')->comment('模板消息标识');
                $table->string('content')->default('')->comment('自定义备注');
                $table->text('template')->nullable()->default('')->comment('模板消息模板');
                $table->string('title')->default('')->comment('模板消息标题');
                $table->unsignedInteger('add_time')->default(0)->comment('添加时间');
                $table->unsignedTinyInteger('status')->default(0)->comment('启用状态 0 禁止 1 开启');
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
