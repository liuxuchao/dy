<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatRedpackLogTable extends Migration
{
    protected $tableName = 'wechat_redpack_log'; // 现金红包记录

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->tableName, 'notify_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->text('notify_data')->comment('交易数据');
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
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'notify_data')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('notify_data');
            });
        }
    }
}
