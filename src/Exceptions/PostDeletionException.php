<?php

/**
 * Exception thrown when post deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for post deletion failures.
 *
 * Thrown when wp_trash_post() or wp_delete_post() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class PostDeletionException extends Exception {

}
