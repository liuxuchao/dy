<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQrpayManageTable extends Migration
{
    protected $tableName = 'qrpay_manage'; // 扫码收款码

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
                $table->string('qrpay_name')->default('')->comment('收款码名称');
                $table->unsignedTinyInteger('type')->default(0)->comment('收款码类型(0自助、1 指定)');
                $table->decimal('amount', 10, 2)->default(0)->comment('收款码金额');
                $table->unsignedInteger('discount_id')->default(0)->comment('关联优惠类型id');
                $table->unsignedInteger('tag_id')->default(0)->comment('关联标签id');
                $table->unsignedInteger('qrpay_status')->default(0)->comment('收款状况');
                $table->unsignedInteger('ru_id')->default(0)->comment('商家ID');
                $table->string('qrpay_code')->default('')->comment('二维码链接');
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
