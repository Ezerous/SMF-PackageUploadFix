# SMF-PackageUploadFix

[![GitHub release](https://img.shields.io/github/release/Ezerous/SMF-PackageUploadFix.svg)](https://github.com/Ezerous/SMF-PackageUploadFix/releases)
[![SMF](https://img.shields.io/badge/SMF-1.1.x-blue.svg?style=flat)](https://simplemachines.org)
![PHP 5.3+](https://img.shields.io/badge/PHP-5.3%2B-8892BF.svg)
[![Tests](https://github.com/Ezerous/SMF-PackageUploadFix/actions/workflows/tests.yml/badge.svg)](https://github.com/Ezerous/SMF-PackageUploadFix/actions/workflows/tests.yml)
![Last Commit](https://img.shields.io/github/last-commit/Ezerous/SMF-PackageUploadFix/develop.svg?style=flat)

A package for [SMF](https://www.simplemachines.org/) v1.1.x forums that fixes cases where the Package Manager incorrectly reports:

"The package you tried to upload either is not a valid package or has become corrupted."

These errors can occur on certain newer systems due to compatibility issues in SMF 1.1.x's archive handling.

It applies the following fixes:

- **TAR.GZ/TGZ packages:** Fixes package uploads by adjusting a CRC32 comparison, using a change introduced in SMF v2.
- **ZIP packages:** Fixes package uploads by replacing the `read_zip_data()` function with its SMF v2 counterpart, adjusted for SMF 1.1.x compatibility.

## Release archive compatibility

### ZIP archives: The problem

Ironically, the original SMF 1.1.x ZIP parser can reject the very package intended to fix it.

This was encountered with release v1.0.1, where a ZIP archive generated using the standard Linux `zip -r` command was reported as corrupted by SMF 1.1.x, despite being a perfectly valid ZIP archive.

The issue originates from a bug in SMF 1.1.x's `read_zip_data()` function.

ZIP archives contain both Local File Headers and Central Directory entries, each of which may have its own extra fields. Their lengths are not required to match.

However, the original SMF parser incorrectly uses the extra-field length from the Central Directory when calculating the location of compressed data in the Local File Header.

When these lengths differ, the parser reads from an incorrect offset, potentially causing decompression to fail and the package to be rejected as corrupted.

Modern ZIP utilities, particularly those running on Linux, may include additional Unix-specific metadata that exposes this bug.

### ZIP archives: The solution

To ensure that the fix itself can be installed on an unpatched SMF 1.1.x installation, release ZIP archives are generated using:

`zip -X -0 -r "../$archive" installation.txt modification.xml package-info.xml README.md`

The important options are:

- **`-X` (exclude extra attributes):** Removes additional ZIP metadata that can trigger the incorrect offset calculation in the legacy SMF parser. This is the essential workaround.
- **`-0` (store only):** Disables ZIP compression, providing an additional compatibility safeguard for older archive implementations.
- **`-r` (recursive):** Includes directories and their contents when applicable.

This produces a legacy-compatible ZIP archive that can be read by the original SMF 1.1.x Package Manager, allowing the package to install its own fixes.

The workaround does not change the actual package contents or replace the underlying parser fix. It simply ensures that the package can be installed before that fix is available.

### TAR.GZ/TGZ archives: Compatibility recommendations

Although the ZIP issue described above is specific to the SMF ZIP parser, similar precautions can be taken when generating TAR.GZ/TGZ archives intended for legacy SMF installations.

GNU tar may produce archives using GNU-specific extensions or the POSIX PAX format, depending on its configuration and the selected options. Older archive parsers may not support all these extensions.

For improved compatibility, the following command is recommended:

`tar --format=ustar -czf "../$archive" installation.txt modification.xml package-info.xml README.md`

The important options are:

- **`--format=ustar`:** Uses the standardized POSIX USTAR format, avoiding GNU-specific and PAX extended headers.
- **`-c`:** Creates a new TAR archive.
- **`-z`:** Compresses the TAR archive using GZIP.
- **`-f`:** Specifies the output archive filename.

For additional control over the GZIP header, the following alternative can be used:

`tar --format=ustar -cf - installation.txt modification.xml package-info.xml README.md | gzip -n > "../$archive"`

The **`gzip -n`** option prevents the original filename and timestamp from being stored in the GZIP header.

**Important considerations:**

- Unlike ZIP's `-0` option, GZIP compression does not need to be disabled. TAR.GZ archives are expected to use GZIP compression.
- The USTAR format has filename, path-length, and other metadata limitations. Packages containing unusually long paths or special metadata may require a different approach.
- These recommendations are preventive compatibility measures, not a confirmed workaround for the specific CRC32 issue affecting TAR.GZ/TGZ uploads in SMF 1.1.x.
- The TAR.GZ/TGZ CRC32 issue is handled separately by this package's modification.

### Recommended archive formats

For developers distributing packages targeting legacy SMF 1.1.x installations:

| Archive | Recommended command/options | Purpose |
|---|---|---|
| ZIP | `zip -X -0 -r` | Avoid problematic ZIP extra fields and compression-related compatibility issues |
| TAR.GZ/TGZ | `tar --format=ustar -czf` | Avoid newer TAR extensions |
| TAR.GZ/TGZ (alternative) | `tar --format=ustar -cf - ... \| gzip -n` | Additionally omit original filename and timestamp from the GZIP header |

These settings favor compatibility with legacy archive parsers over modern metadata features and, in the case of ZIP, compression efficiency.

## Releases

Releases are created by GitHub Actions from `master`.

To publish a new package, update the version in both `package-info.xml` and `modification.xml`, commit the changes, and push to `master`.

The workflow:

1. Validates that both versions match.
2. Creates `SMF-PackageUploadFix_vX.Y.Z.zip` using the legacy-compatible ZIP options described above.
3. Tags the commit as `vX.Y.Z`.
4. Creates a GitHub release titled `Version X.Y.Z`.

Commits that do not change the version are ignored. A release can also be started manually from the workflow's **Run workflow** action.

**Note:** Previously published release archives are not automatically rebuilt when the workflow changes. Any affected release assets must be regenerated and replaced separately.
