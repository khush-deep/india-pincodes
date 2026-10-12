# khush/india-pincodes

Offline India PIN code lookup for PHP. No API calls, no database: the data ships as plain PHP arrays,
split into one file per 3-digit prefix, so a lookup loads only one small file (and OPcache keeps it).

- PIN → every post office for it: office name, type, delivery status, district and state. All 19,586 PINs from India Post.
- State list: `IndiaState`, the 28 states and 8 union territories by ISO 3166-2:IN code.

## Install

```bash
composer require khush/india-pincodes
```

## Use

```php
use Khush\IndiaPincodes\IndiaState;
use Khush\IndiaPincodes\PincodeLookup;

$lookup = new PincodeLookup();

$offices = $lookup->find('401106'); // '401 106' works too
// [
//     ['office' => 'Dongari', 'type' => 'BO', 'delivery' => true, 'district' => 'Thane', 'state' => 'MH'],
//     ['office' => 'Uttan',   'type' => 'PO', 'delivery' => true, 'district' => 'Thane', 'state' => 'MH'],
// ]

IndiaState::NAMES_BY_CODE[$offices[0][PincodeLookup::STATE] ?? ''] ?? null; // 'Maharashtra' (state can be null)

$lookup->find('011001'); // null - malformed (a PIN never starts with 0)
$lookup->find('999999'); // null - unknown
```

`find()` returns `null` for malformed and unknown PINs. Pass a folder to the constructor to read your
own data (same layout as `data/`), e.g. a fixture in tests. The layout of the data files isn't part of the
public API and can change in any release.

### What's in an office

| Key | Value |
|---|---|
| `office` | Office name, without the type suffix India Post adds (`Kothimir B.O` → `Kothimir`). Otherwise as published, so some names are in capitals or carry other abbreviations. |
| `type` | `HO` head office, `PO` sub office, `BO` branch office (`PincodeLookup::TYPE_*`). |
| `delivery` | `true` if the office delivers mail. |
| `district` | District name. All-capital names are converted to title case; the rest are as published. `null` if India Post has none. |
| `state` | ISO 3166-2:IN code, i.e. `TS`. `null` if India Post has none. |

### Things to know about the data

The package returns what India Post publishes and doesn't guess. So:

- A PIN can cover more than one district (about 1,250 PINs do), and a few list an office in a second state, which is usually a data entry error at India Post.
  If you fill a form from a PIN, decide what to do when the offices don't agree, i.e. only fill the district when there's exactly one non-null district.
- About 340 PINs have at least one office without a district or state, and for about 100 PINs no office has one.
- A few offices are listed twice with a small difference, i.e. once in capitals or once without a district.
- Some districts are named as India Post names them, not as they're commonly known, i.e. Delhi's "Central" or "South West", and Sikkim's "East District".
- State codes follow the ISO update of 2023 (`TS` for Telangana, not `TG`). `IndiaState::OLD_CODES_TO_CODE` maps the old codes, if you stored them before.

## Performance and OPcache

Each `find()` call loads the data file for the PIN's first three digits (at most about 90 KB) with `require`, and keeps the last file it read.
With OPcache on, that file is compiled once and later calls read it from shared memory, so lookups are cheap.

OPcache is on by default for web requests (PHP-FPM, Apache) but **off by default on the command line**. For
CLI scripts or queue workers that look up many PINs, turn it on in `php.ini`:

```ini
opcache.enable_cli=1
```

Without it, every call for a different prefix re-parses its file: still correct, just slower for bulk lookups.

## Regenerating the data

data.gov.in republishes the directory from time to time. To refresh, run from a clone of this repo (not from `vendor/`):

1. Download the CSV from https://www.data.gov.in/resource/all-india-pincode-directory-till-last-month. The site asks for a name, email and captcha, so this step is done in the browser.
2. Run the generator:

```bash
composer install
php bin/generate-pincode-data.php ~/Downloads/<downloaded file>.csv
```

3. Update the version date in [data/NOTICE.md](data/NOTICE.md).

The generator stops on a value it doesn't know (a new state, office type or delivery status) instead of guessing.
A new or renamed state goes into `src/IndiaState.php` first.

Data refreshes and cleaning fixes are released as minor versions. A change to the keys, value types or null rules
of an office, or to a method signature, is a major version.

## Licence

Code: MIT. Data: see [data/NOTICE.md](data/NOTICE.md) (India Post via data.gov.in, GODL-India - attribution required).
