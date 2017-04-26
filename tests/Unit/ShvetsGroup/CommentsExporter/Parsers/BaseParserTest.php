<?php namespace Tests\Unit\ShvetsGroup\CommentsExporter\Parsers;

use Tests\TestCase;
use ShvetsGroup\CommentsExporter\Parsers\Parser;
use ShvetsGroup\CommentsExporter\Parsers\BaseParser;

class BaseParserTest extends TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new BaseParser();
    }

    public function testParsingComplex()
    {
        $content = <<<SOURCE
/**
 * Regular doc comment.
 */
class Test {
    /**
     * Multiple 
     * lines 
     * needs fixing.
     *
     * This should be on new line. Below should be no spacing.
     *
     */
    function test() {
        /*Asterisk comments.*/
        /*Should stay separate.*/
        a = 1;
        // Single line comments are ok.
        b = 2;
        // Multiple single lines comments 
        // should stick together.
        //
        // And respect new lines.
        // Yeap, right.
        c = 3;
        // Although this one.
        
        // 
        // And this one should remain separate.
        //
        /*
        *Malformed comments.
        *
        *   Should fix themselves. 
        */
    }
    
    /**
     * @class
     * Don't fix multiple lines here.
     * - And here.
     * * And here.
     * @param \$id Multiline parameter
     * comment.
     * @param \$type
     */
    private field1;
    
    /**
     * How to handle
     * this one?
     *
     *         ---------
     *         |       |
     *         ---------
     *
     * How about ellipses...
     * ...like that?
     */
    private field2;
    
    /**
     * How about code?
     * @code
     * array(
     *   STATE1 => CONDITIONS_ARRAY1,
     *   STATE2 => CONDITIONS_ARRAY2,
     *   ...
     * )
     * @endcode
     *
     * ```php
     * <?php
     *     echo('hi');
     * ?>
     * ```
     */
}
SOURCE;
        $tokenized = <<<SOURCE
// ###0###
class Test {
    // ###1###
    function test() {
        // ###2###
        // ###3###
        a = 1;
        // ###4###
        b = 2;
        // ###5###
        c = 3;
        // ###6###
        
        // ###7###
        // ###8###
    }
    
    // ###9###
    private field1;
    
    // ###10###
    private field2;
    
    // ###11###
}
SOURCE;

        $result = $this->parser->parse($content, ['fix-word-wrap' => true]);
        $this->assertEquals($tokenized, $result['tokenized']);
        $expected = [
            "Regular doc comment.",
            "Multiple lines needs fixing.\n\nThis should be on new line. Below should be no spacing.",
            "Asterisk comments.",
            "Should stay separate.",
            "Single line comments are ok.",
            "Multiple single lines comments should stick together.\n\nAnd respect new lines. Yeap, right.",
            "Although this one.",
            "And this one should remain separate.",
            "Malformed comments.\n\nShould fix themselves.",
            "@class\nDon't fix multiple lines here.\n- And here.\n* And here.\n@param \$id Multiline parameter comment.\n@param \$type",
            "How to handle this one?\n\n        ---------\n        |       |\n        ---------\n\nHow about ellipses...\n...like that?",
            "How about code?\n@code\narray(\n  STATE1 => CONDITIONS_ARRAY1,\n  STATE2 => CONDITIONS_ARRAY2,\n  ...\n)\n@endcode\n\n```php\n<?php\n    echo('hi');\n?>\n```"
        ];
        $this->assertEquals(count($expected), count($result['comments']));
        for ($i = 0; $i < count($result['comments']); $i++) {
            $this->assertEquals($expected[$i], $result['comments'][$i]->getComment());
        }
    }
}
