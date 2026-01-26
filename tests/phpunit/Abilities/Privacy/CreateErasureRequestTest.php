<?php

/**
 * Tests for CreateErasureRequest ability.
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Privacy;

use FAWpmcp\Abilities\AbstractAbility;
use FAWpmcp\Abilities\Privacy\CreateErasureRequest;
use FAWpmcp\Tests\TestCase\AbilityTestTrait;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Test CreateErasureRequest ability functionality.
 *
 * Tests cover:
 * - Creating erasure requests with valid email
 * - Metadata handling
 * - Input validation
 * - Error handling
 *
 * @package FAWpmcp\Tests\Abilities\Privacy
 */
class CreateErasureRequestTest extends BrainMonkeyTestCase
{
    use AbilityTestTrait;

    /**
     * Get an instance of the ability being tested.
     *
     * @return AbstractAbility
     */
    protected function getAbilityInstance(): AbstractAbility
    {
        return new CreateErasureRequest();
    }

    /**
     * Get expected metadata for the ability.
     *
     * @return array{
     *     name: string,
     *     category: string,
     *     label: string,
     *     description_contains: string,
     *     operation_type: string,
     *     required_capability: string
     * }
     */
    protected function getExpectedMetadata(): array
    {
        return array(
            'name'                 => 'fa-wpmcp/create-erasure-request',
            'category'             => 'privacy',
            'label'                => 'Create Erasure Request',
            'description_contains' => 'erasure request',
            'operation_type'       => 'write',
            'required_capability'  => 'manage_options',
        );
    }

    /**
     * Test creating erasure request successfully.
     *
     * @return void
     */
    public function testExecuteCreatesErasureRequest(): void
    {
        $ability = $this->getAbilityInstance();
        $input   = array(
            'email' => 'user@example.com',
        );

        // Mock wp_create_user_request to return success.
        Functions\expect('wp_create_user_request')
            ->once()
            ->with('user@example.com', 'remove_personal_data', Mockery::type('array'))
            ->andReturn(123);

        // Mock get_post to return request post.
        $mock_post              = Mockery::mock('\WP_Post');
        $mock_post->ID          = 123;
        $mock_post->post_status = 'request-pending';

        Functions\expect('get_post')
            ->once()
            ->with(123)
            ->andReturn($mock_post);

        Functions\expect('get_post_meta')
            ->once()
            ->with(123, '_wp_user_request_confirmed_timestamp', true)
            ->andReturn('');

        $result = $ability->doExecute($input);

        $this->assertTrue($result['success']);
        $this->assertEquals(123, $result['request_id']);
        $this->assertEquals('request-pending', $result['status']);
        $this->assertEquals('user@example.com', $result['email']);
    }

    /**
     * Test creating erasure request with description.
     *
     * @return void
     */
    public function testExecuteCreatesErasureRequestWithDescription(): void
    {
        $ability = $this->getAbilityInstance();
        $input   = array(
            'email'       => 'user@example.com',
            'description' => 'User requested data erasure',
        );

        // Mock wp_create_user_request to return success.
        Functions\expect('wp_create_user_request')
            ->once()
            ->with(
                'user@example.com',
                'remove_personal_data',
                Mockery::on(
                    function ($data) {
                        return isset($data['description']) && 'User requested data erasure' === $data['description'];
                    }
                )
            )
            ->andReturn(124);

        // Mock get_post to return request post.
        $mock_post              = Mockery::mock('\WP_Post');
        $mock_post->ID          = 124;
        $mock_post->post_status = 'request-pending';

        Functions\expect('get_post')
            ->once()
            ->with(124)
            ->andReturn($mock_post);

        Functions\expect('get_post_meta')
            ->once()
            ->with(124, '_wp_user_request_confirmed_timestamp', true)
            ->andReturn('');

        $result = $ability->doExecute($input);

        $this->assertTrue($result['success']);
        $this->assertEquals(124, $result['request_id']);
    }

    /**
     * Test creating erasure request handles WP_Error.
     *
     * @return void
     */
    public function testExecuteHandlesWpError(): void
    {
        $ability = $this->getAbilityInstance();
        $input   = array(
            'email' => 'invalid@example.com',
        );

        // Mock wp_create_user_request to return WP_Error.
        $error = Mockery::mock('\WP_Error');
        $error->shouldReceive('get_error_message')
            ->andReturn('Invalid email address');

        Functions\expect('wp_create_user_request')
            ->once()
            ->andReturn($error);

        Functions\expect('is_wp_error')
            ->once()
            ->with($error)
            ->andReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create erasure request: Invalid email address');

        $ability->doExecute($input);
    }
}
