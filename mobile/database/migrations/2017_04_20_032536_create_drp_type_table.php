<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrpTypeTable extends Migration
{
    protected $tableName = 'drp_type';

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
                $table->unsignedInteger('user_id')->default(0)->comment('会员ID');
                $table->unsignedInteger('cat_id')->index()->default(0)->comment('分类ID');
                $table->unsignedInteger('goods_id')->default(0)->comment('商品ID');
                $table->unsignedTinyInteger('type')->default(0)->comment('分销商品类型：0全部，1分类，2商品');
                $table->unsignedInteger('add_time')->default(0)->comment('添加时间');
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
