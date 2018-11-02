<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeWechatUserTable extends Migration
{
    protected $tableName = 'wechat_user';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 判断字段是否存在 添加
        if (!Schema::hasColumn($this->tableName, 'from')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->unsignedTinyInteger('from')->default(0)->comment('粉丝来源：0 微信公众号关注 1 微信授权注册,2 微信扫码注册');
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
        // 还原字段
        if (Schema::hasColumn($this->tableName, 'from')) {
            Schema::table($this->tableName, function (Blueprint $table) {
                $table->dropColumn('from');
            });
        }
    }
}
