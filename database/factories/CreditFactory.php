<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Credit;
use Faker\Generator as Faker;
use App\Http\Services\CreditService;
use Carbon\Carbon;

$factory->define(Credit::class, function (Faker $faker) {

    $creditService = new CreditService();

    $monto = $faker->randomFloat(2, 100, 999);
    $utilidad = $faker->randomElement([10, 20, 40]);

    $plazo = $faker->randomElement([Credit::PLAZO_SEMANAL, Credit::PLAZO_QUINCENAL,
        Credit::PLAZO_MENSUAL, Credit::PLAZO_MES_Y_MEDIO, Credit::PLAZO_OOS_MESES]);

    switch ($plazo){
        case Credit::PLAZO_QUINCENAL:
        case Credit::PLAZO_SEMANAL:
            $cobro = Credit::COBRO_DIARIO;
            break;
        case Credit::PLAZO_MENSUAL:
            $cobro = Credit::COBRO_SEMANAL;
            break;
        case Credit::PLAZO_OOS_MESES:
            $cobro = Credit::COBRO_MENSUAL;
            break;
        case Credit::PLAZO_MES_Y_MEDIO:
            $cobro = Credit::COBRO_QUINCENAL;
            break;
    }

    $created_at = $faker->randomElement(
        [
            '2020-04-01',
            '2020-04-02', '2020-04-03', '2020-04-04', '2020-04-06','2020-04-09', '2020-04-11', '2020-04-13'
        ]);
    $finicio = Credit::diasInicio($cobro, $created_at);
    
    $f_inicio = $finicio->format('Y-m-d');
    $f_fin = Credit::dateEnd(Credit::diasPlazo($plazo), $finicio)->format('Y-m-d');

    $total_utilidad = ($monto * ($utilidad/100)); // utilidad
    $total = $monto + $total_utilidad; // total con utilidad

    $calc = $creditService->calcCredit($plazo, $total, $cobro);
    $pagos_de = $calc['pagosDe']; // pagos de $
    $pagos_de_last = $calc['pagosDeLast']; // ultimo pago de $
    $description = $calc['description']; // descripción
    $n_pagos = $calc['nPagos'];

    return [
        'monto' => $monto,
        'utilidad' => $utilidad,
        'plazo' => $plazo,
        'cobro' => $cobro,
        'status' => Credit::STATUS_ACTIVO,
        'geo_lat' => $faker->latitude(-3.271840, -3.202415),
        'geo_lon' => $faker->longitude(-79.964380, -79.802020),
        'person_id' => \App\Person::all()->random()->id,
        'user_id' => $faker->randomElement([1, 2, 3]),
        'ruta_id' => $faker->randomElement([1, 2]),
        'address' => $faker->address,
        'f_inicio' => $f_inicio,
        'f_fin' => $f_fin,
        'total_utilidad' => $total_utilidad,
        'total' => $total,
        'pagos_de'=>$pagos_de,
        'pagos_de_last'=>$pagos_de_last,
        'description'=>$description,
        'n_pagos'=>$n_pagos,
        'created_at' => Carbon::parse($created_at),
    ];
});
