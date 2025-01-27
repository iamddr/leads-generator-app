<?php

namespace LeadBrowser\Core\Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class LocalesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('locales')->delete();

        DB::table('locales')->insert([
            [
                'id'   => 1,
                'code' => 'en',
                'name' => 'English',
            ], [
                'id'   => 2,
                'code' => 'fr',
                'name' => 'French',
            ], [
                'id'   => 3,
                'code' => 'nl',
                'name' => 'Dutch',
            ], [
                'id'   => 4,
                'code' => 'tr',
                'name' => 'Türkçe',
            ], [
                'id'   => 5,
                'code' => 'es',
                'name' => 'Español',
            ], [
                'id'   => 6,
                'code' => 'pl',
                'name' => 'Polish',
            ], [
                'id'   => 7,
                'code' => 'de',
                'name' => 'Deutschland',
            ],
        ]);
    }
}