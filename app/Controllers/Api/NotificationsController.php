<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\NotificationModel;

class NotificationsController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $customer = $this->request->customer ?? null;

        if (! $customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can access notifications',
            ], 403);
        }

        if (! $this->tableExists('notifications')) {
            return $this->respond([
                'success' => true,
                'data' => [],
            ]);
        }

        $model = new NotificationModel();
        $rows = $model->where('customer_id', (int) $customer['id'])
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return $this->respond([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function delete($id = null)
    {
        $customer = $this->request->customer ?? null;

        if (! $customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can delete notifications',
            ], 403);
        }

        $notificationId = (int) $id;
        if ($notificationId <= 0) {
            return $this->respond([
                'success' => false,
                'message' => 'Invalid notification id',
            ], 400);
        }

        if (! $this->tableExists('notifications')) {
            return $this->respond([
                'success' => false,
                'message' => 'Notifications table is not available yet.',
            ], 400);
        }

        $model = new NotificationModel();
        $row = $model->where('id', $notificationId)
            ->where('customer_id', (int) $customer['id'])
            ->first();

        if (! $row) {
            return $this->respond([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $model->delete($notificationId);

        return $this->respond([
            'success' => true,
            'message' => 'Notification deleted',
        ]);
    }

    public function clear()
    {
        $customer = $this->request->customer ?? null;

        if (! $customer) {
            return $this->respond([
                'success' => false,
                'message' => 'Only customers can clear notifications',
            ], 403);
        }

        if (! $this->tableExists('notifications')) {
            return $this->respond([
                'success' => false,
                'message' => 'Notifications table is not available yet.',
            ], 400);
        }

        $model = new NotificationModel();
        $model->where('customer_id', (int) $customer['id'])->delete();

        return $this->respond([
            'success' => true,
            'message' => 'All notifications deleted',
        ]);
    }

    protected function tableExists(string $table): bool
    {
        try {
            return db_connect()->tableExists($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
