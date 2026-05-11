<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Models\RestaurantModel;
use App\Models\MenuModel;

class RestaurantOwnersSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();
        $restaurantModel = new RestaurantModel();
        $menuModel = new MenuModel();

        // link existing restaurant owner users to restaurants
        $emails = [
            'restaurant@example.com' => 'Burger ng Mama Mo',
            'vesterlaurel@gmail.com' => 'Laurel\'s Kitchen',
            'owner2@example.com' => 'Palamig ni Admiral Kim',
        ];

        foreach ($emails as $email => $restaurantName) {
            $user = $userModel->where('email', $email)->first();
            if ($user) {
                // check if restaurant already exists for this user
                $existing = $restaurantModel->where('user_id', $user['id'])->first();
                if ($existing) {
                    $restaurantModel->update($existing['id'], [
                        'name'    => $restaurantName,
                        'status'  => 'approved',
                    ]);
                    continue;
                }

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
        if ($firstRestaurantUser) {
            $firstRestaurant = $restaurantModel->where('user_id', $firstRestaurantUser['id'])->first();
            if ($firstRestaurant) {

                $defaultFastFoodItems = [
            [
                'name' => 'Classic Cheeseburger',
                'description' => 'Juicy beef patty with cheddar, pickles, and signature burger sauce.',
                'price' => 149.00,
                'category' => 'fastfood',
                'image_url' => 'uploads/menu/classic cheeseburger.png',
                'availability' => 1,
            ],
            [
                'name' => 'Crispy Chicken Sandwich',
                'description' => 'Crispy chicken fillet, lettuce, and mayo on a toasted brioche bun.',
                'price' => 159.00,
                'category' => 'fastfood',
                'image_url' => 'uploads/menu/crispy chicken sandwich.png',
                'availability' => 1,
            ],
            [
                'name' => 'Double Bacon Burger',
                'description' => 'Two beef patties, smoky bacon strips, melted cheese, and onion jam.',
                'price' => 199.00,
                'category' => 'fastfood',
                'image_url' => 'uploads/menu/Double Bacon Burger.png',
                'availability' => 1,
            ],
            [
                'name' => 'Loaded Fries',
                'description' => 'Seasoned fries topped with cheese sauce, crispy bits, and spring onions.',
                'price' => 119.00,
                'category' => 'fastfood',
                'image_url' => 'uploads/menu/Loaded fries with cheese and bacon.png',
                'availability' => 1,
            ],
            [
                'name' => 'Chicken Nuggets Combo',
                'description' => 'Eight crispy nuggets with dip, fries, and a regular soft drink.',
                'price' => 169.00,
                'category' => 'fastfood',
                'image_url' => 'uploads/menu/Crispy chicken nuggets with fries.png',
                'availability' => 1,
            ],
        ];

                foreach ($defaultFastFoodItems as $item) {
            $existingItem = $menuModel
                ->where('restaurant_id', $firstRestaurant['id'])
                ->where('name', $item['name'])
                ->first();

            $item['restaurant_id'] = $firstRestaurant['id'];

            if ($existingItem) {
                $menuModel->update($existingItem['id'], $item);
                continue;
            }

            $menuModel->insert($item);
        }

            }
        }

        // Laurel's Kitchen
        $laurelUser = $userModel->where('email', 'vesterlaurel@gmail.com')->first();
        if ($laurelUser) {
            $laurelRestaurant = $restaurantModel->where('user_id', $laurelUser['id'])->first();
            if ($laurelRestaurant) {

                $laurelItems = [
            [
                'name' => 'Basic Catering',
                'description' => 'Classic soy-vinegar braised chicken served with steamed rice.',
                'price' => 139.00,
                'category' => 'rice meals',
                'image_url' => 'uploads/menu/basic_catering.png',
                'availability' => 1,
            ],
            [
                'name' => 'Bulk Fried Chicken',
                'description' => 'Sizzling chopped pork with onions, chili, and calamansi.',
                'price' => 179.00,
                'category' => 'rice meals',
                'image_url' => 'uploads/menu/bulk_fried_chicken.png',
                'availability' => 1,
            ],
            [
                'name' => 'Bulk Roast Beef',
                'description' => 'Tender beef in rich peanut sauce with bagoong on the side.',
                'price' => 229.00,
                'category' => 'rice meals',
                'image_url' => 'uploads/menu/bulk_roast_beef.png',
                'availability' => 1,
            ],
            [
                'name' => 'Bulk Spaghetti',
                'description' => 'Crispy spring rolls with sweet chili dipping sauce.',
                'price' => 109.00,
                'category' => 'snacks',
                'image_url' => 'uploads/menu/bulk_spaghetti.png',
                'availability' => 1,
            ],
            [
                'name' => 'Spaghetti Bolognese',
                'description' => 'Mixed shaved ice dessert with leche flan, ube, and ice cream.',
                'price' => 99.00,
                'category' => 'desserts',
                'image_url' => 'uploads/menu/spaghetti_bolognese.png',
                'availability' => 1,
            ],
        ];

                foreach ($laurelItems as $item) {
            $existingItem = $menuModel
                ->where('restaurant_id', $laurelRestaurant['id'])
                ->where('name', $item['name'])
                ->first();

            $item['restaurant_id'] = $laurelRestaurant['id'];

            if ($existingItem) {
                $menuModel->update($existingItem['id'], $item);
                continue;
            }

            $menuModel->insert($item);
        }

            }
        }

        // General
        $owner2User = $userModel->where('email', 'owner2@example.com')->first();
        if ($owner2User) {
            $owner2Restaurant = $restaurantModel->where('user_id', $owner2User['id'])->first();
            if ($owner2Restaurant) {

                $generalItems = [
            [
                'name' => 'Cola Drink',
                'description' => 'Chilled carbonated cola served over ice for a crisp and refreshing taste.',
                'price' => 49.00,
                'category' => 'drinks',
                'image_url' => 'uploads/menu/Cola Drink.png',
                'availability' => 1,
            ],
            [
                'name' => 'Lemon Iced Tea',
                'description' => 'Freshly brewed iced tea with a splash of lemon, perfectly sweet and refreshing.',
                'price' => 59.00,
                'category' => 'drinks',
                'image_url' => 'uploads/menu/Lemon Iced Tea.png',
                'availability' => 1,
            ],
            [
                'name' => 'Chocolate Milkshake',
                'description' => 'Rich and creamy chocolate milkshake topped with whipped cream.',
                'price' => 89.00,
                'category' => 'drinks',
                'image_url' => 'uploads/menu/Chocolate Milkshake.png',
                'availability' => 1,
            ],
            [
                'name' => 'Iced Coffee',
                'description' => 'Smooth brewed coffee served cold with milk and ice for a bold flavor.',
                'price' => 69.00,
                'category' => 'drinks',
                'image_url' => 'uploads/menu/Iced Coffee.png',
                'availability' => 1,
            ],
            [
                'name' => 'Fresh Lemonade',
                'description' => 'Zesty homemade lemonade with real lemon slices, served ice-cold.',
                'price' => 59.00,
                'category' => 'drinks',
                'image_url' => 'uploads/menu/Fresh Lemonade.png',
                'availability' => 1,
            ],
            [
                'name' => 'Cold Beer',
                'description' => 'Ice-cold premium beer with a smooth, refreshing finish.',
                'price' => 99.00,
                'category' => 'drinks',
                'image_url' => 'uploads/menu/Cold Beer.png',
                'availability' => 1,
            ],
        ];

                foreach ($generalItems as $item) {
            $existingItem = $menuModel
                ->where('restaurant_id', $owner2Restaurant['id'])
                ->where('name', $item['name'])
                ->first();

            $item['restaurant_id'] = $owner2Restaurant['id'];

            if ($existingItem) {
                $menuModel->update($existingItem['id'], $item);
                continue;
            }

            $menuModel->insert($item);
        }

            }
        }
    }
}
