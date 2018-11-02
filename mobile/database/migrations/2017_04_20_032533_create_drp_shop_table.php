<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDrpShopTable extends Migration
{
    protected $tableName = 'drp_shop';

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
                $table->unsignedInteger('user_id')->unique()->default(0)->comment('会员id');
                $table->string('shop_name')->default('')->comment('店铺名称');
                $table->string('real_name')->default('')->comment('真实姓名');
                $table->string('mobile')->default('')->comment('手机号');
                $table->string('qq')->default('')->comment('qq');
                $table->string('shop_img')->default('')->comment('店铺背景图');
                $table->unsignedInteger('cat_id')->default(0)->comment('分类id');
                $table->unsignedInteger('create_time')->default(0)->comment('创建时间');
                $table->unsignedTinyInteger('isbuy')->default(0)->comment('是否购买成为分销商');
                $table->unsignedTinyInteger('audit')->default(0)->comment('店铺审核,0未审核,1已审核');
                $table->unsignedTinyInteger('status')->default(0)->comment('店铺状态');
                $table->decimal('shop_money', 10, 2)->default(0)->comment('获得佣金');
                $table->unsignedInteger('shop_points')->default(0)->comment('获得积分');
                $table->unsignedTinyInteger('type')->default(2)->comment('分销商品类型：0全部，1分类，2商品');
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
