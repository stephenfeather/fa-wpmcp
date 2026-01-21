<?php
/**
 * Tests for AbilityExecutor.
 *
 * @package FAWpmcp\Tests\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\AbilityExecutor;
use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Logging\ActivityLoggerInterface;
use FAWpmcp\RateLimiting\RateLimiterInterface;
use FAWpmcp\ValueObjects\PermissionSettings;
use FAWpmcp\ValueObjects\RateLimitResult;
use FAWpmcp\ValueObjects\Result;
use FAWpmcp\Webhooks\WebhookManagerInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test AbilityExecutor pipeline orchestration.
 *
 * Tests the full execution pipeline including:
 * - Permission checking
 * - Rate limiting
 * - Activity logging (before/after)
 * - Webhook firing (before/after)
 * - Ability execution
 * - Error handling
 *
 * @package FAWpmcp\Tests\Abilities
 */
class AbilityExecutorTest extends TestCase {
	/**
	 * Tear down Mockery after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Create a mock ability for testing.
	 *
	 * @param string $name          Ability name.
	 * @param string $category      Ability category.
	 * @param string $operation     Operation type.
	 * @param array  $execute_return Return value from do_execute.
	 * @return AbstractAbility
	 */
	private function create_mock_ability(
		string $name = 'fa-wpmcp/test-ability',
		string $category = 'test-category',
		string $operation = 'read',
		array $execute_return = [ 'result' => 'success' ]
	): AbstractAbility {
		$ability = Mockery::mock( AbstractAbility::class );
		$ability->shouldReceive( 'get_name' )->andReturn( $name );
		$ability->shouldReceive( 'get_category' )->andReturn( $category );
		$ability->shouldReceive( 'get_operation_type' )->andReturn( $operation );
		$ability->shouldReceive( 'do_execute' )->andReturn( $execute_return );
		$ability->shouldReceive( 'get_label' )->andReturn( 'Test Ability' );
		$ability->shouldReceive( 'get_description' )->andReturn( 'Test ability description' );
		$ability->shouldReceive( 'get_required_capability' )->andReturn( 'read' );
		return $ability;
	}

	/**
	 * Test executor runs full pipeline successfully.
	 *
	 * Verifies that all pipeline steps execute in order when all checks pass.
	 *
	 * @return void
	 */
	public function test_executor_runs_full_pipeline(): void {
		// Mock PermissionChecker (static calls).
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		// Mock RateLimiter.
		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )
			->once()
			->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' )
			->once();

		// Mock ActivityLogger.
		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )
			->once()
			->andReturn( 'correlation-id-123' );
		$logger->shouldReceive( 'log_after_execute' )
			->once()
			->with(
				'correlation-id-123',
				Mockery::type( 'array' ),
				true,
				null,
				Mockery::type( 'float' )
			);

		// Mock WebhookManager.
		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' )
			->twice(); // before and after hooks.

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability();
		$result  = $executor->execute( $ability, [ 'test' => 'input' ], 1, 'admin', '127.0.0.1' );

		$this->assertTrue( $result->is_success );
	}

	/**
	 * Test executor returns permission denied error.
	 *
	 * When global permissions block execution, pipeline should stop early.
	 *
	 * @return void
	 */
	public function test_executor_returns_permission_denied(): void {
		// Mock PermissionChecker with read disabled.
		$permission_settings = new PermissionSettings(
			global_read_enabled: false,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		// These should not be called when permission fails.
		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldNotReceive( 'check' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldNotReceive( 'log_before_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldNotReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability( operation: 'read' );
		$result  = $executor->execute( $ability, [ 'test' => 'input' ], 1, 'admin', '127.0.0.1' );

		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'ability_disabled', $result->error_code );
	}

	/**
	 * Test executor returns rate limit exceeded error.
	 *
	 * When rate limit is exceeded, pipeline should stop after permission check.
	 *
	 * @return void
	 */
	public function test_executor_returns_rate_limit_exceeded(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		// Rate limiter returns denied.
		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )
			->once()
			->andReturn( RateLimitResult::denied( 30, 'minute' ) );
		$rate_limiter->shouldNotReceive( 'record' );

		// Logger should not be called.
		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldNotReceive( 'log_before_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldNotReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability();
		$result  = $executor->execute( $ability, [ 'test' => 'input' ], 1, 'admin', '127.0.0.1' );

		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'rate_limit_exceeded', $result->error_code );
		$this->assertStringContainsString( '30', $result->error_message );
	}

	/**
	 * Test executor logs before execution.
	 *
	 * Verify log_before_execute is called with correct parameters.
	 *
	 * @return void
	 */
	public function test_executor_logs_before_execution(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )
			->once()
			->with(
				'fa-wpmcp/list-posts',
				'posts-pages',
				'read',
				1,
				'admin',
				'192.168.1.1',
				[ 'limit' => 10 ]
			)
			->andReturn( 'correlation-123' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability(
			name: 'fa-wpmcp/list-posts',
			category: 'posts-pages',
			operation: 'read'
		);
		$executor->execute( $ability, [ 'limit' => 10 ], 1, 'admin', '192.168.1.1' );
	}

	/**
	 * Test executor logs after execution with success.
	 *
	 * @return void
	 */
	public function test_executor_logs_after_execution_success(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )
			->andReturn( 'correlation-456' );
		$logger->shouldReceive( 'log_after_execute' )
			->once()
			->with(
				'correlation-456',
				[ 'result' => 'success' ],
				true,
				null,
				Mockery::type( 'float' )
			);

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability();
		$executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );
	}

	/**
	 * Test executor fires before webhook.
	 *
	 * @return void
	 */
	public function test_executor_fires_before_webhook(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' )
			->once()
			->with(
				'ability.before_execute',
				Mockery::on(
					function ( $context ) {
						return 'fa-wpmcp/create-post' === $context['ability_name']
						&& 'posts-pages' === $context['category']
						&& 'write' === $context['operation'];
					}
				)
			);
		$webhook_manager->shouldReceive( 'trigger' )
			->once()
			->with( 'ability.after_execute', Mockery::type( 'array' ) );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability(
			name: 'fa-wpmcp/create-post',
			category: 'posts-pages',
			operation: 'write'
		);
		$executor->execute( $ability, [ 'title' => 'Test' ], 1, 'admin', '127.0.0.1' );
	}

	/**
	 * Test executor fires after webhook with success context.
	 *
	 * @return void
	 */
	public function test_executor_fires_after_webhook(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' )
			->with( 'ability.before_execute', Mockery::type( 'array' ) )
			->once();
		$webhook_manager->shouldReceive( 'trigger' )
			->once()
			->with(
				'ability.after_execute',
				Mockery::on(
					function ( $context ) {
						return 'fa-wpmcp/list-posts' === $context['ability_name']
						&& true === $context['success']
						&& isset( $context['execution_time_ms'] );
					}
				)
			);

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability( name: 'fa-wpmcp/list-posts' );
		$executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );
	}

	/**
	 * Test executor handles ability execution failure.
	 *
	 * When ability throws exception, capture error and log failure.
	 *
	 * @return void
	 */
	public function test_executor_handles_ability_failure(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' )
			->once()
			->with(
				'corr-id',
				null,
				false,
				'Something went wrong',
				Mockery::type( 'float' )
			);

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' )
			->with( 'ability.before_execute', Mockery::type( 'array' ) );
		$webhook_manager->shouldReceive( 'trigger' )
			->with( 'ability.failed', Mockery::type( 'array' ) );

		// Create ability that throws exception.
		$ability = Mockery::mock( AbstractAbility::class );
		$ability->shouldReceive( 'get_name' )->andReturn( 'fa-wpmcp/failing-ability' );
		$ability->shouldReceive( 'get_category' )->andReturn( 'test' );
		$ability->shouldReceive( 'get_operation_type' )->andReturn( 'read' );
		$ability->shouldReceive( 'do_execute' )
			->andThrow( new \RuntimeException( 'Something went wrong' ) );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$result = $executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );

		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'internal_error', $result->error_code );
		$this->assertEquals( 'Something went wrong', $result->error_message );
	}

	/**
	 * Test executor fires failed webhook on ability exception.
	 *
	 * @return void
	 */
	public function test_executor_fires_failed_webhook(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' )
			->with( 'ability.before_execute', Mockery::type( 'array' ) );
		$webhook_manager->shouldReceive( 'trigger' )
			->once()
			->with(
				'ability.failed',
				Mockery::on(
					function ( $context ) {
						return 'fa-wpmcp/failing-ability' === $context['ability_name']
						&& false === $context['success']
						&& 'Database error' === $context['error_message'];
					}
				)
			);

		// Create ability that throws exception.
		$ability = Mockery::mock( AbstractAbility::class );
		$ability->shouldReceive( 'get_name' )->andReturn( 'fa-wpmcp/failing-ability' );
		$ability->shouldReceive( 'get_category' )->andReturn( 'test' );
		$ability->shouldReceive( 'get_operation_type' )->andReturn( 'write' );
		$ability->shouldReceive( 'do_execute' )
			->andThrow( new \RuntimeException( 'Database error' ) );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );
	}

	/**
	 * Test executor passes correct context through pipeline.
	 *
	 * Verify ability receives correct input data.
	 *
	 * @return void
	 */
	public function test_executor_passes_input_to_ability(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' );

		$received_input = null;
		$ability        = Mockery::mock( AbstractAbility::class );
		$ability->shouldReceive( 'get_name' )->andReturn( 'fa-wpmcp/test' );
		$ability->shouldReceive( 'get_category' )->andReturn( 'test' );
		$ability->shouldReceive( 'get_operation_type' )->andReturn( 'read' );
		$ability->shouldReceive( 'do_execute' )
			->with(
				Mockery::on(
					function ( $input ) use ( &$received_input ) {
						$received_input = $input;
						return true;
					}
				)
			)
			->andReturn( [ 'done' => true ] );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$executor->execute(
			$ability,
			[
				'key1' => 'value1',
				'key2' => 'value2',
			],
			1,
			'admin',
			'127.0.0.1'
		);

		$this->assertEquals(
			[
				'key1' => 'value1',
				'key2' => 'value2',
			],
			$received_input
		);
	}

	/**
	 * Test executor respects category permissions.
	 *
	 * @return void
	 */
	public function test_executor_respects_category_permissions(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [
				'posts-pages' => [ 'enable_write' => false ],
			],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldNotReceive( 'check' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldNotReceive( 'log_before_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldNotReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability(
			name: 'fa-wpmcp/create-post',
			category: 'posts-pages',
			operation: 'write'
		);
		$result = $executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );

		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'ability_disabled', $result->error_code );
	}

	/**
	 * Test executor respects ability-specific permissions.
	 *
	 * @return void
	 */
	public function test_executor_respects_ability_permissions(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [
				'fa-wpmcp/dangerous-ability' => [ 'enabled' => false ],
			],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldNotReceive( 'check' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldNotReceive( 'log_before_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldNotReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability( name: 'fa-wpmcp/dangerous-ability' );
		$result  = $executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );

		$this->assertFalse( $result->is_success );
		$this->assertEquals( 'ability_disabled', $result->error_code );
	}

	/**
	 * Test executor records rate limit on success.
	 *
	 * @return void
	 */
	public function test_executor_records_rate_limit_on_success(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' )
			->once()
			->with( 'fa-wpmcp/test-ability', 42, '10.0.0.1' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability();
		$executor->execute( $ability, [], 42, 'testuser', '10.0.0.1' );
	}

	/**
	 * Test executor returns output in Result value.
	 *
	 * @return void
	 */
	public function test_executor_returns_ability_output(): void {
		$permission_settings = new PermissionSettings(
			global_read_enabled: true,
			global_write_enabled: true,
			category_settings: [],
			ability_settings: [],
		);

		$rate_limiter = Mockery::mock( RateLimiterInterface::class );
		$rate_limiter->shouldReceive( 'check' )->andReturn( RateLimitResult::allowed() );
		$rate_limiter->shouldReceive( 'record' );

		$logger = Mockery::mock( ActivityLoggerInterface::class );
		$logger->shouldReceive( 'log_before_execute' )->andReturn( 'corr-id' );
		$logger->shouldReceive( 'log_after_execute' );

		$webhook_manager = Mockery::mock( WebhookManagerInterface::class );
		$webhook_manager->shouldReceive( 'trigger' );

		$executor = new AbilityExecutor(
			$permission_settings,
			$rate_limiter,
			$logger,
			$webhook_manager,
		);

		$ability = $this->create_mock_ability(
			execute_return: [ 'posts' => [ [ 'id' => 1 ], [ 'id' => 2 ] ] ]
		);
		$result = $executor->execute( $ability, [], 1, 'admin', '127.0.0.1' );

		$this->assertTrue( $result->is_success );
		$this->assertEquals( [ 'posts' => [ [ 'id' => 1 ], [ 'id' => 2 ] ] ], $result->value );
	}
}
