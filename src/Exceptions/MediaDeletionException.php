<?php

/**
 * Exception thrown when media deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for media deletion failures.
 *
 * Thrown when wp_delete_attachment() fails.
 *
 * @package FAWpmcp\Exceptions
 */
class MediaDeletionException extends Exception
{
}
