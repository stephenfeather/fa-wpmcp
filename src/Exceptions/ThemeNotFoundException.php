<?php

/**
 * ThemeNotFoundException - thrown when a theme is not found.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a requested theme is not found.
 *
 * @package FAWpmcp\Exceptions
 */
final class ThemeNotFoundException extends RuntimeException {

}
