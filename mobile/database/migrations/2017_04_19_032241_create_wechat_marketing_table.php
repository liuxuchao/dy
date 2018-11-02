<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatMarketingTable extends Migration
{
    protected $tableName = 'wechat_marketing';

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
                $table->string('marketing_type')->default('')->comment('活动类型');
                $table->string('name')->default('')->comment('活动名称');
                $table->string('keywords')->default('')->comment('扩展词');
                $table->string('command')->default('')->comment('关键词');
                $table->string('description')->default('')->comment('活动说明');
                $table->unsignedInteger('starttime')->default(0)->comment('开始时间');
                $table->unsignedInteger('endtime')->default(0)->comment('结束时间');
                $table->unsignedInteger('addtime')->default(0)->comment('添加时间');
                $table->string('logo')->default('')->comment('logo图');
                $table->string('background')->default('')->comment('活动背景图');
                $table->text('config')->nullable()->default('')->comment('配置信息');
                $table->string('support')->default('')->comment('赞助支持');
                $table->unsignedTinyInteger('status')->default(0)->comment('活动状态: 0未开始,1进行中,2已结束');
                $table->string('qrcode')->default('')->comment('二维码地址');
                $table->string('url')->default('')->comment('活动地址');
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
