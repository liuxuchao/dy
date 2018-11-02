<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImConfigureTable extends Migration
{
    protected $tableName = 'im_configure';

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
                $table->unsignedInteger('ser_id')->default(0)->comment('客服ID');
                $table->tinyInteger('type')->default(0)->comment('1-快捷回复  2-接入回复  3-离开设置');
                $table->text('content')->default('')->comment('回复内容');
                $table->tinyInteger('is_on')->default(0)->comment('是否开启');
                //            $table->comment('客服设置');
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
