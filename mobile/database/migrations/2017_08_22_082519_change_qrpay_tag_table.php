<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeQrpayTagTable extends Migration
{
    protected $tableName = 'qrpay_tag'; // 扫码收款标签

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
            });
        }
    }
}
