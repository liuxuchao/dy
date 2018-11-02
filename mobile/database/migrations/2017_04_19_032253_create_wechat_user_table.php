<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUserTable extends Migration
{
    protected $tableName = 'wechat_user';

    /**
     * 运行数据库迁移
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('uid');
                $table->unsignedInteger('wechat_id')->index()->default(0)->comment('公众号id');
                $table->unsignedTinyInteger('subscribe')->default(0)->comment('用户是否订阅该公众号标识');
                $table->string('openid')->default('')->comment('用户公众平台唯一标识');
                $table->string('nickname')->default('')->comment('用户昵称');
                $table->unsignedTinyInteger('sex')->default(0)->comment('用户性别');
                $table->string('city')->default('')->comment('用户所在城市');
                $table->string('country')->default('')->comment('用户所在国家');
                $table->string('province')->default('')->comment('用户所在省份');
                $table->string('language')->default('')->comment('语言');
                $table->string('headimgurl')->default('')->comment('用户头像');
                $table->unsignedInteger('subscribe_time')->default(0)->comment('关注时间');
                $table->string('remark')->default('')->comment('备注');
                $table->string('privilege')->default('');
                $table->string('unionid')->default('')->comment('用户开放平台唯一标识');
                $table->unsignedInteger('groupid')->default(0)->comment('用户组id');
                $table->unsignedInteger('ect_uid')->default(0)->comment('ecshop会员id');
                $table->unsignedTinyInteger('bein_kefu')->default(0)->comment('是否处在多客服流程');
                $table->unsignedInteger('parent_id')->default(0)->comment('推荐人id');
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
