<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(\App\Person::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'surname' => $faker->lastName,
        'address' => $faker->address,
        'phones' => '00000000000',
        'phones_b' => '0000000000',
        'email' => $faker->email
    ];
});
