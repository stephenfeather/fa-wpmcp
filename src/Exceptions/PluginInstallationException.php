<?php
/**
 * Exception thrown when a plugin installation fails.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for plugin installation failures.
 *
 * Thrown when attempting to install a plugin fails.
 *
 * @package FAWpmcp\Exceptions
 */
class PluginInstallationException extends RuntimeException {

}
