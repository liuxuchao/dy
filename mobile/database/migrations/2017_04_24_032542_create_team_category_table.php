<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamCategoryTable extends Migration
{
    protected $tableName = 'team_category';

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
                $table->string('name')->default('')->comment('频道名称');
                $table->unsignedInteger('parent_id')->default(0)->comment('父级id');
                $table->string('content')->default('')->comment('频道描述');
                $table->string('tc_img')->default('')->comment('频道图标');
                $table->unsignedInteger('sort_order')->default(0)->comment('排序');
                $table->unsignedTinyInteger('status')->default(1)->comment('显示0否 1显示');
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
