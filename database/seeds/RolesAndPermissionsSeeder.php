<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');


        Permission::create(['name' => 'show_insurance_company']);
        Permission::create(['name' => 'edit_insurance_company']);
        Permission::create(['name' => 'delete_insurance_company']);
        Permission::create(['name' => 'add_insurance_company']);

        // create permissions
        Permission::create(['name' => 'show_admins']);
        Permission::create(['name' => 'edit_admins']);
        Permission::create(['name' => 'delete_admins']);
        Permission::create(['name' => 'add_admins']);

        Permission::create(['name' => 'show_users']);
        Permission::create(['name' => 'edit_users']);
        Permission::create(['name' => 'delete_users']);
        Permission::create(['name' => 'add_users']);

        Permission::create(['name' => 'show_providers']);
        Permission::create(['name' => 'edit_providers']);
        Permission::create(['name' => 'delete_providers']);
        Permission::create(['name' => 'add_providers']);

        Permission::create(['name' => 'show_branches']);
        Permission::create(['name' => 'edit_branches']);
        Permission::create(['name' => 'delete_branches']);
        Permission::create(['name' => 'add_branches']);

        Permission::create(['name' => 'show_doctors']);
        Permission::create(['name' => 'edit_doctors']);
        Permission::create(['name' => 'delete_doctors']);
        Permission::create(['name' => 'add_doctors']);

        Permission::create(['name' => 'show_specialists']);
        Permission::create(['name' => 'edit_specialists']);
        Permission::create(['name' => 'delete_specialists']);
        Permission::create(['name' => 'add_specialists']);

        Permission::create(['name' => 'show_titles']);
        Permission::create(['name' => 'edit_titles']);
        Permission::create(['name' => 'delete_titles']);
        Permission::create(['name' => 'add_titles']);

        Permission::create(['name' => 'show_reservations']);
        Permission::create(['name' => 'edit reservation']);
        Permission::create(['name' => 'delete_reservations']);
        Permission::create(['name' => 'add_reservations']);

        Permission::create(['name' => 'show_cities']);
        Permission::create(['name' => 'edit_cities']);
        Permission::create(['name' => 'delete_cities']);
        Permission::create(['name' => 'add_cities']);


        Permission::create(['name' => 'show_districts']);
        Permission::create(['name' => 'edit_districts']);
        Permission::create(['name' => 'delete_districts']);
        Permission::create(['name' => 'add_districts']);

        Permission::create(['name' => 'show_pages']);
        Permission::create(['name' => 'edit_pages']);
        Permission::create(['name' => 'delete_pages']);
        Permission::create(['name' => 'add_pages']);

        Permission::create(['name' => 'show_nationalities']);
        Permission::create(['name' => 'edit_nationalities']);
        Permission::create(['name' => 'delete_nationalities']);
        Permission::create(['name' => 'add_nationalities']);

        Permission::create(['name' => 'show_coupons']);
        Permission::create(['name' => 'edit_coupons']);
        Permission::create(['name' => 'delete_coupons']);
        Permission::create(['name' => 'add_coupons']);


        Permission::create(['name' => 'show_provider_messages']);
        Permission::create(['name' => 'edit_provider_messages']);
        Permission::create(['name' => 'delete_provider_messages']);
        Permission::create(['name' => 'add_provider_messages']);



        Permission::create(['name' => 'show_settings']);
        Permission::create(['name' => 'edit_settings']);
        Permission::create(['name' => 'delete_settings']);
        Permission::create(['name' => 'add_settings']);

        Permission::create(['name' => 'show_content']);
        Permission::create(['name' => 'edit_content']);
        Permission::create(['name' => 'delete_content']);
        Permission::create(['name' => 'add_content']);

    }

}
