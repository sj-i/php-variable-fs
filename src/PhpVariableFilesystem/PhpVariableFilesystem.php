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

use Fuse\FilesystemDefaultImplementationTrait;
use Fuse\FilesystemInterface;
use Fuse\Libc\Errno\Errno;
use Fuse\Libc\Fuse\FuseFileInfo;
use Fuse\Libc\Fuse\FuseFillDir;
use Fuse\Libc\Fuse\FuseReadDirBuffer;
use Fuse\Libc\String\CBytesBuffer;
use Fuse\Libc\Sys\Stat\Stat;
use PhpVariableFilesystem\Lib\ArrayUtils\ArrayEntryLocator;

final class PhpVariableFilesystem implements FilesystemInterface
{
    use FilesystemDefaultImplementationTrait;

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

    public function getattr(string $path, Stat $stat): int
    {
        $element = $this->getEntry($path);
        if (is_null($element)) {
            return -Errno::ENOENT;
        }

        $stat->st_uid = getmyuid();
        $stat->st_gid = getmygid();

        if (is_array($element)) {
            $stat->st_mode = Stat::S_IFDIR | 0777;
            $stat->st_nlink = 2; // fixme: there may be subdirectories
            return 0;
        }
        $stat->st_mode = Stat::S_IFREG | 0777;
        $stat->st_nlink = 1;
        $stat->st_size = strlen((string)$element);
        return 0;
    }

    public function readdir(
        string $path,
        FuseReadDirBuffer $buf,
        FuseFillDir $filler,
        int $offset,
        FuseFileInfo $fuse_file_info
    ): int {
        $filler($buf, '.', null, 0);
        $filler($buf, '..', null, 0);
        $entry = $this->getEntry($path);
        if (!is_array($entry)) {
            return Errno::ENOTDIR;
        }
        foreach ($entry as $key => $_) {
            $filler($buf, (string)$key, null, 0);
        }

        return 0;
    }
    public function open(string $path, FuseFileInfo $fuse_file_info): int
    {
        $entry = $this->getEntry($path);
        if (!is_scalar($entry)) {
            return -Errno::ENOENT;
        }
        return 0;
    }

    public function read(string $path, CBytesBuffer $buffer, int $size, int $offset, FuseFileInfo $fuse_file_info): int
    {
        $entry = $this->getEntry($path);

        assert(!is_array($entry));
        $len = strlen((string)$entry);

        if ($offset + $size > $len) {
            $size = ($len - $offset);
        }

        $content = substr((string)$entry, $offset, $size);
        $buffer->write($content, $size);

        return $size;
    }

    public function write(string $path, string $buffer, int $size, int $offset, FuseFileInfo $fuse_file_info): int
    {
        $entry = &$this->getEntry($path);

        assert(!is_array($entry));
        $entry = substr_replace((string)$entry, $buffer, $offset, $size);

        return $size;
    }

    public function create(string $path, int $mode, FuseFileInfo $fuse_file_info): int
    {
        $entry = &$this->getParentEntry($path);
        if (is_array($entry)) {
            $entry[basename($path)] = '';
            return 0;
        } else {
            return Errno::ENOENT;
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
            return Errno::ENOENT;
        }
    }

    /**
     * @return array|scalar|null
     */
    private function &getEntry(string $path)
    {
        return $this->array_entry_locator->getEntry($path, $this->array);
    }

    /**
     * @return array|null
     */
    private function &getParentEntry(string $path)
    {
        return $this->array_entry_locator->getParentEntry($path, $this->array);
    }

    private function unsetEntry(string $path): void
    {
        $this->array_entry_locator->unsetEntry($path, $this->array);
    }
}
