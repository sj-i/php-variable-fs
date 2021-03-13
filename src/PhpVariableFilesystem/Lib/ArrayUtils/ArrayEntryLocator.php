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

/**
 * @psalm-internal PhpVariableFilesystem
 */
final class ArrayEntryLocator
{
    /**
     * @psalm-type Entry=array|scalar|null
     * @param string[] $offsets
     * @param null|callable(array,string):Entry $operation
     * @return Entry
     */
    public function &getRecursive(array &$array, array $offsets, ?callable $operation = null)
    {
        $null = null;

        $count = count($offsets);

        if ($count === 0) {
            return $null;
        }
        if ($count === 1) {
            if (isset($array[$offsets[0]])) {
                if (!is_null($operation)) {
                    return $operation($array, $offsets[0]);
                } else {
                    /** @var array|scalar */
                    return $array[$offsets[0]];
                }
            } else {
                return $null;
            }
        }

        $offset = array_shift($offsets);
        if (is_array($array[$offset])) {
            return $this->getRecursive($array[$offset], $offsets);
        } else {
            return $null;
        }
    }

    /**
     * @return array|scalar|null
     */
    public function &getEntry(string $path, array &$array)
    {
        if ($path === '/') {
            return $array;
        }
        $splitted = explode('/', $path);
        array_shift($splitted);
        return $this->getRecursive($array, $splitted);
    }

    /**
     * @return null|array
     */
    public function &getParentEntry(string $path, array &$array)
    {
        $splitted = explode('/', $path);
        array_shift($splitted);
        array_pop($splitted);
        if (count($splitted) === 0) {
            return $array;
        }
        $result = $this->getRecursive($array, $splitted);
        if (!is_null($result) and !is_array($result)) {
            $null = null;
            return $null;
        }
        return $result;
    }

    public function &unsetEntry(string $path, array &$array): void
    {
        $splitted = explode('/', $path);
        array_shift($splitted);
        $this->getRecursive($array, $splitted, function &(array &$array, string $index) {
            $null = null;
            unset($array[$index]);
            return $null;
        });
    }
}
