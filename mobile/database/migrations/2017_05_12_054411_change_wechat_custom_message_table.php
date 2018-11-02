<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatCustomMessageTable extends Migration
{
    protected $tableName = 'wechat_custom_message';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在重命名字段
        if (Schema::hasColumn($this->tableName, 'wechat_admin_id') && !Schema::hasColumn($this->tableName, 'is_wechat_admin')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->renameColumn('wechat_admin_id', 'is_wechat_admin');
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
        // 还原字段
        if (Schema::hasColumn($this->tableName, 'is_wechat_admin')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->renameColumn('is_wechat_admin', 'wechat_admin_id');
            });
        }
    }
}
