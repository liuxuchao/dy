<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatPrizeTable extends Migration
{
    protected $tableName = 'wechat_prize';

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
                $table->string('openid')->default('')->comment('微信用户openid');
                $table->string('prize_name')->default('')->comment('奖品名称');
                $table->unsignedTinyInteger('issue_status')->default(0)->comment('发放状态，0未发放，1发放');
                $table->string('winner')->default('')->comment('信息');
                $table->unsignedInteger('dateline')->default(0)->comment('中奖时间');
                $table->unsignedTinyInteger('prize_type')->default(0)->comment('是否中奖，0未中奖，1中奖');
                $table->string('activity_type')->default('')->comment('活动类型');
                $table->unsignedInteger('market_id')->index()->default(0)->comment('关联活动ID');
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
