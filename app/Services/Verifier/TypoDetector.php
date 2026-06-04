<?php

namespace App\Services\Verifier;

class TypoDetector
{
    private const COMMON_DOMAINS = [
        'gmail.com', 'googlemail.com',
        'yahoo.com', 'yahoo.de', 'yahoo.co.uk', 'yahoo.fr',
        'hotmail.com', 'hotmail.de', 'hotmail.co.uk', 'hotmail.fr',
        'outlook.com', 'outlook.de',
        'live.com', 'live.de',
        'icloud.com', 'me.com', 'mac.com',
        'aol.com',
        'gmx.com', 'gmx.de', 'gmx.net', 'gmx.at',
        'web.de', 't-online.de', 'freenet.de',
        'mail.com', 'mail.de',
        'protonmail.com', 'proton.me',
        'msn.com',
    ];

    private const KNOWN_TYPOS = [
        // Gmail
        'gmal.com' => 'gmail.com',
        'gmial.com' => 'gmail.com',
        'gmaill.com' => 'gmail.com',
        'gamil.com' => 'gmail.com',
        'gamail.com' => 'gmail.com',
        'gmail.co' => 'gmail.com',
        'gmail.cm' => 'gmail.com',
        'gmail.cmo' => 'gmail.com',
        'gmail.ocm' => 'gmail.com',
        'gnail.com' => 'gmail.com',
        'gmali.com' => 'gmail.com',
        'gmai.com' => 'gmail.com',
        'gmailcom' => 'gmail.com',
        // Yahoo
        'yaho.com' => 'yahoo.com',
        'yahooo.com' => 'yahoo.com',
        'yahoo.co' => 'yahoo.com',
        'yhoo.com' => 'yahoo.com',
        'yaho0.com' => 'yahoo.com',
        // Hotmail
        'hotmial.com' => 'hotmail.com',
        'hotmail.co' => 'hotmail.com',
        'hotmal.com' => 'hotmail.com',
        'hotmaill.com' => 'hotmail.com',
        'hotnail.com' => 'hotmail.com',
        'hotmail.cm' => 'hotmail.com',
        // Outlook
        'outlok.com' => 'outlook.com',
        'outllook.com' => 'outlook.com',
        'outlook.co' => 'outlook.com',
        'outlooK.com' => 'outlook.com',
        // iCloud
        'iclould.com' => 'icloud.com',
        'icloude.com' => 'icloud.com',
        // GMX
        'gmx.cm' => 'gmx.de',
        'gmx.cmo' => 'gmx.de',
        // web.de
        'web.de.com' => 'web.de',
        'webb.de' => 'web.de',
    ];

    public function check(VerificationResult $result): void
    {
        $domain = $result->domain;

        // Check known hardcoded typos first
        if (isset(self::KNOWN_TYPOS[$domain])) {
            $result->suggestedCorrection = $this->buildSuggestion(
                $result->normalizedEmail,
                $domain,
                self::KNOWN_TYPOS[$domain]
            );
            return;
        }

        // Levenshtein-based fuzzy match against common domains
        // Skip if the domain is already in the common list (no typo)
        if (in_array($domain, self::COMMON_DOMAINS, true)) {
            return;
        }

        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;

        foreach (self::COMMON_DOMAINS as $common) {
            $distance = levenshtein($domain, $common);
            // Require both similar length AND small edit distance to avoid false positives
            // e.g. gmail.com (9) vs mail.com (8) — length diff is 1, but semantically very different
            $lenDiff = abs(strlen($domain) - strlen($common));
            if ($distance > 0 && $distance === 1 && $lenDiff <= 1 && $distance < $bestDistance) {
                // Extra check: don't suggest if the common domain is a substring of the checked domain
                if (str_contains($domain, $common) || str_contains($common, $domain)) {
                    continue;
                }
                $bestDistance = $distance;
                $bestMatch = $common;
            } elseif ($distance === 2 && $lenDiff === 0 && $distance < $bestDistance) {
                // Two substitutions on same-length domain (e.g. hotmial -> hotmail)
                $bestDistance = $distance;
                $bestMatch = $common;
            }
        }

        if ($bestMatch !== null) {
            $result->suggestedCorrection = $this->buildSuggestion(
                $result->normalizedEmail,
                $domain,
                $bestMatch
            );
        }
    }

    private function buildSuggestion(string $email, string $typo, string $correct): string
    {
        [$local] = explode('@', $email, 2);
        return $local . '@' . $correct;
    }
}
