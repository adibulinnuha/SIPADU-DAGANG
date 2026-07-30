<?php
require __DIR__ . '/vendor/autoload.php';

$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load('c:/laragon/www/sipadu-dagang/storage/app/templates/ERET JULI.xltx');
$sheet = $spreadsheet->getSheetByName('01 Juli');

echo "=== TEMPLATE STRUCTURE (Column A) ===\n";
for ($row = 1; $row <= 50; $row++) {
    $val = $sheet->getCell('A' . $row)->getCalculatedValue();
    if ($val !== null && trim($val) !== '') {
        $isF = $sheet->getCell('F' . $row)->isFormula() ? 'FORMULA' : 'VALUE';
        $fVal = $sheet->getCell('F' . $row)->getValue();
        echo str_pad($row, 2, ' ', STR_PAD_LEFT) . ' | A=' . str_pad(trim($val), 30) . ' | F=' . $isF;
        if ($isF === 'FORMULA') echo ' (' . $fVal . ')';
        echo PHP_EOL;
    }
}

echo "\n=== COLUMN B-E FORMULAS (Subtotal rows) ===\n";
$checkCols = ['B', 'C', 'D', 'E'];
$checkRows = [16, 34, 44, 48];
foreach ($checkRows as $row) {
    foreach ($checkCols as $col) {
        $cell = $col . $row;
        $isF = $sheet->getCell($cell)->isFormula() ? 'FORMULA' : 'VALUE';
        $val = $sheet->getCell($cell)->getValue();
        echo "$cell: $isF";
        if ($isF === 'FORMULA') echo " ($val)";
        echo PHP_EOL;
    }
}

echo "\n=== LISTRIK & MCK ===\n";
for ($row = 40; $row <= 50; $row++) {
    $aVal = $sheet->getCell('A' . $row)->getCalculatedValue();
    if ($aVal !== null && trim($aVal) !== '') {
        $fIsF = $sheet->getCell('F' . $row)->isFormula() ? 'FORMULA' : 'VALUE';
        $fVal = $sheet->getCell('F' . $row)->getValue();
        echo "Row $row: A=" . trim($aVal) . " | B=" . $sheet->getCell('B'.$row)->getValue() . " | F=$fIsF";
        if ($fIsF === 'FORMULA') echo " ($fVal)";
        echo PHP_EOL;
    }
}

$spreadsheet->disconnectWorksheets();

