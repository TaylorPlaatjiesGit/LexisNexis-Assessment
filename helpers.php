<?php

function highlightTerms($text, $terms)
{
    foreach ($terms as $term) {
        $escaped = preg_quote($term, '/');
        $text = preg_replace("/($escaped)/i", '<mark>$1</mark>', $text);
    }
    return $text;
}

function insert_tokens_batch($pdo, $batch)
{
    $values = [];
    $placeholders = [];

    foreach ($batch as [$docId, $keyword, $freq, $pos]) {
        $placeholders[] = '(?, ?, ?, ?)';
        $values[] = $docId;
        $values[] = $keyword;
        $values[] = $freq;
        $values[] = $pos;
    }

    $sql = "INSERT INTO search_index (document_id, keyword, frequency, position)
            VALUES " . implode(',', $placeholders);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

function tokenize($text)
{
    return preg_split('/\W+/', $text, -1, PREG_SPLIT_NO_EMPTY);
}
