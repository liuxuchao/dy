<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImServiceTable extends Migration
{
    protected $tableName = 'im_service';

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
                $table->unsignedInteger('user_id')->default(0)->comment('管理员ID');
                $table->string('user_name', 60)->default('')->comment('管理员名称');
                $table->string('nick_name', 60)->default('')->comment('昵称');
                $table->string('post_desc', 60)->default('')->comment('描述');
                $table->unsignedInteger('login_time')->default(0)->comment('管理员登录时间');
                $table->tinyInteger('chat_status')->default(1)->comment('0-在线 1-离开  2-退出');
                $table->tinyInteger('status')->default(1)->comment('0为删除， 1为正常， 2为暂停');
                //            $table->comment('客服表');
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
