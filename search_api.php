<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

const RELEVANCE_FLOOR = 0.35;
const MAX_RESULTS = 100;
const CANDIDATE_LIMIT = 500;
const MAX_LIKE_PATTERNS = 60;
const ALIGN_SLACK = 1;
const ANCHOR_FLOOR = 0.3;

function normalize_text(string $text): string {
    if (class_exists('Normalizer')) {
        $normalized = Normalizer::normalize($text, Normalizer::FORM_C);
        if ($normalized !== false) $text = $normalized;
    }
    return trim(preg_replace('/\s+/u', ' ', $text));
}

function mb_word_chars(string $word): array {
    return mb_str_split($word, 1, 'UTF-8');
}

function mb_edit_distance(array $a, array $b): int {
    $lenA = count($a);
    $lenB = count($b);
    if ($lenA === 0) return $lenB;
    if ($lenB === 0) return $lenA;
    $prev = range(0, $lenB);
    for ($i = 1; $i <= $lenA; $i++) {
        $curr = [$i];
        for ($j = 1; $j <= $lenB; $j++) {
            $cost = ($a[$i - 1] === $b[$j - 1]) ? 0 : 1;
            $curr[$j] = min(
                $prev[$j] + 1,
                $curr[$j - 1] + 1,
                $prev[$j - 1] + $cost
            );
        }
        $prev = $curr;
    }
    return $prev[$lenB];
}

// Character-level similarity (not byte-level: PHP's built-in levenshtein()
// operates on raw bytes, which corrupts multi-byte UTF-8 scripts like
// Devanagari — a one-character OCR difference could count as 2-3 edits).
function char_similarity(array $charsA, array $charsB): float {
    $maxLen = max(count($charsA), count($charsB));
    if ($maxLen === 0) return 1.0;
    return max(0.0, 1 - mb_edit_distance($charsA, $charsB) / $maxLen);
}

function tokenize(string $text): array {
    $text = normalize_text($text);
    if ($text === '') return [];
    return preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
}

/**
 * Best-window fuzzy phrase alignment: slides across the document's words,
 * matching each query position (a group of acceptable spelling variants —
 * cross-script transliteration candidates) to a nearby doc word while
 * preserving order, tolerating up to $slack skipped doc words between
 * consecutive matches (a whole inserted/dropped word). Returns the best
 * average per-word similarity found anywhere in the document, in [0, 1].
 */
function phrase_alignment_score(
    array $queryVariantChars,
    array $docWords,
    array &$docCharCache,
    int $slack = ALIGN_SLACK,
    float $anchorFloor = ANCHOR_FLOOR
): float {
    $n = count($queryVariantChars);
    $m = count($docWords);
    if ($n === 0 || $m === 0) return 0.0;

    $getDocChars = function (string $word) use (&$docCharCache): array {
        if (!isset($docCharCache[$word])) {
            $docCharCache[$word] = mb_word_chars($word);
        }
        return $docCharCache[$word];
    };

    $simAt = function (int $qi, int $dj) use ($queryVariantChars, $docWords, $getDocChars): float {
        $docWord = $docWords[$dj];
        $best = 0.0;
        foreach ($queryVariantChars[$qi] as $variantStr => $variantChars) {
            if ($variantStr === $docWord) return 1.0;
            $sim = char_similarity($variantChars, $getDocChars($docWord));
            if ($sim > $best) $best = $sim;
        }
        return $best;
    };

    $best = 0.0;
    for ($start = 0; $start < $m; $start++) {
        // Anchor on the first query position — this is what keeps the scan
        // fast: most start positions bail out on one cheap comparison
        // instead of attempting a full n-position alignment.
        if ($simAt(0, $start) < $anchorFloor) continue;

        $pos = $start;
        $total = 0.0;
        $ok = true;
        for ($i = 0; $i < $n; $i++) {
            if ($pos >= $m) { $ok = false; break; }
            $limit = min($pos + $slack, $m - 1);
            $bestLocal = 0.0;
            $bestJ = $pos;
            for ($j = $pos; $j <= $limit; $j++) {
                $sim = $simAt($i, $j);
                if ($sim > $bestLocal) { $bestLocal = $sim; $bestJ = $j; }
            }
            $total += $bestLocal;
            $pos = $bestJ + 1;
        }
        if (!$ok) continue;
        $avg = $total / $n;
        if ($avg > $best) $best = $avg;
        if ($best >= 0.999) break;
    }
    return $best;
}

try {
    $db = new SQLite3('pdf_text.db');

    $exactLine = isset($_GET['query_exact']) ? trim($_GET['query_exact']) : '';
    $rawQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

    $results = [];

    if ($exactLine !== '') {
        // Deterministic literal substring match — every hit here is exact
        // by definition, so it's a single top relevance tier. Occurrence
        // count still breaks ties among multiple exact hits.
        //
        // The OCR'd text preserves the source PDF's original line-wrap
        // points as literal newlines, so a phrase that wraps mid-sentence
        // in the scan (e.g. "...ध्यान में\nकरना...") never matches a query
        // typed with a normal space there. The SQL prefilter uses '%'
        // between words so it's tolerant of whatever sits between them;
        // normalize_text() then confirms a true whitespace-normalized
        // match in PHP, so an inserted *word* (not just different
        // whitespace) still correctly fails to match.
        $normalizedExact = normalize_text($exactLine);
        $words = preg_split('/\s+/u', $normalizedExact, -1, PREG_SPLIT_NO_EMPTY);
        $stmt = $db->prepare(
            "SELECT pdf_name, page_number, raw_text_data FROM pdf_text_data
             WHERE raw_text_data LIKE :pattern"
        );
        $stmt->bindValue(':pattern', '%' . implode('%', $words) . '%', SQLITE3_TEXT);
        $res = $stmt->execute();
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $normalizedRow = normalize_text($row['raw_text_data']);
            $occurrences = substr_count($normalizedRow, $normalizedExact);
            if ($occurrences === 0) continue;
            $results[] = [
                'pdf_name' => $row['pdf_name'],
                'page_number' => $row['page_number'],
                'raw_text_data' => $row['raw_text_data'],
                'relevance' => 100,
                'occurrences' => $occurrences,
            ];
        }
        usort($results, fn($a, $b) => $b['occurrences'] - $a['occurrences']);
        $results = array_slice($results, 0, MAX_RESULTS);
        foreach ($results as &$r) unset($r['occurrences']);
        unset($r);

    } elseif ($rawQuery !== '') {
        // "Any word" mode: each space-separated position may carry several
        // comma-separated spelling variants (cross-script transliteration
        // candidates from the frontend). Build a fuzzy, order-preserving
        // phrase match: cheap SQL LIKE prefilter down to a bounded
        // candidate set, then rank that set with real phrase alignment —
        // keeps this fast regardless of corpus size.
        $positions = preg_split('/\s+/u', $rawQuery, -1, PREG_SPLIT_NO_EMPTY);
        $queryVariantChars = [];
        $likePatterns = [];

        foreach ($positions as $i => $group) {
            $variants = array_filter(array_unique(explode(',', $group)));
            $queryVariantChars[$i] = [];
            foreach ($variants as $variant) {
                $variant = normalize_text($variant);
                if ($variant === '') continue;
                $chars = mb_word_chars($variant);
                $queryVariantChars[$i][$variant] = $chars;

                $likePatterns[] = '%' . $variant . '%';
                // Prefix truncation catches the common OCR failure mode of
                // a dropped trailing matra/character without requiring a
                // full fuzzy scan of the whole table.
                if (count($chars) > 3) {
                    $prefixLen = (int) ceil(count($chars) * 0.6);
                    $likePatterns[] = '%' . implode('', array_slice($chars, 0, $prefixLen)) . '%';
                }
            }
        }

        $likePatterns = array_slice(array_unique($likePatterns), 0, MAX_LIKE_PATTERNS);
        $hasQueryWords = (bool) array_filter($queryVariantChars);

        if (!empty($likePatterns) && $hasQueryWords) {
            // A plain "WHERE ... LIMIT N" with no ORDER BY is unsafe here:
            // common words alone can match thousands of rows, and SQLite
            // then just returns whichever N happen to come first in table
            // order — silently dropping genuinely close matches that
            // happen to sit later in the table. Ranking by how many of the
            // query's LIKE patterns each row matches (cheap to compute, and
            // a decent relevance proxy on its own) before the LIMIT ensures
            // truncation drops the least-plausible candidates first.
            $conditions = implode(' OR ', array_fill(0, count($likePatterns), 'raw_text_data LIKE ?'));
            $matchCount = implode(' + ', array_fill(0, count($likePatterns), '(raw_text_data LIKE ?)'));
            $stmt = $db->prepare(
                "SELECT pdf_name, page_number, raw_text_data FROM pdf_text_data
                 WHERE $conditions ORDER BY ($matchCount) DESC LIMIT " . CANDIDATE_LIMIT
            );
            $idx = 1;
            foreach ($likePatterns as $pattern) {
                $stmt->bindValue($idx++, $pattern, SQLITE3_TEXT);
            }
            foreach ($likePatterns as $pattern) {
                $stmt->bindValue($idx++, $pattern, SQLITE3_TEXT);
            }
            $res = $stmt->execute();

            // Anchoring only forward (on the first query word) misses
            // documents where that specific word is the one that's
            // unrecognizable but the rest of the phrase matches well.
            // Reversing both arrays turns the last word into the "first"
            // one, so a single backward pass with the same function covers
            // that case too.
            $reversedQueryVariantChars = array_values(array_reverse($queryVariantChars));

            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $docWords = tokenize($row['raw_text_data']);
                $docCharCache = [];
                $forward = phrase_alignment_score($queryVariantChars, $docWords, $docCharCache);
                $backward = phrase_alignment_score($reversedQueryVariantChars, array_reverse($docWords), $docCharCache);
                $score = max($forward, $backward);
                if ($score < RELEVANCE_FLOOR) continue;
                $results[] = [
                    'pdf_name' => $row['pdf_name'],
                    'page_number' => $row['page_number'],
                    'raw_text_data' => $row['raw_text_data'],
                    'relevance' => (int) round($score * 100),
                ];
            }
            usort($results, fn($a, $b) => $b['relevance'] - $a['relevance']);
            $results = array_slice($results, 0, MAX_RESULTS);
        }
    }

    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(array_values($results));

    $db->close();
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
