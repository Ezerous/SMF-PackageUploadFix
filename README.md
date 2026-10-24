# SMF-PackageUploadFix

[![GitHub release](https://img.shields.io/github/release/Ezerous/SMF-PackageUploadFix.svg)](https://github.com/Ezerous/SMF-PackageUploadFix/releases)
[![SMF](https://img.shields.io/badge/SMF-1.1.x-blue.svg?style=flat)](https://simplemachines.org)
![PHP 5.3+](https://img.shields.io/badge/PHP-5.3%2B-8892BF.svg)
[![Tests](https://github.com/Ezerous/SMF-PackageUploadFix/actions/workflows/tests.yml/badge.svg)](https://github.com/Ezerous/SMF-PackageUploadFix/actions/workflows/tests.yml)
![Last Commit](https://img.shields.io/github/last-commit/Ezerous/SMF-PackageUploadFix/develop.svg?style=flat)

A package for [SMF](https://www.simplemachines.org/) v1.1.x forums that fixes a wrong case of "The package you tried to upload either is not a valid package or has become corrupted." error, that seems to be happening when SMF runs on specific newer systems.

It applies the following fixes:

- Fixes uploading of .tar.gz/.tgz packages by implementing a small change regarding a crc32 comparison (introduced in SMF v2)
- Fixes uploading of .zip packages by replacing read_zip_data function with the respective one of SMF v2 (adjusted for compatibility)

Note: The released archive had to be created with an older zipping method for it to work!

## Releases

Releases are created by GitHub Actions from `master`. To publish a new package, update the version in both `package-info.xml` and `modification.xml`, commit the changes, and push to `master`. The workflow validates that both versions match, creates `SMF-PackageUploadFix_vX.Y.Z.zip`, tags the commit as `vX.Y.Z`, and creates a release titled `Version X.Y.Z`. Commits that do not change the version are ignored. A release can also be started manually from the workflow's **Run workflow** action.
