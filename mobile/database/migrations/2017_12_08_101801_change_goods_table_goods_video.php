<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGoodsTableGoodsVideo extends Migration
{
    protected $tableName = 'goods';

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn($this->tableName, 'goods_video')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('goods_video')->default('')->comment('商品视频');
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
        if (Schema::hasColumn($this->tableName, 'goods_video')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('goods_video');
            });
        }
    }
}
