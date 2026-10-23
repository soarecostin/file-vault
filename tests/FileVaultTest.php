<?php

namespace SoareCostin\FileVault\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use SoareCostin\FileVault\Facades\FileVault;
use SoareCostin\FileVault\FileVaultServiceProvider;

class FileVaultTest extends TestCase
{
    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            FileVaultServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'FileVault' => FileVault::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Set the storage local filesystem
        $app['config']->set('filesystems.disks.local.driver', 'local');
        $app['config']->set('filesystems.disks.local.root', realpath(__DIR__.'/../storage/app'));
        $app['config']->set('filesystems.default', 'local');

        // Generate and set a random encryption key
        $app['config']->set('file-vault.key', $this->generateRandomKey());
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            \SoareCostin\FileVault\FileVault::generateKey()
        );
    }

    /**
     * Generate a file with random contents.
     *
     * @return int|bool
     */
    protected function generateFile($fileName, $fileSize = 500000)
    {
        $fileContents = random_bytes($fileSize);

        return Storage::put($fileName, $fileContents);
    }

    public function test_encrypt_generates_a_file()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName);

        // Test if the encrypted file exists
        $this->assertFileExists(
            Storage::path("{$fileName}.enc")
        );
    }

    public function test_encrypt_copy_generates_a_file()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encryptCopy($fileName);

        // Test if the encrypted file exists
        $this->assertFileExists(
            Storage::path("{$fileName}.enc")
        );
    }

    public function test_it_can_encrypt_a_file_using_a_different_destination_name()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName, 'encrypted.enc');

        // Test if the encrypted file exists
        $this->assertFileExists(
            Storage::path('encrypted.enc')
        );
    }

    public function test_encrypt_deletes_the_original()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName);

        // Test if the original file has been deleted
        $this->assertFileDoesNotExist(
            Storage::path($fileName)
        );
    }

    public function test_encrypt_copy_keeps_the_original()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encryptCopy($fileName);

        // Test if the original file still exists
        $this->assertFileExists(
            Storage::path($fileName)
        );
    }

    public function test_decrypt()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName);
        FileVault::decrypt("{$fileName}.enc");

        // Test that the decrypted file was generated
        $this->assertFileExists(
            Storage::path($fileName)
        );
    }

    public function test_decrypt_using_a_different_destination_name()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName);
        FileVault::decrypt("{$fileName}.enc", "{$fileName}.dec");

        // Test that the decrypted file was generated
        $this->assertFileExists(
            Storage::path("{$fileName}.dec")
        );
    }

    public function test_decrypt_deletes_the_encrypted_file()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName);
        FileVault::decrypt("{$fileName}.enc");

        // Test that the encrypted file was deleted after decryption
        $this->assertFileDoesNotExist(
            Storage::path("{$fileName}.enc")
        );
    }

    public function test_decrypt_copy_keeps_the_encrypted_file()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encrypt($fileName);
        FileVault::decryptCopy("{$fileName}.enc");

        // Test that the encrypted file was deleted after decryption
        $this->assertFileExists(
            Storage::path("{$fileName}.enc")
        );
    }

    public function test_a_decrypted_file_has_the_same_content_as_the_original_file()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encryptCopy($fileName);
        FileVault::decrypt("{$fileName}.enc", "{$fileName}.dec");

        // Test to see if the decrypted content is the same as the original
        $this->assertEquals(
            Storage::get($fileName),
            Storage::get("{$fileName}.dec")
        );
    }

    public function test_it_can_encrypt_and_decrypt_using_a_user_generated_key()
    {
        $key = FileVault::generateKey();

        $this->generateFile($fileName = 'file.txt');

        FileVault::key($key)->encryptCopy($fileName);
        FileVault::key($key)->decrypt("{$fileName}.enc", "{$fileName}.dec");

        // Test to see if the decrypted content is the same as the original
        $this->assertEquals(
            Storage::get($fileName),
            Storage::get("{$fileName}.dec")
        );
    }

    public function test_it_can_stream_a_decrypted_file()
    {
        $this->generateFile($fileName = 'file.txt');

        FileVault::encryptCopy($fileName);

        ob_start();
        FileVault::streamDecrypt("{$fileName}.enc");
        $phpOutput = ob_get_contents();
        ob_end_clean();

        // Test to see if the decrypted content is sent to php://output
        $this->assertEquals(
            Storage::get($fileName),
            $phpOutput
        );
    }

    public static function v1CipherProvider()
    {
        return [
            'AES-256-CBC' => ['AES-256-CBC', 32],
            'AES-128-CBC' => ['AES-128-CBC', 16],
        ];
    }

    /**
     * Files encrypted with v1.x must still decrypt with this version.
     * The fixtures were created with file-vault v1.2.0.
     */
    #[DataProvider('v1CipherProvider')]
    public function test_it_decrypts_files_encrypted_with_v1($cipher, $keyLength)
    {
        $fixtures = __DIR__.'/fixtures';
        $key = substr(hash('sha256', 'file-vault-test-key'), 0, $keyLength);
        $encrypted = 'v1-'.strtolower($cipher).'.enc';

        copy("{$fixtures}/{$encrypted}", Storage::path($encrypted));
        config(['file-vault.cipher' => $cipher]);

        FileVault::key($key)->decrypt($encrypted, 'v1-decrypted.txt');

        $this->assertSame(
            file_get_contents("{$fixtures}/v1-plain.txt"),
            Storage::get('v1-decrypted.txt')
        );
    }

    public function test_it_builds_s3_stream_paths_from_the_disk_config()
    {
        config(['filesystems.disks.private-s3' => [
            'driver' => 's3',
            'key' => 'key',
            'secret' => 'secret',
            'region' => 'eu-central-1',
            'bucket' => 'my-bucket',
            'root' => 'uploads',
        ]]);

        $vault = (new \SoareCostin\FileVault\FileVault)->disk('private-s3');

        $method = new \ReflectionMethod($vault, 'getFilePath');
        $method->setAccessible(true);

        $this->assertSame('s3://my-bucket/uploads/file.txt', $method->invoke($vault, 'file.txt'));

        $register = new \ReflectionMethod($vault, 'registerServices');
        $register->setAccessible(true);
        $register->invoke($vault);

        $this->assertContains('s3', stream_get_wrappers());
    }

    protected function tearDown(): void
    {
        // Cleanup the storage dir
        array_map('unlink', glob(__DIR__.'/../storage/app/*.*'));

        parent::tearDown();
    }
}
