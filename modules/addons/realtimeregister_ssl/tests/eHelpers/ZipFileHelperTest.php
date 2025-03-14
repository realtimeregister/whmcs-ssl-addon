<?php

namespace Tests\eHelpers;

use AddonModule\RealtimeRegisterSsl\eHelpers\ZipFileHelper;
use PHPUnit\Framework\TestCase;

class ZipFileHelperTest extends TestCase
{
    public function testGetEmptyFile()
    {
        $zipFileHelper = new ZipFileHelper(file_get_contents(__DIR__ . '/../data/zero-bytes-file.zip'));

        $this->assertEmpty($zipFileHelper->getFile('something.txt'));
    }

    public function testGetFilledFile()
    {
        $zipFileHelper = new ZipFileHelper(file_get_contents(__DIR__ . '/../data/one-file.zip'));

        $this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', $zipFileHelper->getFile('something.txt'));
    }

    public function testGetFilledFileinDirectory()
    {
        $zipFileHelper = new ZipFileHelper(file_get_contents(__DIR__ . '/../data/file-in-directory.zip'));

        $this->assertEquals('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', $zipFileHelper->getFile('test-directory/something.txt'));

        $this->expectExceptionMessage('Could not open file \'calvin.txt\'.');

        $zipFileHelper->getFile('calvin.txt');
    }

    public function testFilesInDirectory()
    {
        $zipFileHelper = new ZipFileHelper(file_get_contents(__DIR__ . '/../data/file-in-directory.zip'));

        $this->assertEquals(['test-directory/something.txt'], $zipFileHelper->getNamesOfFilesInDirectory('test-directory'));

        $this->assertEquals([], $zipFileHelper->getNamesOfFilesInDirectory('test-no-directory'));
    }
}
