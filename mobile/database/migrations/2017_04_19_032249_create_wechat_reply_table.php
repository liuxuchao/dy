<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatReplyTable extends Migration
{
    protected $tableName = 'wechat_reply';

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
                $table->string('type')->default('')->comment('自动回复类型');
                $table->string('content')->default('')->comment('回复内容');
                $table->unsignedInteger('media_id')->index()->default(0)->comment('素材id');
                $table->string('rule_name')->default('')->comment('规则名称');
                $table->unsignedInteger('add_time')->default(0)->comment('添加时间');
                $table->string('reply_type')->default('')->comment('关键词回复内容的类型');
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
