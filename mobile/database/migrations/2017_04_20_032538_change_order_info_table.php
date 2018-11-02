<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOrderInfoTable extends Migration
{
    protected $tableName = 'order_info';
    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'drp_is_separate')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('drp_is_separate')->default(0)->comment('订单分销状态');
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
        if (Schema::hasColumn($this->tableName, 'drp_is_separate')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('drp_is_separate');
            });
        }
    }
}
