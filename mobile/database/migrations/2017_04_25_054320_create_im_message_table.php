<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImMessageTable extends Migration
{
    protected $tableName = 'im_message';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('from_user_id')->default(0)->comment('客服对应 im_customer id  客户对应 用户表ID');
                $table->unsignedInteger('to_user_id')->default(0)->comment('客服对应 im_customer id  客户对应 用户表ID');
                $table->unsignedInteger('dialog_id')->default(0)->comment('会话记录');
                $table->text('message')->default('')->comment('聊天内容');
                $table->unsignedInteger('add_time')->default(0)->comment('会话记录');
                $table->tinyInteger('user_type')->default(0)->comment('消息属于  1-客服 2-用户');
                $table->tinyInteger('status')->default(0)->comment('0为已读  1为未读');
                //            $table->comment('聊天消息记录');
            });
        }
    }

    /**
     * Reverse the migrations.
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
