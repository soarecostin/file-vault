# File encryption / decryption in Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/soarecostin/file-vault.svg?style=flat-square)](https://packagist.org/packages/soarecostin/file-vault)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/soarecostin/file-vault/master.svg?style=flat-square)](https://travis-ci.org/soarecostin/file-vault)
[![Quality Score](https://img.shields.io/scrutinizer/g/soarecostin/file-vault.svg?style=flat-square)](https://scrutinizer-ci.com/g/soarecostin/file-vault)
[![StyleCI](https://styleci.io/repos/221933072/shield)](https://styleci.io/repos/221933072)
[![Total Downloads](https://img.shields.io/packagist/dt/soarecostin/file-vault.svg?style=flat-square)](https://packagist.org/packages/soarecostin/file-vault)

With this package, you can encrypt and decrypt files of any size in your Laravel project. This package uses streams and [CBC encryption](https://en.wikipedia.org/wiki/Block_cipher_mode_of_operation#Cipher_Block_Chaining_(CBC)), encrypting / decrypting a segment of data at a time.


## Installation and usage

This package requires PHP 7.2 and Laravel 5.8 or higher.  

You can install the package via composer:

```bash
composer require soarecostin/file-vault
```

## Usage

This package will automatically register a facade called `FileVault`.

### Encrypting a file

The `encrypt` method will search for a file, encrypt it and save it in the same directory. 

``` php
public function encrypt(string $sourceFile, string $destFile = null, $deleteSource = true)
```

#### Examples:

The following example will search for `file.txt` into the `local` disk, save the encrypted file as `file.txt.enc` and delete the original `file.txt`:
``` php
FileVault::encrypt("file.txt");
```

You can also specify a different `disk`, just as you would normally with the Laravel `Storage` facade:
``` php
FileVault::disk('s3')->encrypt("file.txt");
```

The following example will search for `file.txt` into the `local` disk, save the encrypted file as `encrypted.txt` and delete the original `file.txt`:
``` php
FileVault::encrypt("file.txt", "encrypted.txt");
```

The following examples both achive the same results as above, with the only difference that the original file is not deleted:
``` php
// save the encrypted copy to file.txt.enc
FileVault::encryptCopy("file.txt");

// or save the encrypted copy with a different name
FileVault::encryptCopy("file.txt", "encrypted.txt");
```

### Decrypting a file

The `decrypt` method will search for a file, decrypt it and save it in the same directory


### Streaming a decrypted file

Sometimes you will only want to allow users to download the decrypted file, but you don't need to store the actual decrypted file. For this, you can use the `streamDecrypt` function that will decrypt the file and will write it to the `php://output` stream. You can use the Laravel [`streamDownload` method](https://laravel.com/docs/6.x/responses#file-downloads) (available since 5.6) in order to generate a downloadable response:

``` php
return response()->streamDownload(function () {
    FileVault::streamDecrypt('file.txt')
}, 'laravel-readme.md');
```

## Testing

Run the tests with:

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email soarecostin@gmail.com instead of using the issue tracker.

## Credits

- [Costin Soare](https://github.com/soarecostin)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
