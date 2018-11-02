<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBargainGoodsTable extends Migration
{
    protected $tableName = 'bargain_goods';

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
				$table->string('bargain_name')->default('')->comment('砍价活动标题');
                $table->unsignedInteger('goods_id')->default(0)->comment('砍价商品id');
				$table->unsignedInteger('start_time')->default(0)->comment('活动开始时间');
				$table->unsignedInteger('end_time')->default(0)->comment('活动结束时间');
				$table->unsignedInteger('add_time')->default(0)->comment('添加时间');
                $table->decimal('goods_price', 10, 2)->default(0)->comment('活动原价');
                $table->unsignedInteger('min_price')->default(0)->comment('价格区间（最小值');
				$table->unsignedInteger('max_price')->default(0)->comment('价格区间（最大值）');
				$table->decimal('target_price', 10, 2)->default(0)->comment('砍价目标价格');
                $table->unsignedInteger('total_num')->default(0)->comment('参与人数');
                $table->unsignedTinyInteger('is_hot')->default(0)->comment('是否热销');
				$table->unsignedTinyInteger('is_audit')->default(0)->comment('0未审核，1未通过，2已审核');
				$table->string('isnot_aduit_reason')->default('')->comment('审核未通过原因');
				$table->unsignedTinyInteger('status')->default(0)->comment('活动状态（0进行中、1关闭）');
                $table->unsignedTinyInteger('is_delete')->default(0)->comment('活动删除状态（1删除）');
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
