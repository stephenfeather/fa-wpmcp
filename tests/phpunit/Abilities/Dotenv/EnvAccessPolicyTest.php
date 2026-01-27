<?php

/**
 * Tests for EnvAccessPolicy.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use FAWpmcp\Abilities\Dotenv\EnvAccessPolicy;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test EnvAccessPolicy functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class EnvAccessPolicyTest extends BrainMonkeyTestCase
{
    /**
     * The policy instance under test.
     *
     * @var EnvAccessPolicy
     */
    private EnvAccessPolicy $policy;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EnvAccessPolicy();
    }

    /**
     * Test isSensitive returns true for PASSWORD pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForPasswordPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('DB_PASSWORD'));
        $this->assertTrue($this->policy->isSensitive('API_PASSWORD'));
        $this->assertTrue($this->policy->isSensitive('password'));
    }

    /**
     * Test isSensitive returns true for SECRET pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForSecretPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('CLIENT_SECRET'));
        $this->assertTrue($this->policy->isSensitive('APP_SECRET'));
        $this->assertTrue($this->policy->isSensitive('secret_key'));
    }

    /**
     * Test isSensitive returns true for KEY pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForKeyPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('API_KEY'));
        $this->assertTrue($this->policy->isSensitive('AUTH_KEY'));
        $this->assertTrue($this->policy->isSensitive('SECURE_AUTH_KEY'));
    }

    /**
     * Test isSensitive returns true for SALT pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForSaltPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('AUTH_SALT'));
        $this->assertTrue($this->policy->isSensitive('SECURE_AUTH_SALT'));
        $this->assertTrue($this->policy->isSensitive('LOGGED_IN_SALT'));
    }

    /**
     * Test isSensitive returns true for TOKEN pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForTokenPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('ACCESS_TOKEN'));
        $this->assertTrue($this->policy->isSensitive('REFRESH_TOKEN'));
        $this->assertTrue($this->policy->isSensitive('api_token'));
    }

    /**
     * Test isSensitive returns true for CREDENTIAL pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForCredentialPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('AWS_CREDENTIALS'));
        $this->assertTrue($this->policy->isSensitive('credential'));
    }

    /**
     * Test isSensitive returns true for AUTH pattern.
     *
     * @return void
     */
    public function testIsSensitiveReturnsTrueForAuthPattern(): void
    {
        $this->assertTrue($this->policy->isSensitive('BASIC_AUTH'));
        $this->assertTrue($this->policy->isSensitive('AUTH_TOKEN'));
    }

    /**
     * Test isSensitive returns false for non-sensitive variables.
     *
     * @return void
     */
    public function testIsSensitiveReturnsFalseForNonSensitiveVariables(): void
    {
        $this->assertFalse($this->policy->isSensitive('WP_ENV'));
        $this->assertFalse($this->policy->isSensitive('WP_HOME'));
        $this->assertFalse($this->policy->isSensitive('DB_HOST'));
        $this->assertFalse($this->policy->isSensitive('DEBUG'));
        $this->assertFalse($this->policy->isSensitive('APP_URL'));
    }

    /**
     * Test isProtected returns true for DB_NAME.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForDbName(): void
    {
        $this->assertTrue($this->policy->isProtected('DB_NAME'));
    }

    /**
     * Test isProtected returns true for DB_USER.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForDbUser(): void
    {
        $this->assertTrue($this->policy->isProtected('DB_USER'));
    }

    /**
     * Test isProtected returns true for DB_PASSWORD.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForDbPassword(): void
    {
        $this->assertTrue($this->policy->isProtected('DB_PASSWORD'));
    }

    /**
     * Test isProtected returns true for DB_HOST.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForDbHost(): void
    {
        $this->assertTrue($this->policy->isProtected('DB_HOST'));
    }

    /**
     * Test isProtected returns true for WP_ENV.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForWpEnv(): void
    {
        $this->assertTrue($this->policy->isProtected('WP_ENV'));
    }

    /**
     * Test isProtected returns true for WP_HOME.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForWpHome(): void
    {
        $this->assertTrue($this->policy->isProtected('WP_HOME'));
    }

    /**
     * Test isProtected returns true for WP_SITEURL.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForWpSiteurl(): void
    {
        $this->assertTrue($this->policy->isProtected('WP_SITEURL'));
    }

    /**
     * Test isProtected returns true for variables with _KEY suffix.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForKeySuffix(): void
    {
        $this->assertTrue($this->policy->isProtected('AUTH_KEY'));
        $this->assertTrue($this->policy->isProtected('SECURE_AUTH_KEY'));
        $this->assertTrue($this->policy->isProtected('LOGGED_IN_KEY'));
        $this->assertTrue($this->policy->isProtected('NONCE_KEY'));
    }

    /**
     * Test isProtected returns true for variables with _SALT suffix.
     *
     * @return void
     */
    public function testIsProtectedReturnsTrueForSaltSuffix(): void
    {
        $this->assertTrue($this->policy->isProtected('AUTH_SALT'));
        $this->assertTrue($this->policy->isProtected('SECURE_AUTH_SALT'));
        $this->assertTrue($this->policy->isProtected('LOGGED_IN_SALT'));
        $this->assertTrue($this->policy->isProtected('NONCE_SALT'));
    }

    /**
     * Test isProtected returns false for non-protected variables.
     *
     * @return void
     */
    public function testIsProtectedReturnsFalseForNonProtectedVariables(): void
    {
        $this->assertFalse($this->policy->isProtected('DEBUG'));
        $this->assertFalse($this->policy->isProtected('APP_URL'));
        $this->assertFalse($this->policy->isProtected('CUSTOM_VAR'));
        $this->assertFalse($this->policy->isProtected('MY_SETTING'));
    }

    /**
     * Test isProtected is case-insensitive.
     *
     * @return void
     */
    public function testIsProtectedIsCaseInsensitive(): void
    {
        $this->assertTrue($this->policy->isProtected('db_name'));
        $this->assertTrue($this->policy->isProtected('Db_Name'));
        $this->assertTrue($this->policy->isProtected('wp_env'));
    }

    /**
     * Test isSensitive is case-insensitive.
     *
     * @return void
     */
    public function testIsSensitiveIsCaseInsensitive(): void
    {
        $this->assertTrue($this->policy->isSensitive('Password'));
        $this->assertTrue($this->policy->isSensitive('SECRET'));
        $this->assertTrue($this->policy->isSensitive('api_key'));
    }

    /**
     * Test getSensitivePatterns returns array.
     *
     * @return void
     */
    public function testGetSensitivePatternsReturnsArray(): void
    {
        $patterns = $this->policy->getSensitivePatterns();
        $this->assertIsArray($patterns);
        $this->assertContains('PASSWORD', $patterns);
        $this->assertContains('SECRET', $patterns);
    }

    /**
     * Test getProtectedVariables returns array.
     *
     * @return void
     */
    public function testGetProtectedVariablesReturnsArray(): void
    {
        $variables = $this->policy->getProtectedVariables();
        $this->assertIsArray($variables);
        $this->assertContains('DB_NAME', $variables);
        $this->assertContains('WP_ENV', $variables);
    }

    /**
     * Test getProtectedSuffixes returns array.
     *
     * @return void
     */
    public function testGetProtectedSuffixesReturnsArray(): void
    {
        $suffixes = $this->policy->getProtectedSuffixes();
        $this->assertIsArray($suffixes);
        $this->assertContains('_KEY', $suffixes);
        $this->assertContains('_SALT', $suffixes);
    }
}
