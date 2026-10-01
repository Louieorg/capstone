<?php

namespace App\Services;

/**
 * Validates a Filipino rendering of a stored, completed English cluster explanation.
 *
 * The English synthesis is canonical. This checks the translation preserves it:
 * same shape, same counts, same numeric tokens, no new personal data, no
 * internals, and wording that reads as Filipino rather than copied English.
 */
class ClusterTranslationValidator
{
    public const RULE_SHAPE = 'shape';

    public const RULE_LENGTH = 'length';

    public const RULE_NUMBERS = 'numbers';

    public const RULE_PERSONAL_DATA = 'personal_data';

    public const RULE_INTERNALS = 'internals';

    public const RULE_NOT_FILIPINO = 'not_filipino';

    private const SUMMARY_MIN = 40;

    private const SUMMARY_MAX = 900;

    private const PATTERN_MIN_ITEMS = 1;

    private const PATTERN_MAX_ITEMS = 4;

    private const PATTERN_MIN_LENGTH = 15;

    private const PATTERN_MAX_LENGTH = 120;

    private const EXPERIENCE_MAX_ITEMS = 6;

    private const EXPERIENCE_TITLE_MIN = 10;

    private const EXPERIENCE_TITLE_MAX = 90;

    private const EXPERIENCE_BODY_MIN = 30;

    private const EXPERIENCE_BODY_MAX = 500;

    private const TOTAL_MAX_LENGTH = 4000;

    private const RESULT_KEYS = ['summary', 'patterns', 'experiences'];

    private const HONORIFICS = ['Mr.', 'Mrs.', 'Ms.', 'Mx.', 'Dr.', 'Engr.', 'Prof.', 'Atty.'];

    private const LEAK_WORDS = ['cluster key', 'unclassified', 'hash', 'score'];

    /** @var array<int, string> */
    private const FILIPINO_MARKERS = [
        'ng', 'sa', 'ang', 'mga', 'ay', 'dahil', 'upang', 'para',
        'nila', 'kanila', 'namin', 'atin', 'kami', 'tayo', 'kayo',
        'ito', 'iyon', 'iyan', 'dito', 'doon', 'dyan', 'rin', 'din',
        'lamang', 'naman', 'po', 'opo', 'estudyante', 'mag-aaral',
        'paaralan', 'opisina', 'kailangan', 'dapat', 'maaari', 'wala',
        'mayroon', 'problema', 'isyu', 'ulat', 'kahilingan', 'proseso',
        'sistema', 'ginagawa', 'ginawa', 'gagawin', 'naiulat', 'nakikita',
        'naghihintay', 'pumipila', 'bumabalik', 'nagtatanong', 'maraming',
        'ilang', 'pareho', 'bawat', 'lahat', 'kadalasan', 'madalas',
        'mahirap', 'maayos', 'panahon', 'oras', 'araw', 'klase', 'silid',
        'talaan', 'arkibo', 'dokumento', 'serbisyo', 'tulong', 'suporta',
    ];

    /**
     * @param  array<array-key, mixed>  $result
     * @param  array<string, mixed>  $source
     * @return array{ok: bool, result: array<string, mixed>|null, rule: string|null}
     */
    public function validate(array $result, array $source): array
    {
        $sourceNormalized = $this->normalizeShape($source);

        if ($sourceNormalized === null) {
            return $this->reject(self::RULE_SHAPE);
        }

        $normalized = $this->normalizeShape($result);

        if ($normalized === null) {
            return $this->reject(self::RULE_SHAPE);
        }

        if (! $this->countsMatch($normalized, $sourceNormalized)) {
            return $this->reject(self::RULE_SHAPE);
        }

        $lengthRule = $this->checkLengths($normalized);

        if ($lengthRule !== null) {
            return $this->reject($lengthRule);
        }

        if (! $this->personalDataIsAbsent($normalized, $sourceNormalized)) {
            return $this->reject(self::RULE_PERSONAL_DATA);
        }

        if (! $this->numbersArePreserved($normalized, $sourceNormalized)) {
            return $this->reject(self::RULE_NUMBERS);
        }

        if (! $this->internalsAreAbsent($normalized)) {
            return $this->reject(self::RULE_INTERNALS);
        }

        if (! $this->readsAsFilipino($normalized, $sourceNormalized)) {
            return $this->reject(self::RULE_NOT_FILIPINO);
        }

        return ['ok' => true, 'result' => $normalized, 'rule' => null];
    }

    /** @return array{ok: false, result: null, rule: string} */
    private function reject(string $rule): array
    {
        return ['ok' => false, 'result' => null, 'rule' => $rule];
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}|null
     */
    private function normalizeShape(array $value): ?array
    {
        if (array_keys($value) !== self::RESULT_KEYS) {
            return null;
        }

        if (! is_string($value['summary']) || ! is_array($value['patterns']) || ! is_array($value['experiences'])) {
            return null;
        }

        $patterns = [];

        foreach (array_values($value['patterns']) as $pattern) {
            if (! is_string($pattern)) {
                return null;
            }

            $patterns[] = $this->normalizeText($pattern);
        }

        $experiences = [];

        foreach (array_values($value['experiences']) as $experience) {
            if (! is_array($experience) || array_keys($experience) !== ['title', 'body']) {
                return null;
            }

            if (! is_string($experience['title']) || ! is_string($experience['body'])) {
                return null;
            }

            $experiences[] = [
                'title' => $this->normalizeText($experience['title']),
                'body' => $this->normalizeText($experience['body']),
            ];
        }

        return ['summary' => $this->normalizeText($value['summary']), 'patterns' => $patterns, 'experiences' => $experiences];
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $source
     */
    private function countsMatch(array $normalized, array $source): bool
    {
        if (count($normalized['patterns']) < self::PATTERN_MIN_ITEMS
            || count($normalized['patterns']) > self::PATTERN_MAX_ITEMS
            || count($normalized['patterns']) !== count($source['patterns'])) {
            return false;
        }

        if (count($normalized['experiences']) > self::EXPERIENCE_MAX_ITEMS
            || count($normalized['experiences']) !== count($source['experiences'])) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     */
    private function checkLengths(array $normalized): ?string
    {
        if ($this->textLength($normalized['summary']) < self::SUMMARY_MIN
            || $this->textLength($normalized['summary']) > self::SUMMARY_MAX) {
            return self::RULE_LENGTH;
        }

        foreach ($normalized['patterns'] as $pattern) {
            $length = $this->textLength($pattern);

            if ($length < self::PATTERN_MIN_LENGTH || $length > self::PATTERN_MAX_LENGTH) {
                return self::RULE_LENGTH;
            }
        }

        foreach ($normalized['experiences'] as $experience) {
            $titleLength = $this->textLength($experience['title']);
            $bodyLength = $this->textLength($experience['body']);

            if ($titleLength < self::EXPERIENCE_TITLE_MIN || $titleLength > self::EXPERIENCE_TITLE_MAX
                || $bodyLength < self::EXPERIENCE_BODY_MIN || $bodyLength > self::EXPERIENCE_BODY_MAX) {
                return self::RULE_LENGTH;
            }
        }

        if ($this->textLength($this->flattenText($normalized)) > self::TOTAL_MAX_LENGTH) {
            return self::RULE_LENGTH;
        }

        return null;
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $source
     */
    private function numbersArePreserved(array $normalized, array $source): bool
    {
        $translationNumbers = $this->numericTokens($this->flattenText($normalized));

        foreach ($this->numericTokens($this->flattenText($source)) as $token) {
            if (! in_array($token, $translationNumbers, true)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, string> */
    private function numericTokens(string $value): array
    {
        preg_match_all('/\d+(?:[.,]\d+)?%?/u', $value, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $source
     */
    private function personalDataIsAbsent(array $normalized, array $source): bool
    {
        $output = $this->flattenText($normalized);
        $sourceText = $this->flattenText($source);

        foreach ([
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}/i',
            '/@/',
            '/\\+63/',
            '/\\b09\\d{9}\\b/',
            '/\\b(?:Mr|Mrs|Ms|Mx|Dr|Engr|Prof|Atty)\\.\\s+\\p{Lu}\\p{L}*\\b/u',
        ] as $pattern) {
            preg_match_all($pattern, $output, $matches);

            foreach ($matches[0] as $match) {
                if (! str_contains($sourceText, $match)) {
                    return false;
                }
            }
        }

        $sourceNames = $this->personNames($this->flattenText($source));

        if ($sourceNames === []) {
            return $this->personNames($output) === [];
        }

        foreach (array_diff($this->personNames($output), $sourceNames) as $name) {
            if ($name !== '') {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, string> */
    private function personNames(string $value): array
    {
        $names = [];

        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3}\b/u', $value, $matches);

        foreach ($matches[0] as $phrase) {
            foreach (self::HONORIFICS as $honorific) {
                if (str_starts_with($phrase.' ', $honorific.' ')) {
                    $names[] = $phrase;
                }
            }
        }

        preg_match_all('/\b[A-Z][a-z]{2,}(?:\s+[A-Z][a-z]{2,})+\b/u', $value, $candidates);

        foreach ($candidates[0] as $phrase) {
            if (! in_array($phrase, $names, true)) {
                $names[] = $phrase;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     */
    private function internalsAreAbsent(array $normalized): bool
    {
        $output = strtolower($this->flattenText($normalized));

        if (str_contains($output, '_unclassified_')) {
            return false;
        }

        if ((bool) preg_match('/\b[0-9a-f]{12,}\b/', $output)) {
            return false;
        }

        foreach (self::LEAK_WORDS as $word) {
            if (str_contains($output, $word)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $source
     */
    private function readsAsFilipino(array $normalized, array $source): bool
    {
        $text = mb_strtolower($this->flattenText($normalized), 'UTF-8');
        $sourceText = $this->normalizeText($this->flattenText($source));

        if ($this->normalizeText($this->flattenText($normalized)) === $sourceText) {
            return false;
        }

        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach (self::FILIPINO_MARKERS as $marker) {
            if (in_array(mb_strtolower($marker, 'UTF-8'), $words, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{summary: string, patterns: array<int, string>, experiences: array<int, array{title: string, body: string}>}  $normalized
     */
    private function flattenText(array $normalized): string
    {
        $text = $normalized['summary'].' '.implode(' ', $normalized['patterns']);

        foreach ($normalized['experiences'] as $experience) {
            $text .= ' '.$experience['title'].' '.$experience['body'];
        }

        return $text;
    }

    private function normalizeText(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function textLength(string $value): int
    {
        return mb_strlen($this->normalizeText($value));
    }
}
