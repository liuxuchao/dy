<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatTemplateLogTable extends Migration
{
    protected $tableName = 'wechat_template_log';

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
                $table->string('code')->default('')->comment('模板消息标识');
                $table->string('openid')->default('')->index()->comment('微信用户openid');
                $table->text('data')->nullable()->default('')->comment('消息数据');
                $table->string('url')->default('')->comment('消息链接地址');
                $table->unsignedTinyInteger('status')->default(0)->comment('状态');
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
