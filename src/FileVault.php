<?php

namespace SoareCostin\FileVault;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class FileVault
{
    /**
     * The storage disk.
     *
     * @var string
     */
    protected $disk;

    /**
     * The encryption key.
     *
     * @var string
     */
    protected $key;

    /**
     * The algorithm used for encryption.
     *
     * @var string
     */
    protected $cipher;

    public function __construct()
    {
        $this->disk = config('file-vault.disk');
        $this->key = config('file-vault.key');
        $this->cipher = config('file-vault.cipher');
    }

    /**
     * Set the disk where the files are located
     *
     * @param  string  $disk
     * @return $this
     */
    public function disk($disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set the encryption key
     *
     * @param  string  $key
     * @return $this
     */
    public function key($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Create a new encryption key for the given cipher.
     *
     * @return string
     */
    public static function generateKey()
    {
        return random_bytes(config('file-vault.cipher') === 'AES-128-CBC' ? 16 : 32);
    }

    /**
     * Encrypt the passed file and saves the result in a new file with ".enc" as suffix.
     *
     * @param string $sourceFile Path to file that should be encrypted, relative to the storage disk specified
     * @param string $destFile   File name where the encryped file should be written to, relative to the storage disk specified
     * @return $this
     */
    public function encrypt($sourceFile, $destFile = null, $deleteSource = true)
    {
        if (is_null($destFile)) {
            $destFile = "{$sourceFile}.enc";
        }

        $sourcePath = Storage::disk($this->disk)->path($sourceFile);
        $destPath = Storage::disk($this->disk)->path($destFile);

        // Create a new encrypter instance
        $encrypter = new FileEncrypter($this->key, $this->cipher);

        // If encryption is successful, delete the source file
        if ($encrypter->encrypt($sourcePath, $destPath) && $deleteSource) {
            Storage::disk($this->disk)->delete($sourceFile);
        }

        return $this;
    }

    public function encryptCopy($sourceFile, $destFile = null)
    {
        return self::encrypt($sourceFile, $destFile, false);
    }

    /**
     * Dencrypt the passed file and saves the result in a new file, removing the
     * last 4 characters from file name.
     *
     * @param string $sourceFile Path to file that should be decrypted
     * @param string $destFile   File name where the decryped file should be written to.
     * @return $this
     */
    public function decrypt($sourceFile, $destFile = null, $deleteSource = true)
    {
        if (is_null($destFile)) {
            $destFile = Str::endsWith($sourceFile, '.enc')
                        ? Str::replaceLast(".enc", "", $sourceFile)
                        : $sourceFile . ".dec";
        }

        $sourcePath = Storage::disk($this->disk)->path($sourceFile);
        $destPath = Storage::disk($this->disk)->path($destFile);

        // Create a new encrypter instance
        $encrypter = new FileEncrypter($this->key, $this->cipher);

        // If decryption is successful, delete the source file
        if ($encrypter->decrypt($sourcePath, $destPath) && $deleteSource) {
            Storage::disk($this->disk)->delete($sourceFile);
        }

        return $this;
    }

    public function decryptCopy($sourceFile, $destFile = null)
    {
        return self::decrypt($sourceFile, $destFile, false);
    }
}
