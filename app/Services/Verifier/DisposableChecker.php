<?php

namespace App\Services\Verifier;

class DisposableChecker
{
    private static ?array $domains = null;

    public function check(VerificationResult $result): void
    {
        if ($this->isDisposable($result->domain)) {
            $result->isDisposable = true;
            $result->status = 'disposable';
            $result->reason = 'Domain is a known disposable/temporary email provider';
        }
    }

    private function isDisposable(string $domain): bool
    {
        return in_array(strtolower($domain), $this->getDomains(), true);
    }

    private function getDomains(): array
    {
        if (self::$domains !== null) {
            return self::$domains;
        }

        $listPath = resource_path('data/disposable-domains.txt');

        if (file_exists($listPath)) {
            self::$domains = array_filter(
                array_map('trim', file($listPath)),
                fn($line) => !empty($line) && !str_starts_with($line, '#')
            );
        } else {
            self::$domains = $this->getBuiltInList();
        }

        return self::$domains;
    }

    private function getBuiltInList(): array
    {
        return [
            'mailinator.com', 'guerrillamail.com', 'guerrillamail.net',
            'guerrillamail.org', 'guerrillamail.biz', 'guerrillamail.de',
            'sharklasers.com', 'guerrillamailblock.com', 'grr.la',
            'guerrillamail.info', 'spam4.me', 'yopmail.com', 'yopmail.fr',
            'cool.fr.nf', 'jetable.fr.nf', 'nospam.ze.tc', 'nomail.xl.cx',
            'mega.zik.dj', 'speed.1s.fr', 'courriel.fr.nf', 'moncourrier.fr.nf',
            'monemail.fr.nf', 'monmail.fr.nf', 'throwam.com', 'trashmail.com',
            'trashmail.at', 'trashmail.io', 'trashmail.me', 'trashmail.net',
            'trashmail.org', 'wegwerfadresse.de', 'sogetthis.com',
            'maildrop.cc', 'dispostable.com', 'throwam.com', 'fakeinbox.com',
            'tempmail.com', 'temp-mail.org', 'throwaway.email', 'sharklasers.com',
            'mailnull.com', 'spamgourmet.com', 'spamgourmet.net', 'spamgourmet.org',
            'discard.email', 'spamspot.com', 'trashdevil.com', 'trashdevil.de',
            'mailexpire.com', 'spamfree24.org', 'spamfree24.de', 'spamfree24.eu',
            'spamfree24.info', 'spamfree24.net', 'spamgourmet.com',
            'notmailinator.com', 'safetymail.info', 'mailnew.com',
            'pookmail.com', 'mailsiphon.com', 'emailondeck.com',
            'getairmail.com', 'spambox.us', 'fastacura.com', 'mt2015.com',
            'mt2014.com', 'yep.it', 'thisisnotmyrealemail.com', 'barwon.org',
            'bobmail.info', 'chammy.info', 'devnullmail.com', 'letthemeatspam.com',
            'moncourrier.fr.nf', 'nospamfor.us', 'objectmail.com', 'ownmail.net',
            'pecinan.com', 'putthisinyourspamdatabase.com', 'throwam.com',
            'spamtrail.com', 'theliminal.com', 'mtemp.com',
        ];
    }
}
