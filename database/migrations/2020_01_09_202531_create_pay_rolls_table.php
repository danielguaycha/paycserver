<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayRollsTable extends Migration
{
    public function up()
    {
        Schema::create('pay_rolls', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->float('amount', 12, 2);
            $table->string('concept', 100)->default(\App\PayRoll::PAGO_SUELDO);
            $table->float('bonus', 12, 2)->default(0);
            $table->float('extra', 12, 2)->default(0);
            $table->float('discount', 12, 2)->default(0);
            $table->float('total', 12, 2)->default(0);
            $table->string('description', 100)->nullable();
            $table->boolean('advance')->default(false);
            $table->date('date')->useCurrent();
            $table->integer('status')->default(\App\PayRoll::STATUS_ACTIVE);

            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('employ_id')->unsigned();

            $table->foreign('employ_id')->references('id')->on('employs');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pay_rolls');
    }
}
