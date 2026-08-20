<?php

/**
 * Abstract base class for WooCommerce abilities.
 *
 * @package FAWpmcp\Abilities\WooCommerce
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\WooCommerce;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Exceptions\WooCommerceNotActiveException;

/**
 * Abstract base class for WooCommerce-specific abilities.
 *
 * Provides WooCommerce activation and version checking before execution.
 * All WooCommerce abilities should extend this class.
 */
abstract class AbstractWooCommerceAbility extends AbstractAbility
{
    /**
     * Minimum required WooCommerce version.
     *
     * WooCommerce 8.0+ required for HPOS (High-Performance Order Storage) compatibility.
     */
    protected const MIN_WC_VERSION = '8.0';

    /**
     * Execute the ability with WooCommerce validation.
     *
     * This method is final to ensure WooCommerce checks always run.
     * Subclasses must implement doWooCommerceExecute() instead.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Output data.
     * @throws WooCommerceNotActiveException If WooCommerce is not active or version is too low.
     */
    final public function doExecute(array $input): array
    {
        $this->ensureWooCommerceActive();
        return $this->doWooCommerceExecute($input);
    }

    /**
     * Execute the WooCommerce-specific ability logic.
     *
     * Implement this method in subclasses to perform the actual ability logic.
     * WooCommerce is guaranteed to be active when this method is called.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Output data.
     */
    abstract protected function doWooCommerceExecute(array $input): array;

    /**
     * Ensure WooCommerce is installed, active, and meets version requirements.
     *
     * @throws WooCommerceNotActiveException If WooCommerce is not active or version is too low.
     */
    protected function ensureWooCommerceActive(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            throw new WooCommerceNotActiveException(
                'WooCommerce is not installed or active'
            );
        }

        $wc = WC();
        if ($wc === null) {
            throw new WooCommerceNotActiveException(
                'WooCommerce is not initialized'
            );
        }

        if (version_compare($wc->version, self::MIN_WC_VERSION, '<')) {
            throw new WooCommerceNotActiveException(
                sprintf(
                    'WooCommerce %s or higher is required. Current version: %s',
                    self::MIN_WC_VERSION,
                    $wc->version
                )
            );
        }
    }
}
