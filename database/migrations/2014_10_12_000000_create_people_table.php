<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("name", 150);
            $table->string("surname",100)->nullable();
            $table->string("address", 255)->nullable();
            $table->string("phones", 100)->nullable();
            $table->string("email", 150)->nullable();
            $table->integer("status")->default(1);
            $table->string("type")->default(\App\Person::TYPE_CLIENT);

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
        Schema::dropIfExists('persons');
    }
}
