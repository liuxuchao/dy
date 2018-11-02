<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrpAffiliateLogTable extends Migration
{
    protected $tableName = 'drp_affiliate_log';

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('log_id');
                $table->unsignedInteger('order_id')->index()->default(0)->comment('订单号');
                $table->unsignedInteger('time')->default(0)->comment('添加时间');
                $table->unsignedInteger('user_id')->index()->default(0)->comment('会员ID');
                $table->string('user_name')->default('')->comment('姓名');
                $table->decimal('money', 10, 2)->default(0)->comment('佣金');
                $table->unsignedInteger('point')->default(0);
                $table->unsignedTinyInteger('separate_type')->default(0)->comment('分销类型');
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
