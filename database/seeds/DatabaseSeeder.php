<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {


        // create default routes
        DB::table("rutas")->insert(['name' => 'Zona 1']);
        DB::table("rutas")->insert(['name' => 'Zona 2']);

        // create admin Role and person

        $id =  DB::table("persons")->insertGetId([
            'name' => 'admin',
            'surname' => '',
            'status' => 1,
            'address' => 'Local',
            'phones' => '000000000, 000000000',
            'email' => 'admin@mail.com'
        ]);

        $user = \App\User::create([
            'person_id' => $id,
            'username' => 'admin',
            'password' => bcrypt('admin'),
        ]);

        $this->call(PermitSeeder::class);
        $user->assignRole('admin');


        // create employ for dev
        if(config('app.debug')) {
            $epId =  DB::table("persons")->insertGetId([
                'name' => 'employ',
                'surname' => '1',
                'status' => 1,
                'address' => 'employ address',
                'phones' => '000000000, 000000000',
                'email' => 'employn@mail.com'
            ]);

            $epUser = \App\User::create([
                'person_id' => $epId,
                'username' => 'user',
                'password' => bcrypt('123'),
            ]);

            DB::table("employs")->insert([
               'person_id' => $epId,
               'user_id' => $epUser->id,
               'sueldo' => 500,
                'pago_sueldo' => \App\Employ::PAGO_SEMANAL,
            ]);

            $epUser->rutas()->sync([1, 2]);
            $epUser->assignRole('employ');

        }

    }
}
