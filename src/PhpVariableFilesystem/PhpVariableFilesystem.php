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

namespace PhpVariableFilesystem;

use FFI;
use FFI\CData;
use Fuse\FilesystemDefaultImplementationTrait;
use Fuse\FilesystemInterface;
use Fuse\Fuse;
use PhpVariableFilesystem\Lib\ArrayUtils\ArrayEntryLocator;

final class PhpVariableFilesystem implements FilesystemInterface
{
    use FilesystemDefaultImplementationTrait;

    private const ENOENT = 2;
    private const ENOTDIR = 20;
    private const S_IFDIR = 0040000;
    private const S_IFREG = 0100000;

    private ArrayEntryLocator $array_entry_locator;

    private array $array;

    public function __construct(array $array)
    {
        $this->array_entry_locator = new ArrayEntryLocator();
        $this->array = $array;
    }

    public function getArray(): array
    {
        return $this->array;
    }

    public function getattr(string $path, CData $stat): int
    {
        $element = $this->getEntry($path);
        if (is_null($element)) {
            return -self::ENOENT;
        }

        $this->initializeStructStat($stat);

        $stat->st_uid = getmyuid();
        $stat->st_gid = getmygid();

        if (is_array($element)) {
            $stat->st_mode = self::S_IFDIR | 0777;
            $stat->st_nlink = 2; // fixme: there may be subdirectories
            return 0;
        }
        $stat->st_mode = self::S_IFREG | 0777;
        $stat->st_nlink = 1;
        $stat->st_size = strlen((string)$element);
        return 0;
    }

    /**
     * @param CData|callable $filler
     */
    public function readdir(string $path, CData $buf, CData $filler, int $offset, CData $fi): int
    {
        $filler($buf, '.', null, 0);
        $filler($buf, '..', null, 0);
        $entry = $this->getEntry($path);
        if (!is_array($entry)) {
            return self::ENOTDIR;
        }
        foreach ($entry as $key => $value) {
            $filler($buf, (string)$key, null, 0);
        }

        return 0;
    }

    public function open(string $path, CData $fi): int
    {
        $entry = $this->getEntry($path);
        if (!is_scalar($entry)) {
            return -self::ENOENT;
        }
        return 0;
    }

    public function read(string $path, CData $buf, int $size, int $offset, CData $fi): int
    {
        $entry = $this->getEntry($path);

        $len = strlen((string)$entry);

        if ($offset + $size > $len) {
            $size = ($len - $offset);
        }

        $content = substr((string)$entry, $offset, $size);
        FFI::memcpy($buf, $content, $size);

        return $size;
    }

    public function write(string $path, string $buffer, int $size, int $offset, CData $fuse_file_info): int
    {
        $entry = &$this->getEntry($path);
        $entry = substr_replace($entry, $buffer, $offset, $size);

        return $size;
    }

    public function create(string $path, int $mode, CData $fuse_file_info): int
    {
        $entry = &$this->getParentEntry($path);
        if (is_array($entry)) {
            $entry[basename($path)] = '';
            return 0;
        } else {
            return self::ENOENT;
        }
    }

    public function unlink(string $path): int
    {
        $this->unsetEntry($path);
        return 0;
    }

    public function rename(string $from, string $to): int
    {
        $parent_entry = &$this->getParentEntry($to);
        if (is_array($parent_entry)) {
            $parent_entry[basename($to)] = $this->getEntry($from);
            $this->unsetEntry($from);
            return 0;
        } else {
            return self::ENOENT;
        }
    }

    /**
     * @return array|scalar|null
     */
    private function &getEntry(string $path)
    {
        return $this->array_entry_locator->getEntry($path, $this->array);
    }

    private function &getParentEntry(string $path)
    {
        return $this->array_entry_locator->getParentEntry($path, $this->array);
    }

    private function unsetEntry(string $path): void
    {
        $this->array_entry_locator->unsetEntry($path, $this->array);
    }

    private function initializeStructStat(CData $struct_stat): void
    {
        $typename = 'struct stat';
        $type = Fuse::getInstance()->ffi->type(
            $typename
        );
        $size = FFI::sizeof(
            $type
        );

        FFI::memset($struct_stat, 0, $size);
    }
}
