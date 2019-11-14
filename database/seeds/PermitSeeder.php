<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'edit.employ']);
        Permission::create(['name' => 'delete employ']);
        Permission::create(['name' => 'create.employ']);

        Permission::create(['name' => 'create.ruta']);
        Permission::create(['name' => 'edit.ruta']);
        Permission::create(['name' => 'delete.ruta']);

        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo(Permission::all());

        $roleEmploy = Role::create(['name' => 'employ']);
        $roleEmploy->givePermissionTo([]);


    }
}
