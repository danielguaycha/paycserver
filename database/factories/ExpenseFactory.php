<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Expense;
use Faker\Generator as Faker;

$factory->define(Expense::class, function (Faker $faker) {

    return [
        'monto' => $faker->randomFloat(2, 10, 150),
        'category' => $faker->randomElement(['COMIDA', 'COMBUSTIBLE', 'OTROS']),
        'description' => $faker->sentence(2),
        'user_id' => \App\User::all()->random()->id,
        'date' => $faker->dateTimeBetween('-60 days', 'now'),
        'image' => null,
    ];
});
