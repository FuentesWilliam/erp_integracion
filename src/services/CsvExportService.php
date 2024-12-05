<?php
class CsvExportService
{
    public function exportNotFoundProducts($stockData)
    {
        $notFoundProducts = array_filter($stockData, function ($product) {
            return isset($product['encontrado']) && $product['encontrado'] == 0;
        });

        $csvFile = tempnam(sys_get_temp_dir(), 'no_encontrados_') . '.csv';
        $fileHandle = fopen($csvFile, 'w');
        fputcsv($fileHandle, ['Referencia']);

        foreach ($notFoundProducts as $product) {
            fputcsv($fileHandle, [
                $product['reference']
            ]);
        }

        fclose($fileHandle);
        return $csvFile;
    }

    public function downloadCsv($csvFile)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="productos_no_encontrados.csv"');
        header('Content-Length: ' . filesize($csvFile));

        readfile($csvFile);
        unlink($csvFile);
        exit;
    }
}

