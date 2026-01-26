<?php
/**
 * Tests for AbstractAbility default behavior.
 *
 * @package FAWpmcp\Tests\Abilities
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities;

use FAWpmcp\Abilities\AbstractAbility;
use PHPUnit\Framework\TestCase;

/**
 * Test AbstractAbility defaults.
 */
final class AbstractAbilityTest extends TestCase {
	/**
	 * Test default operation type and annotations.
	 *
	 * @return void
	 */
	public function test_default_operation_type_and_annotations(): void {
		$ability = new class() extends AbstractAbility {
			public function getName(): string {
				return 'fa-wpmcp/test-ability';
			}

			public function getCategory(): string {
				return 'test';
			}

			public function getLabel(): string {
				return 'Test Ability';
			}

			public function getDescription(): string {
				return 'Test ability description.';
			}

			public function getInputSchema(): array {
				return array( 'type' => 'object' );
			}

			public function getOutputSchema(): array {
				return array( 'type' => 'object' );
			}

			public function getRequiredCapability(): string {
				return 'read';
			}

			public function doExecute( array $input ): array {
				return array();
			}
		};

		$this->assertSame( 'read', $ability->getOperationType() );

		$annotations = $ability->getAnnotations();
		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
		$this->assertSame( $ability->getDescription(), $annotations['instructions'] );
	}

	/**
	 * Test to_registration_array builds full payload.
	 *
	 * @return void
	 */
	public function test_to_registration_array_builds_payload(): void {
		$ability = new class() extends AbstractAbility {
			public function getName(): string {
				return 'fa-wpmcp/test-registration';
			}

			public function getCategory(): string {
				return 'test';
			}

			public function getLabel(): string {
				return 'Test Registration';
			}

			public function getDescription(): string {
				return 'Registration description.';
			}

			public function getInputSchema(): array {
				return array(
					'type'       => 'object',
					'properties' => array(),
				);
			}

			public function getOutputSchema(): array {
				return array(
					'type'       => 'object',
					'properties' => array(),
				);
			}

			public function getRequiredCapability(): string {
				return 'read';
			}

			public function doExecute( array $input ): array {
				return array();
			}
		};

		$payload = $ability->toRegistrationArray();

		$this->assertSame( 'fa-wpmcp/test-registration', $payload['name'] );
		$this->assertSame( 'test', $payload['category'] );
		$this->assertSame( 'Test Registration', $payload['label'] );
		$this->assertSame( 'Registration description.', $payload['description'] );
		$this->assertSame( 'read', $payload['operationType'] );
		$this->assertArrayHasKey( 'annotations', $payload );
	}
}
