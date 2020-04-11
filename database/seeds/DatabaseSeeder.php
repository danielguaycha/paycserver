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
        $regenerate = true;


        if ($regenerate) {
            // create default routes
            DB::table("rutas")->insert(['name' => 'Zona 1']);
            DB::table("rutas")->insert(['name' => 'Zona 2']);

            // Crear Roles y permisos iniciales
            $this->call(PermitSeeder::class);

            // create Root Person
            $root =  DB::table("persons")->insertGetId([
                'name' => 'ROOT',
                'surname' => '',
                'status' => -999,
                'address' => '',
                'phones' => '000000000',
                'phones_b' => '000000000',
                'email' => 'root@mail.com'
            ]);

            $userRoot = \App\User::create([
                'person_id' => $root,
                'username' => 'root',
                'password' => bcrypt('root'),
            ]);

            $userRoot->assignRole(\App\Role::ROOT);


            // create employ and admin for dev
            if(config('app.debug')) {
                // personas
                $employ =  DB::table("persons")->insertGetId([
                    'name' => 'employ',
                    'surname' => '1',
                    'status' => 1,
                    'address' => 'employ address',
                    'phones' => '000000000',
                    'phones_b' => '000000000',
                    'email' => 'employn@mail.com'
                ]);

                $admin =  DB::table("persons")->insertGetId([
                    'name' => 'ADMIN',
                    'surname' => '',
                    'status' => -999,
                    'address' => '',
                    'phones' => '000000000',
                    'phones_b' => '000000000',
                    'email' => 'admin@mail.com'
                ]);

                // usuarios
                $employUser = \App\User::create([
                    'person_id' => $employ,
                    'username' => 'user',
                    'password' => bcrypt('1234'),
                ]);

                $userAdmin = \App\User::create([
                    'person_id' => $admin,
                    'username' => 'admin',
                    'password' => bcrypt('admin'),
                ]);

                // empleados
                DB::table("employs")->insert([
                    'person_id' => $employ,
                    'user_id' => $employUser->id,
                    'sueldo' => 500,
                    'pago_sueldo' => \App\Employ::PAGO_SEMANAL,
                ]);

                // roles, zonas
                $employUser->rutas()->sync([1, 2]);
                $employUser->assignRole(\App\Role::EMPLOY);

                // Asignar Roles y permisos
                $userAdmin->assignRole(\App\Role::ADMIN);


                factory(App\Person::class, 100)->create();
                factory(App\Credit::class, 45)->create()->each(function($c) {
                    $creditService = new \App\Http\Services\CreditService();
                    $calc = $creditService->calcCredit($c->plazo, $c->total, $c->cobro);
                    $creditService->storePayments($c->id, $calc, $c->f_inicio, $c->f_fin, $c->cobro);
                });
                factory(App\Expense::class, 100)->create();
            }
        }



    }
}

