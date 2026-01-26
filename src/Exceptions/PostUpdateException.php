<?php

/**
 * Exception thrown when post update fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for post update failures.
 *
 * Thrown when wp_update_post() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class PostUpdateException extends Exception {

}
