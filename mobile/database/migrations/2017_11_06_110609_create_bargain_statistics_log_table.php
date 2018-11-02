<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBargainStatisticsLogTable extends Migration
{
    protected $tableName = 'bargain_statistics_log';

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
				$table->string('goods_attr_id')->default('')->comment('属性id');
				$table->unsignedInteger('user_id')->default(0)->comment('会员id');
				$table->decimal('final_price', 10, 2)->default(0)->comment('砍后最终购买价');
				$table->unsignedInteger('add_time')->default(0)->comment('添加时间');
				$table->unsignedInteger('count_num')->default(0)->comment('参与人次');
                $table->unsignedTinyInteger('status')->default(0)->comment('状态（1完成）');
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
