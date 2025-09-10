<?php

require 'vendor/autoload.php';

use Smalot\PdfParser\Parser;

function extract_text($filePath, $extension) {
    if ($extension === 'txt') {
        return file_get_contents($filePath);
    }

    if ($extension === 'pdf') {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        return $pdf->getText();
    }

    return '';
}

function index_document($pdo, $document_id, $file_path)
{
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    if ($extension === 'txt') {
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            error_log("Could not open file for indexing: $file_path");
            return;
        }

        $position = 0;
        $batch = [];

        while (!feof($handle)) {
            $chunk = fread($handle, 4096);
            $tokens = tokenize($chunk);

            foreach ($tokens as $token) {
                $token = strtolower($token);
                $position++;

                // Collect batch inserts
                $batch[] = [$document_id, $token, 1, $position];

                // Insert in batches of 1000
                if (count($batch) >= 1000) {
                    insert_tokens_batch($pdo, $batch);
                    $batch = [];
                }
            }
        }

        fclose($handle);

        // Insert remainder
        if (!empty($batch)) {
            insert_tokens_batch($pdo, $batch);
        }

        return;
    }

    // PDF fallback
    $content = extract_text($file_path, $extension);
    index_tokens_from_content($pdo, $document_id, $content);
}

function index_tokens_from_content($pdo, $document_id, $content) {
    $tokens = tokenize($content);

    $position = 0;
    $batch = [];

    foreach ($tokens as $token) {
        $token = strtolower($token);
        $position++;

        $batch[] = [$document_id, $token, 1, $position];

        if (count($batch) >= 1000) {
            insert_tokens_batch($pdo, $batch);
            $batch = [];
        }
    }

    if (!empty($batch)) {
        insert_tokens_batch($pdo, $batch);
    }
}

