<?php
require_once 'database.php';
require_once 'helpers.php';
require_once 'SearchIndexer.php';

class DocumentController
{
    public static function upload()
    {
        global $pdo;

        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'File is required']);
            return;
        }

        $file = $_FILES['file'];
        $title = $_POST['title'] ?? $file['name'];
        $description = $_POST['description'] ?? '';
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        $allowed = ['txt', 'pdf'];
        if (!in_array($extension, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Only .txt and .pdf files are allowed']);
            return;
        }

        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uniqueName = uniqid() . '.' . $extension;
        $savePath = $uploadDir . '/' . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $savePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save file']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO documents (title, description, path) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $savePath]);
        $docId = $pdo->lastInsertId();

        index_document($pdo, $docId, $savePath);

        echo json_encode(['success' => true, 'id' => $docId]);
    }

    public static function list()
    {
        global $pdo;

        $limit = isset($_GET['pageSize']) ? (int)$_GET['pageSize'] : (isset($_GET['limit']) ? (int)$_GET['limit'] : 10);
        $pageIndex = isset($_GET['pageIndex']) ? (int)$_GET['pageIndex'] : 0;
        $offset = $pageIndex * $limit;

        $sql = "SELECT * FROM documents LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $pdo->query("SELECT COUNT(*) FROM documents");
        $totalCount = $countStmt->fetchColumn();

        echo json_encode([
            'documents' => $documents,
            'totalCount' => (int) $totalCount,
        ]);
    }

    public static function get($id)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            return;
        }

        echo json_encode($doc);
    }

    public static function delete($id)
    {
        global $pdo;

        // Get file path before deletion so that we can delete the file too
        $stmt = $pdo->prepare("SELECT path FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            http_response_code(404);
            echo json_encode(['error' => 'Document not found']);
            return;
        }

        $pdo->prepare("DELETE FROM documents WHERE id = ?")->execute([$id]);

        // Delete file
        $filePath = __DIR__ . '/uploads/' . $doc['path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        echo json_encode(['success' => true]);
    }

    public static function search()
    {
        global $pdo;

        $cacheDir = __DIR__ . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $query = $_GET['query'] ?? '';
        $tokens = preg_split('/\s+/', strtolower(trim($query)));
        $placeholders = implode(',', array_fill(0, count($tokens), '?'));

        // Cache key from query
        $cacheKey = md5($query);
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

        if (file_exists($cacheFile) && filemtime($cacheFile) > (time() - 600)) {
            $cachedContent = file_get_contents($cacheFile);

            if (!empty($cachedContent)) {
                echo $cachedContent;
                return;
            }
        }

        $sql = "
            SELECT d.id, d.title, d.description, d.path, SUM(s.frequency) AS relevance
            FROM documents d
            JOIN search_index s ON d.id = s.document_id
            WHERE s.keyword IN ($placeholders)
            GROUP BY d.id
            ORDER BY relevance DESC
            LIMIT 20
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($tokens);

        $results = $stmt->fetchAll();

        foreach ($results as &$result) {
            $result['title'] = highlightTerms($result['title'], $tokens);
            $result['description'] = highlightTerms($result['description'], $tokens);
        }

        if (count($results) > 0) {
            file_put_contents($cacheFile, json_encode($results));
        }

        echo json_encode($results);
    }
}