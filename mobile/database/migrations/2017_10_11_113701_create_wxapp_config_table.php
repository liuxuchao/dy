<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWxappConfigTable extends Migration
{
    protected $tableName = 'wxapp_config'; // 小程序配置表

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
                $table->string('wx_appname')->default('')->comment('小程序名称');
                $table->string('wx_appid')->default('')->comment('小程序AppID');
                $table->string('wx_appsecret')->default('')->comment('小程序AppSecret');
                $table->string('wx_mch_id')->default('')->comment('商户号');
                $table->string('wx_mch_key')->default('')->comment('支付密钥');
                $table->string('token_secret')->default('')->comment('Token授权加密key');
                $table->unsignedInteger('add_time')->default(0)->comment('添加时间');
                $table->unsignedTinyInteger('status')->default(0)->comment('状态：0 关闭 1 开启');
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
