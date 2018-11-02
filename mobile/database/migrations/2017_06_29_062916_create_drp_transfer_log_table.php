<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrpTransferLogTable extends Migration
{
    protected $tableName = 'drp_transfer_log'; // 分销佣金转出记录

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
                $table->unsignedInteger('user_id')->default(0)->comment('会员id');
                $table->decimal('money', 10, 2)->default(0)->comment('转出金额');
                $table->unsignedInteger('add_time')->default(0)->comment('转出时间');
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
