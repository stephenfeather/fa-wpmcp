<?php
/**
 * Pipeline executor for ability operations.
 *
 * @package FAWpmcp\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities;

use FAWpmcp\Logging\ActivityLoggerInterface;
use FAWpmcp\RateLimiting\RateLimiterInterface;
use FAWpmcp\ValueObjects\PermissionSettings;
use FAWpmcp\ValueObjects\Result;
use FAWpmcp\Webhooks\WebhookManagerInterface;
use RuntimeException;

/**
 * Orchestrates ability execution pipeline.
 *
 * Integrates all cross-cutting concerns:
 * - Permission checking
 * - Rate limiting
 * - Activity logging (before/after)
 * - Webhook firing (before/after/failed)
 * - Error handling
 *
 * @package FAWpmcp\Abilities
 */
final class AbilityExecutor {
	/**
	 * Permission settings.
	 *
	 * @var PermissionSettings
	 */
	private PermissionSettings $permissionSettings;

	/**
	 * Rate limiter.
	 *
	 * @var RateLimiterInterface
	 */
	private RateLimiterInterface $rateLimiter;

	/**
	 * Activity logger.
	 *
	 * @var ActivityLoggerInterface
	 */
	private ActivityLoggerInterface $logger;

	/**
	 * Webhook manager.
	 *
	 * @var WebhookManagerInterface
	 */
	private WebhookManagerInterface $webhookManager;

	/**
	 * Constructor.
	 *
	 * @param PermissionSettings      $permissionSettings Permission settings.
	 * @param RateLimiterInterface    $rateLimiter        Rate limiter.
	 * @param ActivityLoggerInterface $logger             Activity logger.
	 * @param WebhookManagerInterface $webhookManager     Webhook manager.
	 */
	public function __construct(
		PermissionSettings $permissionSettings,
		RateLimiterInterface $rateLimiter,
		ActivityLoggerInterface $logger,
		WebhookManagerInterface $webhookManager
	) {
		$this->permissionSettings = $permissionSettings;
		$this->rateLimiter        = $rateLimiter;
		$this->logger             = $logger;
		$this->webhookManager     = $webhookManager;
	}

	/**
	 * Execute an ability with full pipeline.
	 *
	 * @param AbstractAbility      $ability    Ability to execute.
	 * @param array<string, mixed> $input      Input data.
	 * @param int                  $user_id    User ID.
	 * @param string               $user_login User login.
	 * @param string               $ip_address IP address.
	 * @return Result Execution result.
	 */
	public function execute(
		AbstractAbility $ability,
		array $input,
		int $user_id,
		string $user_login,
		string $ip_address
	): Result {
		$start_time = microtime( true );

		// Build context for pipeline.
		$context = array(
			'ability'      => $ability,
			'ability_name' => $ability->getName(),
			'category'     => $ability->getCategory(),
			'operation'    => $ability->getOperationType(),
			'input'        => $input,
			'user_id'      => $user_id,
			'user_login'   => $user_login,
			'ip_address'   => $ip_address,
			'start_time'   => $start_time,
		);

		// Check permissions first.
		$permission_result = $this->checkPermissions( $context );
		if ( ! $permission_result->is_success ) {
			return $permission_result;
		}

		// Check rate limit.
		$rate_limit_result = $this->checkRateLimit( $context );
		if ( ! $rate_limit_result->is_success ) {
			return $rate_limit_result;
		}

		// Log before execution.
		$correlation_id            = $this->logBeforeExecute( $context );
		$context['correlation_id'] = $correlation_id;

		// Fire before webhook.
		$this->fireBeforeWebhook( $context );

		// Execute the ability.
		$execution_result = $this->executeAbility( $context );

		// Calculate execution time.
		$execution_time_ms            = ( microtime( true ) - $start_time ) * 1000;
		$context['execution_time_ms'] = $execution_time_ms;

		if ( $execution_result->is_success ) {
			$context['output']  = $execution_result->value;
			$context['success'] = true;

			// Record rate limit usage.
			$this->rateLimiter->record(
				$ability->getName(),
				$user_id,
				$ip_address
			);

			// Log after execution.
			$this->logAfterExecute(
				$correlation_id,
				$execution_result->value,
				true,
				null,
				$start_time
			);

			// Fire after webhook.
			$this->fireAfterWebhook( $context );
		} else {
			$context['success']       = false;
			$context['error_message'] = $execution_result->error_message;

			// Log after execution with error.
			$this->logAfterExecute(
				$correlation_id,
				null,
				false,
				$execution_result->error_message,
				$start_time
			);

			// Fire failed webhook.
			$this->fireFailedWebhook( $context );
		}

		return $execution_result;
	}

	/**
	 * Check if ability execution is permitted.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return Result Success if permitted, failure if denied.
	 */
	private function checkPermissions( array $context ): Result {
		$ability_name = $context['ability_name'];
		$category     = $context['category'];
		$operation    = $context['operation'];

		// Check all permission levels (ability, category, global) - first failure wins.
		$failure = $this->checkAbilityPermission( $ability_name )
				?? $this->checkCategoryPermission( $category, $operation )
				?? $this->checkGlobalPermission( $operation );

		if ( $failure !== null ) {
			return $failure;
		}

		$capability_failure = $this->checkCapability( $context );
		if ( $capability_failure !== null ) {
			return $capability_failure;
		}

		return Result::success( $context );
	}

	/**
	 * Check ability-specific permission.
	 *
	 * @param string $ability_name Ability name.
	 * @return Result|null Failure result if denied, null if allowed.
	 */
	private function checkAbilityPermission( string $ability_name ): ?Result {
		if ( isset( $this->permissionSettings->ability_settings[ $ability_name ] ) ) {
			$ability_settings = $this->permissionSettings->ability_settings[ $ability_name ];
			if ( isset( $ability_settings['enabled'] ) && false === $ability_settings['enabled'] ) {
				return Result::failure(
					'ability_disabled',
					sprintf( 'Ability "%s" is disabled.', $ability_name )
				);
			}
		}
		return null;
	}

	/**
	 * Check category-level permission.
	 *
	 * @param string $category Category name.
	 * @param string $operation Operation type (read/write).
	 * @return Result|null Failure result if denied, null if allowed.
	 */
	private function checkCategoryPermission( string $category, string $operation ): ?Result {
		if ( ! isset( $this->permissionSettings->category_settings[ $category ] ) ) {
			return null;
		}

		$category_settings = $this->permissionSettings->category_settings[ $category ];

		// Check operation-specific permission.
		$is_read_disabled  = 'read' === $operation && isset( $category_settings['enable_read'] ) && false === $category_settings['enable_read'];
		$is_write_disabled = 'write' === $operation && isset( $category_settings['enable_write'] ) && false === $category_settings['enable_write'];

		if ( $is_read_disabled || $is_write_disabled ) {
			return Result::failure(
				'ability_disabled',
				sprintf( '%s operations in category "%s" are disabled.', ucfirst( $operation ), $category )
			);
		}

		return null;
	}

	/**
	 * Check global permission.
	 *
	 * @param string $operation Operation type (read/write).
	 * @return Result|null Failure result if denied, null if allowed.
	 */
	private function checkGlobalPermission( string $operation ): ?Result {
		if ( 'read' === $operation && ! $this->permissionSettings->global_read_enabled ) {
			return Result::failure(
				'ability_disabled',
				'Global read operations are disabled. Enable via WP Admin > Settings > FA WPMCP, or run: wp option update fa_wpmcp_permissions \'{"global_read_enabled":true,"global_write_enabled":false}\' --format=json'
			);
		}

		if ( 'write' === $operation && ! $this->permissionSettings->global_write_enabled ) {
			return Result::failure(
				'ability_disabled',
				'Global write operations are disabled. Enable via WP Admin > Settings > FA WPMCP, or run: wp option update fa_wpmcp_permissions \'{"global_read_enabled":true,"global_write_enabled":true}\' --format=json'
			);
		}

		return null;
	}

	/**
	 * Check WordPress capability for this ability.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return Result|null Failure result if denied, null if allowed or unavailable.
	 */
	private function checkCapability( array $context ): ?Result {
		$ability    = $context['ability'];
		$capability = $ability->getRequiredCapability();

		if ( '' === $capability ) {
			return null;
		}

		if ( function_exists( 'user_can' ) ) {
			$allowed = user_can( $context['user_id'], $capability );
		} elseif ( function_exists( 'current_user_can' ) ) {
			$allowed = current_user_can( $capability );
		} else {
			return null;
		}

		if ( ! $allowed ) {
			return Result::failure(
				'insufficient_capability',
				sprintf( 'User lacks required capability: %s', $capability )
			);
		}

		return null;
	}

	/**
	 * Check if request is within rate limits.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return Result Success if allowed, failure if rate limited.
	 */
	private function checkRateLimit( array $context ): Result {
		$result = $this->rateLimiter->check(
			$context['ability_name'],
			$context['user_id'],
			$context['ip_address']
		);

		if ( ! $result->allowed ) {
			return Result::failure(
				'rate_limit_exceeded',
				sprintf(
					'Rate limit exceeded. Retry after %d seconds.',
					$result->retry_after
				)
			);
		}

		return Result::success( $context );
	}

	/**
	 * Log before ability execution.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return string Correlation ID.
	 */
	private function logBeforeExecute( array $context ): string {
		return $this->logger->logBeforeExecute(
			$context['ability_name'],
			$context['category'],
			$context['operation'],
			$context['user_id'],
			$context['user_login'],
			$context['ip_address'],
			$context['input']
		);
	}

	/**
	 * Log after ability execution.
	 *
	 * @param string      $correlation_id Correlation ID.
	 * @param array|null  $output         Output data.
	 * @param bool        $success        Whether execution succeeded.
	 * @param string|null $error_message  Error message if failed.
	 * @param float       $start_time     Start time.
	 * @return void
	 */
	private function logAfterExecute(
		string $correlation_id,
		?array $output,
		bool $success,
		?string $error_message,
		float $start_time
	): void {
		$this->logger->logAfterExecute(
			$correlation_id,
			$output,
			$success,
			$error_message,
			$start_time
		);
	}

	/**
	 * Fire before execution webhook.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return void
	 */
	private function fireBeforeWebhook( array $context ): void {
		$this->webhookManager->trigger(
			'ability.before_execute',
			array(
				'ability_name' => $context['ability_name'],
				'category'     => $context['category'],
				'operation'    => $context['operation'],
				'input'        => $context['input'],
				'user_id'      => $context['user_id'],
				'user_login'   => $context['user_login'],
			)
		);
	}

	/**
	 * Fire after execution webhook.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return void
	 */
	private function fireAfterWebhook( array $context ): void {
		$this->webhookManager->trigger(
			'ability.after_execute',
			array(
				'ability_name'      => $context['ability_name'],
				'category'          => $context['category'],
				'operation'         => $context['operation'],
				'input'             => $context['input'] ?? array(),
				'output'            => $context['output'] ?? array(),
				'user_id'           => $context['user_id'] ?? 0,
				'user_login'        => $context['user_login'] ?? '',
				'ip'                => $context['ip_address'] ?? '',
				'success'           => true,
				'execution_time_ms' => $context['execution_time_ms'],
			)
		);
	}

	/**
	 * Fire failed execution webhook.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return void
	 */
	private function fireFailedWebhook( array $context ): void {
		$this->webhookManager->trigger(
			'ability.failed',
			array(
				'ability_name'      => $context['ability_name'],
				'category'          => $context['category'],
				'operation'         => $context['operation'],
				'input'             => $context['input'] ?? array(),
				'output'            => array(),
				'user_id'           => $context['user_id'] ?? 0,
				'user_login'        => $context['user_login'] ?? '',
				'ip'                => $context['ip_address'] ?? '',
				'success'           => false,
				'error_message'     => $context['error_message'] ?? null,
				'execution_time_ms' => $context['execution_time_ms'] ?? 0,
			)
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array<string, mixed> $context Execution context.
	 * @return Result Execution result.
	 */
	private function executeAbility( array $context ): Result {
		try {
			$ability = $context['ability'];
			$output  = $ability->doExecute( $context['input'] );
			return Result::success( $output );
		} catch ( RuntimeException $e ) {
			return Result::failure( 'internal_error', $e->getMessage() );
		} catch ( \Exception $e ) {
			return Result::failure( 'internal_error', $e->getMessage() );
		}
	}
}
