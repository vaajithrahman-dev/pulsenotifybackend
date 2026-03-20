<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Store;
use App\Models\Membership;

class DevSeed extends Seeder
{
    public function run(): void
    {
        // Create dev user
        $user = User::firstOrCreate(
            ['email' => 'dev@pulsenotify.local'],
            [
                'name' => 'Dev User',
                // password optional for now; we will do magic-link later
                'password' => bcrypt(Str::random(32)),
            ]
        );

        // Create dev store
        $store = Store::firstOrCreate(
            ['store_id' => 'store_dev_001'],
            [
                'store_name' => 'Dev Store',
                'account_email' => 'owner@example.com',
                'store_site_url' => 'https://example.com',
                'signing_secret' => Str::random(48),
                'status' => 'active',
            ]
        );

        // Create membership
        Membership::firstOrCreate(
            ['user_id' => $user->id, 'store_id' => $store->id],
            ['role' => 'owner']
        );


        $store2 = Store::firstOrCreate(
            ['store_id' => 'store_dev_002'],
            [
                'store_name' => 'Dev Store 2',
                'account_email' => 'owner2@example.com',
                'store_site_url' => 'https://example2.com',
                'signing_secret' => Str::random(48),
                'status' => 'active',
            ]
        );

        Membership::firstOrCreate(
            ['user_id' => $user->id, 'store_id' => $store2->id],
            ['role' => 'owner']
        );
    }
}