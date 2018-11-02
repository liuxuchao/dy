<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBargainStatisticsTable extends Migration
{
    protected $tableName = 'bargain_statistics';

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
				$table->unsignedInteger('bs_id')->default(0)->comment('创建活动id');
				$table->unsignedInteger('user_id')->default(0)->comment('会员id');
				$table->decimal('subtract_price', 10, 2)->default(0)->comment('砍掉商品价格');
				$table->unsignedInteger('add_time')->default(0)->comment('参与砍价时间');
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
