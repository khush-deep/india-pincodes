<?php

namespace Khush\IndiaPincodes\Tests;

use Khush\IndiaPincodes\PincodeLookup;
use PHPUnit\Framework\TestCase;

class PincodeGeneratorTest extends TestCase
{
    protected const HEADER = 'circlename,regionname,divisionname,officename,pincode,officetype,delivery,district,statename,latitude,longitude';

    protected string $tmpOut;
    protected string $tmpIn;

    protected function setUp(): void
    {
        $this->tmpOut = sys_get_temp_dir() . '/pincode-out-' . uniqid();
        $this->tmpIn  = sys_get_temp_dir() . '/pincode-in-' . uniqid();

        mkdir($this->tmpIn, 0755, true);
    }

    protected function tearDown(): void
    {
        deleteDirectory($this->tmpOut);
        deleteDirectory($this->tmpIn);
    }

    public function testGeneratorBuildsTheDataFromTheIndiaPostCsv(): void
    {
        // Act
        $result = $this->runGenerator(__DIR__ . '/fixtures/pincodes.csv');

        // Assert
        $this->assertSame(0, $result['exit']);
        $this->assertStringContainsString('12 PINs written, 1 rows skipped', $result['stdout']);

        $lookup = new PincodeLookup($this->tmpOut);

        // The duplicate row is dropped, offices are sorted by name, suffixes and extra spaces are gone.
        $this->assertSame([
            $this->office('Asifabad', 'PO', false, 'Kumuram Bheem Asifabad', 'TS'),
            $this->office('Kothimir', 'BO', true, 'Kumuram Bheem Asifabad', 'TS'),
        ], $lookup->find('504273'));

        $this->assertSame([$this->office('New Delhi GPO', 'HO', true, 'New Delhi', 'DL')], $lookup->find('110001'));
        $this->assertSame([$this->office('Daman', 'PO', true, 'Daman', 'DH')], $lookup->find('396210'));
        $this->assertSame([$this->office('Kondapak', 'BO', true, null, null)], $lookup->find('502255'));
    }

    public function testGeneratorTitleCasesOnlyAllCapitalDistricts(): void
    {
        // Act
        $this->runGenerator(__DIR__ . '/fixtures/pincodes.csv');

        // Assert
        $lookup = new PincodeLookup($this->tmpOut);

        $this->assertSame('Y.S.R.', $lookup->find('516001')[0][PincodeLookup::DISTRICT]);
        $this->assertSame('Bapatla', $lookup->find('522101')[0][PincodeLookup::DISTRICT]);
        $this->assertSame('Lahul and Spiti', $lookup->find('175132')[0][PincodeLookup::DISTRICT]);
    }

    public function testGeneratorRemovesOnlyTheSuffixOfTheOfficesType(): void
    {
        // Act
        $this->runGenerator(__DIR__ . '/fixtures/pincodes.csv');

        // Assert
        $lookup = new PincodeLookup($this->tmpOut);

        // Written twice, glued to the name, and in lowercase.
        $this->assertSame(['Duppalli', 'Relawan', 'Tadur'], array_column($lookup->find('325215'), PincodeLookup::OFFICE));

        // "HO" on a sub office is part of its name.
        $this->assertSame('NDC Coimbatore HO', $lookup->find('641001')[0][PincodeLookup::OFFICE]);
    }

    public function testGeneratorCleansNonBreakingSpacesAcronymsAndAmpersands(): void
    {
        // Act
        $this->runGenerator(__DIR__ . '/fixtures/pincodes.csv');

        // Assert
        $lookup = new PincodeLookup($this->tmpOut);

        $this->assertSame('NDC DHANBAD', $lookup->find('826011')[0][PincodeLookup::OFFICE]);
        $this->assertSame('NTR', $lookup->find('521228')[0][PincodeLookup::DISTRICT]);
        $this->assertSame('JK', $lookup->find('180004')[0][PincodeLookup::STATE]);
    }

    public function testGeneratorKeepsOtherFilesInTheOutputFolder(): void
    {
        // Arrange
        mkdir($this->tmpOut, 0755, true);
        file_put_contents($this->tmpOut . '/NOTICE.md', '# Notice');
        file_put_contents($this->tmpOut . '/999.php', '<?php return [];');

        // Act
        $this->runGenerator(__DIR__ . '/fixtures/pincodes.csv');

        // Assert
        $this->assertFileExists($this->tmpOut . '/NOTICE.md');
        $this->assertFileDoesNotExist($this->tmpOut . '/999.php');
    }

    public function testGeneratorStopsOnAnUnknownState(): void
    {
        // Arrange
        $csv = $this->writeCsv('"C","R","D","Somewhere BO",110001,BO,Delivery,"NEW DELHI","NEW STATE",NA,NA');

        // Act
        $result = $this->runGenerator($csv);

        // Assert
        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Unknown state "NEW STATE" on line 2', $result['stderr']);
        $this->assertDirectoryDoesNotExist($this->tmpOut);
    }

    public function testGeneratorStopsOnAnUnknownOfficeType(): void
    {
        // Arrange
        $csv = $this->writeCsv('"C","R","D","Somewhere BO",110001,XO,Delivery,"NEW DELHI",DELHI,NA,NA');

        // Act
        $result = $this->runGenerator($csv);

        // Assert
        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Unknown office type "XO" on line 2', $result['stderr']);
    }

    public function testGeneratorStopsOnAnUnknownDeliveryValue(): void
    {
        // Arrange
        $csv = $this->writeCsv('"C","R","D","Somewhere BO",110001,BO,Maybe,"NEW DELHI",DELHI,NA,NA');

        // Act
        $result = $this->runGenerator($csv);

        // Assert
        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Unknown delivery value "Maybe" on line 2', $result['stderr']);
    }

    public function testGeneratorStopsOnInvalidUtf8(): void
    {
        // Arrange
        $csv = $this->writeCsv("\"C\",\"R\",\"D\",\"Bad \xFF Name BO\",110001,BO,Delivery,\"NEW DELHI\",DELHI,NA,NA");

        // Act
        $result = $this->runGenerator($csv);

        // Assert
        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Invalid UTF-8 on line 2', $result['stderr']);
        $this->assertDirectoryDoesNotExist($this->tmpOut);
    }

    public function testGeneratorNamesAMissingColumn(): void
    {
        // Arrange
        $csv = $this->tmpIn . '/pincodes.csv';
        file_put_contents($csv, "officename,pincode,officetype,delivery,district\nX BO,110001,BO,Delivery,NEW DELHI\n");

        // Act
        $result = $this->runGenerator($csv);

        // Assert
        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('Missing column in the CSV: statename', $result['stderr']);
    }

    public function testGeneratorNamesAMissingSourceFile(): void
    {
        // Act
        $result = $this->runGenerator($this->tmpIn . '/missing.csv');

        // Assert
        $this->assertSame(1, $result['exit']);
        $this->assertStringContainsString('missing.csv', $result['stderr']);
    }

    protected function office(string $office, string $type, bool $delivery, ?string $district, ?string $state): array
    {
        return [
            PincodeLookup::OFFICE   => $office,
            PincodeLookup::TYPE     => $type,
            PincodeLookup::DELIVERY => $delivery,
            PincodeLookup::DISTRICT => $district,
            PincodeLookup::STATE    => $state,
        ];
    }

    protected function writeCsv(string $row): string
    {
        $path = $this->tmpIn . '/pincodes.csv';

        file_put_contents($path, self::HEADER . "\n" . $row . "\n");

        return $path;
    }

    /**
     * @return array{exit: int, stdout: string, stderr: string}
     */
    protected function runGenerator(string $input): array
    {
        $command = [PHP_BINARY, \dirname(__DIR__) . '/bin/generate-pincode-data.php', $input, $this->tmpOut];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        return ['exit' => proc_close($process), 'stdout' => $stdout, 'stderr' => $stderr];
    }
}
