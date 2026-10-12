<?php

namespace Khush\IndiaPincodes\Tests;

use Khush\IndiaPincodes\PincodeLookup;
use PHPUnit\Framework\TestCase;

class PincodeLookupTest extends TestCase
{
    protected string $fixtureDirectory;

    protected string $emptyDirectory;

    protected PincodeLookup $lookup;

    protected function setUp(): void
    {
        $this->fixtureDirectory = sys_get_temp_dir() . '/pincode-fixture-' . uniqid();
        $this->emptyDirectory   = sys_get_temp_dir() . '/pincode-empty-' . uniqid();

        mkdir($this->emptyDirectory, 0755, true);

        $this->writeFixtureFile('110.php', [
            '110001' => [
                ['Baroda House', 'PO', false, 'New Delhi', 'DL'],
                ['New Delhi GPO', 'HO', true, 'New Delhi', 'DL'],
            ],
        ]);
        $this->writeFixtureFile('502.php', ['502255' => [['Kondapak', 'BO', true, null, null]]]);

        // Each file below is what a malformed code would reach if find() cut or built the path
        // before checking the whole code, so a missing guard returns a match.
        $this->writeFixtureFile('011.php', ['011001' => [['Starts With Zero', 'BO', true, null, null]]]);
        $this->writeFixtureFile('123.php', ['123456' => [['Cut From 1234567', 'BO', true, null, null]]]);
        $this->writeFixtureFile('.php', ['../x11' => [['Path Traversal', 'BO', true, null, null]]]);

        $this->lookup = new PincodeLookup($this->fixtureDirectory);
    }

    protected function tearDown(): void
    {
        deleteDirectory($this->fixtureDirectory);
        deleteDirectory($this->emptyDirectory);
    }

    public function testFindsEveryOfficeForAPin(): void
    {
        // Act
        $result = $this->lookup->find('110001');

        // Assert
        $this->assertSame([
            [
                PincodeLookup::OFFICE   => 'Baroda House',
                PincodeLookup::TYPE     => PincodeLookup::TYPE_SUB_OFFICE,
                PincodeLookup::DELIVERY => false,
                PincodeLookup::DISTRICT => 'New Delhi',
                PincodeLookup::STATE    => 'DL',
            ],
            [
                PincodeLookup::OFFICE   => 'New Delhi GPO',
                PincodeLookup::TYPE     => PincodeLookup::TYPE_HEAD_OFFICE,
                PincodeLookup::DELIVERY => true,
                PincodeLookup::DISTRICT => 'New Delhi',
                PincodeLookup::STATE    => 'DL',
            ],
        ], $result);
    }

    public function testFindsAPinWrittenWithASpaceOrAroundSpaces(): void
    {
        // Assert
        $this->assertCount(2, $this->lookup->find('110 001'));
        $this->assertCount(2, $this->lookup->find(' 110001 '));
    }

    public function testAnOfficeWithoutADistrictOrStateHasNulls(): void
    {
        // Act
        $result = $this->lookup->find('502255');

        // Assert
        $this->assertNull($result[0][PincodeLookup::DISTRICT]);
        $this->assertNull($result[0][PincodeLookup::STATE]);
    }

    public function testFindsPinsWhenSwitchingBetweenPrefixes(): void
    {
        // Assert
        $this->assertCount(2, $this->lookup->find('110001'));
        $this->assertCount(1, $this->lookup->find('502255'));
        $this->assertNull($this->lookup->find('110099'));
        $this->assertCount(2, $this->lookup->find('110001'));
    }

    public function testValidPinReturnsNullWhenItsPrefixFileIsMissing(): void
    {
        // Act
        $result = (new PincodeLookup($this->emptyDirectory))->find('110001');

        // Assert
        $this->assertNull($result);
    }

    public function testUnknownPinReturnsNull(): void
    {
        // Act
        $result = $this->lookup->find('110099');

        // Assert
        $this->assertNull($result);
    }

    public function testMalformedPinsReturnNull(): void
    {
        // Assert
        $this->assertNull($this->lookup->find('../x11'));
        $this->assertNull($this->lookup->find('011001'));
        $this->assertNull($this->lookup->find('1234567'));
        $this->assertNull($this->lookup->find('11000'));
        $this->assertNull($this->lookup->find('110-001'));
        $this->assertNull($this->lookup->find('110  001'));
        $this->assertNull($this->lookup->find(''));
    }

    protected function writeFixtureFile(string $relativePath, array $entries): void
    {
        $path = $this->fixtureDirectory . '/' . $relativePath;

        @mkdir(\dirname($path), 0755, true);

        file_put_contents($path, '<?php return ' . var_export($entries, true) . ';');
    }
}
