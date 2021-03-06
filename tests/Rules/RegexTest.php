<?php

namespace Kontrolio\Tests\Rules;

use Kontrolio\Rules\Core\Regex;

class RegexTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testShouldOnlyAcceptPatternAsString()
    {
        new Regex(234);
    }

    public function testMatching()
    {
        $rule = new Regex('/[a-z]{2}/');

        $this->assertTrue($rule->isValid('ab'));
        $this->assertFalse($rule->isValid(''));
        $this->assertFalse($rule->isValid(null));
        $this->assertFalse($rule->isValid('жз'));
    }
}
