<?php

/**
 * Exception thrown when comment creation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for comment creation failures.
 *
 * Thrown when wp_insert_comment() or wp_new_comment() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class CommentCreationException extends Exception {

}
