<?php

namespace App\Support;

use RuntimeException;

/**
 * Minimal single-sheet XLSX writer with no extension dependencies — the ZIP
 * container is assembled by hand using stored (uncompressed) entries, so it
 * works even where ext-zip is unavailable. Enough for tabular exports without
 * pulling in a spreadsheet package. All cells are written as inline strings
 * except int/float values, which become numeric cells.
 */
class SimpleXlsxWriter
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<list<string|int|float|null>>  $rows
     */
    public function write(string $path, array $headings, iterable $rows, string $sheetName = 'Export'): void
    {
        $archive = $this->buildZip([
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->relsXml(),
            'xl/workbook.xml' => $this->workbookXml($sheetName),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $this->sheetXml($headings, $rows),
        ]);

        if (file_put_contents($path, $archive) === false) {
            throw new RuntimeException("Unable to write XLSX file at {$path}.");
        }
    }

    /**
     * Assemble a ZIP archive with stored (method 0) entries.
     *
     * @param  array<string, string>  $files  name => content
     */
    private function buildZip(array $files): string
    {
        $local = '';
        $central = '';
        // DOS date/time for "now" — precision beyond minutes is irrelevant here.
        $dosTime = (date('H') << 11) | (date('i') << 5) | ((int) (date('s') / 2));
        $dosDate = (max(0, date('Y') - 1980) << 9) | (date('n') << 5) | date('j');

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $offset = strlen($local);

            $header = pack('vvvvvVVVvv', 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, strlen($name), 0);

            $local .= "PK\x03\x04".$header.$name.$content;

            $central .= "PK\x01\x02"
                .pack('v', 20)
                .$header
                .pack('vvvVV', 0, 0, 0, 32, $offset)
                .$name;
        }

        $endOfCentralDirectory = "PK\x05\x06".pack(
            'vvvvVVv',
            0,
            0,
            count($files),
            count($files),
            strlen($central),
            strlen($local),
            0,
        );

        return $local.$central.$endOfCentralDirectory;
    }

    /**
     * @param  list<string>  $headings
     * @param  iterable<list<string|int|float|null>>  $rows
     */
    private function sheetXml(array $headings, iterable $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= $this->rowXml(1, $headings, header: true);

        $rowIndex = 2;
        foreach ($rows as $row) {
            $xml .= $this->rowXml($rowIndex++, $row);
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  list<string|int|float|null>  $cells
     */
    private function rowXml(int $rowIndex, array $cells, bool $header = false): string
    {
        $xml = "<row r=\"{$rowIndex}\">";

        foreach (array_values($cells) as $columnIndex => $value) {
            $ref = $this->columnLetter($columnIndex).$rowIndex;
            $style = $header ? ' s="1"' : '';

            if (is_int($value) || is_float($value)) {
                $xml .= "<c r=\"{$ref}\"{$style}><v>{$value}</v></c>";
            } else {
                $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= "<c r=\"{$ref}\"{$style} t=\"inlineStr\"><is><t xml:space=\"preserve\">{$escaped}</t></is></c>";
            }
        }

        return $xml.'</row>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(string $sheetName): string
    {
        $name = htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            ."<sheets><sheet name=\"{$name}\" sheetId=\"1\" r:id=\"rId1\"/></sheets></workbook>";
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            .'<cellXfs count="2"><xf xfId="0"/><xf xfId="0" fontId="1" applyFont="1"/></cellXfs>'
            .'</styleSheet>';
    }
}
