<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTeamGoodsTable extends Migration
{
    protected $tableName = 'team_goods';
    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'team_desc')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('team_desc')->default('')->comment('拼团介绍');
            });
        }
		if (!Schema::hasColumn($this->tableName, 'isnot_aduit_reason')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('isnot_aduit_reason')->default('')->comment('审核未通过理由');
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
        if (Schema::hasColumn($this->tableName, 'team_desc')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('team_desc');
            });
        }
		if (Schema::hasColumn($this->tableName, 'isnot_aduit_reason')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('isnot_aduit_reason');
            });
        }
        
    }
}
