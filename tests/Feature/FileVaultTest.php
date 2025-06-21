<?php

use Illuminate\Support\Facades\Storage;
use SoareCostin\FileVault\Facades\FileVault;

use function PHPUnit\Framework\assertFileDoesNotExist;

/**
 * Generate a file with random contents.
 *
 * @return int|bool
 */
function generateFile($fileName, $fileSize = 500000)
{
    $fileContents = random_bytes($fileSize);

    return Storage::put($fileName, $fileContents);
}

test('encrypt generates a file', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName);

    // Test if the encrypted file exists
    expect(Storage::path("{$fileName}.enc"))->toBeFile();
});

test('encrypt copy generates a file', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encryptCopy($fileName);

    // Test if the encrypted file exists
    expect(Storage::path("{$fileName}.enc"))->toBeFile();
});

test('it can encrypt a file using a different destination name', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName, 'encrypted.enc');

    // Test if the encrypted file exists
    expect(Storage::path('encrypted.enc'))->toBeFile();
});

test('encrypt deletes the original', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName);

    // Test if the original file has been deleted
    assertFileDoesNotExist(Storage::path($fileName));
});

test('encrypt copy keeps the original', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encryptCopy($fileName);

    // Test if the original file still exists
    expect(Storage::path($fileName))->toBeFile();
});

test('decrypt', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName);
    FileVault::decrypt("{$fileName}.enc");

    // Test that the decrypted file was generated
    expect(Storage::path($fileName))->toBeFile();
});

test('decrypt using a different destination name', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName);
    FileVault::decrypt("{$fileName}.enc", "{$fileName}.dec");

    // Test that the decrypted file was generated
    expect(Storage::path("{$fileName}.dec"))->toBeFile();
});

test('decrypt deletes the encrypted file', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName);
    FileVault::decrypt("{$fileName}.enc");

    // Test that the encrypted file was deleted after decryption
    assertFileDoesNotExist(
        Storage::path("{$fileName}.enc")
    );
});

test('decrypt copy keeps the encrypted file', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encrypt($fileName);
    FileVault::decryptCopy("{$fileName}.enc");

    // Test that the encrypted file was deleted after decryption
    expect(Storage::path("{$fileName}.enc"))->toBeFile();
});

test('a decrypted file has the same content as the original file', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encryptCopy($fileName);
    FileVault::decrypt("{$fileName}.enc", "{$fileName}.dec");

    // Test to see if the decrypted content is the same as the original
    expect(Storage::get("{$fileName}.dec"))->toEqual(Storage::get($fileName));
});

test('it can encrypt and decrypt using a user generated key', function () {
    $key = FileVault::generateKey();

    generateFile($fileName = 'file.txt');

    FileVault::key($key)->encryptCopy($fileName);
    FileVault::key($key)->decrypt("{$fileName}.enc", "{$fileName}.dec");

    // Test to see if the decrypted content is the same as the original
    expect(Storage::get("{$fileName}.dec"))->toEqual(Storage::get($fileName));
});

test('it can stream a decrypted file', function () {
    generateFile($fileName = 'file.txt');

    FileVault::encryptCopy($fileName);

    ob_start();
    FileVault::streamDecrypt("{$fileName}.enc");
    $phpOutput = ob_get_contents();
    ob_end_clean();

    // Test to see if the decrypted content is sent to php://output
    expect($phpOutput)->toEqual(Storage::get($fileName));
});

afterEach(function () {
    // Cleanup the storage dir
    array_map('unlink', glob(__DIR__.'/../storage/app/*.*'));
});
