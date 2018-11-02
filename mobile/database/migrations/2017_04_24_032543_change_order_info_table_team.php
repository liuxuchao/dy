<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOrderInfoTableTeam extends Migration
{
    protected $tableName = 'order_info';
    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'team_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('team_id')->default(0)->comment('开团记录id');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'team_parent_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('team_parent_id')->default(0)->comment('团长id');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'team_user_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('team_user_id')->default(0)->comment('团员id');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'team_price')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->decimal('team_price', 10, 2)->default(0)->comment('拼团商品价格');
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
        if (Schema::hasColumn($this->tableName, 'team_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('team_id');
            });
        }
        if (Schema::hasColumn($this->tableName, 'team_parent_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('team_parent_id');
            });
        }
        if (Schema::hasColumn($this->tableName, 'team_user_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('team_user_id');
            });
        }
        if (Schema::hasColumn($this->tableName, 'team_price')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('team_price');
            });
        }
    }
}
