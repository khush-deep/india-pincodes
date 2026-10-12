<?php

namespace Khush\IndiaPincodes\Tests;

use Khush\IndiaPincodes\IndiaState;
use Khush\IndiaPincodes\PincodeLookup;
use PHPUnit\Framework\TestCase;

class ShippedDataTest extends TestCase
{
    // The suffix letter the generator removes for each office type, spaced in any case or glued in capitals.
    protected const SUFFIX_LETTERS = [
        PincodeLookup::TYPE_HEAD_OFFICE   => 'H',
        PincodeLookup::TYPE_SUB_OFFICE    => '[SP]',
        PincodeLookup::TYPE_BRANCH_OFFICE => 'B',
    ];

    public function testDefaultLookupReadsTheShippedData(): void
    {
        // Act
        $result = (new PincodeLookup())->find('110001');

        // Assert
        $offices = array_column($result, PincodeLookup::STATE, PincodeLookup::OFFICE);

        $this->assertSame('DL', $offices['New Delhi GPO']);
    }

    public function testEveryShippedOfficeHasTheDocumentedShape(): void
    {
        // Arrange
        $types = [PincodeLookup::TYPE_HEAD_OFFICE, PincodeLookup::TYPE_SUB_OFFICE, PincodeLookup::TYPE_BRANCH_OFFICE];
        $files = glob(\dirname(__DIR__) . '/data/[0-9][0-9][0-9].php');

        // Act
        $problems = [];

        foreach ($files as $file)
        {
            foreach (require $file as $pin => $offices)
            {
                foreach ($offices as $office)
                {
                    [$name, $type, $delivery, $district, $state] = $office;

                    if ((preg_match('/^[1-9]\d{5}$/', (string) $pin) !== 1) ||
                        (str_starts_with((string) $pin, basename($file, '.php')) === false) ||
                        (count($office) !== count(PincodeLookup::FIELDS)) ||
                        ($name === '') ||
                        ($name !== trim($name)) ||
                        (preg_match('/^[\x20-\x7E]+$/', $name) !== 1) ||
                        ($this->hasTypeSuffix($name, $type) === true) ||
                        (in_array($type, $types, true) === false) ||
                        (is_bool($delivery) === false) ||
                        (($district !== null) && ($district === '')) ||
                        (($state !== null) && (isset(IndiaState::NAMES_BY_CODE[$state]) === false)))
                    {
                        $problems[] = $pin . ': ' . json_encode($office);
                    }
                }
            }
        }

        // Assert
        $this->assertNotEmpty($files);
        $this->assertSame([], \array_slice($problems, 0, 10));
    }

    public function testThereAre28StatesAnd8UnionTerritories(): void
    {
        // Assert
        $this->assertCount(36, IndiaState::NAMES_BY_CODE);
    }

    public function testOldStateCodesPointAtCurrentOnes(): void
    {
        // Assert
        foreach (IndiaState::OLD_CODES_TO_CODE as $oldCode => $code)
        {
            $this->assertArrayNotHasKey($oldCode, IndiaState::NAMES_BY_CODE);
            $this->assertArrayHasKey($code, IndiaState::NAMES_BY_CODE);
        }
    }

    protected function hasTypeSuffix(string $name, string $type): bool
    {
        $letter = self::SUFFIX_LETTERS[$type] ?? null;

        if ($letter === null)
        {
            return false;
        }

        return (preg_match('/\s' . $letter . '\.?\s?O\.?$/i', $name) === 1) ||
            (preg_match('/(?<=[a-z)])' . $letter . '(?:\.O|O)\.?$/', $name) === 1);
    }
}
