<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQrpayDiscountsTable extends Migration
{
    protected $tableName = 'qrpay_discounts'; // 扫码收款优惠

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
                $table->decimal('min_amount', 10, 2)->default(0)->comment('满金额');
                $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额');
                $table->decimal('max_discount_amount', 10, 2)->default(0)->comment('最高优惠金额');
                $table->unsignedTinyInteger('status')->default(0)->comment('优惠状态(0 关闭，1 开启)');
                $table->unsignedInteger('add_time')->default(0)->comment('创建时间');
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
