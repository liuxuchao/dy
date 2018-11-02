<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeQrpayLogTable extends Migration
{
    protected $tableName = 'qrpay_log'; // 扫码收款记录

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->tableName, 'ru_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('ru_id')->default(0)->comment('商家ID');
                $table->string('openid')->default('')->comment('微信用户openid');
                $table->string('payment_code')->default('')->comment('支付方式');
                $table->string('trade_no')->default('')->comment('支付交易号');
                $table->text('notify_data')->comment('交易数据');
                $table->unsignedTinyInteger('is_settlement')->default(0)->comment('是否结算：0未结算 1已结算 ');
                $table->string('pay_desc')->default('')->comment('备注');
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
        if (Schema::hasColumn($this->tableName, 'ru_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('ru_id');
                $table->dropColumn('openid');
                $table->dropColumn('payment_code');
                $table->dropColumn('trade_no');
                $table->dropColumn('notify_data');
                $table->dropColumn('is_settlement');
                $table->dropColumn('pay_desc');
            });
        }
    }
}
