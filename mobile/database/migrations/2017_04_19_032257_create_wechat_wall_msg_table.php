<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatWallMsgTable extends Migration
{
    protected $tableName = 'wechat_wall_msg';

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
                $table->unsignedInteger('wall_id')->default(0)->comment('微信墙活动id');
                $table->unsignedInteger('user_id')->default(0)->comment('用户编号');
                $table->text('content')->comment('留言内容');
                $table->unsignedInteger('addtime')->default(0)->comment('发送时间');
                $table->unsignedInteger('checktime')->default(0)->comment('审核时间');
                $table->unsignedTinyInteger('status')->default(0)->comment('消息审核状态:0未审核,1审核通过');
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
