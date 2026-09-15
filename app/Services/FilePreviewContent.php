<?php

namespace App\Services;

use DOMDocument;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use ZipArchive;

class FilePreviewContent
{
    public function text(string $binary, string $filename): ?string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! in_array($extension, ['docx', 'xls', 'xlsx'], true) || strlen($binary) > 15 * 1024 * 1024) {
            return null;
        }
        $path = tempnam(sys_get_temp_dir(), 'simpram-preview-');
        if ($path === false) {
            return null;
        }
        try {
            file_put_contents($path, $binary);
            if (in_array($extension, ['docx', 'xlsx'], true)) {
                $zip = new ZipArchive;
                if ($zip->open($path) !== true) {
                    return null;
                }
                try {
                    $expandedSize = 0;
                    for ($index = 0; $index < $zip->numFiles; $index++) {
                        $expandedSize += $zip->statIndex($index)['size'];
                        if ($expandedSize > 30 * 1024 * 1024) {
                            return null;
                        }
                    }
                    if ($extension === 'docx') {
                        $xml = $zip->getFromName('word/document.xml');
                        if ($xml === false || str_contains(strtoupper($xml), '<!DOCTYPE')) {
                            return null;
                        }
                        $document = new DOMDocument;
                        if (! @$document->loadXML($xml, LIBXML_NONET)) {
                            return null;
                        }
                        $paragraphs = [];
                        foreach ($document->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p') as $paragraph) {
                            $paragraphs[] = $paragraph->textContent;
                        }

                        return mb_substr(implode("\n\n", $paragraphs), 0, 200000);
                    }
                } finally {
                    $zip->close();
                }
            }
            $reader = IOFactory::createReader($extension === 'xlsx' ? 'Xlsx' : 'Xls');
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new class implements IReadFilter
            {
                public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
                {
                    return $row <= 200 && Coordinate::columnIndexFromString($columnAddress) <= 52;
                }
            });
            $sheetNames = $reader->listWorksheetNames($path);
            if ($sheetNames === []) {
                return null;
            }
            $reader->setLoadSheetsOnly([$sheetNames[0]]);
            $spreadsheet = $reader->load($path);
            try {
                $lines = [$sheetNames[0]];
                foreach ($spreadsheet->getSheet(0)->rangeToArray('A1:AZ200', null, false, false) as $row) {
                    $line = rtrim(implode("\t", array_map(fn (mixed $cell): string => (string) $cell, $row)));
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                }

                return mb_substr(implode("\n", $lines), 0, 200000);
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        } catch (\Throwable) {
            return null;
        } finally {
            unlink($path);
        }
    }
}
