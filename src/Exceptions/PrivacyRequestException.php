<?php
/**
 * Exception thrown when a privacy request operation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for privacy request operation failures.
 *
 * Thrown when creating, retrieving, or processing privacy requests fails.
 *
 * @package FAWpmcp\Exceptions
 */
class PrivacyRequestException extends RuntimeException {
}
