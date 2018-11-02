<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImDialogTable extends Migration
{
    protected $tableName = 'im_dialog';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('customer_id')->default(0)->comment('客户ID');
                $table->unsignedInteger('services_id')->default(0)->comment('客服ID');
                $table->unsignedInteger('goods_id')->default(0)->comment('商品ID');
                $table->unsignedInteger('store_id')->default(0)->comment('商家ID');
                $table->unsignedInteger('start_time')->default(0)->comment('开始时间');
                $table->unsignedInteger('end_time')->default(0)->comment('结束时间');
                $table->tinyInteger('origin')->default(0)->comment('1-PC 2-phone');
                $table->tinyInteger('status')->default(1)->comment('1-未结束  2-已结束');
                //            $table->comment('会话记录');
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
        if (Schema::hasTable($this->tableName)) {
            Schema::drop($this->tableName);
        }
    }
}
