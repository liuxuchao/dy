<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatShareCountTable extends Migration
{
    protected $tableName = 'wechat_share_count'; // 微信JSSDK分享统计表

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
                $table->string('openid')->default('')->comment('用户公众平台唯一标识');
                $table->unsignedTinyInteger('share_type')->default(0)->comment('分享类型 如分享到朋友圈 默认0');
                $table->string('link')->default('')->comment('分享链接');
                $table->unsignedInteger('share_time')->default(0)->comment('分享时间');
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
