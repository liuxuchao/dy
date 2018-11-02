<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQrpayLogTable extends Migration
{
    protected $tableName = 'qrpay_log'; // 扫码收款记录

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
                $table->string('pay_order_sn')->default('')->comment('收款订单号');
                $table->decimal('pay_amount', 10, 2)->default(0)->comment('收款金额');
                $table->unsignedInteger('qrpay_id')->default(0)->comment('关联收款码id');
                $table->unsignedInteger('pay_user_id')->default(0)->comment('支付用户id');
                $table->unsignedInteger('pay_status')->default(0)->comment('是否支付(0未支付 1已支付)');
                $table->unsignedInteger('add_time')->default(0)->comment('记录时间');
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
