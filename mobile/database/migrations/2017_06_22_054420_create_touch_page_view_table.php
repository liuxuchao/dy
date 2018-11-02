<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTouchPageViewTable extends Migration
{
    protected $tableName = 'touch_page_view';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable($this->tableName)) {
            Schema::create($this->tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('ru_id')->default(0)->comment('商家ID');
                $table->string('type')->default('1')->comment('类型');
                $table->unsignedInteger('page_id')->default(0)->comment('页面ID');
                $table->string('title')->default('')->comment('标题');
                $table->string('keywords')->default('')->comment('关键字');
                $table->string('description')->default('')->comment('描述');
                $table->longText('data')->comment('内容');
                $table->longText('pic')->comment('图片');
                $table->longText('thumb_pic')->default('')->comment('缩略图');
                $table->unsignedInteger('create_at')->default(0)->comment('创建时间');
                $table->unsignedInteger('update_at')->default(0)->comment('更新时间');
                $table->unsignedTinyInteger('default')->default(0)->comment('数据 0 自定义数据 1 默认数据');
                $table->unsignedTinyInteger('review_status')->default(1)->comment('审核状态1 3 ');
                $table->unsignedTinyInteger('is_show')->default(1)->comment('是否显示 0 1');
            });
        }
    }

    /**
     * Reverse the migrations.
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
