<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatMessageLogTable extends Migration
{
    protected $tableName = 'wechat_message_log';

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
                $table->string('fromusername')->default('')->comment('发送方帐号openid');
                $table->unsignedInteger('createtime')->default(0)->comment('消息创建时间');
                $table->string('keywords')->default('')->comment('微信消息内容');
                $table->string('msgtype')->default('')->comment('微信消息类型');
                $table->unsignedBigInteger('msgid')->default(0)->comment('微信消息ID');
                $table->unsignedTinyInteger('is_send')->default(0)->comment('发送状态');
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
