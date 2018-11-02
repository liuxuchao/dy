<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUserTagTable extends Migration
{
    protected $tableName = 'wechat_user_tag';

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
                $table->string('openid')->default('')->comment('用户openid');
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
