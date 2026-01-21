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
	 * Test get_subscribed_urls returns empty when option invalid.
	 *
	 * @return void
	 */
	public function test_get_subscribed_urls_returns_empty_on_invalid_option(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_urls', array() )
			->andReturn( 'invalid' );

		$config = new OptionsWebhookConfig();
		$this->assertSame( array(), $config->get_subscribed_urls( 'event' ) );
	}

	/**
	 * Test get_subscribed_urls returns event URLs.
	 *
	 * @return void
	 */
	public function test_get_subscribed_urls_returns_event_urls(): void {
		Functions\expect( 'get_option' )
			->once()
			->andReturn( array(
				'event' => array( 'https://a.test', 'https://b.test' ),
			) );

		$config = new OptionsWebhookConfig();
		$this->assertSame( array( 'https://a.test', 'https://b.test' ), $config->get_subscribed_urls( 'event' ) );
	}

	/**
	 * Test get_secret returns existing secret.
	 *
	 * @return void
	 */
	public function test_get_secret_returns_existing_secret(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret' )
			->andReturn( 'existing-secret' );

		$config = new OptionsWebhookConfig();
		$this->assertSame( 'existing-secret', $config->get_secret() );
	}

	/**
	 * Test get_secret generates and stores new secret when missing.
	 *
	 * @return void
	 */
	public function test_get_secret_generates_and_stores_when_missing(): void {
		Functions\expect( 'get_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret' )
			->andReturn( false );

		Functions\expect( 'update_option' )
			->once()
			->with( 'fa_wpmcp_webhook_secret', Mockery::on( function( $secret ) {
				return is_string( $secret ) && 64 === strlen( $secret );
			} ) )
			->andReturn( true );

		$config = new OptionsWebhookConfig();
		$secret = $config->get_secret();

		$this->assertSame( 64, strlen( $secret ) );
	}
}
