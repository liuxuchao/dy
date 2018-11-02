<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrpConfigTable extends Migration
{
    protected $tableName = 'drp_config';

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
                $table->string('code')->default('')->comment('关键词');
                $table->string('type')->default('')->comment('字段类型');
                $table->string('store_range')->default('')->comment('值范围');
                $table->text('value')->default('')->comment('值');
                $table->string('name')->default('')->comment('字段中文名称');
                $table->string('warning')->default('')->comment('提示');
                $table->unsignedInteger('sort_order')->default(0)->comment('排序');
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
