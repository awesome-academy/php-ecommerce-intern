<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OrderDetail;
use Faker\Generator as Faker;

$factory->define(OrderDetail::class, function (Faker $faker) {
    return [
        'order_id' => 1,
        'product_id' => $faker->randomNumber(1),
        'quantities' => $faker->randomNumber(1),
        'status' => $faker->numberBetween($min = 0, $max = 2),
    ];
});
