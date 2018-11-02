<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDrpShopTable extends Migration
{
    protected $tableName = 'drp_shop';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'credit_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('credit_id')->default(0)->after('type')->comment('分销商等级id');
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
        // 删除字段
        if (Schema::hasColumn($this->tableName, 'credit_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('credit_id');
            });
        }
    }
}
