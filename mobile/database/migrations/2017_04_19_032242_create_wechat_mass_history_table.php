<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatMassHistoryTable extends Migration
{
    protected $tableName = 'wechat_mass_history';

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
                $table->unsignedInteger('media_id')->index()->default(0)->comment('素材id');
                $table->string('type')->default('')->comment('发送内容类型');
                $table->string('status')->default('')->comment('发送状态，对应微信通通知状态');
                $table->unsignedInteger('send_time')->default(0)->comment('发送时间');
                $table->string('msg_id')->default('')->comment('微信端返回的消息ID');
                $table->unsignedInteger('totalcount')->default(0)->comment('group_id下粉丝数或者openid_list中的粉丝数');
                $table->unsignedInteger('filtercount')->default(0);
                $table->unsignedInteger('sentcount')->default(0)->comment('发送成功的粉丝数');
                $table->unsignedInteger('errorcount')->default(0)->comment('发送失败的粉丝数');
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
