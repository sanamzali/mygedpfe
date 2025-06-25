<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateNullableFieldsSeeder extends Seeder
{
    public function run()
    {
        DB::statement("UPDATE folders SET users = '[1]'::jsonb WHERE users IS NULL;");
        DB::statement("UPDATE files SET users = '[1]'::jsonb WHERE users IS NULL;");
        DB::statement("UPDATE projects SET users = '[1]'::jsonb WHERE users IS NULL;");
        DB::statement("UPDATE spaces SET users = '[1]'::jsonb WHERE users IS NULL;");

        DB::statement("UPDATE folders SET created_by = 1 WHERE created_by IS NULL;");
        DB::statement("UPDATE files SET created_by = 1 WHERE created_by IS NULL;");
        DB::statement("UPDATE projects SET created_by = 1 WHERE created_by IS NULL;");
        DB::statement("UPDATE spaces SET created_by = 1 WHERE created_by IS NULL;");
    }
}
