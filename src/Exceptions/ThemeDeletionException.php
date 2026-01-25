<?php
/**
 * Exception thrown when a theme deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for theme deletion failures.
 *
 * Thrown when attempting to delete a theme fails.
 *
 * @package FAWpmcp\Exceptions
 */
class ThemeDeletionException extends RuntimeException {
}
