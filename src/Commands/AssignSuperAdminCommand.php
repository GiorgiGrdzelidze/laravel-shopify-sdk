<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use Illuminate\Console\Command;
use LaravelShopifySdk\Models\Role;

class AssignSuperAdminCommand extends Command
{
    protected $signature = 'shopify:assign-admin {email : The email of the user to assign as Super Admin}';

    protected $description = 'Assign the Super Admin role to a user by email';

    public function handle(): int
    {
        $email = $this->argument('email');
        
        // Get the User model class
        $userModel = config('auth.providers.users.model', 'App\\Models\\User');
        
        // Find the user
        $user = $userModel::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return self::FAILURE;
        }

        // Check if trait is present
        if (!method_exists($user, 'shopifyRoles')) {
            $this->error('The User model does not have the HasShopifyRoles trait.');
            $this->line('');
            $this->line('Add this to your User model:');
            $this->line('');
            $this->info('use LaravelShopifySdk\Traits\HasShopifyRoles;');
            $this->line('');
            $this->info('class User extends Authenticatable');
            $this->info('{');
            $this->info('    use HasShopifyRoles;');
            $this->info('}');
            return self::FAILURE;
        }

        // Find or create Super Admin role
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full access to all features and settings',
            ]
        );

        // Assign the role
        $user->shopifyRoles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->info("✅ User '{$user->name}' ({$email}) has been assigned the Super Admin role.");
        
        return self::SUCCESS;
    }
}
