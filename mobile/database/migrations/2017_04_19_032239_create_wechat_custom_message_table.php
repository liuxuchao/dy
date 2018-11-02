<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatCustomMessageTable extends Migration
{
    protected $tableName = 'wechat_custom_message';

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
                $table->unsignedInteger('uid')->index()->default(0)->comment('wechat_user表用户uid');
                $table->string('msg')->default('')->comment('信息内容');
                $table->unsignedInteger('send_time')->default(0)->comment('发送时间');
                $table->unsignedTinyInteger('is_wechat_admin')->default(0)->comment('是否管理员回复: 0否,1是');
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
