<?php
/**
 * Exception thrown when encryption or decryption operations fail.
 *
 * @package FAWpmcp\Exceptions
 */

declare(strict_types=1);

namespace FAWpmcp\Exceptions;

use RuntimeException;

/**
 * Exception for encryption operation failures.
 *
 * Thrown when encryption, decryption, or key derivation operations fail.
 *
 * @package FAWpmcp\Exceptions
 */
class EncryptionException extends RuntimeException {

}
