<?php

/**
 * Exception thrown when comment deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for comment deletion failures.
 *
 * Thrown when wp_trash_comment() or wp_delete_comment() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class CommentDeletionException extends Exception
{
}
