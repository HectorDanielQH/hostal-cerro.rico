<?php

namespace App\Support;

class SimpleXlsxWriter
{
    /**
     * @param  array<int, array{title: string, rows: array<int, array<int, mixed>>, columns?: array<int, float>, merges?: array<int, string>}>  $sheets
     */
    public function make(array $sheets): string
    {
        $files = [
            '[Content_Types].xml' => $this->contentTypes(count($sheets)),
            '_rels/.rels' => $this->rootRelationships(),
            'xl/workbook.xml' => $this->workbook($sheets),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(count($sheets)),
            'xl/styles.xml' => $this->styles(),
        ];

        foreach ($sheets as $index => $sheet) {
            $files['xl/worksheets/sheet'.($index + 1).'.xml'] = $this->worksheet(
                $sheet['rows'],
                $sheet['columns'] ?? [],
                $sheet['merges'] ?? []
            );
        }

        return $this->zip($files);
    }

    private function contentTypes(int $sheetCount): string
    {
        $sheetOverrides = '';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$sheetOverrides
            .'</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, array{title: string}>  $sheets
     */
    private function workbook(array $sheets): string
    {
        $sheetNodes = '';

        foreach ($sheets as $index => $sheet) {
            $id = $index + 1;
            $sheetNodes .= '<sheet name="'.$this->xml($sheet['title']).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetNodes.'</sheets>'
            .'</workbook>';
    }

    private function workbookRelationships(int $sheetCount): string
    {
        $relationships = '';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFD9EAF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FF8EA9C1"/></left><right style="thin"><color rgb="FF8EA9C1"/></right><top style="thin"><color rgb="FF8EA9C1"/></top><bottom style="thin"><color rgb="FF8EA9C1"/></bottom><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment wrapText="1" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, float>  $columns
     * @param  array<int, string>  $merges
     */
    private function worksheet(array $rows, array $columns, array $merges): string
    {
        $maxColumn = max(1, collect($rows)->map(fn (array $row): int => count($row))->max() ?? 1);
        $dimension = 'A1:'.$this->columnName($maxColumn).max(count($rows), 1);
        $columnXml = $this->columns($columns);
        $rowXml = '';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $height = $rowNumber === 1 ? ' ht="42" customHeight="1"' : '';
            $rowXml .= '<row r="'.$rowNumber.'"'.$height.'>';

            foreach (array_values($row) as $columnIndex => $value) {
                $cell = $this->columnName($columnIndex + 1).$rowNumber;
                $style = $rowNumber === 1 ? 1 : 2;
                $rowXml .= '<c r="'.$cell.'" t="inlineStr" s="'.$style.'"><is><t>'.$this->xml($this->cellValue($value)).'</t></is></c>';
            }

            $rowXml .= '</row>';
        }

        $mergeXml = '';

        if ($merges !== []) {
            $mergeXml = '<mergeCells count="'.count($merges).'">';

            foreach ($merges as $merge) {
                $mergeXml .= '<mergeCell ref="'.$this->xml($merge).'"/>';
            }

            $mergeXml .= '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .$columnXml
            .'<sheetData>'.$rowXml.'</sheetData>'
            .$mergeXml
            .'</worksheet>';
    }

    /**
     * @param  array<int, float>  $columns
     */
    private function columns(array $columns): string
    {
        if ($columns === []) {
            return '';
        }

        $xml = '<cols>';

        foreach ($columns as $column => $width) {
            $xml .= '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private function cellValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * Creates an XLSX-compatible ZIP without relying on PHP ext-zip.
     *
     * @param  array<string, string>  $files
     */
    private function zip(array $files): string
    {
        $localFiles = '';
        $centralDirectory = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $dateTime = $this->dosDateTime();
            $nameLength = strlen($name);

            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dateTime['time'], $dateTime['date'], $crc, $size, $size, $nameLength, 0).$name;
            $localFiles .= $localHeader.$content;

            $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dateTime['time'], $dateTime['date'], $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset).$name;
            $offset += strlen($localHeader) + $size;
        }

        $centralSize = strlen($centralDirectory);
        $centralOffset = strlen($localFiles);
        $count = count($files);

        return $localFiles.$centralDirectory.pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0);
    }

    /**
     * @return array{date: int, time: int}
     */
    private function dosDateTime(): array
    {
        $now = getdate();

        return [
            'date' => (($now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'],
            'time' => ($now['hours'] << 11) | ($now['minutes'] << 5) | intdiv($now['seconds'], 2),
        ];
    }
}
