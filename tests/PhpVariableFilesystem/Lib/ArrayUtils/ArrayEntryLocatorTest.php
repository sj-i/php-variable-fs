<?php

/**
 * This file is part of the sj-i/php-variable-fs package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpVariableFilesystem\Lib\ArrayUtils;

use PHPUnit\Framework\TestCase;

class ArrayEntryLocatorTest extends TestCase
{
    public function testGetRecursive()
    {
        $array_entry_locator = new ArrayEntryLocator();

        $test_array1 = [1];
        $entry =& $array_entry_locator->getRecursive($test_array1, ['0']);
        $entry = 2;
        $this->assertSame([2], $test_array1);
        unset($entry);

        $test_array1 = [1, [1 => 2], ['a' => 3, 'b' => ['c' => 4]]];
        $entry =& $array_entry_locator->getRecursive($test_array1, ['0']);
        $entry = 5;
        unset($entry);
        $entry =& $array_entry_locator->getRecursive($test_array1, ['1', '1']);
        $entry = 6;
        unset($entry);
        $entry =& $array_entry_locator->getRecursive($test_array1, ['2', 'a']);
        $entry = 7;
        unset($entry);
        $entry =& $array_entry_locator->getRecursive($test_array1, ['2', 'b', 'c']);
        $entry = 8;
        unset($entry);
        $this->assertSame([5, [1 => 6], ['a' => 7, 'b' => ['c' => 8]]], $test_array1);
    }
}
