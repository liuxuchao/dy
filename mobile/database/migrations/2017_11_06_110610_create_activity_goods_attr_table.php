<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityGoodsAttrTable extends Migration
{
    protected $tableName = 'activity_goods_attr';

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
                $table->unsignedInteger('bargain_id')->default(0)->comment('活动id');
				$table->unsignedInteger('goods_id')->default(0)->comment('商品id');
				$table->unsignedInteger('product_id')->default(0)->comment('属性id');
				$table->decimal('target_price', 10, 2)->default(0)->comment('砍价目标价格');
				$table->string('type')->default('')->comment('活动类型');
				
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
