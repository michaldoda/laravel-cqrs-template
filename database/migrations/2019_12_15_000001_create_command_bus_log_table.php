<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommandBusLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('command_bus_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->boolean('multiple')->default(false);
            $table->string('command_name', 700)->nullable();
            $table->json('command_params')->nullable();
            $table->string('user_id')->nullable()->index();
            $table->boolean('committed');
            $table->text('error_message')->nullable();
            $table->dateTime('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('command_bus_log');
    }
}
