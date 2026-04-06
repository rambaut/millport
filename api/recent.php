<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 1900 || $year > 2100) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid year']);
    exit;
}

// since_id: only return rows with id > this value (used for live polling)
$since_id = null;
if (isset($_GET['since_id'])) {
    $v = (int)$_GET['since_id'];
    $since_id = $v >= 0 ? $v : 0;
}

// limit: 1–100, default 50
$limit = 50;
if (isset($_GET['limit'])) {
    $limit = min(max((int)$_GET['limit'], 1), 100);
}

try {
    $pdo = get_pdo();

    $sql = "
        SELECT i.id,
               i.time,
               i.year,
               TRIM(CONCAT(s.genus, ' ', COALESCE(s.species, ''))) AS binomial,
               s.name    AS common_name,
               s.class,
               p.name    AS phylum,
               si.name   AS site_name,
               i.identified_by,
               i.corroborated_by,
               i.laboratory,
               i.notes
        FROM identifications i
        JOIN  species s  ON i.species_id = s.id
        LEFT JOIN phyla   p  ON s.phylum_id  = p.id
        LEFT JOIN sites   si ON i.site       = si.id
        WHERE i.year = ?
    ";
    $params = [$year];

    if ($since_id !== null) {
        $sql    .= ' AND i.id > ?';
        $params[] = $since_id;
    }

    // LIMIT is a validated integer — embed directly to avoid PARAM_INT binding edge cases
    $sql .= ' ORDER BY i.id DESC LIMIT ' . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode($rows, JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
