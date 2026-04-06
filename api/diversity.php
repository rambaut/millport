<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
if ($year < 1900 || $year > 2100) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid year']);
    exit;
}

try {
    $pdo = get_pdo();

    // Overall totals for the year
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT species_id) AS total_species,
               COUNT(id)                 AS total_idents
        FROM identifications
        WHERE year = ?
    ");
    $stmt->execute([$year]);
    $totals = $stmt->fetch();

    // Distinct species and identification counts per phylum
    $stmt = $pdo->prepare("
        SELECT p.id   AS phylum_id,
               p.name AS phylum,
               p.is_plant,
               COUNT(DISTINCT i.species_id) AS species_count,
               COUNT(i.id)                  AS ident_count
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        JOIN phyla   p ON s.phylum_id  = p.id
        WHERE i.year = ?
        GROUP BY p.id, p.name, p.is_plant
        ORDER BY species_count DESC
    ");
    $stmt->execute([$year]);
    $by_phylum = $stmt->fetchAll();

    // Distinct species and identification counts per class
    $stmt = $pdo->prepare("
        SELECT COALESCE(s.class, 'Unknown') AS class,
               p.id   AS phylum_id,
               p.name AS phylum,
               COUNT(DISTINCT i.species_id) AS species_count,
               COUNT(i.id)                  AS ident_count
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        JOIN phyla   p ON s.phylum_id  = p.id
        WHERE i.year = ?
        GROUP BY s.class, p.id, p.name
        ORDER BY p.name, species_count DESC
    ");
    $stmt->execute([$year]);
    $by_class = $stmt->fetchAll();

    // Top 20 genera
    $stmt = $pdo->prepare("
        SELECT s.genus,
               p.id   AS phylum_id,
               p.name AS phylum,
               COUNT(DISTINCT i.species_id) AS species_count,
               COUNT(i.id)                  AS ident_count
        FROM identifications i
        JOIN species      s ON i.species_id  = s.id
        LEFT JOIN phyla   p ON s.phylum_id   = p.id
        WHERE i.year = ?
        GROUP BY s.genus, p.id, p.name
        ORDER BY species_count DESC
        LIMIT 20
    ");
    $stmt->execute([$year]);
    $by_genus = $stmt->fetchAll();

    // Years that have at least one identification (for year selector)
    $stmt = $pdo->query("
        SELECT DISTINCT year AS y
        FROM identifications
        WHERE year > 0
        ORDER BY y DESC
    ");
    $available_years = array_column($stmt->fetchAll(), 'y');

    echo json_encode([
        'year'            => $year,
        'totals'          => $totals,
        'by_phylum'       => $by_phylum,
        'by_class'        => $by_class,
        'by_genus'        => $by_genus,
        'available_years' => $available_years,
    ], JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
