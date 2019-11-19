<?php

namespace SoareCostin\FileVault;

use Exception;
use Illuminate\Support\Str;
use RuntimeException;

class FileEncrypter
{
    /**
     * Define the number of blocks that should be read from the source file for each chunk.
     * We chose 255 because on decryption we want to read chunks of 4kb ((255 + 1)*16).
     */
    protected const FILE_ENCRYPTION_BLOCKS = 255;

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

    /**
     * Create a new encrypter instance.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return void
     *
     * @throws \RuntimeException
     */
    public function __construct($key, $cipher = 'AES-128-CBC')
    {
        // If the key starts with "base64:", we will need to decode the key before handing
        // it off to the encrypter. Keys may be base-64 encoded for presentation and we
        // want to make sure to convert them back to the raw bytes before encrypting.
        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (static::supported($key, $cipher)) {
            $this->key = $key;
            $this->cipher = $cipher;
        } else {
            throw new RuntimeException('The only supported ciphers are AES-128-CBC and AES-256-CBC with the correct key lengths.');
        }
    }

    /**
     * Determine if the given key and cipher combination is valid.
     *
     * @param  string  $key
     * @param  string  $cipher
     * @return bool
     */
    public static function supported($key, $cipher)
    {
        $length = mb_strlen($key, '8bit');

        return ($cipher === 'AES-128-CBC' && $length === 16) ||
               ($cipher === 'AES-256-CBC' && $length === 32);
    }

    /**
     * Encrypts the source file and saves the result in a new file.
     *
     * @param string $sourcePath  Path to file that should be encrypted
     * @param string $destPath  File name where the encryped file should be written to.
     * @return bool
     */
    public function encrypt($sourcePath, $destPath)
    {
        $fpOut = $this->openDestFile($destPath);
        $fpIn = $this->openSourceFile($sourcePath);

        // Put the initialzation vector to the beginning of the file
        $iv = openssl_random_pseudo_bytes(16);
        fwrite($fpOut, $iv);

        $numberOfChunks = ceil(filesize($sourcePath) / (16 * self::FILE_ENCRYPTION_BLOCKS));

        $i = 0;
        while (! feof($fpIn)) {
            $plaintext = fread($fpIn, 16 * self::FILE_ENCRYPTION_BLOCKS);
            $ciphertext = openssl_encrypt($plaintext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

            // Because Amazon S3 will randomly return smaller sized chunks:
            // Check if the size read from the stream is different than the requested chunk size
            // In this scenario, request the chunk again, unless this is the last chunk
            if (strlen($plaintext) !== 16 * self::FILE_ENCRYPTION_BLOCKS
                && $i + 1 < $numberOfChunks
            ) {
                fseek($fpIn, 16 * self::FILE_ENCRYPTION_BLOCKS * $i);
                continue;
            }

            // Use the first 16 bytes of the ciphertext as the next initialization vector
            $iv = substr($ciphertext, 0, 16);
            fwrite($fpOut, $ciphertext);

            $i++;
        }

        fclose($fpIn);
        fclose($fpOut);

        return true;
    }

    /**
     * Decrypts the source file and saves the result in a new file.
     *
     * @param string $sourcePath   Path to file that should be decrypted
     * @param string $destPath  File name where the decryped file should be written to.
     * @return bool
     */
    public function decrypt($sourcePath, $destPath)
    {
        $fpOut = $this->openDestFile($destPath);
        $fpIn = $this->openSourceFile($sourcePath);

        // Get the initialzation vector from the beginning of the file
        $iv = fread($fpIn, 16);

        $numberOfChunks = ceil((filesize($sourcePath) - 16) / (16 * (self::FILE_ENCRYPTION_BLOCKS + 1)));

        $i = 0;
        while (! feof($fpIn)) {
            // We have to read one block more for decrypting than for encrypting because of the initialization vector
            $ciphertext = fread($fpIn, 16 * (self::FILE_ENCRYPTION_BLOCKS + 1));
            $plaintext = openssl_decrypt($ciphertext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

            // Because Amazon S3 will randomly return smaller sized chunks:
            // Check if the size read from the stream is different than the requested chunk size
            // In this scenario, request the chunk again, unless this is the last chunk
            if (strlen($ciphertext) !== 16 * (self::FILE_ENCRYPTION_BLOCKS + 1)
                && $i + 1 < $numberOfChunks
            ) {
                fseek($fpIn, 16 + 16 * (self::FILE_ENCRYPTION_BLOCKS + 1) * $i);
                continue;
            }

            if ($plaintext === false) {
                throw new Exception('Decryption failed');
            }

            // Get the the first 16 bytes of the ciphertext as the next initialization vector
            $iv = substr($ciphertext, 0, 16);
            fwrite($fpOut, $plaintext);

            $i++;
        }

        fclose($fpIn);
        fclose($fpOut);

        return true;
    }

    protected function openDestFile($destPath)
    {
        if (($fpOut = fopen($destPath, 'w')) === false) {
            throw new Exception('Cannot open file for writing');
        }

        return $fpOut;
    }

    protected function openSourceFile($sourcePath)
    {
        $contextOpts = Str::startsWith($sourcePath, 's3://') ? ['s3' => ['seekable' => true]] : [];

        if (($fpIn = fopen($sourcePath, 'r', false, stream_context_create($contextOpts))) === false) {
            throw new Exception('Cannot open file for reading');
        }

        return $fpIn;
    }
}
