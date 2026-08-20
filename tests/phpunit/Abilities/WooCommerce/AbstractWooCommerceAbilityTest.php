<?php

/**
 * Tests for AbstractWooCommerceAbility base class.
 *
 * @package FAWpmcp\Tests\Abilities\WooCommerce
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\WooCommerce;

use Brain\Monkey\Functions;
use FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility;
use FAWpmcp\Exceptions\WooCommerceNotActiveException;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Tests for AbstractWooCommerceAbility base class.
 *
 * @covers \FAWpmcp\Abilities\WooCommerce\AbstractWooCommerceAbility
 */
class AbstractWooCommerceAbilityTest extends BrainMonkeyTestCase
{
    /**
     * Test that doExecute calls doWooCommerceExecute when WooCommerce is active.
     *
     * @return void
     */
    public function testDoExecuteCallsWooCommerceExecuteWhenActive(): void
    {
        // Mock WooCommerce as active.
        $wc = (object) ['version' => '8.5.0'];
        Functions\when('WC')->justReturn($wc);

        $ability = $this->createConcreteAbility(['result' => 'success']);

        $result = $ability->doExecute(['input' => 'test']);

        $this->assertEquals(['result' => 'success'], $result);
    }

    /**
     * Test that doExecute throws exception when WooCommerce class not loaded.
     *
     * @return void
     */
    public function testDoExecuteThrowsWhenWooCommerceNotLoaded(): void
    {
        // Mock WC() to return null (WooCommerce not initialized).
        Functions\when('WC')->justReturn(null);

        $ability = $this->createConcreteAbility([]);

        $this->expectException(WooCommerceNotActiveException::class);
        $this->expectExceptionMessage('WooCommerce is not initialized');

        $ability->doExecute([]);
    }

    /**
     * Test that doExecute throws exception when WooCommerce version is too old.
     *
     * @return void
     */
    public function testDoExecuteThrowsWhenVersionTooOld(): void
    {
        // Mock WooCommerce with old version.
        $wc = (object) ['version' => '7.9.0'];
        Functions\when('WC')->justReturn($wc);

        $ability = $this->createConcreteAbility([]);

        $this->expectException(WooCommerceNotActiveException::class);
        $this->expectExceptionMessage('WooCommerce 8.0 or higher is required');

        $ability->doExecute([]);
    }

    /**
     * Test that version 8.0.0 exactly passes the version check.
     *
     * @return void
     */
    public function testVersion80ExactlyPasses(): void
    {
        $wc = (object) ['version' => '8.0.0'];
        Functions\when('WC')->justReturn($wc);

        $ability = $this->createConcreteAbility(['passed' => true]);

        $result = $ability->doExecute([]);

        $this->assertEquals(['passed' => true], $result);
    }

    /**
     * Test that doExecute receives the input array.
     *
     * @return void
     */
    public function testDoExecutePassesInputToWooCommerceExecute(): void
    {
        $wc = (object) ['version' => '9.0.0'];
        Functions\when('WC')->justReturn($wc);

        $receivedInput = null;
        $ability = $this->createConcreteAbilityWithCallback(function ($input) use (&$receivedInput) {
            $receivedInput = $input;
            return ['done' => true];
        });

        $ability->doExecute(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $receivedInput);
    }

    /**
     * Create a concrete implementation of AbstractWooCommerceAbility for testing.
     *
     * @param array<string, mixed> $returnValue Value to return from doWooCommerceExecute.
     * @return AbstractWooCommerceAbility
     */
    private function createConcreteAbility(array $returnValue): AbstractWooCommerceAbility
    {
        return new class ($returnValue) extends AbstractWooCommerceAbility {
            /** @var array<string, mixed> */
            private array $returnValue;

            /**
             * @param array<string, mixed> $returnValue
             */
            public function __construct(array $returnValue)
            {
                $this->returnValue = $returnValue;
            }

            public function getName(): string
            {
                return 'fa-wpmcp/test-wc-ability';
            }

            public function getCategory(): string
            {
                return 'woocommerce-products';
            }

            public function getLabel(): string
            {
                return 'Test WC Ability';
            }

            public function getDescription(): string
            {
                return 'Test ability for WooCommerce.';
            }

            public function getInputSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function getOutputSchema(): array
            {
                return ['type' => 'object'];
            }

            public function getRequiredCapability(): string
            {
                return 'edit_products';
            }

            protected function doWooCommerceExecute(array $input): array
            {
                return $this->returnValue;
            }
        };
    }

    /**
     * Create a concrete ability with a custom callback for doWooCommerceExecute.
     *
     * @param callable $callback Callback to execute.
     * @return AbstractWooCommerceAbility
     */
    private function createConcreteAbilityWithCallback(callable $callback): AbstractWooCommerceAbility
    {
        return new class ($callback) extends AbstractWooCommerceAbility {
            /** @var callable */
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function getName(): string
            {
                return 'fa-wpmcp/test-wc-ability';
            }

            public function getCategory(): string
            {
                return 'woocommerce-products';
            }

            public function getLabel(): string
            {
                return 'Test WC Ability';
            }

            public function getDescription(): string
            {
                return 'Test ability for WooCommerce.';
            }

            public function getInputSchema(): array
            {
                return ['type' => 'object', 'properties' => []];
            }

            public function getOutputSchema(): array
            {
                return ['type' => 'object'];
            }

            public function getRequiredCapability(): string
            {
                return 'edit_products';
            }

            protected function doWooCommerceExecute(array $input): array
            {
                return ($this->callback)($input);
            }
        };
    }
}
