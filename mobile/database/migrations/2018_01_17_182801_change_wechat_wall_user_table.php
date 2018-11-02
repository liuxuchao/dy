<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatWallUserTable extends Migration
{
    protected $tableName = 'wechat_wall_user';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在 添加
        if (!Schema::hasColumn($this->tableName, 'sign_number')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('sign_number')->default('')->comment('号码');
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
        if (Schema::hasColumn($this->tableName, 'sign_number')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('sign_number');
            });
        }
    }
}
