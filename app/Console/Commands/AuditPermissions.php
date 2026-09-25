<?php

namespace App\Console\Commands;

use App\Services\PermissionsAuditService;
use Illuminate\Console\Command;

class AuditPermissions extends Command
{
    protected $signature = 'permissions:audit {--admin-role=Admin : Role name that should receive all permissions}';

    protected $description = 'Sync permissions from config/auth_permissions.php for every tenant';

    public function handle(): int
    {
        $adminRoleName = (string) $this->option('admin-role');

        $this->info('Auditing permissions...');

        try {
            $result = app(PermissionsAuditService::class)->sync($adminRoleName);
            $this->line(
                sprintf(
                    'Created: %d, Deleted: %d, Guard: %s',
                    count($result['created']),
                    count($result['deleted']),
                    $result['guard_name']
                )
            );
        } catch (\Throwable $throwable) {
            $this->error('Failed: ' . $throwable->getMessage());
        }

        $this->info('Permissions audit complete.');

        return self::SUCCESS;
    }
}
