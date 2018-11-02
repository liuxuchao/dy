<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatWallMsgTable extends Migration
{
    protected $tableName = 'wechat_wall_msg';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'wall_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('wall_id')->default(0)->comment('微信墙id');
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
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'wall_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('wall_id');
            });
        }
    }
}
