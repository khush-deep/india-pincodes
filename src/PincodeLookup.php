<?php

namespace Khush\IndiaPincodes;

class PincodeLookup
{
    public const OFFICE   = 'office';
    public const TYPE     = 'type';
    public const DELIVERY = 'delivery';
    public const DISTRICT = 'district';
    public const STATE    = 'state';

    // Office types as India Post publishes them.
    public const TYPE_HEAD_OFFICE   = 'HO';
    public const TYPE_SUB_OFFICE    = 'PO';
    public const TYPE_BRANCH_OFFICE = 'BO';

    // The order of an office's values in the data files. Keeping each office as a plain list
    // instead of a keyed array makes the data about half the size.
    public const FIELDS = [self::OFFICE, self::TYPE, self::DELIVERY, self::DISTRICT, self::STATE];

    // Six digits, not starting with 0. People often write a space after the third digit.
    protected const PIN_PATTERN = '/^[1-9]\d{2} ?\d{3}$/';

    protected const PREFIX_LENGTH = 3;

    protected string $dataDirectory;

    // The last data file read, so bulk lookups in one prefix don't re-read it when OPcache is off.
    protected ?string $loadedPrefix = null;

    protected array $loadedEntries = [];

    public function __construct(?string $dataDirectory = null)
    {
        $this->dataDirectory = $dataDirectory ?? \dirname(__DIR__) . '/data';
    }

    /**
     * Looks up a PIN, i.e. '110001' or '110 001'. Returns every post office for it, sorted by name,
     * or null for a malformed or unknown PIN. Each office is
     * ['office' => name, 'type' => 'HO'|'PO'|'BO', 'delivery' => bool, 'district' => ?string, 'state' => ?ISO code].
     *
     * @return list<array{office: string, type: 'HO'|'PO'|'BO', delivery: bool, district: ?string, state: ?string}>|null
     */
    public function find(string $pin): ?array
    {
        $code = $this->normalisePin($pin);

        if ($code === null)
        {
            return null;
        }

        $entries = $this->entriesForPrefix(substr($code, 0, self::PREFIX_LENGTH));

        if (isset($entries[$code]) === false)
        {
            return null;
        }

        return array_map(fn (array $office) => array_combine(self::FIELDS, $office), $entries[$code]);
    }

    protected function entriesForPrefix(string $prefix): array
    {
        if ($this->loadedPrefix !== $prefix)
        {
            $path = $this->dataDirectory . '/' . $prefix . '.php';

            // A missing prefix file means "no data", not an error.
            $this->loadedEntries = (is_file($path) === true) ? require $path : [];
            $this->loadedPrefix  = $prefix;
        }

        return $this->loadedEntries;
    }

    /**
     * Checks the whole input before cutting or building a path, because this method is public
     * and cutting first would let 1100011 look up 110001.
     */
    protected function normalisePin(string $pin): ?string
    {
        $trimmed = trim($pin);

        return preg_match(self::PIN_PATTERN, $trimmed) === 1 ? str_replace(' ', '', $trimmed) : null;
    }
}
