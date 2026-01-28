<?php

/**
 * Exception thrown when checksum verification fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for checksum verification failures.
 *
 * Thrown when unable to fetch or verify WordPress checksums.
 *
 * @package FAWpmcp\Exceptions
 */
class ChecksumException extends RuntimeException
{
}
