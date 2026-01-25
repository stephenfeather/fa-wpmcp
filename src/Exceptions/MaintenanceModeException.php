<?php
/**
 * Exception thrown when a maintenance mode operation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for maintenance mode operation failures.
 */
class MaintenanceModeException extends RuntimeException {
}
