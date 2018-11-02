<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatWallUserTable extends Migration
{
    protected $tableName = 'wechat_wall_user';

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
                $table->unsignedInteger('wall_id')->index()->default(0)->comment('微信墙活动id');
                $table->string('nickname')->default('')->comment('用户昵称');
                $table->unsignedTinyInteger('sex')->default(0)->comment('性别:1男,2女,0保密');
                $table->string('headimg')->default('')->comment('头像');
                $table->unsignedTinyInteger('status')->default(0)->comment('用户审核状态:0未审核,1审核通过');
                $table->unsignedInteger('addtime')->default(0)->comment('添加时间');
                $table->unsignedInteger('checktime')->default(0)->comment('审核时间');
                $table->string('openid')->index()->default('')->comment('微信用户openid');
                $table->string('wechatname')->default('')->comment('微信用户昵称');
                $table->string('headimgurl')->default('')->comment('微信用户头像');
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
