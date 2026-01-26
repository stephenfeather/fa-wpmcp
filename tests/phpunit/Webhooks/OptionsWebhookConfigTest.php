<?php

/**
 * Tests for OptionsWebhookConfig.
 *
 * @package FAWpmcp\Tests\Webhooks
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Webhooks;

use Brain\Monkey\Functions;
use FAWpmcp\Webhooks\OptionsWebhookConfig;
use FAWpmcp\Webhooks\SecretEncryption;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Test OptionsWebhookConfig behavior.
 */
final class OptionsWebhookConfigTest extends TestCase {

	use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		\Brain\Monkey\setUp();
	}

	protected function tearDown(): void {
		\Brain\Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Create mock encryption for testing
	 *
	 * @return SecretEncryption
	 */
	private function createMockEncryption(): SecretEncryption {
		$encryption = Mockery::mock( SecretEncryption::class );
		$encryption->shouldReceive( 'isEncrypted' )->andReturn( false )->byDefault();
		return $encryption;
	}

	/**
	 * Test getSubscribedUrls returns empty when option invalid.
	 *
	 * @return void
	 */
	public function test_getSubscribedUrls_returns_empty_on_invalid_option(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_urls', array() )
			->andReturn( 'invalid' );

		$config = new OptionsWebhookConfig( $this->createMockEncryption() );
		$this->assertSame( array(), $config->getSubscribedUrls( 'event' ) );
	}

	/**
	 * Test getSubscribedUrls returns event URLs.
	 *
	 * @return void
	 */
	public function test_getSubscribedUrls_returns_event_urls(): void {
		Functions\expect( 'get_option' )
			->once()
			->andReturn(
				array(
					'event' => array( 'https://a.test', 'https://b.test' ),
				)
			);

		$config = new OptionsWebhookConfig( $this->createMockEncryption() );
		$this->assertSame( array( 'https://a.test', 'https://b.test' ), $config->getSubscribedUrls( 'event' ) );
	}

	/**
	 * Test getSecret decrypts encrypted secret.
	 *
	 * @return void
	 */
	public function test_getSecret_decrypts_encrypted_secret(): void {
		$encryption = Mockery::mock( SecretEncryption::class );
		$encryption->shouldReceive( 'isEncrypted' )
			->once()
			->with( 'sodium:v1:encrypted-data' )
			->andReturn( true );
		$encryption->shouldReceive( 'decrypt' )
			->once()
			->with( 'sodium:v1:encrypted-data' )
			->andReturn( 'decrypted-secret' );

		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret' )
			->andReturn( 'sodium:v1:encrypted-data' );

		$config = new OptionsWebhookConfig( $encryption );
		$this->assertSame( 'decrypted-secret', $config->getSecret() );
	}

	/**
	 * Test getSecret performs lazy migration on plain text secret.
	 *
	 * @return void
	 */
	public function test_getSecret_encrypts_plain_text_secret(): void {
		$encryption = Mockery::mock( SecretEncryption::class );
		$encryption->shouldReceive( 'isEncrypted' )
			->once()
			->with( 'plain-text-secret' )
			->andReturn( false );
		$encryption->shouldReceive( 'encrypt' )
			->once()
			->with( 'plain-text-secret' )
			->andReturn( 'sodium:v1:encrypted' );

		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret' )
			->andReturn( 'plain-text-secret' );

		Functions\expect( 'update_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret', 'sodium:v1:encrypted' )
			->andReturn( true );

		$config = new OptionsWebhookConfig( $encryption );
		$secret = $config->getSecret();

		$this->assertSame( 'plain-text-secret', $secret );
	}

	/**
	 * Test getSecret generates and stores encrypted secret when missing.
	 *
	 * @return void
	 */
	public function test_getSecret_generates_and_stores_encrypted_when_missing(): void {
		$encryption = Mockery::mock( SecretEncryption::class );
		$encryption->shouldReceive( 'encrypt' )
			->once()
			->with(
				Mockery::on(
					function ( $secret ) {
						return is_string( $secret ) && 64 === strlen( $secret );
					}
				)
			)
			->andReturn( 'sodium:v1:encrypted-new-secret' );

		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret' )
			->andReturn( false );

		Functions\expect( 'update_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret', 'sodium:v1:encrypted-new-secret' )
			->andReturn( true );

		$config = new OptionsWebhookConfig( $encryption );
		$secret = $config->getSecret();

		$this->assertSame( 64, strlen( $secret ) );
	}
}
