<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credits', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->decimal("utilidad", 8, 2);  // porcentaje de ganancia
            $table->string('plazo', 25)->default(\App\Credit::PLAZO_SEMANAL); // plazo para pagar  (Semanal, Quincenal, Mensual)
            $table->string("cobro", 25); // tiempo de cobro (Diario, semanal, quincenal)
            $table->integer('status')->default(\App\Credit::STATUS_ACTIVO);
            $table->string('description', 100)->nullable();
            $table->string('address', 150)->nullable();

            $table->string('ref_img', 150)->nullable();
            $table->string('ref_detail', 150)->nullable();

            // money values
            $table->decimal('monto', 13, 4);  // monto a prestar
            $table->double('total_utilidad', 10, 4)->default(0);
            $table->double('total', 10, 4)->default(0);
            $table->double('pagos_de', 10, 4)->default(0);

            $table->string('geo_lat')->nullable();
            $table->string('geo_lon')->nullable();

            $table->bigInteger('ruta_id')->unsigned();
            $table->bigInteger('person_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();

            $table->date('f_inicio')->nullable();
            $table->date('f_fin')->nullable();

            $table->foreign('ruta_id')->references('id')->on('rutas');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('person_id')->references('id')->on('persons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credits');
    }
}
