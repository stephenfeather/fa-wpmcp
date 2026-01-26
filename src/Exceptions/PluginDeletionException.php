<?php

/**
 * Exception thrown when a plugin deletion fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for plugin deletion failures.
 *
 * Thrown when attempting to delete a plugin fails.
 *
 * @package FAWpmcp\Exceptions
 */
class PluginDeletionException extends RuntimeException
{
}
