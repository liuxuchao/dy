<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTeamLogTable extends Migration
{
    protected $tableName = 'team_log';
    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 't_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('t_id')->default('0')->comment('拼团活动id');
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
        if (Schema::hasColumn($this->tableName, 't_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('t_id');
            });
        }
		
        
    }
}
