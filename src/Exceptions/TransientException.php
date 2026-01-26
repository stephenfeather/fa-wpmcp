<?php
/**
 * Exception thrown when a transient operation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for transient operation failures.
 *
 * Thrown when getting, setting, or deleting transients fails.
 *
 * @package FAWpmcp\Exceptions
 */
class TransientException extends RuntimeException {

}
