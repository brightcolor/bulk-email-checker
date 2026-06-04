<?php

namespace Tests\Feature;

use App\Services\Verifier\EmailVerifierService;
use Tests\TestCase;

class EmailVerifierTest extends TestCase
{
    private EmailVerifierService $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new EmailVerifierService();
    }

    public function test_syntax_error_detected(): void
    {
        $result = $this->verifier->verify('not-an-email');
        $this->assertEquals('syntax_error', $result->status);
    }

    public function test_missing_at_sign(): void
    {
        $result = $this->verifier->verify('invalidemail.com');
        $this->assertEquals('syntax_error', $result->status);
    }

    public function test_double_at_sign(): void
    {
        $result = $this->verifier->verify('user@@example.com');
        $this->assertEquals('syntax_error', $result->status);
    }

    public function test_empty_local_part(): void
    {
        $result = $this->verifier->verify('@example.com');
        $this->assertEquals('syntax_error', $result->status);
    }

    public function test_valid_syntax_passes(): void
    {
        $result = $this->verifier->verify('user@example.com');
        $this->assertNotEquals('syntax_error', $result->status);
        $this->assertEquals('user@example.com', $result->normalizedEmail);
        $this->assertEquals('example.com', $result->domain);
    }

    public function test_email_normalization(): void
    {
        $result = $this->verifier->verify('User@EXAMPLE.COM');
        $this->assertEquals('user@example.com', $result->normalizedEmail);
    }

    public function test_disposable_domain_detected(): void
    {
        $result = $this->verifier->verify('test@mailinator.com');
        $this->assertTrue($result->isDisposable);
        $this->assertEquals('disposable', $result->status);
    }

    public function test_role_account_detected(): void
    {
        $result = $this->verifier->verify('info@example.com');
        $this->assertTrue($result->isRoleAccount);
    }

    public function test_admin_role_account(): void
    {
        $result = $this->verifier->verify('admin@example.com');
        $this->assertTrue($result->isRoleAccount);
    }

    public function test_noreply_role_account(): void
    {
        $result = $this->verifier->verify('noreply@example.com');
        $this->assertTrue($result->isRoleAccount);
    }

    public function test_gmail_typo_suggestion(): void
    {
        $result = $this->verifier->verify('user@gmal.com');
        $this->assertNotNull($result->suggestedCorrection);
        $this->assertStringContainsString('gmail.com', $result->suggestedCorrection);
    }

    public function test_hotmail_typo_suggestion(): void
    {
        $result = $this->verifier->verify('user@hotmial.com');
        $this->assertNotNull($result->suggestedCorrection);
        $this->assertStringContainsString('hotmail.com', $result->suggestedCorrection);
    }

    public function test_no_typo_for_correct_domain(): void
    {
        $result = $this->verifier->verify('user@gmail.com');
        $this->assertNull($result->suggestedCorrection);
    }
}
