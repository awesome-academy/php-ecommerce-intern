<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Order;
use Faker\Generator as Faker;

$factory->define(Order::class, function (Faker $faker) {
    return [
        'user_id' => $faker->randomNumber(1),
        'status' => $faker->numberBetween($min = 0, $max = 2),
        'ordered_date' => now(),
        'address' => $faker->country,
        'phone_number' => $faker->phoneNumber,
    ];
});
