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
            $table->string("name", 100);
            $table->string("surname",100)->nullable();
            $table->string("address", 100)->nullable();
            $table->string("phones", 13)->nullable();
            $table->string("phones_b", 13)->nullable();
            $table->string("email", 100)->nullable();
            $table->integer("status")->default(1);
            $table->string("type", 25)->default(\App\Person::TYPE_CLIENT);
            $table->boolean('special')->default(false)->nullable();
            $table->double('rank', 12, 2)->default(100.00);
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

