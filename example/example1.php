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

use Fuse\Mounter;
use PhpVariableFilesystem\PhpVariableFilesystem;

require __DIR__ . '/../vendor/autoload.php';

$e = new \DateTimeImmutable();

$mounter = new Mounter();
$variable_fs = new PhpVariableFilesystem([
    1,
    2,
    'foo' => 'bar',
    'e' => json_decode(json_encode($e), true)
]);
$result = $mounter->mount('/tmp/example/', $variable_fs);
var_dump($variable_fs->getArray());
return $result;
