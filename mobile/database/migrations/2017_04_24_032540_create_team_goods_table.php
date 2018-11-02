<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamGoodsTable extends Migration
{
    protected $tableName = 'team_goods';

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
                $table->unsignedInteger('goods_id')->default(0)->comment('拼团商品id');
                $table->decimal('team_price', 10, 2)->default(0)->comment('拼团商品价格');
                $table->unsignedInteger('team_num')->default(0)->comment('几人团');
                $table->unsignedInteger('validity_time')->default(0)->comment('开团有效期(小时)');
                $table->unsignedInteger('limit_num')->default(0)->comment('已参团人数(添加虚拟数量)');
                $table->unsignedInteger('astrict_num')->default(0)->comment('限购数量');
                $table->unsignedInteger('tc_id')->default(0)->comment('频道id');
                $table->unsignedTinyInteger('is_audit')->default(0)->comment('0未审核，1未通过，2通过');
                $table->unsignedTinyInteger('is_team')->default(1)->comment('显示0否 1显示');
                $table->unsignedInteger('sort_order')->default(0)->comment('排序');
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
