<?php

/**
 * Exception thrown when comment update fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for comment update failures.
 *
 * Thrown when wp_update_comment() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class CommentUpdateException extends Exception {

}
