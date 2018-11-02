<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePayLogTable extends Migration
{
    protected $tableName = 'pay_log';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'openid')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('openid')->default('')->comment('微信用户openid');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'transid')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('transid')->default('')->comment('微信支付交易id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'openid')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('openid');
            });
        }
        if (Schema::hasColumn($this->tableName, 'transid')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('transid');
            });
        }
    }
}
