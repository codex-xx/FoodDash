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
            'restaurant@example.com' => 'Sample Restaurant',
            'vesterlaurel@gmail.com' => 'Laurel\'s Kitchen',
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
    }
}
