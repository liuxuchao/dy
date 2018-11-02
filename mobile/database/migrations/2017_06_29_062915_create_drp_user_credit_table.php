<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrpUserCreditTable extends Migration
{
    protected $tableName = 'drp_user_credit'; // 分销商等级

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
                $table->string('credit_name')->default('')->comment('等级名称');
                $table->unsignedInteger('min_money')->default(0)->comment('金额下限');
                $table->unsignedInteger('max_money')->default(0)->comment('金额上限');
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
