<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatRedpackLogTable extends Migration
{
    protected $tableName = 'wechat_redpack_log';

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
                $table->unsignedInteger('market_id')->index()->default(0)->comment('关联活动id');
                $table->unsignedTinyInteger('hb_type')->default(0)->comment('红包类型： 0 普通红包，1裂变红包');
                $table->string('openid')->default('')->comment('微信用户公众号唯一标示');
                $table->unsignedTinyInteger('hassub')->default(0)->comment('是否领取：0未领取，1已领取');
                $table->decimal('money', 10, 2)->default(0)->comment('领取金额');
                $table->unsignedInteger('time')->default(0)->comment('领取时间');
                $table->string('mch_billno')->default('')->comment('商户订单号');
                $table->string('mch_id')->default('')->comment('微信支付商户号');
                $table->string('wxappid')->default('')->comment('公众账号appid');
                $table->string('bill_type')->default('')->comment('订单类型');
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
