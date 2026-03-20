<?php

class Dictionary {
    private PDO $pdo;
    private array $i18n;
    private array $supportedLangs;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $i18nFile = dirname(__DIR__) . '/scripts/data/dict_i18n.json';
        $this->i18n = file_exists($i18nFile) ? json_decode(file_get_contents($i18nFile), true) : [];
        $this->supportedLangs = array_keys($this->i18n);
    }

    /**
     * Check if a language code is supported.
     */
    public function isValidLang(string $lang): bool {
        return in_array($lang, $this->supportedLangs, true);
    }

    /**
     * Get the list of supported language codes.
     */
    public function getSupportedLangs(): array {
        return $this->supportedLangs;
    }

    /**
     * Normalize a word for case/accent-insensitive matching.
     */
    private function normalize(string $word): string {
        $word = mb_strtolower($word, 'UTF-8');
        if (function_exists('transliterator_transliterate')) {
            $word = transliterator_transliterate('NFD; [:Nonspacing Mark:] Remove; NFC;', $word);
        } else {
            $word = preg_replace('/[áà]/u', 'a', $word);
            $word = preg_replace('/[éè]/u', 'e', $word);
            $word = preg_replace('/[íì]/u', 'i', $word);
            $word = preg_replace('/[óò]/u', 'o', $word);
            $word = preg_replace('/[úù]/u', 'u', $word);
            $word = str_replace('ñ', 'n', $word);
        }
        return trim($word);
    }

    /**
     * Try to find the lemma (base form) for an inflected word.
     * Returns the lemma word string if found, null otherwise.
     */
    public function lemmatize(string $lang, string $word): ?string {
        $normalized = $this->normalize($word);
        $candidates = [];

        // Spanish / Portuguese / Italian / French plural rules
        if (in_array($lang, ['es', 'pt', 'it', 'fr'])) {
            // -ces → -z (Spanish: peces→pez, luces→luz)
            if (preg_match('/ces$/u', $normalized)) {
                $candidates[] = preg_replace('/ces$/u', 'z', $normalized);
            }
            // -es → remove (Spanish: bancos→banco won't match, but flores→flor, colores→color)
            if (preg_match('/es$/u', $normalized) && mb_strlen($normalized) > 3) {
                $candidates[] = mb_substr($normalized, 0, -2);
            }
            // -s → remove (most common: bancos→banco, casas→casa)
            if (preg_match('/s$/u', $normalized) && mb_strlen($normalized) > 2) {
                $candidates[] = mb_substr($normalized, 0, -1);
            }
        }

        // English plural rules
        if ($lang === 'en') {
            if (preg_match('/ies$/u', $normalized) && mb_strlen($normalized) > 4) {
                $candidates[] = preg_replace('/ies$/u', 'y', $normalized);
            }
            if (preg_match('/ves$/u', $normalized) && mb_strlen($normalized) > 4) {
                $candidates[] = preg_replace('/ves$/u', 'f', $normalized);
                $candidates[] = preg_replace('/ves$/u', 'fe', $normalized);
            }
            if (preg_match('/ses$/u', $normalized) || preg_match('/xes$/u', $normalized) || preg_match('/zes$/u', $normalized) || preg_match('/ches$/u', $normalized) || preg_match('/shes$/u', $normalized)) {
                $candidates[] = mb_substr($normalized, 0, -2);
            }
            if (preg_match('/s$/u', $normalized) && mb_strlen($normalized) > 2) {
                $candidates[] = mb_substr($normalized, 0, -1);
            }
        }

        // German plural rules
        if ($lang === 'de') {
            if (preg_match('/en$/u', $normalized) && mb_strlen($normalized) > 3) {
                $candidates[] = mb_substr($normalized, 0, -2);
                $candidates[] = mb_substr($normalized, 0, -1); // -e base
            }
            if (preg_match('/er$/u', $normalized) && mb_strlen($normalized) > 3) {
                $candidates[] = mb_substr($normalized, 0, -2);
            }
            if (preg_match('/e$/u', $normalized) && mb_strlen($normalized) > 2) {
                $candidates[] = mb_substr($normalized, 0, -1);
            }
            if (preg_match('/s$/u', $normalized) && mb_strlen($normalized) > 2) {
                $candidates[] = mb_substr($normalized, 0, -1);
            }
        }

        // Dutch plural rules
        if ($lang === 'nl') {
            if (preg_match('/en$/u', $normalized) && mb_strlen($normalized) > 3) {
                $candidates[] = mb_substr($normalized, 0, -2);
            }
            if (preg_match('/s$/u', $normalized) && mb_strlen($normalized) > 2) {
                $candidates[] = mb_substr($normalized, 0, -1);
            }
        }

        // Try each candidate in order
        $stmt = $this->pdo->prepare(
            'SELECT word FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1'
        );
        foreach ($candidates as $candidate) {
            if ($candidate === $normalized) continue;
            $stmt->execute([$lang, $candidate]);
            $row = $stmt->fetch();
            if ($row) return $row['word'];
        }

        return null;
    }

    /**
     * Find similar words for "did you mean?" suggestions.
     * Uses prefix matching + Levenshtein distance.
     */
    public function fuzzyMatch(string $lang, string $word, int $limit = 5): array {
        $normalized = $this->normalize($word);
        if (mb_strlen($normalized) < 2) return [];

        // Strategy 1: prefix match (first 2-3 chars)
        $prefixLen = min(3, mb_strlen($normalized));
        $prefix = mb_substr($normalized, 0, $prefixLen);

        $stmt = $this->pdo->prepare(
            'SELECT word, word_normalized FROM dict_words
             WHERE lang_code = ? AND word_normalized LIKE ?
             ORDER BY frequency_rank ASC, LENGTH(word) ASC
             LIMIT 50'
        );
        $stmt->execute([$lang, $prefix . '%']);
        $candidates = $stmt->fetchAll();

        // Strategy 2: if prefix match gives too few, try shorter prefix
        if (count($candidates) < 5 && $prefixLen > 2) {
            $shortPrefix = mb_substr($normalized, 0, 2);
            $stmt->execute([$lang, $shortPrefix . '%']);
            $candidates = array_merge($candidates, $stmt->fetchAll());
        }

        // Score by Levenshtein distance
        $scored = [];
        $seen = [];
        foreach ($candidates as $c) {
            $cn = $c['word_normalized'];
            if (isset($seen[$cn])) continue;
            $seen[$cn] = true;

            $dist = levenshtein($normalized, $cn);
            $maxLen = max(mb_strlen($normalized), mb_strlen($cn));
            // Only include if reasonably close (within 30% of word length, max 3)
            if ($dist <= min(3, max(1, (int)($maxLen * 0.3)))) {
                $scored[] = ['word' => $c['word'], 'distance' => $dist];
            }
        }

        usort($scored, function($a, $b) { return $a['distance'] - $b['distance']; });
        return array_slice(array_column($scored, 'word'), 0, $limit);
    }

    /**
     * Full word lookup by language and word.
     * Returns entry with definitions, translations (as hyperlinks), examples, conjugations.
     * If the word is an inflected form (plural etc.), returns the lemma entry with a redirect hint.
     *
     * Level-gated definitions:
     *  - A1/A2 user → definitions in user's interface language
     *  - A2 Advanced+ → Spanish-only definitions
     *  - Not logged in → definitions in the word's own language
     */
    public function lookup(string $lang, string $word, ?array $user = null): ?array {
        $normalized = $this->normalize($word);

        // Find the word entry
        $stmt = $this->pdo->prepare(
            'SELECT * FROM dict_words WHERE lang_code = ? AND word_normalized = ? LIMIT 1'
        );
        $stmt->execute([$lang, $normalized]);
        $entry = $stmt->fetch();

        // If not found, try lemmatization
        if (!$entry) {
            $lemma = $this->lemmatize($lang, $word);
            if ($lemma) {
                return ['_redirect_to' => $lemma];
            }
            $suggestions = $this->fuzzyMatch($lang, $word);
            if (!empty($suggestions)) {
                return ['_did_you_mean' => $suggestions];
            }
            return null;
        }

        $wordId = (int)$entry['id'];

        // Determine which language to show definitions in
        $defLang = $this->getDefinitionLanguage($lang, $user);

        // Definitions in the appropriate language
        $stmt = $this->pdo->prepare(
            'SELECT definition, usage_note, lang_code FROM dict_definitions
             WHERE word_id = ? AND lang_code = ?
             ORDER BY sort_order'
        );
        $stmt->execute([$wordId, $defLang]);
        $entry['definitions'] = $stmt->fetchAll();

        // If no definitions in the target language, fall back to the word's own language
        if (empty($entry['definitions']) && $defLang !== $lang) {
            $stmt->execute([$wordId, $lang]);
            $entry['definitions'] = $stmt->fetchAll();
        }

        // If still empty, try Spanish as last resort
        if (empty($entry['definitions']) && $lang !== 'es' && $defLang !== 'es') {
            $stmt->execute([$wordId, 'es']);
            $entry['definitions'] = $stmt->fetchAll();
        }

        // Translations as hyperlinks — get ALL translations to other languages
        $stmt = $this->pdo->prepare(
            'SELECT w.word, w.lang_code, w.part_of_speech, w.gender, t.context
             FROM dict_translations t
             JOIN dict_words w ON w.id = t.target_word_id
             WHERE t.source_word_id = ?
             ORDER BY w.lang_code, w.word'
        );
        $stmt->execute([$wordId]);
        $translations = $stmt->fetchAll();

        // Format translations with URL for hyperlink model
        $i18n = $this->i18n;
        $entry['translations'] = array_map(function($t) use ($i18n) {
            $langCode = $t['lang_code'];
            $urlWord = rawurlencode(mb_strtolower($t['word'], 'UTF-8'));
            $prefix = $i18n[$langCode]['url_prefix'] ?? '';
            $slug = $prefix ? $prefix . '-' . $urlWord : $urlWord;
            return [
                'word' => $t['word'],
                'lang' => $langCode,
                'part_of_speech' => $t['part_of_speech'],
                'gender' => $t['gender'],
                'context' => $t['context'],
                'url' => '/dictionary/' . $langCode . '/' . $slug,
            ];
        }, $translations);

        // Examples
        $stmt = $this->pdo->prepare(
            'SELECT sentence, translation, cefr_level, source FROM dict_examples WHERE word_id = ? ORDER BY id'
        );
        $stmt->execute([$wordId]);
        $entry['examples'] = $stmt->fetchAll();

        // Conjugations (verbs only)
        if ($entry['part_of_speech'] === 'verb') {
            $stmt = $this->pdo->prepare(
                'SELECT tense, mood, yo, tu, vos, el, nosotros, ustedes, ellos, is_irregular
                 FROM dict_conjugations WHERE word_id = ? ORDER BY id'
            );
            $stmt->execute([$wordId]);
            $entry['conjugations'] = $stmt->fetchAll();
        }

        // Related words
        $stmt = $this->pdo->prepare(
            'SELECT w.word, w.lang_code, r.relation_type
             FROM dict_related_words r
             JOIN dict_words w ON w.id = r.related_word_id
             WHERE r.word_id = ?'
        );
        $stmt->execute([$wordId]);
        $entry['related'] = $stmt->fetchAll();

        return $entry;
    }

    /**
     * Backward-compatible lookup by language pair (es-en).
     */
    public function lookupByPair(string $sourceLang, string $targetLang, string $word, ?array $user = null): ?array {
        $entry = $this->lookup($sourceLang, $word, $user);
        if (!$entry) return null;

        // Filter translations to only the target language
        $entry['translations'] = array_values(array_filter($entry['translations'], function($t) use ($targetLang) {
            return $t['lang'] === $targetLang;
        }));

        return $entry;
    }

    /**
     * Determine which language to show definitions in based on user CEFR level.
     */
    private function getDefinitionLanguage(string $wordLang, ?array $user = null): string {
        if (!$user) {
            // Not logged in: show definitions in the word's own language
            return $wordLang;
        }

        $cefrLevel = $user['cefr_level'] ?? 'A1';
        $interfaceLang = $user['interface_lang'] ?? 'es';

        // A1 and A2 Basic: definitions in user's interface language
        if (in_array($cefrLevel, ['A1', 'A2'])) {
            return $interfaceLang;
        }

        // A2 Advanced and above: Spanish only (immersion)
        return 'es';
    }

    /**
     * Autocomplete suggestions within a specific language.
     */
    public function suggest(string $query, string $langCode, int $limit = 10): array {
        $normalized = $this->normalize($query);
        if (mb_strlen($normalized, 'UTF-8') < 1) return [];

        $stmt = $this->pdo->prepare(
            'SELECT word, part_of_speech, cefr_level
             FROM dict_words
             WHERE lang_code = ? AND word_normalized LIKE ?
             ORDER BY (word_normalized = ?) DESC, LENGTH(word) ASC, frequency_rank ASC, word ASC
             LIMIT ?'
        );
        $stmt->execute([$langCode, $normalized . '%', $normalized, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Full conjugation table for a verb.
     */
    public function getConjugations(string $word, string $langCode = 'es'): ?array {
        $normalized = $this->normalize($word);

        $stmt = $this->pdo->prepare(
            'SELECT id, word, pronunciation_ipa FROM dict_words
             WHERE lang_code = ? AND word_normalized = ? AND part_of_speech = "verb" LIMIT 1'
        );
        $stmt->execute([$langCode, $normalized]);
        $entry = $stmt->fetch();

        if (!$entry) return null;

        $stmt = $this->pdo->prepare(
            'SELECT tense, mood, yo, tu, vos, el, nosotros, ustedes, ellos, is_irregular
             FROM dict_conjugations WHERE word_id = ? ORDER BY id'
        );
        $stmt->execute([(int)$entry['id']]);
        $entry['conjugations'] = $stmt->fetchAll();

        return $entry;
    }

    /**
     * Get translations for a word to a specific target language.
     */
    public function getTranslations(int $wordId, string $targetLang): array {
        $stmt = $this->pdo->prepare(
            'SELECT w.word, w.part_of_speech, w.gender, t.context
             FROM dict_translations t
             JOIN dict_words w ON w.id = t.target_word_id
             WHERE t.source_word_id = ? AND w.lang_code = ?'
        );
        $stmt->execute([$wordId, $targetLang]);
        return $stmt->fetchAll();
    }

    /**
     * Get example sentences for a word.
     */
    public function getExamples(int $wordId): array {
        $stmt = $this->pdo->prepare(
            'SELECT sentence, translation, cefr_level, source FROM dict_examples WHERE word_id = ? ORDER BY id'
        );
        $stmt->execute([$wordId]);
        return $stmt->fetchAll();
    }

    /**
     * Get related words (synonyms, antonyms, derived).
     */
    public function getRelated(int $wordId): array {
        $stmt = $this->pdo->prepare(
            'SELECT w.word, w.lang_code, r.relation_type
             FROM dict_related_words r
             JOIN dict_words w ON w.id = r.related_word_id
             WHERE r.word_id = ?'
        );
        $stmt->execute([$wordId]);
        return $stmt->fetchAll();
    }

    /**
     * Word of the day — deterministic per day and language.
     * Uses date as seed to pick the same word all day.
     */
    public function getRandomWord(string $langCode = 'es', ?string $cefrLevel = null): ?array {
        // Get total count for this language/level combo
        $countSql = 'SELECT COUNT(*) FROM dict_words WHERE lang_code = ?';
        $params = [$langCode];
        if ($cefrLevel) {
            $countSql .= ' AND cefr_level = ?';
            $params[] = $cefrLevel;
        }
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        if ($total === 0) return null;

        // Deterministic offset based on date + language
        $seed = crc32(date('Y-m-d') . $langCode . ($cefrLevel ?? ''));
        $offset = abs($seed) % $total;

        $sql = 'SELECT word, part_of_speech, cefr_level, pronunciation_ipa, frequency_rank
                FROM dict_words WHERE lang_code = ?';
        $params2 = [$langCode];
        if ($cefrLevel) {
            $sql .= ' AND cefr_level = ?';
            $params2[] = $cefrLevel;
        }
        $sql .= ' ORDER BY id LIMIT 1 OFFSET ?';
        $params2[] = (int) $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params2);
        return $stmt->fetch() ?: null;
    }

    /**
     * All 104 available languages.
     */
    public function getLanguages(): array {
        $stmt = $this->pdo->query(
            'SELECT l.code, l.name_native, l.name_en,
                    COUNT(w.id) AS word_count
             FROM dict_languages l
             LEFT JOIN dict_words w ON w.lang_code = l.code
             GROUP BY l.code, l.name_native, l.name_en
             ORDER BY word_count DESC, l.name_en'
        );
        return $stmt->fetchAll();
    }

    /**
     * @deprecated Use getLanguages() instead.
     */
    public function getLanguagePairs(): array {
        return $this->getLanguages();
    }
}
