<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatQrcodeTable extends Migration
{
    protected $tableName = 'wechat_qrcode';

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
                $table->unsignedTinyInteger('type')->default(0)->comment('二维码类型，0临时，1永久');
                $table->unsignedInteger('expire_seconds')->default(0)->comment('二维码有效时间');
                $table->unsignedInteger('scene_id')->default(0)->comment('场景值ID');
                $table->string('username')->default('')->comment('推荐人');
                $table->string('function')->default('')->comment('功能');
                $table->string('ticket')->default('')->comment('二维码ticket');
                $table->string('qrcode_url')->default('')->comment('二维码路径');
                $table->unsignedInteger('endtime')->default(0)->comment('结束时间');
                $table->unsignedInteger('scan_num')->default(0)->comment('扫描量');
                $table->unsignedTinyInteger('status')->default(1)->comment('状态');
                $table->unsignedInteger('sort')->default(0)->comment('排序');
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
