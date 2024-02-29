# SMF-PackageUploadFix

[![GitHub release](https://img.shields.io/github/release/Ezerous/SMF-PackageUploadFix.svg)](https://github.com/Ezerous/SMF-PackageUploadFix/releases)
[![SMF](https://img.shields.io/badge/SMF-1.1.x-blue.svg?style==flat)](https://simplemachines.org)
![Last Commit](https://img.shields.io/github/last-commit/Ezerous/SMF-PackageUploadFix/develop.svg?style=flat)

A package for [SMF](https://www.simplemachines.org/) v1.1.x forums that fixes a wrong case of "The package you tried to upload either is not a valid package or has become corrupted." error, that seems to be happening when SMF runs on specific newer systems.

It applies the following fixes:

- Fixes uploading of .tar.gz/.tgz packages by implementing a small change regarding a crc32 comparison (introduced in SMF v2)
- Fixes uploading of .zip packages by replacing read_zip_data function with the respective one of SMF v2 (adjusted for compatibility)

Note: The released archive had to be created with an older zipping method for it to work!
