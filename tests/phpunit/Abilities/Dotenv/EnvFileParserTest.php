<?php

/**
 * Tests for EnvFileParser.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */

declare(strict_types=1);

namespace FAWpmcp\Tests\Abilities\Dotenv;

use FAWpmcp\Abilities\Dotenv\EnvFileParser;
use FAWpmcp\Tests\TestCase\BrainMonkeyTestCase;

/**
 * Test EnvFileParser functionality.
 *
 * @package FAWpmcp\Tests\Abilities\Dotenv
 */
class EnvFileParserTest extends BrainMonkeyTestCase
{
    /**
     * The parser instance under test.
     *
     * @var EnvFileParser
     */
    private EnvFileParser $parser;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new EnvFileParser();
    }

    /**
     * Test parse handles simple key-value pairs.
     *
     * @return void
     */
    public function testParseHandlesSimpleKeyValuePairs(): void
    {
        $content = "KEY=value\nANOTHER=test";
        $result = $this->parser->parse($content);

        $this->assertEquals('value', $result['KEY']);
        $this->assertEquals('test', $result['ANOTHER']);
    }

    /**
     * Test parse handles double-quoted values.
     *
     * @return void
     */
    public function testParseHandlesDoubleQuotedValues(): void
    {
        $content = 'KEY="value with spaces"';
        $result = $this->parser->parse($content);

        $this->assertEquals('value with spaces', $result['KEY']);
    }

    /**
     * Test parse handles single-quoted values.
     *
     * @return void
     */
    public function testParseHandlesSingleQuotedValues(): void
    {
        $content = "KEY='value with spaces'";
        $result = $this->parser->parse($content);

        $this->assertEquals('value with spaces', $result['KEY']);
    }

    /**
     * Test parse handles export prefix.
     *
     * @return void
     */
    public function testParseHandlesExportPrefix(): void
    {
        $content = "export KEY=value\nexport ANOTHER=test";
        $result = $this->parser->parse($content);

        $this->assertEquals('value', $result['KEY']);
        $this->assertEquals('test', $result['ANOTHER']);
    }

    /**
     * Test parse skips comments.
     *
     * @return void
     */
    public function testParseSkipsComments(): void
    {
        $content = "# This is a comment\nKEY=value\n# Another comment";
        $result = $this->parser->parse($content);

        $this->assertCount(1, $result);
        $this->assertEquals('value', $result['KEY']);
    }

    /**
     * Test parse skips blank lines.
     *
     * @return void
     */
    public function testParseSkipsBlankLines(): void
    {
        $content = "KEY=value\n\n\nANOTHER=test";
        $result = $this->parser->parse($content);

        $this->assertCount(2, $result);
    }

    /**
     * Test parse handles inline comments.
     *
     * @return void
     */
    public function testParseHandlesInlineComments(): void
    {
        $content = "KEY=value # inline comment";
        $result = $this->parser->parse($content);

        $this->assertEquals('value', $result['KEY']);
    }

    /**
     * Test parse handles escaped quotes in double-quoted values.
     *
     * @return void
     */
    public function testParseHandlesEscapedQuotesInDoubleQuotedValues(): void
    {
        $content = 'KEY="value with \"escaped\" quotes"';
        $result = $this->parser->parse($content);

        $this->assertEquals('value with "escaped" quotes', $result['KEY']);
    }

    /**
     * Test parse handles empty values.
     *
     * @return void
     */
    public function testParseHandlesEmptyValues(): void
    {
        $content = "KEY=\nANOTHER=test";
        $result = $this->parser->parse($content);

        $this->assertEquals('', $result['KEY']);
        $this->assertEquals('test', $result['ANOTHER']);
    }

    /**
     * Test parse handles values with equals sign.
     *
     * @return void
     */
    public function testParseHandlesValuesWithEqualsSign(): void
    {
        $content = "KEY=value=with=equals";
        $result = $this->parser->parse($content);

        $this->assertEquals('value=with=equals', $result['KEY']);
    }

    /**
     * Test setValue creates new variable.
     *
     * @return void
     */
    public function testSetValueCreatesNewVariable(): void
    {
        $content = "EXISTING=value\n";
        $result = $this->parser->setValue($content, 'NEW_VAR', 'new_value');

        $this->assertEquals('created', $result['action']);
        $this->assertStringContainsString('NEW_VAR=new_value', $result['content']);
    }

    /**
     * Test setValue updates existing variable.
     *
     * @return void
     */
    public function testSetValueUpdatesExistingVariable(): void
    {
        $content = "KEY=old_value\n";
        $result = $this->parser->setValue($content, 'KEY', 'new_value');

        $this->assertEquals('updated', $result['action']);
        $this->assertStringContainsString('KEY=new_value', $result['content']);
        $this->assertStringNotContainsString('old_value', $result['content']);
    }

    /**
     * Test setValue preserves export prefix.
     *
     * @return void
     */
    public function testSetValuePreservesExportPrefix(): void
    {
        $content = "export KEY=old_value\n";
        $result = $this->parser->setValue($content, 'KEY', 'new_value');

        $this->assertEquals('updated', $result['action']);
        $this->assertStringContainsString('export KEY=new_value', $result['content']);
    }

    /**
     * Test setValue quotes value when requested.
     *
     * @return void
     */
    public function testSetValueQuotesValueWhenRequested(): void
    {
        $content = "";
        $result = $this->parser->setValue($content, 'KEY', 'value with spaces', true);

        $this->assertStringContainsString('KEY="value with spaces"', $result['content']);
    }

    /**
     * Test deleteValue removes variable.
     *
     * @return void
     */
    public function testDeleteValueRemovesVariable(): void
    {
        $content = "KEY=value\nANOTHER=test\n";
        $result = $this->parser->deleteValue($content, 'KEY');

        $this->assertTrue($result['deleted']);
        $this->assertStringNotContainsString('KEY=value', $result['content']);
        $this->assertStringContainsString('ANOTHER=test', $result['content']);
    }

    /**
     * Test deleteValue preserves comments.
     *
     * @return void
     */
    public function testDeleteValuePreservesComments(): void
    {
        $content = "# Comment\nKEY=value\n# Another comment\n";
        $result = $this->parser->deleteValue($content, 'KEY');

        $this->assertTrue($result['deleted']);
        $this->assertStringContainsString('# Comment', $result['content']);
        $this->assertStringContainsString('# Another comment', $result['content']);
    }

    /**
     * Test deleteValue returns false when variable not found.
     *
     * @return void
     */
    public function testDeleteValueReturnsFalseWhenVariableNotFound(): void
    {
        $content = "KEY=value\n";
        $result = $this->parser->deleteValue($content, 'NONEXISTENT');

        $this->assertFalse($result['deleted']);
        $this->assertStringContainsString('KEY=value', $result['content']);
    }

    /**
     * Test deleteValue handles export prefix.
     *
     * @return void
     */
    public function testDeleteValueHandlesExportPrefix(): void
    {
        $content = "export KEY=value\n";
        $result = $this->parser->deleteValue($content, 'KEY');

        $this->assertTrue($result['deleted']);
        $this->assertStringNotContainsString('KEY', $result['content']);
    }

    /**
     * Test hasKey returns true for existing key.
     *
     * @return void
     */
    public function testHasKeyReturnsTrueForExistingKey(): void
    {
        $content = "KEY=value\n";
        $this->assertTrue($this->parser->hasKey($content, 'KEY'));
    }

    /**
     * Test hasKey returns false for non-existing key.
     *
     * @return void
     */
    public function testHasKeyReturnsFalseForNonExistingKey(): void
    {
        $content = "KEY=value\n";
        $this->assertFalse($this->parser->hasKey($content, 'NONEXISTENT'));
    }

    /**
     * Test getValue returns value for existing key.
     *
     * @return void
     */
    public function testGetValueReturnsValueForExistingKey(): void
    {
        $content = "KEY=myvalue\n";
        $this->assertEquals('myvalue', $this->parser->getValue($content, 'KEY'));
    }

    /**
     * Test getValue returns null for non-existing key.
     *
     * @return void
     */
    public function testGetValueReturnsNullForNonExistingKey(): void
    {
        $content = "KEY=value\n";
        $this->assertNull($this->parser->getValue($content, 'NONEXISTENT'));
    }

    /**
     * Test parse handles Windows line endings.
     *
     * @return void
     */
    public function testParseHandlesWindowsLineEndings(): void
    {
        $content = "KEY=value\r\nANOTHER=test\r\n";
        $result = $this->parser->parse($content);

        $this->assertEquals('value', $result['KEY']);
        $this->assertEquals('test', $result['ANOTHER']);
    }

    /**
     * Test setValue adds newline to content without trailing newline.
     *
     * @return void
     */
    public function testSetValueAddsNewlineToContentWithoutTrailingNewline(): void
    {
        $content = "KEY=value";
        $result = $this->parser->setValue($content, 'NEW', 'test');

        $this->assertStringContainsString("KEY=value\nNEW=test", $result['content']);
    }
}
