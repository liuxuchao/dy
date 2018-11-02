<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeBargainGoodsTableBargainDesc extends Migration
{
    protected $tableName = 'bargain_goods';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'bargain_desc')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->string('bargain_desc')->default('')->after('isnot_aduit_reason')->comment('砍价介绍');
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
        if (Schema::hasColumn($this->tableName, 'bargain_desc')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('bargain_desc');
            });
        }
    }
}
