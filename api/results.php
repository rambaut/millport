<?php
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Validated constants
const MIN_YEAR = 2010;
const MAX_YEAR = 2100;
const ALGAE_PHYLA = ['Rhodophyta', 'Heterokontophyta', 'Chlorophyta'];
const NAMED_SITES = ['White Bay', 'Ballochmartin Bay', 'Farland Point'];

$current_year = (int)date('Y');

try {
    $pdo = get_pdo();

    // ── 1. Current year summary ──────────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT i.species_id)                    AS total_species,
            COUNT(i.id)                                     AS total_idents,
            COUNT(DISTINCT s.class)                         AS total_classes,
            COUNT(DISTINCT s.phylum_id)                     AS total_phyla
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        WHERE i.year = ?
    ");
    $stmt->execute([$current_year]);
    $current_summary = $stmt->fetch();
    $current_summary['year'] = $current_year;

    // Shannon H' for current year (phylum level, species counts)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT i.species_id) AS n
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        WHERE i.year = ?
        GROUP BY s.phylum_id
    ");
    $stmt->execute([$current_year]);
    $phylum_counts = array_column($stmt->fetchAll(), 'n');
    $current_summary['shannon_h'] = shannon_h($phylum_counts);
    $current_summary['pielou_j']  = pielou_j($phylum_counts);

    // ── 2. Current year phylum table ────────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT p.name AS phylum, p.is_plant,
               COUNT(DISTINCT i.species_id) AS species_count,
               COUNT(i.id) AS ident_count
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        JOIN phyla   p ON s.phylum_id  = p.id
        WHERE i.year = ?
        GROUP BY p.id, p.name, p.is_plant
        ORDER BY species_count DESC
    ");
    $stmt->execute([$current_year]);
    $current_phyla = $stmt->fetchAll();
    $total_sp = array_sum(array_column($current_phyla, 'species_count'));
    foreach ($current_phyla as &$row) {
        $row['proportion'] = $total_sp > 0 ? round($row['species_count'] / $total_sp, 4) : 0;
    }
    unset($row);

    // ── 3. Per-year stats (2010–present) ────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            i.year,
            COUNT(DISTINCT i.species_id)  AS total_species,
            COUNT(i.id)                   AS total_idents,
            COUNT(DISTINCT s.class)       AS total_classes,
            COUNT(DISTINCT s.phylum_id)   AS total_phyla,
            y.class_size,
            y.tides_at_sampling          AS tide_height,
            y.weather
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        LEFT JOIN years y ON y.year = i.year
        WHERE i.year >= ? AND i.year <= ?
        GROUP BY i.year, y.class_size, y.tides_at_sampling, y.weather
        ORDER BY i.year ASC
    ");
    $stmt->execute([MIN_YEAR, MAX_YEAR]);
    $year_stats = $stmt->fetchAll();

    // Add Shannon H' per year
    foreach ($year_stats as &$ys) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            WHERE i.year = ?
            GROUP BY s.phylum_id
        ");
        $stmt2->execute([$ys['year']]);
        $pc = array_column($stmt2->fetchAll(), 'n');
        $ys['shannon_h'] = shannon_h($pc);
        $ys['pielou_j']  = pielou_j($pc);
    }
    unset($ys);

    // ── 4. Stacked phylum proportions per year (2010–present) ───────────
    // Also compute "all years combined"
    $stmt = $pdo->prepare("
        SELECT i.year, p.name AS phylum,
               COUNT(DISTINCT i.species_id) AS species_count
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        JOIN phyla   p ON s.phylum_id  = p.id
        WHERE i.year >= ? AND i.year <= ?
        GROUP BY i.year, p.id, p.name
        ORDER BY i.year ASC, species_count DESC
    ");
    $stmt->execute([MIN_YEAR, MAX_YEAR]);
    $stacked_raw = $stmt->fetchAll();

    // All years combined
    $stmt = $pdo->prepare("
        SELECT p.name AS phylum,
               COUNT(DISTINCT i.species_id) AS species_count
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        JOIN phyla   p ON s.phylum_id  = p.id
        WHERE i.year >= ? AND i.year <= ?
        GROUP BY p.id, p.name
        ORDER BY species_count DESC
    ");
    $stmt->execute([MIN_YEAR, MAX_YEAR]);
    $combined_phyla = $stmt->fetchAll();

    // ── 5. Class diversity per site per year (non-algae phyla) ──────────
    $named_sites_placeholders = implode(',', array_fill(0, count(NAMED_SITES), '?'));
    $algae_placeholders       = implode(',', array_fill(0, count(ALGAE_PHYLA), '?'));

    $stmt = $pdo->prepare("
        SELECT i.year, si.name AS site,
               COUNT(DISTINCT s.class)       AS total_classes,
               COUNT(DISTINCT i.species_id)  AS total_species,
               COUNT(DISTINCT s.phylum_id)   AS total_phyla
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND p.name  NOT IN ($algae_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY i.year, si.name
        ORDER BY i.year ASC, si.name ASC
    ");
    $params = array_merge(NAMED_SITES, ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]);
    $stmt->execute($params);
    $site_diversity_raw = $stmt->fetchAll();

    // Add Shannon H' per site per year (non-algae)
    foreach ($site_diversity_raw as &$sd) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            JOIN phyla   p ON s.phylum_id  = p.id
            JOIN sites   si ON i.site      = si.id
            WHERE si.name = ? AND i.year = ?
              AND p.name NOT IN ($algae_placeholders)
            GROUP BY s.phylum_id
        ");
        $p2 = array_merge([$sd['site'], $sd['year']], ALGAE_PHYLA);
        $stmt2->execute($p2);
        $pc = array_column($stmt2->fetchAll(), 'n');
        $sd['shannon_h'] = shannon_h($pc);
    }
    unset($sd);

    // Combined across sites per year (non-algae)
    $stmt = $pdo->prepare("
        SELECT i.year,
               COUNT(DISTINCT s.class)       AS total_classes,
               COUNT(DISTINCT i.species_id)  AS total_species,
               COUNT(DISTINCT s.phylum_id)   AS total_phyla
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND p.name  NOT IN ($algae_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY i.year
        ORDER BY i.year ASC
    ");
    $stmt->execute(array_merge(NAMED_SITES, ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
    $site_diversity_combined = $stmt->fetchAll();

    foreach ($site_diversity_combined as &$sd) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            JOIN phyla   p ON s.phylum_id  = p.id
            JOIN sites   si ON i.site      = si.id
            WHERE i.year = ?
              AND si.name IN ($named_sites_placeholders)
              AND p.name NOT IN ($algae_placeholders)
            GROUP BY s.phylum_id
        ");
        $p2 = array_merge([$sd['year']], NAMED_SITES, ALGAE_PHYLA);
        $stmt2->execute($p2);
        $pc = array_column($stmt2->fetchAll(), 'n');
        $sd['shannon_h'] = shannon_h($pc);
    }
    unset($sd);

    // ── 6. Algae site diversity per year ────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT i.year, si.name AS site,
               COUNT(DISTINCT s.class)       AS total_classes,
               COUNT(DISTINCT i.species_id)  AS total_species
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND p.name  IN ($algae_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY i.year, si.name
        ORDER BY i.year ASC, si.name ASC
    ");
    $stmt->execute(array_merge(NAMED_SITES, ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
    $algae_site_raw = $stmt->fetchAll();

    foreach ($algae_site_raw as &$ad) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            JOIN phyla   p ON s.phylum_id  = p.id
            JOIN sites   si ON i.site      = si.id
            WHERE si.name = ? AND i.year = ?
              AND p.name IN ($algae_placeholders)
            GROUP BY s.phylum_id
        ");
        $p2 = array_merge([$ad['site'], $ad['year']], ALGAE_PHYLA);
        $stmt2->execute($p2);
        $pc = array_column($stmt2->fetchAll(), 'n');
        $ad['shannon_h'] = shannon_h($pc);
    }
    unset($ad);

    // Combined algae across sites per year
    $stmt = $pdo->prepare("
        SELECT i.year,
               COUNT(DISTINCT s.class)       AS total_classes,
               COUNT(DISTINCT i.species_id)  AS total_species
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND p.name  IN ($algae_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY i.year
        ORDER BY i.year ASC
    ");
    $stmt->execute(array_merge(NAMED_SITES, ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
    $algae_combined = $stmt->fetchAll();

    foreach ($algae_combined as &$ad) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            JOIN phyla   p ON s.phylum_id  = p.id
            JOIN sites   si ON i.site      = si.id
            WHERE i.year = ?
              AND si.name IN ($named_sites_placeholders)
              AND p.name IN ($algae_placeholders)
            GROUP BY s.phylum_id
        ");
        $p2 = array_merge([$ad['year']], NAMED_SITES, ALGAE_PHYLA);
        $stmt2->execute($p2);
        $pc = array_column($stmt2->fetchAll(), 'n');
        $ad['shannon_h'] = shannon_h($pc);
    }
    unset($ad);

    // ── 7. Collectors curve data ─────────────────────────────────────────
    // All identifications in current year ordered by timestamp
    $stmt = $pdo->prepare("
        SELECT i.id, i.time, i.species_id, s.class
        FROM identifications i
        JOIN species s ON i.species_id = s.id
        WHERE i.year = ?
        ORDER BY i.time ASC, i.id ASC
    ");
    $stmt->execute([$current_year]);
    $ident_ordered = $stmt->fetchAll();

    $collectors = [];
    $seen_species = [];
    $seen_classes = [];
    foreach ($ident_ordered as $row) {
        $seen_species[$row['species_id']] = true;
        $seen_classes[$row['class']]      = true;
        $collectors[] = [
            'ident_n'  => count($collectors) + 1,
            'species'  => count($seen_species),
            'classes'  => count($seen_classes),
            'time'     => $row['time'],
        ];
    }

    // ── 8. Per-site totals across all years (non-algae) ─────────────────
    $stmt = $pdo->prepare("
        SELECT si.name AS site,
               COUNT(DISTINCT i.species_id)  AS total_species,
               COUNT(DISTINCT s.class)       AS total_classes
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND p.name  NOT IN ($algae_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY si.name
        ORDER BY si.name ASC
    ");
    $stmt->execute(array_merge(NAMED_SITES, ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
    $site_totals_raw = $stmt->fetchAll();

    foreach ($site_totals_raw as &$st) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            JOIN phyla   p ON s.phylum_id  = p.id
            JOIN sites   si ON i.site      = si.id
            WHERE si.name = ?
              AND p.name NOT IN ($algae_placeholders)
              AND i.year >= ? AND i.year <= ?
            GROUP BY s.phylum_id
        ");
        $stmt2->execute(array_merge([$st['site']], ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
        $pc = array_column($stmt2->fetchAll(), 'n');
        $st['shannon_h'] = shannon_h($pc);
    }
    unset($st);

    // ── 9. Per-site totals across all years (algae) ───────────────────────
    $stmt = $pdo->prepare("
        SELECT si.name AS site,
               COUNT(DISTINCT i.species_id)  AS total_species,
               COUNT(DISTINCT s.class)       AS total_classes
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND p.name  IN ($algae_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY si.name
        ORDER BY si.name ASC
    ");
    $stmt->execute(array_merge(NAMED_SITES, ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
    $algae_site_totals_raw = $stmt->fetchAll();

    foreach ($algae_site_totals_raw as &$st) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT i.species_id) AS n
            FROM identifications i
            JOIN species s ON i.species_id = s.id
            JOIN phyla   p ON s.phylum_id  = p.id
            JOIN sites   si ON i.site      = si.id
            WHERE si.name = ?
              AND p.name IN ($algae_placeholders)
              AND i.year >= ? AND i.year <= ?
            GROUP BY s.phylum_id
        ");
        $stmt2->execute(array_merge([$st['site']], ALGAE_PHYLA, [MIN_YEAR, MAX_YEAR]));
        $pc = array_column($stmt2->fetchAll(), 'n');
        $st['shannon_h'] = shannon_h($pc);
    }
    unset($st);

    // ── Output ──────────────────────────────────────────────────────────

    // ── 10. Species and class counts per site per phylum (all years) ─────
    $stmt = $pdo->prepare("
        SELECT si.name AS site, p.name AS phylum,
               COUNT(DISTINCT i.species_id) AS total_species,
               COUNT(DISTINCT s.class)      AS total_classes
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY si.name, p.id, p.name
        ORDER BY si.name ASC, total_species DESC
    ");
    $stmt->execute(array_merge(NAMED_SITES, [MIN_YEAR, MAX_YEAR]));
    $phylum_by_site_raw = $stmt->fetchAll();

    // Compute Shannon H′ over classes-within-phylum per site
    $stmt = $pdo->prepare("
        SELECT si.name AS site, p.name AS phylum, s.class,
               COUNT(DISTINCT i.species_id) AS n
        FROM identifications i
        JOIN species s  ON i.species_id = s.id
        JOIN phyla   p  ON s.phylum_id  = p.id
        JOIN sites   si ON i.site       = si.id
        WHERE si.name IN ($named_sites_placeholders)
          AND i.year >= ? AND i.year <= ?
        GROUP BY si.name, p.id, p.name, s.class
    ");
    $stmt->execute(array_merge(NAMED_SITES, [MIN_YEAR, MAX_YEAR]));
    // Group counts by site+phylum key
    $class_counts_by_site_phylum = [];
    foreach ($stmt->fetchAll() as $row) {
        $key = $row['site'] . '|||' . $row['phylum'];
        $class_counts_by_site_phylum[$key][] = (int)$row['n'];
    }
    // Attach shannon_h to each phylum_by_site row
    foreach ($phylum_by_site_raw as &$pbsr) {
        $key = $pbsr['site'] . '|||' . $pbsr['phylum'];
        $counts = $class_counts_by_site_phylum[$key] ?? [];
        $pbsr['class_shannon_h'] = shannon_h($counts);
    }
    unset($pbsr);

    echo json_encode([
        'current_year'          => $current_year,
        'current_summary'       => $current_summary,
        'current_phyla'         => $current_phyla,
        'year_stats'            => $year_stats,
        'stacked_by_year'       => $stacked_raw,
        'combined_phyla'        => $combined_phyla,
        'site_diversity'        => $site_diversity_raw,
        'site_diversity_combined' => $site_diversity_combined,
        'site_totals'             => $site_totals_raw,
        'algae_site_diversity'    => $algae_site_raw,
        'algae_combined'          => $algae_combined,
        'algae_site_totals'       => $algae_site_totals_raw,
        'phylum_by_site'          => $phylum_by_site_raw,
        'collectors'            => $collectors,
    ], JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

// ── Diversity helpers ────────────────────────────────────────────────────
function shannon_h(array $counts): float {
    $total = array_sum($counts);
    if ($total === 0) return 0.0;
    $h = 0.0;
    foreach ($counts as $c) {
        if ($c > 0) {
            $p  = $c / $total;
            $h -= $p * log($p);
        }
    }
    return round($h, 4);
}

function pielou_j(array $counts): float {
    $s = count(array_filter($counts, fn($c) => $c > 0));
    if ($s <= 1) return 1.0;
    $h = shannon_h($counts);
    return round($h / log($s), 4);
}
