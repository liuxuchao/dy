<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQrpayTagTable extends Migration
{
    protected $tableName = 'qrpay_tag'; // 扫码收款标签

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
                $table->string('tag_name')->default('')->comment('标签名称');
                $table->unsignedInteger('self_qrpay_num')->default(0)->comment('相关自助收款码数量');
                $table->unsignedInteger('fixed_qrpay_num')->default(0)->comment('相关指定金额收款码数量');
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
