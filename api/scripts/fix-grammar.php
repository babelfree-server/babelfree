#!/usr/bin/env php
<?php
/**
 * fix-grammar.php — Auto-fix common Spanish grammar issues in destination JSON files.
 *
 * Usage:
 *   php fix-grammar.php --dry-run --dest=1-58     # Preview all fixes (default)
 *   php fix-grammar.php --apply --dest=1           # Apply fixes to dest1 only
 *   php fix-grammar.php --dry-run --dest=1-5       # Preview fixes for dest1 through dest5
 *
 * Fix categories:
 *   1. Missing accents on common words (safe, unambiguous cases)
 *   2. Capitalization (sentence-initial only, NOT English Title Case)
 *   3. Spacing (double spaces, missing/extra spaces around punctuation, ¡¿ pairs)
 */

// ---------- CLI argument parsing ----------

$opts = getopt('', ['dry-run', 'apply', 'dest:']);

$apply = isset($opts['apply']);
$dryRun = !$apply; // dry-run is default

$destRange = $opts['dest'] ?? '1-58';
if (strpos($destRange, '-') !== false) {
    [$dStart, $dEnd] = explode('-', $destRange, 2);
    $destinations = range((int)$dStart, (int)$dEnd);
} else {
    $destinations = [(int)$destRange];
}

$contentDir = dirname(__DIR__, 2) . '/content';

// ---------- Stats ----------

$stats = [
    'accents' => 0,
    'capitalization' => 0,
    'spacing' => 0,
    'total_files' => 0,
    'files_changed' => 0,
];
$allChanges = [];

// ---------- Text fields to process ----------

$textFields = [
    'text', 'sentence', 'instruction', 'body', 'answer', 'prompt',
    'question', 'label', 'title', 'hint', 'closingQuestion',
    'imageAlt', 'gloss', 'audio',
];

// ---------- Fix functions ----------

/**
 * Fix missing accents on unambiguous common words.
 * Returns [fixed_text, count].
 */
function fixAccents(string $text): array {
    $count = 0;

    // Always-accented words (no ambiguity — these forms without accent are not valid Spanish words or extremely rare)
    $alwaysAccented = [
        'tambien'  => 'también',
        'Tambien'  => 'También',
        'aqui'     => 'aquí',
        'Aqui'     => 'Aquí',
        'ahi'      => 'ahí',
        'Ahi'      => 'Ahí',
        'detras'   => 'detrás',
        'Detras'   => 'Detrás',
        'ademas'   => 'además',
        'Ademas'   => 'Además',
        'jamas'    => 'jamás',
        'Jamas'    => 'Jamás',
        'quizas'   => 'quizás',
        'Quizas'   => 'Quizás',
        'asi'      => 'así',
        'Asi'      => 'Así',
    ];

    foreach ($alwaysAccented as $wrong => $right) {
        $pattern = '/\b' . preg_quote($wrong, '/') . '\b/u';
        $text = preg_replace_callback($pattern, function ($m) use ($right, &$count) {
            $count++;
            return $right;
        }, $text);
    }

    // "mas" → "más" when meaning "more" (before adjective/noun/number, or after verb)
    // Heuristic: "mas" followed by a space and a word that is NOT a conjunction pattern
    // Conservative: only fix "mas" when preceded by nothing/space and followed by adjective-like context
    // Skip "mas" when it could mean "but" (beginning of clause after comma)
    $text = preg_replace_callback(
        '/(?<=\s|^)(?<![,;])mas(?=\s+(?:de|que|o\s+menos|grande|pequeño|alto|bajo|lejos|cerca|rápido|lento|fuerte|importante|difícil|fácil|bonito|largo|corto|viejo|nuevo|tarde|temprano|allá|acá))\b/u',
        function ($m) use (&$count) {
            $count++;
            return 'más';
        },
        $text
    );

    // Question words at start of question (after ¿ or at string start with ?)
    // ¿que → ¿qué, ¿como → ¿cómo, etc.
    $questionWords = [
        'que'    => 'qué',
        'Que'    => 'Qué',
        'como'   => 'cómo',
        'Como'   => 'Cómo',
        'donde'  => 'dónde',
        'Donde'  => 'Dónde',
        'cuando' => 'cuándo',
        'Cuando' => 'Cuándo',
        'cual'   => 'cuál',
        'Cual'   => 'Cuál',
        'cuales' => 'cuáles',
        'Cuales' => 'Cuáles',
        'quien'  => 'quién',
        'Quien'  => 'Quién',
        'quienes'=> 'quiénes',
        'Quienes'=> 'Quiénes',
        'cuanto' => 'cuánto',
        'Cuanto' => 'Cuánto',
        'cuanta' => 'cuánta',
        'Cuanta' => 'Cuánta',
        'cuantos'=> 'cuántos',
        'Cuantos'=> 'Cuántos',
        'cuantas'=> 'cuántas',
        'Cuantas'=> 'Cuántas',
    ];

    foreach ($questionWords as $wrong => $right) {
        // After ¿ (with optional space)
        $pattern = '/(?<=¿)\s*\b' . preg_quote($wrong, '/') . '\b/u';
        $text = preg_replace_callback($pattern, function ($m) use ($right, &$count) {
            $count++;
            // Preserve any space that was there
            $space = preg_match('/^(\s*)/', $m[0], $sp) ? $sp[1] : '';
            return $space . $right;
        }, $text);
    }

    // "esta" → "está" after subject pronouns (él/ella/usted/eso/esto) — clearly verb usage
    $text = preg_replace_callback(
        '/\b((?:[Éé]l|[Ee]lla|[Uu]sted|[Ee]so|[Ee]sto|[Tt]odo|[Nn]ada|[Aa]lgo))\s+esta\b/u',
        function ($m) use (&$count) {
            $count++;
            return $m[1] . ' está';
        },
        $text
    );

    // "esta" at start of sentence before adjective-like words (está + adjective pattern)
    $text = preg_replace_callback(
        '/(?<=^|[.!?¡¿]\s)Esta\s+(?=(?:bien|mal|aquí|ahí|allí|lejos|cerca|listo|lista|lleno|llena|vacío|vacía|abierto|abierta|cerrado|cerrada|quieto|quieta|vivo|viva|muerto|muerta|loco|loca|solo|sola|claro|clara|oscuro|oscura|frío|fría|caliente|enfermo|enferma|contento|contenta|triste|feliz|cansado|cansada|perdido|perdida|roto|rota|sucio|sucia|limpio|limpia|mojado|mojada|seco|seca)\b)/u',
        function ($m) use (&$count) {
            $count++;
            return 'Está ';
        },
        $text
    );

    return [$text, $count];
}

/**
 * Fix capitalization issues (sentence-initial only).
 * Only applies to text that looks like a full sentence (contains spaces and punctuation or is long enough).
 * Returns [fixed_text, count].
 */
function fixCapitalization(string $text): array {
    $count = 0;

    // Skip short fragments (e.g., single words like "soy", "hola") — these are options/answers
    // A "sentence" must have at least one space or punctuation mark, or be > 15 chars
    $isSentenceLike = (mb_strpos($text, ' ') !== false) ||
                      (preg_match('/[.!?¡¿,;:]/', $text)) ||
                      (mb_strlen($text) > 15);

    if (!$isSentenceLike) {
        return [$text, 0];
    }

    // First character of the string should be uppercase (if it's a letter)
    // But only if the text looks like it starts a sentence (has punctuation or multiple words)
    $text = preg_replace_callback(
        '/^(\s*)([a-záéíóúñü])/u',
        function ($m) use (&$count) {
            $count++;
            return $m[1] . mb_strtoupper($m[2], 'UTF-8');
        },
        $text
    );

    // After sentence-ending punctuation + space, capitalize
    // Handles: . ! ? followed by space(s) and a lowercase letter
    // But NOT after ellipsis (...) which is a pause, not a sentence end
    $text = preg_replace_callback(
        '/(?<!\.)([.!?])\s+([a-záéíóúñü])/u',
        function ($m) use (&$count) {
            $count++;
            return $m[1] . ' ' . mb_strtoupper($m[2], 'UTF-8');
        },
        $text
    );

    // After ¡ or ¿ at start of string, capitalize the next letter
    // Mid-sentence ¿/¡ do NOT require capitalization in Spanish (e.g., "Elige: ¿soy o eres?")
    $text = preg_replace_callback(
        '/^(\s*[¡¿])\s*([a-záéíóúñü])/u',
        function ($m) use (&$count) {
            $count++;
            return $m[1] . mb_strtoupper($m[2], 'UTF-8');
        },
        $text
    );

    // After sentence-ending punctuation followed by ¡/¿, capitalize
    $text = preg_replace_callback(
        '/([.!?])\s+([¡¿])\s*([a-záéíóúñü])/u',
        function ($m) use (&$count) {
            $count++;
            return $m[1] . ' ' . $m[2] . mb_strtoupper($m[3], 'UTF-8');
        },
        $text
    );

    return [$text, $count];
}

/**
 * Fix spacing issues and missing ¡¿ marks.
 * Returns [fixed_text, count].
 */
function fixSpacing(string $text): array {
    $count = 0;
    $original = $text;

    // Double spaces → single space
    $newText = preg_replace('/  +/', ' ', $text);
    if ($newText !== $text) {
        $count += substr_count($text, '  ') - substr_count($newText, '  ');
        $text = $newText;
    }

    // Space before period or comma → remove
    // But not before ellipsis (...) and not after underscores (fill-in-the-blank like "___ .")
    $newText = preg_replace('/(?<!_) ([,])/', '$1', $text); // space before comma
    if ($newText !== $text) {
        $count++;
        $text = $newText;
    }
    // Space before single period (not part of ellipsis, not after ___)
    $newText = preg_replace('/(?<!_|\.) \.(?!\.)/', '.', $text);
    if ($newText !== $text) {
        $count++;
        $text = $newText;
    }

    // Missing space after period/comma when followed by a letter (not numbers like "3.5")
    // But NOT before closing quotes like » " '
    $newText = preg_replace('/([.,])([a-zA-ZáéíóúñüÁÉÍÓÚÑÜ¡¿])(?![»"\'"])/', '$1 $2', $text);
    if ($newText !== $text) {
        $count++;
        $text = $newText;
    }

    // Skip ¡¿ insertion for English text, glosses (containing =), or mixed-language content
    $looksEnglish = preg_match('/\b(the|is|are|you|how|what|where|also|too|old|there|who|eat|drink|take|have|do|can|will|not|and|or|but|for|with|from|this|that|it|he|she|we|they|my|your|his|her|our|ago|command)\b/i', $text)
        || mb_strpos($text, '=') !== false;  // Gloss entries like "Come = Eat!"

    // Missing opening ¡ before ! (Spanish requires ¡...!)
    // Strategy: split on ! and check each exclamatory segment
    if (!$looksEnglish && mb_strpos($text, '!') !== false && mb_strpos($text, '¡') === false) {
        // Simple case: text has ! but no ¡ at all — add ¡ at the start of each exclamatory clause
        $text = preg_replace_callback(
            '/(?:^|(?<=[.?!\s]))([^!]*[a-zA-ZáéíóúñüÁÉÍÓÚÑÜ][^!]*?)!/u',
            function ($m) use (&$count) {
                $chunk = $m[1];
                if (preg_match('/^(\s*)(.*)/su', $chunk, $parts)) {
                    $count++;
                    return $parts[1] . '¡' . $parts[2] . '!';
                }
                return $m[0];
            },
            $text
        );
    }

    // Missing opening ¿ before ? (Spanish requires ¿...?)
    if (!$looksEnglish && mb_strpos($text, '?') !== false && mb_strpos($text, '¿') === false) {
        // Simple case: text has ? but no ¿ at all
        $text = preg_replace_callback(
            '/(?:^|(?<=[.!?\s]))([^?]*[a-zA-ZáéíóúñüÁÉÍÓÚÑÜ][^?]*?)\?/u',
            function ($m) use (&$count) {
                $chunk = $m[1];
                if (preg_match('/^(\s*)(.*)/su', $chunk, $parts)) {
                    $count++;
                    return $parts[1] . '¿' . $parts[2] . '?';
                }
                return $m[0];
            },
            $text
        );
    }

    return [$text, $count];
}

/**
 * Apply all fixes to a text string.
 * $skipCaps: if true, skip capitalization fixes (for fragments like gloss, tip lines, options).
 * Returns [fixed_text, changes_array].
 */
function fixText(string $text, bool $skipCaps = false): array {
    $changes = [];
    $original = $text;

    // Protect template tokens like {pool:eco.place}, {nombre}, etc. from being modified
    $tokens = [];
    $text = preg_replace_callback('/\{[^}]+\}/', function ($m) use (&$tokens) {
        $idx = count($tokens);
        $placeholder = "\x01TK{$idx}\x01";
        $tokens[$placeholder] = $m[0];
        return $placeholder;
    }, $text);

    // 1. Accents
    [$text, $accentCount] = fixAccents($text);
    if ($accentCount > 0) {
        $changes[] = ['category' => 'accents', 'count' => $accentCount];
    }

    // 2. Spacing (before capitalization, so caps work on clean text)
    [$text, $spacingCount] = fixSpacing($text);
    if ($spacingCount > 0) {
        $changes[] = ['category' => 'spacing', 'count' => $spacingCount];
    }

    // 3. Capitalization (skip for fragments like gloss, tip lines, options)
    if (!$skipCaps) {
        [$text, $capCount] = fixCapitalization($text);
        if ($capCount > 0) {
            $changes[] = ['category' => 'capitalization', 'count' => $capCount];
        }
    }

    // Restore template tokens
    if (!empty($tokens)) {
        $text = str_replace(array_keys($tokens), array_values($tokens), $text);
    }

    return [$text, $changes, $original];
}

/**
 * Recursively walk a JSON structure and fix text fields.
 */
function walkAndFix(&$data, array $textFields, string $path, array &$allChanges, array &$stats): bool {
    $changed = false;

    if (is_array($data)) {
        foreach ($data as $key => &$value) {
            $currentPath = $path . '.' . $key;

            // Fields where capitalization should be skipped (fragments, not sentences)
            $noCapsFields = ['gloss', 'answer', 'audio', 'prompt', 'hint'];

            // If this key is a text field and value is a string, fix it
            if (is_string($key) && in_array($key, $textFields) && is_string($value)) {
                // Skip caps for designated fragment fields and for text inside items/cards arrays
                $skipCaps = in_array($key, $noCapsFields)
                    || ($key === 'text' && preg_match('/\.items\.\d+\.text$/', $currentPath))
                    || ($key === 'text' && preg_match('/\.cards\.\d+\.text$/', $currentPath))
                    || ($key === 'text' && preg_match('/\.spells\.\d+\.(text|hint|prompt)$/', $currentPath))
                    || ($key === 'title' && preg_match('/\.tip\.title$/', $currentPath));
                [$fixed, $changes, $original] = fixText($value, $skipCaps);
                if ($fixed !== $original) {
                    $changed = true;
                    $value = $fixed;
                    foreach ($changes as $c) {
                        $stats[$c['category']] += $c['count'];
                    }
                    $allChanges[] = [
                        'path' => $currentPath,
                        'before' => $original,
                        'after' => $fixed,
                        'categories' => array_column($changes, 'category'),
                    ];
                }
            }
            // If this key is "options" and value is an array of strings, fix each (skip caps — these are choices)
            elseif (is_string($key) && $key === 'options' && is_array($value)) {
                foreach ($value as $i => &$opt) {
                    if (is_string($opt)) {
                        [$fixed, $changes, $original] = fixText($opt, true);
                        if ($fixed !== $original) {
                            $changed = true;
                            $opt = $fixed;
                            foreach ($changes as $c) {
                                $stats[$c['category']] += $c['count'];
                            }
                            $allChanges[] = [
                                'path' => $currentPath . "[$i]",
                                'before' => $original,
                                'after' => $fixed,
                                'categories' => array_column($changes, 'category'),
                            ];
                        }
                    }
                }
                unset($opt);
            }
            // If this key is "lines" and value is an array of strings (tip lines), fix each (skip caps — these are fragments)
            elseif (is_string($key) && $key === 'lines' && is_array($value)) {
                foreach ($value as $i => &$line) {
                    if (is_string($line)) {
                        [$fixed, $changes, $original] = fixText($line, true);
                        if ($fixed !== $original) {
                            $changed = true;
                            $line = $fixed;
                            foreach ($changes as $c) {
                                $stats[$c['category']] += $c['count'];
                            }
                            $allChanges[] = [
                                'path' => $currentPath . "[$i]",
                                'before' => $original,
                                'after' => $fixed,
                                'categories' => array_column($changes, 'category'),
                            ];
                        }
                    }
                }
                unset($line);
            }
            // Recurse into nested structures
            elseif (is_array($value)) {
                if (walkAndFix($value, $textFields, $currentPath, $allChanges, $stats)) {
                    $data[$key] = $value;
                    $changed = true;
                }
            }
        }
        unset($value);
    }

    return $changed;
}

// ---------- Main ----------

echo "=== Spanish Grammar Auto-Fixer ===\n";
echo "Mode: " . ($dryRun ? "DRY RUN (preview only)" : "APPLY (writing changes)") . "\n";
echo "Destinations: " . $destRange . "\n";
echo str_repeat('=', 50) . "\n\n";

foreach ($destinations as $destNum) {
    $files = [];

    // Destination content file
    $destFile = $contentDir . "/dest{$destNum}.json";
    if (file_exists($destFile)) {
        $files[] = $destFile;
    }

    // Template file
    $templateFile = $contentDir . "/templates/dest{$destNum}-templates.json";
    if (file_exists($templateFile)) {
        $files[] = $templateFile;
    }

    foreach ($files as $file) {
        $stats['total_files']++;
        $json = file_get_contents($file);
        $data = json_decode($json, true);

        if ($data === null) {
            echo "  ERROR: Could not parse {$file}\n";
            continue;
        }

        $fileChanges = [];
        $filePath = basename(dirname($file)) . '/' . basename($file);
        if (basename(dirname($file)) === 'content') {
            $filePath = basename($file);
        }

        $changed = walkAndFix($data, $textFields, $filePath, $fileChanges, $stats);

        if (!empty($fileChanges)) {
            $stats['files_changed']++;
            echo "--- {$filePath} (" . count($fileChanges) . " fixes) ---\n";

            foreach ($fileChanges as $change) {
                $cats = implode(', ', $change['categories']);
                echo "  [{$cats}] {$change['path']}\n";
                echo "    BEFORE: {$change['before']}\n";
                echo "    AFTER:  {$change['after']}\n";
            }
            echo "\n";

            // Merge file changes into global list
            $allChanges = array_merge($allChanges, $fileChanges);

            // Write if applying
            if ($apply && $changed) {
                $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                file_put_contents($file, $output . "\n");
                echo "  >> Written to {$file}\n\n";
            }
        }
    }
}

// ---------- Summary ----------

echo str_repeat('=', 50) . "\n";
echo "SUMMARY\n";
echo str_repeat('=', 50) . "\n";
echo "Files scanned:    {$stats['total_files']}\n";
echo "Files with fixes: {$stats['files_changed']}\n";
echo "Total fixes:      " . ($stats['accents'] + $stats['capitalization'] + $stats['spacing']) . "\n";
echo "  Accents:        {$stats['accents']}\n";
echo "  Capitalization:  {$stats['capitalization']}\n";
echo "  Spacing:         {$stats['spacing']}\n";

if ($dryRun) {
    echo "\nThis was a DRY RUN. No files were modified.\n";
    echo "Run with --apply to write changes.\n";
}
