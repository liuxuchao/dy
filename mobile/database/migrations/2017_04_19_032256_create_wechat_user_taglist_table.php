<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUserTaglistTable extends Migration
{
    protected $tableName = 'wechat_user_taglist';

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
                $table->unsignedInteger('tag_id')->index()->default(0)->comment('标签id');
                $table->string('name')->default('')->comment('标签名字，UTF8编码');
                $table->unsignedInteger('count')->default(0)->comment('标签内用户数量');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
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
