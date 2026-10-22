# Changelog

All notable changes to `file-vault` will be documented in this file.

## 2.0.0 - 2026-10-22

### Added
- Support for Laravel 10, 11, 12 and 13.
- Any disk using the `s3` driver now works, not only a disk named `s3`.
- Tests that prove files encrypted with v1.x still decrypt.
- GitHub Actions test matrix (replaces Travis CI).

### Changed
- S3 support now uses Flysystem 3 (`league/flysystem-aws-s3-v3` ^3.0), as required by Laravel 9 and later.
- On S3 disks, the disk's `root` option is now applied to file paths, matching how Laravel's `Storage` resolves them.

### Removed
- Support for PHP < 8.1 and Laravel < 10. Stay on 1.x for older versions.

### Upgrading from 1.x
- The encrypted file format is unchanged: files encrypted with 1.x decrypt with 2.x, and the other way round.
- If your S3 disk config sets a `root`, check where your existing encrypted files are stored. 1.x ignored `root`, so they may sit at the bucket root.

## 1.0.0
- Initial release.
