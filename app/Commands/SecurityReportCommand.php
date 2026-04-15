<?php

namespace App\Commands;

use App\Libraries\SecurityAuditService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SecurityReportCommand extends BaseCommand
{
    protected $group = 'Security';
    protected $name = 'security:report';
    protected $description = 'Generate a security audit report CSV (daily, weekly, monthly).';
    protected $usage = 'security:report [--period=daily|weekly|monthly]';
    protected $options = [
        '--period' => 'Report period: daily, weekly, or monthly.',
    ];

    public function run(array $params)
    {
        $period = strtolower((string) (CLI::getOption('period') ?? 'daily'));
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            CLI::error('Invalid period. Use daily, weekly, or monthly.');
            return;
        }

        $service = new SecurityAuditService();
        $summary = $service->buildReportSummary($period);

        $dir = WRITEPATH . 'reports/security';
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            CLI::error('Unable to create report directory: ' . $dir);
            return;
        }

        $file = $dir . '/security_report_' . $period . '_' . date('Ymd_His') . '.csv';
        $csv = $this->buildCsv($summary);

        if (@file_put_contents($file, $csv) === false) {
            CLI::error('Failed to write report file.');
            return;
        }

        CLI::write('Security report generated.', 'green');
        CLI::write('Period: ' . $period);
        CLI::write('File: ' . $file);
    }

    protected function buildCsv(array $summary): string
    {
        $rows = [
            ['metric', 'value'],
            ['period', (string) ($summary['period'] ?? '')],
            ['failed_login_attempts', (string) ($summary['failed_login_attempts'] ?? 0)],
            ['intrusion_attempts', (string) ($summary['intrusion_attempts'] ?? 0)],
            ['blocked_ip_events', (string) ($summary['blocked_ip_events'] ?? 0)],
            ['system_vulnerabilities_detected', (string) ($summary['system_vulnerabilities_detected'] ?? 0)],
            ['generated_at', (string) ($summary['generated_at'] ?? '')],
        ];

        $output = '';
        foreach ($rows as $row) {
            $escaped = array_map(static function ($value): string {
                $value = str_replace('"', '""', (string) $value);
                return '"' . $value . '"';
            }, $row);
            $output .= implode(',', $escaped) . "\n";
        }

        return $output;
    }
}
