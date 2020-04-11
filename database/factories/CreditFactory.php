<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Credit;
use Faker\Generator as Faker;
use App\Http\Controllers\Api\CreditController;

$factory->define(Credit::class, function (Faker $faker) {

    $creditController = new CreditController();

    $monto = $faker->randomFloat(2, 4);
    $utilidad = $faker->randomElement([10, 20, 40]);
    $plazo = $faker->randomElement([Credit::PLAZO_SEMANAL, Credit::PLAZO_QUINCENAL,
        Credit::PLAZO_MENSUAL, Credit::PLAZO_MES_Y_MEDIO, Credit::PLAZO_OOS_MESES]);
    $cobro = $faker->randomElement([Credit::COBRO_DIARIO, Credit::COBRO_SEMANAL,
        Credit::COBRO_QUINCENAL, Credit::COBRO_MENSUAL]);

    return [
        'monto' => $monto,
        'utilidad' => $utilidad,
        'plazo' => $plazo,
        'cobro' => $cobro,
        'status' => Credit::STATUS_ACTIVO,
        'geo_lat' => $faker->latitude,
        'geo_lon' => $faker->longitude,
        'person_id' => \App\Person::all()->random()->id,
        'user_id' => $faker->randomElement([1, 2, 3]),
        'ruta_id' => $faker->randomElement([1, 2]),
        'address' => $faker->address,
    ];
});
