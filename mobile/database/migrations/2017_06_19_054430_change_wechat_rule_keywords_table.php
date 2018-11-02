<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatRuleKeywordsTable extends Migration
{
    protected $tableName = 'wechat_rule_keywords';

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
                $table->unsignedInteger('wechat_id')->default(0)->after('id')->comment('公众号id');
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
    }
}
