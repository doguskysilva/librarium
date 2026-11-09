<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(BookSeeder::class);
        $this->call(AuthorSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(CollectionSeeder::class);
        $this->call(BibliothecaRelationshipSeeder::class);
    }
}
