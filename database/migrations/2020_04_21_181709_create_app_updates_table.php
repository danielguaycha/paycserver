<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->float("build", 12, 3); // para comprobar la que tiene la app
            $table->string("version", 10)->default("1.0");
            $table->string('description', 200)->default("Corrección de errores y mejoras de rendimiento");
            $table->string("src", 100);
            $table->string("status")->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_updates');
    }
}
