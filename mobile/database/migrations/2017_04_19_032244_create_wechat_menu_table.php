<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatMenuTable extends Migration
{
    protected $tableName = 'wechat_menu';

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
                $table->unsignedInteger('wechat_id')->index()->default(0)->comment('公众号id');
                $table->unsignedInteger('pid')->default(0)->comment('父级ID');
                $table->string('name')->default('')->comment('菜单标题');
                $table->string('type')->default('')->comment('菜单的响应动作类型');
                $table->string('key')->default('')->comment('菜单KEY值，click类型必须');
                $table->string('url')->default('')->comment('网页链接，view类型必须');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
                $table->unsignedTinyInteger('status')->default(0)->comment('显示状态');
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
