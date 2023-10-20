<?php
/*
The MIT License (MIT)

Copyright (c) 2015 PortPHP

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */
namespace Port\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Port\Reader\CountableReader;

/**
 * Reads Spreadsheet files with the help of PHPSpreadsheet
 *
 * @author David de Boer <david@ddeboer.nl>
 *
 * @see https://github.com/PHPOffice/PhpSpreadsheet
 */
class SpreadsheetReader implements CountableReader, \SeekableIterator
{
    /**
     * @var array
     */
    protected $columnHeaders;

    /**
     * Total number of rows
     *
     * @var int
     */
    protected $count;

    /**
     * @var int
     */
    protected $headerRowNumber;

    /**
     * @var int
     */
    protected $pointer = 0;

    /**
     * @var array
     */
    protected $worksheet;

    // phpcs:disable Generic.Files.LineLength.MaxExceeded
    /**
     * @param \SplFileObject $file            Spreadsheet file
     * @param int            $headerRowNumber Optional number of header row
     * @param int            $activeSheet     Index of active sheet to read from
     * @param bool           $readOnly        If set to false, the reader take care of the spreadsheet formatting (slow)
     * @param int            $maxRows         Maximum number of rows to read
     */
    public function __construct(\SplFileObject $file, $headerRowNumber = null, $activeSheet = null, $readOnly = true, $maxRows = null)
    {
        // phpcs:enable Generic.Files.LineLength.MaxExceeded
        $reader = IOFactory::createReaderForFile($file->getPathName());
        $reader->setReadDataOnly($readOnly);
        /** @var Spreadsheet $spreadsheet */
        $spreadsheet = $reader->load($file->getPathname());

        if (null !== $activeSheet) {
            $spreadsheet->setActiveSheetIndex($activeSheet);
        }

        /** @var Worksheet $sheet */
        $sheet = $spreadsheet->getActiveSheet();

        if ($maxRows && $maxRows < $sheet->getHighestDataRow()) {
            $maxColumn       = $sheet->getHighestDataColumn();
            $this->worksheet = $sheet->rangeToArray('A1:'.$maxColumn.$maxRows);
        } else {
            $this->worksheet = $spreadsheet->getActiveSheet()->toArray();
        }

        if (null !== $headerRowNumber) {
            $this->setHeaderRowNumber($headerRowNumber);
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        $count = count($this->worksheet);
        if (null !== $this->headerRowNumber) {
            $count--;
        }

        return $count;
    }

    /**
     * Return the current row as an array
     *
     * If a header row has been set, an associative array will be returned
     *
     * @return array|null
     *
     * @author  Derek Chafin <infomaniac50@gmail.com>
     */
    public function current(): mixed
    {
        $row = $this->worksheet[$this->pointer];

        // If the spreadsheet file has column headers, use them to construct an associative
        // array for the columns in this line
        if (!empty($this->columnHeaders) && count($this->columnHeaders) === count($row)) {
            return array_combine(array_values($this->columnHeaders), $row);
        }

        // Else just return the column values
        return $row;
    }

    /**
     * Get column headers
     *
     * @return array
     */
    public function getColumnHeaders(): array
    {
        return $this->columnHeaders;
    }

    /**
     * Get a row
     *
     * @param int $number
     *
     * @return array
     */
    public function getRow(int $number): array
    {
        $this->seek($number);

        return $this->current();
    }

    /**
     * Return the key of the current element
     *
     * @return int|null scalar on success, or null on failure.
     */
    public function key(): mixed
    {
        return $this->pointer;
    }

    /**
     * Move forward to next element
     *
     * @return void Any returned value is ignored.
     */
    public function next(): void
    {
        $this->pointer++;
    }

    /**
     * Rewind the file pointer
     *
     * If a header row has been set, the pointer is set just below the header
     * row. That way, when you iterate over the rows, that header row is
     * skipped.
     *
     * @return void Any returned value is ignored.
     */
    public function rewind(): void
    {
        if (null === $this->headerRowNumber) {
            $this->pointer = 0;
        } else {
            $this->pointer = $this->headerRowNumber + 1;
        }
    }

    /**
     * Seeks to a position
     *
     * @link http://php.net/manual/en/seekableiterator.seek.php
     *
     * @param int $pointer The position to seek to.
     *
     * @return void Any returned value is ignored.
     */
    public function seek(int $pointer): void
    {
        $this->pointer = $pointer;
    }

    /**
     * Set column headers
     *
     * @param array $columnHeaders
     *
     * @return void Any returned value is ignored.
     */
    public function setColumnHeaders(array $columnHeaders): void
    {
        $this->columnHeaders = $columnHeaders;
    }

    /**
     * Set header row number
     *
     * @param int $rowNumber Number of the row that contains column header names
     *
     * @return void Any returned value is ignored.
     */
    public function setHeaderRowNumber($rowNumber)
    {
        $this->headerRowNumber = $rowNumber;
        $this->columnHeaders   = $this->worksheet[$rowNumber];
    }

    /**
     * Checks if current position is valid
     *
     * @return bool The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid(): bool
    {
        return isset($this->worksheet[$this->pointer]);
    }
}
