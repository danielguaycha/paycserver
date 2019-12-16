<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('total', 12, 2);
            $table->bigInteger('credit_id')->unsigned();
            $table->integer('status')->default(\App\Payment::STATUS_ACTIVE);

            $table->date('date')->nullable();

            $table->date('date_payment')->nullable();
            $table->string('description', 100)->nullable();
            $table->boolean('mora')->default(false);

            $table->foreign('credit_id')->references('id')->on('credits');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
