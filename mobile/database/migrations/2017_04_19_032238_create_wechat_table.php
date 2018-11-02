<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatTable extends Migration
{
    protected $tableName = 'wechat';

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
                $table->string('name')->default('')->comment('公众号名称');
                $table->string('orgid')->default('')->comment('公众号原始ID');
                $table->string('weixin')->default('')->comment('微信号');
                $table->string('token')->default('')->comment('Token');
                $table->string('appid')->default('')->comment('AppID');
                $table->string('appsecret')->default('')->comment('AppSecret');
                $table->string('encodingaeskey')->default('')->comment('EncodingAESKey');
                $table->unsignedTinyInteger('type')->default(0)->comment('公众号类型');
                $table->unsignedTinyInteger('oauth_status')->default(0)->comment('是否开启微信登录');
                $table->string('secret_key')->default('')->comment('密钥');
                $table->string('oauth_redirecturi')->default('')->comment('回调地址');
                $table->unsignedInteger('oauth_count')->default(0)->comment('回调统计');
                $table->unsignedInteger('time')->default(0)->comment('添加时间');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
                $table->unsignedTinyInteger('status')->default(0)->comment('状态');
                $table->unsignedTinyInteger('default_wx')->default(0)->comment('1为平台标识，0为商家标识');
                $table->unsignedInteger('ru_id')->unique()->default(0)->comment('商家ID');
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
