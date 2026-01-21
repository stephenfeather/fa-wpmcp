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
			public function get_name(): string {
				return 'fa-wpmcp/test-ability';
			}

			public function get_category(): string {
				return 'test';
			}

			public function get_label(): string {
				return 'Test Ability';
			}

			public function get_description(): string {
				return 'Test ability description.';
			}

			public function get_input_schema(): array {
				return array( 'type' => 'object' );
			}

			public function get_output_schema(): array {
				return array( 'type' => 'object' );
			}

			public function get_required_capability(): string {
				return 'read';
			}

			public function do_execute( array $input ): array {
				return array();
			}
		};

		$this->assertSame( 'read', $ability->get_operation_type() );

		$annotations = $ability->get_annotations();
		$this->assertTrue( $annotations['readonly'] );
		$this->assertFalse( $annotations['destructive'] );
		$this->assertTrue( $annotations['idempotent'] );
		$this->assertSame( $ability->get_description(), $annotations['instructions'] );
	}

	/**
	 * Test to_registration_array builds full payload.
	 *
	 * @return void
	 */
	public function test_to_registration_array_builds_payload(): void {
		$ability = new class() extends AbstractAbility {
			public function get_name(): string {
				return 'fa-wpmcp/test-registration';
			}

			public function get_category(): string {
				return 'test';
			}

			public function get_label(): string {
				return 'Test Registration';
			}

			public function get_description(): string {
				return 'Registration description.';
			}

			public function get_input_schema(): array {
				return array( 'type' => 'object', 'properties' => array() );
			}

			public function get_output_schema(): array {
				return array( 'type' => 'object', 'properties' => array() );
			}

			public function get_required_capability(): string {
				return 'read';
			}

			public function do_execute( array $input ): array {
				return array();
			}
		};

		$payload = $ability->to_registration_array();

		$this->assertSame( 'fa-wpmcp/test-registration', $payload['name'] );
		$this->assertSame( 'test', $payload['category'] );
		$this->assertSame( 'Test Registration', $payload['label'] );
		$this->assertSame( 'Registration description.', $payload['description'] );
		$this->assertSame( 'read', $payload['operationType'] );
		$this->assertArrayHasKey( 'annotations', $payload );
	}
}
