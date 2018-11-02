<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeUsersTable extends Migration
{
    protected $tableName = 'users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'drp_parent_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('drp_parent_id')->default(0)->after('parent_id')->comment('分销商父级id');
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
        if (Schema::hasColumn($this->tableName, 'drp_parent_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('drp_parent_id');
            });
        }
    }
}
