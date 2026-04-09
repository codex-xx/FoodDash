<?php

namespace App\Controllers\Api;

use App\Libraries\DatabaseBackupService;
use CodeIgniter\RESTful\ResourceController;

class AdminBackupController extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $service = new DatabaseBackupService();

        return $this->respond([
            'success' => true,
            'message' => 'Available backups fetched successfully.',
            'data' => $service->listBackups(),
        ]);
    }

    public function runBackup()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $label = is_array($payload) && isset($payload['label']) ? (string) $payload['label'] : 'admin';

        $service = new DatabaseBackupService();
        $result = $service->createBackup($label, 'admin-api');

        $statusCode = ($result['success'] ?? false) ? 201 : 500;
        return $this->respond([
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? 'Backup process completed.'),
            'data' => $result,
        ], $statusCode);
    }

    public function restoreBackup()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $file = is_array($payload) && isset($payload['file_name']) ? (string) $payload['file_name'] : '';

        if ($file === '') {
            return $this->respond([
                'success' => false,
                'message' => 'file_name is required.',
            ], 400);
        }

        $service = new DatabaseBackupService();
        $result = $service->restoreBackup($file, 'admin-api');

        $statusCode = ($result['success'] ?? false) ? 200 : 500;

        return $this->respond([
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? 'Restore process completed.'),
            'data' => $result,
        ], $statusCode);
     }
 }
