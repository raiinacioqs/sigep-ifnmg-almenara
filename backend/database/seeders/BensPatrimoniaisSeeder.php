<?php

namespace Database\Seeders;

use App\Models\BemPatrimonial;
use Illuminate\Database\Seeder;

class BensPatrimoniaisSeeder extends Seeder
{
    public function run(): void
    {
        BemPatrimonial::factory()
            ->count(30)
            ->create();
    }
}