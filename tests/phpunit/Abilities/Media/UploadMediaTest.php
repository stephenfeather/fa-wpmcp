<?php
/**
 * Tests for UploadMedia ability.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Media;

use FAWpmcp\Abilities\Media\UploadMedia;
use FAWpmcp\Exceptions\MediaUploadException;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test UploadMedia ability functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Media
 */
class UploadMediaTest extends TestCase {
	/**
	 * Set up Brain\Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test ability returns correct name.
	 *
	 * @return void
	 */
	public function testGetName(): void {
		$ability = new UploadMedia();
		$this->assertEquals( 'fa-wpmcp/upload-media', $ability->getName() );
	}

	/**
	 * Test ability returns correct category.
	 *
	 * @return void
	 */
	public function testGetCategory(): void {
		$ability = new UploadMedia();
		$this->assertEquals( 'media', $ability->getCategory() );
	}

	/**
	 * Test ability returns correct operation type.
	 *
	 * @return void
	 */
	public function testGetOperationType(): void {
		$ability = new UploadMedia();
		$this->assertEquals( 'write', $ability->getOperationType() );
	}

	/**
	 * Test ability returns correct required capability.
	 *
	 * @return void
	 */
	public function testGetRequiredCapability(): void {
		$ability = new UploadMedia();
		$this->assertEquals( 'upload_files', $ability->getRequiredCapability() );
	}

	/**
	 * Test execute throws exception when no file data or url provided.
	 *
	 * @return void
	 */
	public function testExecuteThrowsExceptionWhenNoFileDataOrUrl(): void {
		$ability = new UploadMedia();

		$this->expectException( MediaUploadException::class );
		$this->expectExceptionMessage( 'Either file_data or url must be provided' );

		$ability->doExecute( array( 'filename' => 'test.jpg' ) );
	}

	/**
	 * Test input schema has required fields.
	 *
	 * @return void
	 */
	public function testInputSchemaHasRequiredFields(): void {
		$ability = new UploadMedia();
		$schema  = $ability->getInputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'filename', $schema['properties'] );
		$this->assertArrayHasKey( 'file_data', $schema['properties'] );
		$this->assertArrayHasKey( 'url', $schema['properties'] );
		$this->assertArrayHasKey( 'required', $schema );
		$this->assertContains( 'filename', $schema['required'] );
	}

	/**
	 * Test output schema structure.
	 *
	 * @return void
	 */
	public function testOutputSchemaStructure(): void {
		$ability = new UploadMedia();
		$schema  = $ability->getOutputSchema();

		$this->assertIsArray( $schema );
		$this->assertArrayHasKey( 'properties', $schema );
		$this->assertArrayHasKey( 'media_id', $schema['properties'] );
		$this->assertArrayHasKey( 'url', $schema['properties'] );
		$this->assertArrayHasKey( 'mime_type', $schema['properties'] );
	}
}
