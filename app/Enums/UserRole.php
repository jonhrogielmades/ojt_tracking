<?php

namespace App\Enums;

enum UserRole: string
{
    /** @deprecated Use SuperAdmin for the highest administrator role. */
    public const Admin = self::SuperAdmin;

    case Student = 'student';
    case SuperAdmin = 'super_admin';
    case OjtCoordinator = 'ojt_coordinator';
    case Auditor = 'auditor';
    case DocumentOfficer = 'document_officer';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::SuperAdmin => 'Super Administrator',
            self::OjtCoordinator => 'OJT Coordinator',
            self::Auditor => 'Auditor / Viewer',
            self::DocumentOfficer => 'Document Officer',
        };
    }

    public function isAdministrator(): bool
    {
        return $this !== self::Student;
    }

    public function allows(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * @return list<string>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::Student => [],
            self::SuperAdmin => [
                'admin.access',
                'students.view',
                'students.manage',
                'academic.manage',
                'companies.manage',
                'documents.manage',
                'reports.view',
                'reports.manage',
                'announcements.manage',
                'approvals.manage',
                'risk.view',
                'audit.view',
                'administrators.manage',
                'backups.manage',
                'system-health.view',
            ],
            self::OjtCoordinator => [
                'admin.access',
                'students.view',
                'students.manage',
                'academic.manage',
                'companies.manage',
                'documents.manage',
                'reports.view',
                'reports.manage',
                'announcements.manage',
                'approvals.manage',
                'risk.view',
                'system-health.view',
            ],
            self::Auditor => [
                'admin.access',
                'students.view',
                'reports.view',
                'audit.view',
                'risk.view',
                'system-health.view',
            ],
            self::DocumentOfficer => [
                'admin.access',
                'students.view',
                'documents.manage',
            ],
        };
    }

    /**
     * @return list<self>
     */
    public static function administratorRoles(): array
    {
        return [
            self::SuperAdmin,
            self::OjtCoordinator,
            self::Auditor,
            self::DocumentOfficer,
        ];
    }
}
