<?php
/**
 * Exception thrown when an option operation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for option operation failures.
 *
 * Thrown when getting, setting, or deleting options fails.
 *
 * @package FAWpmcp\Exceptions
 */
class OptionException extends RuntimeException {
}
