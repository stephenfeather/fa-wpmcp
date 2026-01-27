<?php

/**
 * Exception thrown when a config constant operation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for config constant operation failures.
 *
 * Thrown when accessing sensitive or invalid configuration constants.
 *
 * @package FAWpmcp\Exceptions
 */
class ConfigConstantException extends RuntimeException
{
}
