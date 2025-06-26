<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            PbcCategorySeeder::class,
            ClientSeeder::class,
            ProjectSeeder::class,
            PbcTemplateSeeder::class,
            SettingsSeeder::class,
            UserPermissionSeeder::class,
            SamplePbcRequestSeeder::class,
            PbcDocumentSeeder::class,
        ]);
    }
}
