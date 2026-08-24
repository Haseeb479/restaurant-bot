<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit coverage for the password helpers that make the plaintext→bcrypt
 * migration safe. Replaces the `assertTrue(true)` scaffold test.
 *
 * The `isHashed()` guard is not cosmetic: Hash::check() *throws* a
 * RuntimeException on non-bcrypt input when hashing.verify is enabled, so
 * verifying a legacy plaintext password without the guard is an HTTP 500, not a
 * failed login.
 */
class PasswordHelpersTest extends TestCase
{
    public function test_is_hashed_recognises_bcrypt_hashes(): void
    {
        $this->assertTrue(DashboardController::isHashed(Hash::make('some-password')));
    }

    public function test_is_hashed_rejects_plaintext_and_empty_values(): void
    {
        $this->assertFalse(DashboardController::isHashed('admin123'));
        $this->assertFalse(DashboardController::isHashed('$2y$notreallyahash'));
        $this->assertFalse(DashboardController::isHashed(''));
        $this->assertFalse(DashboardController::isHashed(null));
    }

    public function test_password_matches_verifies_hashed_values(): void
    {
        $hash = Hash::make('correct-horse-battery');

        $this->assertTrue(DashboardController::passwordMatches('correct-horse-battery', $hash));
        $this->assertFalse(DashboardController::passwordMatches('wrong', $hash));
    }

    public function test_password_matches_verifies_legacy_plaintext_without_throwing(): void
    {
        $this->assertTrue(DashboardController::passwordMatches('admin123', 'admin123'));
        $this->assertFalse(DashboardController::passwordMatches('admin124', 'admin123'));
    }

    public function test_password_matches_rejects_empty_input_on_either_side(): void
    {
        $this->assertFalse(DashboardController::passwordMatches('', 'admin123'));
        $this->assertFalse(DashboardController::passwordMatches('admin123', ''));
        $this->assertFalse(DashboardController::passwordMatches('', ''));
        $this->assertFalse(DashboardController::passwordMatches('anything', null));
    }
}
