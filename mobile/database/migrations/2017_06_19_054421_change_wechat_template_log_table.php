<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatTemplateLogTable extends Migration
{
    protected $tableName = 'wechat_template_log';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'wechat_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedInteger('wechat_id')->index()->default(0)->comment('公众号id');
            });
        }

        // 判断字段是否存在添加
        if (!Schema::hasColumn($this->tableName, 'msgid')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('msgid')->default(0)->after('wechat_id')->comment('微信消息ID');
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
        if (Schema::hasColumn($this->tableName, 'wechat_id')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('wechat_id');
            });
        }

        // 删除字段
        if (Schema::hasColumn($this->tableName, 'msgid')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('msgid');
            });
        }
    }
}
