<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDrpShopTableShopPortrait extends Migration
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
        if (!Schema::hasColumn($this->tableName, 'shop_portrait')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('shop_portrait')->default('')->after('shop_img')->comment('店铺头像');
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
        if (Schema::hasColumn($this->tableName, 'shop_portrait')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('shop_portrait');
            });
        }
    }
}
