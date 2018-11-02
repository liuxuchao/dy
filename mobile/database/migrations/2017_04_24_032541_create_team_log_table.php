<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamLogTable extends Migration
{
    protected $tableName = 'team_log';

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('team_id');
                $table->unsignedInteger('goods_id')->default(0)->comment('拼团商品id');
                $table->unsignedInteger('start_time')->default(0)->comment('开团时间');
                $table->unsignedTinyInteger('status')->default(0)->comment('拼团状态（1成功，2失败）');
                $table->unsignedTinyInteger('is_show')->default(1)->comment('是否显示');
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
