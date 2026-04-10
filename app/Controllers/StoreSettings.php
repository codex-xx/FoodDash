<?php

namespace App\Controllers;

use App\Models\RestaurantModel;

class StoreSettings extends BaseController
{
    protected $restaurantModel;

    public function __construct()
    {
        $this->restaurantModel = new RestaurantModel();
    }

    /**
     * Display store settings
     */
    public function index()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return redirect()->to('/login')->with('error', 'Unauthorized');
        }

        $restaurantId = $session->get('restaurant_id');
        $restaurant = $this->restaurantModel->find($restaurantId);

        if (!$restaurant) {
            return redirect()->to('/dashboard/restaurant')->with('error', 'Restaurant not found');
        }

        return view('restaurant/settings/index', ['restaurant' => $restaurant]);
    }

    /**
     * Update store settings
     */
    public function update()
    {
        $session = session();
        if (!$session->get('isLoggedIn') || $session->get('role') !== 'restaurant') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $restaurantId = $session->get('restaurant_id');

        if (!$restaurantId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Restaurant information missing']);
        }

        // Validate input
        $rules = [
            'name' => 'required|min_length[2]|max_length[255]',
            'address' => 'permit_empty|max_length[255]',
            'logo' => 'permit_empty|is_image[logo]|max_size[logo,2048]|ext_in[logo,jpg,jpeg,png,gif]',
            'opening_hours' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $this->validator->getErrors()]);
        }

        $data = [
            'name' => $this->request->getPost('name'),
            'address' => $this->request->getPost('address'),
            'opening_hours' => $this->request->getPost('opening_hours') ?: null,
            'is_open' => $this->request->getPost('is_open') ? 1 : 0,
        ];

        // Handle logo upload
        $logo = $this->request->getFile('logo');
        if ($logo && $logo->isValid() && !$logo->hasMoved()) {
            // Delete old logo if exists
            $restaurant = $this->restaurantModel->find($restaurantId);
            if ($restaurant && !empty($restaurant['logo'])) {
                $oldLogoPath = FCPATH . 'uploads/logos/' . $restaurant['logo'];
                if (file_exists($oldLogoPath)) {
                    @unlink($oldLogoPath);
                }
            }

            // Generate unique filename
            $newName = $logo->getRandomName();
            
            // Create uploads/logos directory if it doesn't exist
            $uploadPath = FCPATH . 'uploads/logos/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Move uploaded file
            $logo->move($uploadPath, $newName);
            $data['logo'] = $newName;
        }

        if ($this->restaurantModel->update($restaurantId, $data)) {
            // Update session with new restaurant name
            $session->set('restaurant_name', $data['name']);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Store settings updated successfully',
                'logo' => isset($data['logo']) ? base_url('uploads/logos/' . $data['logo']) : null
            ]);
        }

        return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to update settings']);
    }
}
