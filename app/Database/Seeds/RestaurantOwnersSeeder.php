<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Models\RestaurantModel;
use App\Models\MenuItemModel;

class RestaurantOwnersSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();
        $restaurantModel = new RestaurantModel();
        $menuItemModel = new MenuItemModel();

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

        // Seed only the first restaurant account with default fast-food items.
        $firstRestaurantUser = $userModel->where('email', 'restaurant@example.com')->first();
        if (! $firstRestaurantUser) {
            return;
        }

        $firstRestaurant = $restaurantModel->where('user_id', $firstRestaurantUser['id'])->first();
        if (! $firstRestaurant) {
            return;
        }

        $defaultFastFoodItems = [
            [
                'name' => 'Classic Cheeseburger',
                'description' => 'Juicy beef patty with cheddar, pickles, and signature burger sauce.',
                'price' => 149.00,
                'category' => 'fastfood',
                'image' => 'uploads/menu/classic cheeseburger.png',
                'is_available' => 1,
            ],
            [
                'name' => 'Crispy Chicken Sandwich',
                'description' => 'Crispy chicken fillet, lettuce, and mayo on a toasted brioche bun.',
                'price' => 159.00,
                'category' => 'fastfood',
                'image' => 'uploads/menu/crispy chicken sandwich.png',
                'is_available' => 1,
            ],
            [
                'name' => 'Double Bacon Burger',
                'description' => 'Two beef patties, smoky bacon strips, melted cheese, and onion jam.',
                'price' => 199.00,
                'category' => 'fastfood',
                'image' => 'uploads/menu/Double Bacon Burger.png',
                'is_available' => 1,
            ],
            [
                'name' => 'Loaded Fries',
                'description' => 'Seasoned fries topped with cheese sauce, crispy bits, and spring onions.',
                'price' => 119.00,
                'category' => 'fastfood',
                'image' => 'uploads/menu/Loaded fries with cheese and bacon.png',
                'is_available' => 1,
            ],
            [
                'name' => 'Chicken Nuggets Combo',
                'description' => 'Eight crispy nuggets with dip, fries, and a regular soft drink.',
                'price' => 169.00,
                'category' => 'fastfood',
                'image' => 'uploads/menu/Crispy chicken nuggets with fries.png',
                'is_available' => 1,
            ],
        ];

        foreach ($defaultFastFoodItems as $item) {
            $existingItem = $menuItemModel
                ->where('restaurant_id', $firstRestaurant['id'])
                ->where('name', $item['name'])
                ->first();

            $item['restaurant_id'] = $firstRestaurant['id'];

            if ($existingItem) {
                $menuItemModel->update($existingItem['id'], $item);
                continue;
            }

            $menuItemModel->insert($item);
        }

        // General
        $owner2User = $userModel->where('email', 'owner2@example.com')->first();
        if (! $owner2User) {
            return;
        }

        $owner2Restaurant = $restaurantModel->where('user_id', $owner2User['id'])->first();
        if (! $owner2Restaurant) {
            return;
        }

        $generalItems = [
            [
                'name' => 'Cola Drink',
                'description' => 'Chilled carbonated cola served over ice for a crisp and refreshing taste.',
                'price' => 49.00,
                'category' => 'drinks',
                'image' => '',
                'is_available' => 1,
            ],
            [
                'name' => 'Lemon Iced Tea',
                'description' => 'Freshly brewed iced tea with a splash of lemon, perfectly sweet and refreshing.',
                'price' => 59.00,
                'category' => 'drinks',
                'image' => '',
                'is_available' => 1,
            ],
            [
                'name' => 'Chocolate Milkshake',
                'description' => 'Rich and creamy chocolate milkshake topped with whipped cream.',
                'price' => 89.00,
                'category' => 'drinks',
                'image' => '',
                'is_available' => 1,
            ],
            [
                'name' => 'Iced Coffee',
                'description' => 'Smooth brewed coffee served cold with milk and ice for a bold flavor.',
                'price' => 69.00,
                'category' => 'drinks',
                'image' => '',
                'is_available' => 1,
            ],
            [
                'name' => 'Fresh Lemonade',
                'description' => 'Zesty homemade lemonade with real lemon slices, served ice-cold.',
                'price' => 59.00,
                'category' => 'drinks',
                'image' => '',
                'is_available' => 1,
            ],
            [
                'name' => 'Cold Beer',
                'description' => 'Ice-cold premium beer with a smooth, refreshing finish.',
                'price' => 99.00,
                'category' => 'drinks',
                'image' => '',
                'is_available' => 1,
            ],
        ];

        foreach ($generalItems as $item) {
            $existingItem = $menuItemModel
                ->where('restaurant_id', $owner2Restaurant['id'])
                ->where('name', $item['name'])
                ->first();

            $item['restaurant_id'] = $owner2Restaurant['id'];

            if ($existingItem) {
                $menuItemModel->update($existingItem['id'], $item);
                continue;
            }

            $menuItemModel->insert($item);
        }
    }
}
