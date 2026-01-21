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
    private PermissionSettings $permission_settings;

    /**
     * Rate limiter.
     *
     * @var RateLimiterInterface
     */
    private RateLimiterInterface $rate_limiter;

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
    private WebhookManagerInterface $webhook_manager;

    /**
     * Constructor.
     *
     * @param PermissionSettings      $permission_settings Permission settings.
     * @param RateLimiterInterface    $rate_limiter        Rate limiter.
     * @param ActivityLoggerInterface $logger              Activity logger.
     * @param WebhookManagerInterface $webhook_manager     Webhook manager.
     */
    public function __construct(
        PermissionSettings $permission_settings,
        RateLimiterInterface $rate_limiter,
        ActivityLoggerInterface $logger,
        WebhookManagerInterface $webhook_manager
    ) {
        $this->permission_settings = $permission_settings;
        $this->rate_limiter        = $rate_limiter;
        $this->logger              = $logger;
        $this->webhook_manager     = $webhook_manager;
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
        $context = [
            'ability'      => $ability,
            'ability_name' => $ability->get_name(),
            'category'     => $ability->get_category(),
            'operation'    => $ability->get_operation_type(),
            'input'        => $input,
            'user_id'      => $user_id,
            'user_login'   => $user_login,
            'ip_address'   => $ip_address,
            'start_time'   => $start_time,
        ];

        // Check permissions first.
        $permission_result = $this->check_permissions( $context );
        if ( ! $permission_result->is_success ) {
            return $permission_result;
        }

        // Check rate limit.
        $rate_limit_result = $this->check_rate_limit( $context );
        if ( ! $rate_limit_result->is_success ) {
            return $rate_limit_result;
        }

        // Log before execution.
        $correlation_id              = $this->log_before_execute( $context );
        $context['correlation_id']   = $correlation_id;

        // Fire before webhook.
        $this->fire_before_webhook( $context );

        // Execute the ability.
        $execution_result = $this->execute_ability( $context );

        // Calculate execution time.
        $execution_time_ms             = ( microtime( true ) - $start_time ) * 1000;
        $context['execution_time_ms']  = $execution_time_ms;

        if ( $execution_result->is_success ) {
            $context['output']  = $execution_result->value;
            $context['success'] = true;

            // Record rate limit usage.
            $this->rate_limiter->record(
                $ability->get_name(),
                $user_id,
                $ip_address
            );

            // Log after execution.
            $this->log_after_execute(
                $correlation_id,
                $execution_result->value,
                true,
                null,
                $start_time
            );

            // Fire after webhook.
            $this->fire_after_webhook( $context );
        } else {
            $context['success']       = false;
            $context['error_message'] = $execution_result->error_message;

            // Log after execution with error.
            $this->log_after_execute(
                $correlation_id,
                null,
                false,
                $execution_result->error_message,
                $start_time
            );

            // Fire failed webhook.
            $this->fire_failed_webhook( $context );
        }

        return $execution_result;
    }

    /**
     * Check if ability execution is permitted.
     *
     * @param array<string, mixed> $context Execution context.
     * @return Result Success if permitted, failure if denied.
     */
    private function check_permissions( array $context ): Result {
        $ability_name = $context['ability_name'];
        $category     = $context['category'];
        $operation    = $context['operation'];

        // Check all permission levels (ability, category, global) - first failure wins.
        $failure = $this->check_ability_permission( $ability_name )
                ?? $this->check_category_permission( $category, $operation )
                ?? $this->check_global_permission( $operation );

        if ( $failure !== null ) {
            return $failure;
        }

        return Result::success( $context );
    }

    /**
     * Check ability-specific permission.
     *
     * @param string $ability_name Ability name.
     * @return Result|null Failure result if denied, null if allowed.
     */
    private function check_ability_permission( string $ability_name ): ?Result {
        if ( isset( $this->permission_settings->ability_settings[ $ability_name ] ) ) {
            $ability_settings = $this->permission_settings->ability_settings[ $ability_name ];
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
    private function check_category_permission( string $category, string $operation ): ?Result {
        if ( ! isset( $this->permission_settings->category_settings[ $category ] ) ) {
            return null;
        }

        $category_settings = $this->permission_settings->category_settings[ $category ];

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
    private function check_global_permission( string $operation ): ?Result {
        if ( 'read' === $operation && ! $this->permission_settings->global_read_enabled ) {
            return Result::failure(
                'ability_disabled',
                'Global read operations are disabled.'
            );
        }

        if ( 'write' === $operation && ! $this->permission_settings->global_write_enabled ) {
            return Result::failure(
                'ability_disabled',
                'Global write operations are disabled.'
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
    private function check_rate_limit( array $context ): Result {
        $result = $this->rate_limiter->check(
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
    private function log_before_execute( array $context ): string {
        return $this->logger->log_before_execute(
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
    private function log_after_execute(
        string $correlation_id,
        ?array $output,
        bool $success,
        ?string $error_message,
        float $start_time
    ): void {
        $this->logger->log_after_execute(
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
    private function fire_before_webhook( array $context ): void {
        $this->webhook_manager->trigger(
            'ability.before_execute',
            [
                'ability_name' => $context['ability_name'],
                'category'     => $context['category'],
                'operation'    => $context['operation'],
                'input'        => $context['input'],
                'user_id'      => $context['user_id'],
                'user_login'   => $context['user_login'],
            ]
        );
    }

    /**
     * Fire after execution webhook.
     *
     * @param array<string, mixed> $context Execution context.
     * @return void
     */
    private function fire_after_webhook( array $context ): void {
        $this->webhook_manager->trigger(
            'ability.after_execute',
            [
                'ability_name'      => $context['ability_name'],
                'category'          => $context['category'],
                'operation'         => $context['operation'],
                'success'           => true,
                'execution_time_ms' => $context['execution_time_ms'],
            ]
        );
    }

    /**
     * Fire failed execution webhook.
     *
     * @param array<string, mixed> $context Execution context.
     * @return void
     */
    private function fire_failed_webhook( array $context ): void {
        $this->webhook_manager->trigger(
            'ability.failed',
            [
                'ability_name'      => $context['ability_name'],
                'category'          => $context['category'],
                'operation'         => $context['operation'],
                'success'           => false,
                'error_message'     => $context['error_message'] ?? null,
                'execution_time_ms' => $context['execution_time_ms'] ?? 0,
            ]
        );
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $context Execution context.
     * @return Result Execution result.
     */
    private function execute_ability( array $context ): Result {
        try {
            $ability = $context['ability'];
            $output  = $ability->do_execute( $context['input'] );
            return Result::success( $output );
        } catch ( RuntimeException $e ) {
            return Result::failure( 'internal_error', $e->getMessage() );
        } catch ( \Exception $e ) {
            return Result::failure( 'internal_error', $e->getMessage() );
        }
    }
}
