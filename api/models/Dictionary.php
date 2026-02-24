<?php

class Dictionary {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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
     * Full word lookup by language and word.
     * Returns entry with definitions, translations (as hyperlinks), examples, conjugations.
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

        if (!$entry) return null;

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
        $entry['translations'] = array_map(function($t) {
            $urlWord = rawurlencode(mb_strtolower($t['word'], 'UTF-8'));
            return [
                'word' => $t['word'],
                'lang' => $t['lang_code'],
                'part_of_speech' => $t['part_of_speech'],
                'gender' => $t['gender'],
                'context' => $t['context'],
                'url' => '/dictionary/' . $t['lang_code'] . '/' . $urlWord,
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
        if (strlen($normalized) < 1) return [];

        $stmt = $this->pdo->prepare(
            'SELECT word, part_of_speech, cefr_level
             FROM dict_words
             WHERE lang_code = ? AND word_normalized LIKE ?
             ORDER BY frequency_rank ASC, word ASC
             LIMIT ?'
        );
        $stmt->execute([$langCode, $normalized . '%', $limit]);
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
     * Random word (for "word of the day").
     */
    public function getRandomWord(string $langCode = 'es', ?string $cefrLevel = null): ?array {
        $sql = 'SELECT word, part_of_speech, cefr_level, pronunciation_ipa, frequency_rank FROM dict_words WHERE lang_code = ?';
        $params = [$langCode];

        if ($cefrLevel) {
            $sql .= ' AND cefr_level = ?';
            $params[] = $cefrLevel;
        }

        $sql .= ' ORDER BY RAND() LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /**
     * All 104 available languages.
     */
    public function getLanguages(): array {
        $stmt = $this->pdo->query(
            'SELECT code, name_native, name_en, flag, text_direction
             FROM dict_languages
             WHERE is_active = 1
             ORDER BY name_en'
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
