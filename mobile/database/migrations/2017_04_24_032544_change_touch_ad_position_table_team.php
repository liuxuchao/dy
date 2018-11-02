<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTouchAdPositionTableTeam extends Migration
{
    protected $tableName = 'touch_ad_position';
    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'tc_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('tc_id')->default(0)->comment('频道id');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'tc_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('tc_type')->default('')->comment('广告类型');
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
        if (Schema::hasColumn($this->tableName, 'tc_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('tc_id');
            });
        }
        if (Schema::hasColumn($this->tableName, 'tc_type')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('tc_type');
            });
        }
    }
}
