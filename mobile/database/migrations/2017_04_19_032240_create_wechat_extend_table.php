<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatExtendTable extends Migration
{
    protected $tableName = 'wechat_extend';

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
                $table->string('name')->default('')->comment('功能名称');
                $table->string('keywords')->default('')->comment('关键词');
                $table->string('command')->default('')->comment('扩展词');
                $table->text('config')->nullable()->default('')->comment('配置信息');
                $table->string('type')->default('')->comment('类型');
                $table->unsignedTinyInteger('enable')->default(0)->comment('是否安装，1为已安装，0未安装');
                $table->string('author')->default('')->comment('作者');
                $table->string('website')->default('')->comment('网址');
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
