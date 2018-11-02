<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOrderGoodsTable extends Migration
{
    protected $tableName = 'order_goods';
    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'drp_money')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->decimal('drp_money', 10, 2)->default(0)->comment('分销佣金百分比');
            });
        }
        if (!Schema::hasColumn($this->tableName, 'is_distribution')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('is_distribution')->default(0)->comment('订单商品是否参与分销');
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
        if (Schema::hasColumn($this->tableName, 'drp_money')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('drp_money');
            });
        }
        if (Schema::hasColumn($this->tableName, 'is_distribution')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('is_distribution');
            });
        }
    }
}
