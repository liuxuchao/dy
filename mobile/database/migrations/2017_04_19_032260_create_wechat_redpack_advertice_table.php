<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatRedpackAdverticeTable extends Migration
{
    protected $tableName = 'wechat_redpack_advertice';

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
                $table->unsignedInteger('market_id')->index()->default(0)->comment('关联活动id');
                $table->string('icon')->default('')->comment('商家图标');
                $table->string('content')->default('')->comment('广告内容');
                $table->string('url')->default('')->comment('广告链接');
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
