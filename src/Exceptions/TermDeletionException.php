<?php
/**
 * Exception thrown when term deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for term deletion failures.
 *
 * Thrown when wp_delete_term() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class TermDeletionException extends Exception {
}
