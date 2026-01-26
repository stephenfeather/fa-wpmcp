<?php

/**
 * Exception thrown when a privacy request is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use Exception;

/**
 * Exception for privacy request not found errors.
 *
 * Thrown when attempting to retrieve a privacy request that doesn't exist.
 *
 * @package FAWpmcp\Exceptions
 */
class PrivacyRequestNotFoundException extends Exception {

}
