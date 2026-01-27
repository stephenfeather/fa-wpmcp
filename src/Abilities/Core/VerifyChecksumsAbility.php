<?php

/**
 * VerifyChecksumsAbility - verifies WordPress core file checksums.
 *
 * @package FAWpmcp\Abilities\Core
 */

declare(strict_types=1);

namespace FAWpmcp\Abilities\Core;

use FAWpmcp\Abilities\AbstractAbility;

/**
 * Ability to verify WordPress core file checksums.
 *
 * Compares local core files against official WordPress checksums
 * to detect modified or compromised files.
 *
 * @package FAWpmcp\Abilities\Core
 */
final class VerifyChecksumsAbility extends AbstractAbility
{
    /**
     * Get the unique ability name.
     *
     * @return string Ability name.
     */
    public function getName(): string
    {
        return 'fa-wpmcp/verify-checksums';
    }

    /**
     * Get the ability category.
     *
     * @return string Category name.
     */
    public function getCategory(): string
    {
        return 'core';
    }

    /**
     * Get the human-readable label.
     *
     * @return string Ability label.
     */
    public function getLabel(): string
    {
        return 'Verify Checksums';
    }

    /**
     * Get the ability description.
     *
     * @return string Description.
     */
    public function getDescription(): string
    {
        return 'Verify WordPress core file checksums against official hashes to detect modifications.';
    }

    /**
     * Get the input schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getInputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'version' => array(
                    'type'        => 'string',
                    'description' => 'WordPress version to verify against (default: current version).',
                ),
                'locale' => array(
                    'type'        => 'string',
                    'description' => 'Locale to verify against (default: en_US).',
                    'default'     => 'en_US',
                ),
            ),
        );
    }

    /**
     * Get the output schema.
     *
     * @return array<string, mixed> JSON Schema array.
     */
    public function getOutputSchema(): array
    {
        return array(
            'type'       => 'object',
            'properties' => array(
                'verified'       => array(
                    'type'        => 'boolean',
                    'description' => 'Whether all checksums verified successfully.',
                ),
                'version'        => array(
                    'type'        => 'string',
                    'description' => 'WordPress version verified against.',
                ),
                'locale'         => array(
                    'type'        => 'string',
                    'description' => 'Locale verified against.',
                ),
                'files_checked'  => array(
                    'type'        => 'integer',
                    'description' => 'Number of files checked.',
                ),
                'mismatches'     => array(
                    'type'        => 'array',
                    'description' => 'List of files with checksum mismatches.',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'file'     => array( 'type' => 'string' ),
                            'expected' => array( 'type' => 'string' ),
                            'actual'   => array( 'type' => 'string' ),
                            'status'   => array( 'type' => 'string' ),
                        ),
                    ),
                ),
                'missing_files'  => array(
                    'type'        => 'array',
                    'description' => 'List of missing core files.',
                    'items'       => array( 'type' => 'string' ),
                ),
                'extra_files'    => array(
                    'type'        => 'array',
                    'description' => 'List of extra files not in checksums (sample only, max 50).',
                    'items'       => array( 'type' => 'string' ),
                ),
            ),
        );
    }

    /**
     * Get the required WordPress capability.
     *
     * @return string WordPress capability name.
     */
    public function getRequiredCapability(): string
    {
        return 'manage_options';
    }

    /**
     * Execute the ability.
     *
     * @param array<string, mixed> $input Validated input data.
     * @return array<string, mixed> Verification results.
     * @throws \RuntimeException If checksums cannot be retrieved.
     */
    public function doExecute(array $input): array
    {
        $version = $input['version'] ?? get_bloginfo('version');
        $locale  = $input['locale'] ?? 'en_US';

        // Fetch official checksums from WordPress.org.
        $checksums = $this->fetchChecksums($version, $locale);

        if (empty($checksums)) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to fetch checksums for WordPress %s (%s).',
                    $version,
                    $locale
                )
            );
        }

        $mismatches    = array();
        $missing_files = array();
        $files_checked = 0;

        foreach ($checksums as $file => $checksum) {
            $file_path = ABSPATH . $file;
            $files_checked++;

            if (! file_exists($file_path)) {
                $missing_files[] = $file;
                continue;
            }

            $actual_checksum = md5_file($file_path);

            if ($actual_checksum !== $checksum) {
                $mismatches[] = array(
                    'file'     => $file,
                    'expected' => $checksum,
                    'actual'   => $actual_checksum ?: 'unreadable',
                    'status'   => 'modified',
                );
            }
        }

        $verified = empty($mismatches) && empty($missing_files);

        return array(
            'verified'      => $verified,
            'version'       => $version,
            'locale'        => $locale,
            'files_checked' => $files_checked,
            'mismatches'    => $mismatches,
            'missing_files' => $missing_files,
            'extra_files'   => array(), // Not implemented - would require full directory scan.
        );
    }

    /**
     * Fetch checksums from WordPress.org API.
     *
     * @param string $version WordPress version.
     * @param string $locale  Locale code.
     * @return array<string, string> File => checksum mapping.
     */
    private function fetchChecksums(string $version, string $locale): array
    {
        $url = sprintf(
            'https://api.wordpress.org/core/checksums/1.0/?version=%s&locale=%s',
            rawurlencode($version),
            rawurlencode($locale)
        );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (! is_array($data) || ! isset($data['checksums']) || ! is_array($data['checksums'])) {
            return array();
        }

        return $data['checksums'];
    }
}
