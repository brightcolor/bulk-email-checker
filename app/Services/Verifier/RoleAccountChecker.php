<?php

namespace App\Services\Verifier;

class RoleAccountChecker
{
    private const ROLE_PREFIXES = [
        'info', 'admin', 'administrator', 'support', 'help', 'helpdesk',
        'sales', 'marketing', 'contact', 'contacts', 'abuse', 'postmaster',
        'hostmaster', 'webmaster', 'noreply', 'no-reply', 'donotreply',
        'do-not-reply', 'billing', 'accounts', 'accounting', 'finance',
        'office', 'hello', 'enquiries', 'enquiry', 'security', 'privacy',
        'legal', 'compliance', 'jobs', 'careers', 'hr', 'recruitment',
        'press', 'media', 'pr', 'newsletter', 'news', 'subscribe',
        'unsubscribe', 'mail', 'email', 'notifications', 'alerts',
        'noti', 'team', 'service', 'services', 'operations', 'ops',
        'devops', 'sysadmin', 'root', 'sys', 'system',
    ];

    public function check(VerificationResult $result): void
    {
        [$local] = explode('@', $result->normalizedEmail, 2);
        $localClean = strtolower(trim($local));

        if (in_array($localClean, self::ROLE_PREFIXES, true)) {
            $result->isRoleAccount = true;
            $result->status = 'role_account';
            $result->reason = 'Role-based email address (e.g. info@, admin@)';
        }
    }
}
