<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class IntrusionDetection extends BaseConfig
{
    public int $failedLoginThreshold = 5;
    public int $failedLoginWindowSeconds = 60;
    public int $captchaThreshold = 3;
    public int $captchaWindowSeconds = 300;
    public int $blockDurationSeconds = 900;
    public int $unauthorizedThreshold = 3;
    public int $unauthorizedWindowSeconds = 60;
    public int $locationDeviceAnomalyWindowSeconds = 600;

    public ?string $adminAlertEmail = null;
}
