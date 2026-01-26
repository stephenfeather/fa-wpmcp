<?php
/**
 * Tests for PrivacyRedactor HTTP utility.
 *
 * TDD RED Phase: These tests define the expected behavior of PrivacyRedactor
 * before implementation. All tests should fail with "Class not found" initially.
 *
 * @package FAWpmcp\Tests\Http
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Http;

use FAWpmcp\Http\PrivacyRedactor;
use PHPUnit\Framework\TestCase;

/**
 * Test PrivacyRedactor pure functions.
 *
 * Tests cover:
 * - Basic redaction of sensitive fields (password, token, etc.)
 * - Nested data structure handling
 * - Pure function behavior (immutability)
 * - Case-insensitive field matching
 * - Edge cases (empty arrays, null values)
 *
 * Sensitive fields to redact:
 * - password, user_pass
 * - token, access_token, refresh_token
 * - api_key, apikey
 * - secret
 */
class PrivacyRedactorTest extends TestCase {

	// =========================================================================
	// Basic Redaction Tests
	// =========================================================================

	/**
	 * Test redacts password fields.
	 *
	 * @return void
	 */
	public function test_redacts_password_fields(): void {
		$data = array(
			'username' => 'admin',
			'password' => 'secret123',
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( 'admin', $redacted['username'] );
		$this->assertEquals( '[REDACTED]', $redacted['password'] );
	}

	/**
	 * Test redacts token fields.
	 *
	 * Covers: token, api_key, secret, access_token, refresh_token.
	 *
	 * @return void
	 */
	public function test_redacts_token_fields(): void {
		$data = array(
			'token'         => 'bearer-token-12345',
			'api_key'       => 'sk-live-12345',
			'secret'        => 'my-secret-value',
			'access_token'  => 'access-12345',
			'refresh_token' => 'refresh-12345',
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( '[REDACTED]', $redacted['token'] );
		$this->assertEquals( '[REDACTED]', $redacted['api_key'] );
		$this->assertEquals( '[REDACTED]', $redacted['secret'] );
		$this->assertEquals( '[REDACTED]', $redacted['access_token'] );
		$this->assertEquals( '[REDACTED]', $redacted['refresh_token'] );
	}

	/**
	 * Test redacts user_pass field (WordPress convention).
	 *
	 * @return void
	 */
	public function test_redacts_user_pass_field(): void {
		$data = array(
			'user_login' => 'admin',
			'user_pass'  => 'hashed_password_here',
			'user_email' => 'admin@example.com',
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( 'admin', $redacted['user_login'] );
		$this->assertEquals( '[REDACTED]', $redacted['user_pass'] );
		// Email is now redacted for enhanced PII protection.
		$this->assertEquals( '[REDACTED]', $redacted['user_email'] );
	}

	/**
	 * Test redacts apikey field (no underscore variant).
	 *
	 * @return void
	 */
	public function test_redacts_apikey_field(): void {
		$data = array(
			'apikey' => 'my-api-key',
			'name'   => 'Test API',
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( '[REDACTED]', $redacted['apikey'] );
		$this->assertEquals( 'Test API', $redacted['name'] );
	}

	/**
	 * Test preserves non-sensitive fields.
	 *
	 * @return void
	 */
	public function test_preserves_non_sensitive_fields(): void {
		$data = array(
			'id'          => 42,
			'username'    => 'testuser',
			'first_name'  => 'John',
			'last_name'   => 'Doe',
			'post_title'  => 'My Post',
			'post_status' => 'publish',
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( $data, $redacted );
	}

	// =========================================================================
	// Nested Data Tests
	// =========================================================================

	/**
	 * Test redacts nested sensitive fields.
	 *
	 * @return void
	 */
	public function test_redacts_nested_sensitive_fields(): void {
		$data = array(
			'user' => array(
				'name'    => 'John',
				'api_key' => 'sk-12345',
			),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( 'John', $redacted['user']['name'] );
		$this->assertEquals( '[REDACTED]', $redacted['user']['api_key'] );
	}

	/**
	 * Test redacts deeply nested structures (3+ levels).
	 *
	 * @return void
	 */
	public function test_redacts_deeply_nested_structures(): void {
		$data = array(
			'level1' => array(
				'level2' => array(
					'level3' => array(
						'password' => 'deep-secret',
						'name'     => 'Still visible',
					),
				),
			),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( '[REDACTED]', $redacted['level1']['level2']['level3']['password'] );
		$this->assertEquals( 'Still visible', $redacted['level1']['level2']['level3']['name'] );
	}

	/**
	 * Test redacts multiple nested objects.
	 *
	 * @return void
	 */
	public function test_redacts_multiple_nested_objects(): void {
		$data = array(
			'primary_user'   => array(
				'name'     => 'Alice',
				'password' => 'alice-pass',
			),
			'secondary_user' => array(
				'name'     => 'Bob',
				'password' => 'bob-pass',
			),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( 'Alice', $redacted['primary_user']['name'] );
		$this->assertEquals( '[REDACTED]', $redacted['primary_user']['password'] );
		$this->assertEquals( 'Bob', $redacted['secondary_user']['name'] );
		$this->assertEquals( '[REDACTED]', $redacted['secondary_user']['password'] );
	}

	/**
	 * Test redacts arrays of objects with sensitive fields.
	 *
	 * @return void
	 */
	public function test_redacts_array_of_objects(): void {
		$data = array(
			'users' => array(
				array(
					'id'       => 1,
					'password' => 'pass1',
				),
				array(
					'id'       => 2,
					'password' => 'pass2',
				),
				array(
					'id'       => 3,
					'password' => 'pass3',
				),
			),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( 1, $redacted['users'][0]['id'] );
		$this->assertEquals( '[REDACTED]', $redacted['users'][0]['password'] );
		$this->assertEquals( 2, $redacted['users'][1]['id'] );
		$this->assertEquals( '[REDACTED]', $redacted['users'][1]['password'] );
		$this->assertEquals( 3, $redacted['users'][2]['id'] );
		$this->assertEquals( '[REDACTED]', $redacted['users'][2]['password'] );
	}

	// =========================================================================
	// Pure Function Tests
	// =========================================================================

	/**
	 * Test is a pure function (original array unchanged).
	 *
	 * @return void
	 */
	public function test_is_pure_function(): void {
		$original = array( 'password' => 'secret' );
		$redacted = PrivacyRedactor::redact( $original );

		// Original unchanged.
		$this->assertEquals( 'secret', $original['password'] );
		// New array with redaction.
		$this->assertEquals( '[REDACTED]', $redacted['password'] );
	}

	/**
	 * Test preserves original array structure.
	 *
	 * @return void
	 */
	public function test_preserves_original_array_structure(): void {
		$original = array(
			'user' => array(
				'name'     => 'John',
				'password' => 'secret',
				'settings' => array(
					'theme' => 'dark',
					'token' => 'abc123',
				),
			),
		);

		$original_copy = $original;
		$redacted      = PrivacyRedactor::redact( $original );

		// Original should be exactly as it was.
		$this->assertEquals( $original_copy, $original );
		$this->assertEquals( 'secret', $original['user']['password'] );
		$this->assertEquals( 'abc123', $original['user']['settings']['token'] );
	}

	/**
	 * Test returns new array (not the same reference).
	 *
	 * @return void
	 */
	public function test_returns_new_array(): void {
		$original = array( 'name' => 'test' );
		$redacted = PrivacyRedactor::redact( $original );

		// Even with no redactions, should be a copy.
		$this->assertEquals( $original, $redacted );

		// Modify redacted to prove it's a copy.
		$redacted['name'] = 'modified';
		$this->assertEquals( 'test', $original['name'] );
	}

	// =========================================================================
	// Edge Case Tests
	// =========================================================================

	/**
	 * Test handles empty array.
	 *
	 * @return void
	 */
	public function test_handles_empty_array(): void {
		$redacted = PrivacyRedactor::redact( array() );

		$this->assertEquals( array(), $redacted );
		$this->assertIsArray( $redacted );
	}

	/**
	 * Test handles null values in sensitive fields.
	 *
	 * @return void
	 */
	public function test_handles_null_values(): void {
		$data = array(
			'username' => 'admin',
			'password' => null,
		);

		$redacted = PrivacyRedactor::redact( $data );

		// Null should be redacted even if null.
		$this->assertEquals( '[REDACTED]', $redacted['password'] );
		$this->assertEquals( 'admin', $redacted['username'] );
	}

	/**
	 * Test handles empty string values in sensitive fields.
	 *
	 * @return void
	 */
	public function test_handles_empty_string_values(): void {
		$data = array(
			'password' => '',
			'token'    => '',
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( '[REDACTED]', $redacted['password'] );
		$this->assertEquals( '[REDACTED]', $redacted['token'] );
	}

	/**
	 * Test handles numeric values in sensitive fields.
	 *
	 * @return void
	 */
	public function test_handles_numeric_values(): void {
		$data = array(
			'password' => 12345,
			'token'    => 67890,
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( '[REDACTED]', $redacted['password'] );
		$this->assertEquals( '[REDACTED]', $redacted['token'] );
	}

	// =========================================================================
	// Case Sensitivity Tests
	// =========================================================================

	/**
	 * Test redacts case-insensitive field names.
	 *
	 * @return void
	 */
	public function test_redacts_case_insensitive(): void {
		$data = array(
			'Password'       => 'secret1',
			'PASSWORD'       => 'secret2',
			'password'       => 'secret3',
			'PassWord'       => 'secret4',
			'API_KEY'        => 'key1',
			'Api_Key'        => 'key2',
			'api_key'        => 'key3',
			'TOKEN'          => 'token1',
			'Token'          => 'token2',
			'SECRET'         => 'secret-val',
			'access_TOKEN'   => 'access1',
			'ACCESS_TOKEN'   => 'access2',
			'refresh_TOKEN'  => 'refresh1',
			'REFRESH_TOKEN'  => 'refresh2',
		);

		$redacted = PrivacyRedactor::redact( $data );

		// All variations should be redacted.
		$this->assertEquals( '[REDACTED]', $redacted['Password'] );
		$this->assertEquals( '[REDACTED]', $redacted['PASSWORD'] );
		$this->assertEquals( '[REDACTED]', $redacted['password'] );
		$this->assertEquals( '[REDACTED]', $redacted['PassWord'] );
		$this->assertEquals( '[REDACTED]', $redacted['API_KEY'] );
		$this->assertEquals( '[REDACTED]', $redacted['Api_Key'] );
		$this->assertEquals( '[REDACTED]', $redacted['api_key'] );
		$this->assertEquals( '[REDACTED]', $redacted['TOKEN'] );
		$this->assertEquals( '[REDACTED]', $redacted['Token'] );
		$this->assertEquals( '[REDACTED]', $redacted['SECRET'] );
		$this->assertEquals( '[REDACTED]', $redacted['access_TOKEN'] );
		$this->assertEquals( '[REDACTED]', $redacted['ACCESS_TOKEN'] );
		$this->assertEquals( '[REDACTED]', $redacted['refresh_TOKEN'] );
		$this->assertEquals( '[REDACTED]', $redacted['REFRESH_TOKEN'] );
	}

	/**
	 * Test case-insensitive matching in nested structures.
	 *
	 * @return void
	 */
	public function test_case_insensitive_nested(): void {
		$data = array(
			'user' => array(
				'PASSWORD' => 'secret',
				'Name'     => 'John',
			),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( '[REDACTED]', $redacted['user']['PASSWORD'] );
		$this->assertEquals( 'John', $redacted['user']['Name'] );
	}

	// =========================================================================
	// Comprehensive Sensitive Field Tests
	// =========================================================================

	/**
	 * Test all defined sensitive fields are redacted.
	 *
	 * Tests all fields that should be redacted per the specification.
	 *
	 * @return void
	 */
	public function test_all_sensitive_fields_redacted(): void {
		$sensitive_fields = array(
			'password',
			'token',
			'api_key',
			'secret',
			'user_pass',
			'apikey',
			'access_token',
			'refresh_token',
		);

		foreach ( $sensitive_fields as $field ) {
			$data     = array( $field => 'sensitive-value' );
			$redacted = PrivacyRedactor::redact( $data );

			$this->assertEquals(
				'[REDACTED]',
				$redacted[ $field ],
				"Field '$field' should be redacted"
			);
		}
	}

	/**
	 * Test does not redact similar but non-sensitive field names.
	 *
	 * @return void
	 */
	public function test_does_not_redact_similar_field_names(): void {
		$data = array(
			'password_reset' => 'token-123',       // Contains 'password' but different field.
			'token_count'    => 5,                  // Contains 'token' but different field.
			'secret_code'    => 'abc',             // Contains 'secret' but different field.
			'api_key_id'     => 'key-123',         // Contains 'api_key' but different field.
		);

		$redacted = PrivacyRedactor::redact( $data );

		// These should NOT be redacted (exact field match only).
		$this->assertEquals( 'token-123', $redacted['password_reset'] );
		$this->assertEquals( 5, $redacted['token_count'] );
		$this->assertEquals( 'abc', $redacted['secret_code'] );
		$this->assertEquals( 'key-123', $redacted['api_key_id'] );
	}

	// =========================================================================
	// Data Type Preservation Tests
	// =========================================================================

	/**
	 * Test preserves array structure and non-sensitive data types.
	 *
	 * @return void
	 */
	public function test_preserves_data_types(): void {
		$data = array(
			'id'          => 42,
			'score'       => 3.14,
			'is_active'   => true,
			'is_admin'    => false,
			'description' => null,
			'tags'        => array( 'php', 'wordpress' ),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertIsInt( $redacted['id'] );
		$this->assertIsFloat( $redacted['score'] );
		$this->assertIsBool( $redacted['is_active'] );
		$this->assertIsBool( $redacted['is_admin'] );
		$this->assertNull( $redacted['description'] );
		$this->assertIsArray( $redacted['tags'] );
	}

	/**
	 * Test preserves sequential arrays (lists).
	 *
	 * @return void
	 */
	public function test_preserves_sequential_arrays(): void {
		$data = array(
			'items' => array( 'a', 'b', 'c' ),
			'ids'   => array( 1, 2, 3 ),
		);

		$redacted = PrivacyRedactor::redact( $data );

		$this->assertEquals( array( 'a', 'b', 'c' ), $redacted['items'] );
		$this->assertEquals( array( 1, 2, 3 ), $redacted['ids'] );
	}

	/**
	 * Test handles mixed array with sensitive and non-sensitive data.
	 *
	 * @return void
	 */
	public function test_handles_mixed_data(): void {
		$data = array(
			'request' => array(
				'method'  => 'POST',
				'url'     => 'https://api.example.com',
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer xyz',
				),
				'body'    => array(
					'username' => 'testuser',
					'password' => 'testpass',
					'data'     => array(
						'title'  => 'Test',
						'secret' => 'hidden',
					),
				),
			),
		);

		$redacted = PrivacyRedactor::redact( $data );

		// Non-sensitive preserved.
		$this->assertEquals( 'POST', $redacted['request']['method'] );
		$this->assertEquals( 'https://api.example.com', $redacted['request']['url'] );
		$this->assertEquals( 'application/json', $redacted['request']['headers']['Content-Type'] );
		$this->assertEquals( 'testuser', $redacted['request']['body']['username'] );
		$this->assertEquals( 'Test', $redacted['request']['body']['data']['title'] );

		// Sensitive redacted.
		$this->assertEquals( '[REDACTED]', $redacted['request']['body']['password'] );
		$this->assertEquals( '[REDACTED]', $redacted['request']['body']['data']['secret'] );
	}
}
