<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Models\RestaurantModel;

class RestaurantOwnersSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();
        $restaurantModel = new RestaurantModel();

        // link existing restaurant owner users to restaurants
        $emails = [
            'owner1@example.com' => 'Pizza Palace',
            'owner2@example.com' => 'Burger Barn',
        ];

        foreach ($emails as $email => $restaurantName) {
            $user = $userModel->where('email', $email)->first();
            if ($user) {
                // check if restaurant already exists for this user
                $existing = $restaurantModel->where('user_id', $user['id'])->first();
                if (! $existing) {
                    $restaurantModel->insert([
                        'user_id' => $user['id'],
                        'name'    => $restaurantName,
                        'status'  => 'approved',
                    ]);
                }
            }
        }

        // also link the generic 'restaurant@example.com' user if it exists and has no restaurant
        $user = $userModel->where('email', 'restaurant@example.com')->first();
        if ($user) {
            $existing = $restaurantModel->where('user_id', $user['id'])->first();
            if (! $existing) {
                $restaurantModel->insert([
                    'user_id' => $user['id'],
                    'name'    => 'Sample Restaurant',
                    'status'  => 'approved',
                ]);
            }
        }
    }
}
