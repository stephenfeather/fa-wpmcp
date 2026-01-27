<?php

/**
 * Exception thrown for .env file operations.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for dotenv operation errors.
 *
 * Thrown when attempting to read, write, or modify environment
 * variables in the .env file fails.
 *
 * @package FAWpmcp\Exceptions
 */
class DotenvException extends Exception
{
}
