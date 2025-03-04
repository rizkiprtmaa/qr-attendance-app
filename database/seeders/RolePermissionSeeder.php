<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'create-teachers']);
        Permission::create(['name' => 'edit-teachers']);
        Permission::create(['name' => 'delete-teachers']);
        Permission::create(['name' => 'show-teachers']);


        Permission::create(['name' => 'create-students']);
        Permission::create(['name' => 'edit-students']);
        Permission::create(['name' => 'delete-students']);
        Permission::create(['name' => 'show-students']);


        Role::create(['name' => 'admin']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);


        $role = Role::findByName('admin');
        $role->givePermissionTo('create-teachers');
        $role->givePermissionTo('edit-teachers');
        $role->givePermissionTo('delete-teachers');
        $role->givePermissionTo('show-teachers');


        $role = Role::findByName('teacher');
        $role->givePermissionTo('create-students');
        $role->givePermissionTo('edit-students');
        $role->givePermissionTo('delete-students');
        $role->givePermissionTo('show-students');
    }
}
