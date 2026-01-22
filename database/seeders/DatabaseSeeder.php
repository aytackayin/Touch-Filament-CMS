<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Languages
        Language::create([
            'name' => 'Türkçe',
            'code' => 'tr',
            'charset' => 'UTF-8',
            'direction' => 'ltr',
            'is_default' => true,
            'is_active' => true,
        ]);

        Language::create([
            'name' => 'English',
            'code' => 'en',
            'charset' => 'UTF-8',
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
        ]);

        Language::create([
            'name' => 'Deutsch',
            'code' => 'de',
            'charset' => 'UTF-8',
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
        ]);

        // 2. Roles
        $superAdminRole = Role::create(['name' => 'super_admin']);
        $adminRole = Role::create(['name' => 'admin']);
        $panelUserRole = Role::create(['name' => 'panel_user']);
        $blogWriterRole = Role::create(['name' => 'blog_writer']);

        // 3. Generate Shield Permissions (PascalCase:Resource)
        $resources = [
            'User',
            'Role',
            'TouchFile',
            'Blog',
            'BlogCategory',
            'Language',
        ];

        $prefixes = [
            'ViewAny',
            'View',
            'Create',
            'Update',
            'Delete',
            'DeleteAny',
            'Reorder',
            'Import',
            'Export',
            'Sync'
        ];

        foreach ($resources as $resource) {
            foreach ($prefixes as $prefix) {
                Permission::firstOrCreate(['name' => "{$prefix}:{$resource}", 'guard_name' => 'web']);
            }
        }

        // Add Pages & Widgets permissions (View:Name)
        $views = ['ManageSiteSettings', 'ServerCommands', 'BreezyProfile', 'LatestBlogsWidget'];
        foreach ($views as $view) {
            Permission::firstOrCreate(['name' => "View:{$view}", 'guard_name' => 'web']);
        }

        // 4. Assign Permissions to Roles
        $allPermissions = Permission::all();
        $superAdminRole->givePermissionTo($allPermissions);
        $adminRole->givePermissionTo($allPermissions);

        // Blog Writer Permissions
        $writerPermissions = Permission::where(function ($q) {
            $q->where('name', 'like', '%Blog')
                ->orWhere('name', 'like', '%BlogCategory')
                ->orWhere('name', 'like', '%TouchFile')
                ->orWhere('name', 'View:LatestBlogsWidget');
        })->get();
        $blogWriterRole->givePermissionTo($writerPermissions);

        // 5. Users
        $ayaq = User::create([
            'name' => 'Aytaç KAYIN',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
        ]);
        $ayaq->assignRole($superAdminRole);

        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin_user@admin.com',
            'password' => Hash::make('password'),
        ]);
        $adminUser->assignRole($adminRole);

        $writer = User::create([
            'name' => 'Blog Writer',
            'email' => 'writer@writer.com',
            'password' => Hash::make('password'),
        ]);
        $writer->assignRole($panelUserRole, $blogWriterRole);

        User::create([
            'name' => 'Regular User',
            'email' => 'user@user.com',
            'password' => Hash::make('password'),
        ]);

        // 6. Settings
        DB::table('settings')->insert([
            ['group' => 'general', 'name' => 'site_title', 'payload' => json_encode('Antigravity CMS'), 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'general', 'name' => 'site_description', 'payload' => json_encode('Filament v4 tabanlı gelişmiş içerik yönetim sistemi.'), 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'general', 'name' => 'site_keywords', 'payload' => json_encode(['laravel', 'filament', 'cms', 'antigravity']), 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'general', 'name' => 'attachments_path', 'payload' => json_encode('attachments'), 'created_at' => now(), 'updated_at' => now()],
            ['group' => 'general', 'name' => 'custom_settings', 'payload' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Optional: Blog Data (Uncomment to seed by default)
        // $this->call([
        //     BlogCategorySeeder::class,
        //     BlogSeeder::class,
        // ]);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
