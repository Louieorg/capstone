<?php

namespace App\Services;

/**
 * Validates a generated cluster explanation against the evidence it must
 * describe.
 *
 * The DSS decides; this only checks that an explanation stays inside the
 * evidence it was given. It holds no Http, Eloquent, database or logging
 * dependency, and it never returns a partially accepted result: the first
 * failed hard rule rejects the whole output, so a stored explanation is
 * always whole and always grounded.
 */
class ClusterExplanationValidator
{
    public const RULE_SHAPE = 'shape';

    public const RULE_LENGTH = 'length';

    public const RULE_NUMBERS = 'numbers';

    public const RULE_PERSONAL_DATA = 'personal_data';

    public const RULE_INTERNALS = 'internals';

    public const WARNING_UNFAMILIAR_NAMES = 'unfamiliar_names';

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

    private const PACKAGE_TEXT_KEYS = [
        'title', 'description', 'impact', 'frequency',
        'affected_users', 'current_process',
    ];

    private const PACKAGE_COUNT_KEYS = ['report_count', 'evidence_shown', 'evidence_total'];

    private const HONORIFICS = ['Mr.', 'Mrs.', 'Ms.', 'Mx.', 'Dr.', 'Engr.', 'Prof.', 'Atty.'];

    private const LEAK_WORDS = ['cluster key', 'unclassified', 'hash', 'score', 'scores'];

    /**
     * Validate a decoded explanation against its evidence package.
     *
     * @param  array<array-key, mixed>  $result  Decoded model output.
     * @param  array<string, mixed>  $package  The evidence it must describe.
     * @return array{ok: bool, result: array<string, mixed>|null, rule: string|null, warnings: array<int, string>}
     */
    public function validate(array $result, array $package): array
    {
        $normalized = $this->normalizeShape($result);

        if ($normalized === null) {
            return $this->reject(self::RULE_SHAPE);
        }

        $lengthRule = $this->checkLengths($normalized);

        if ($lengthRule !== null) {
            return $this->reject($lengthRule);
        }

        if (! $this->numbersAreGrounded($normalized, $package)) {
            return $this->reject(self::RULE_NUMBERS);
        }

        if (! $this->personalDataIsAbsent($normalized, $package)) {
            return $this->reject(self::RULE_PERSONAL_DATA);
        }

        if (! $this->internalsAreAbsent($normalized, $package)) {
            return $this->reject(self::RULE_INTERNALS);
        }

        return [
            'ok' => true,
            'result' => $normalized,
            'rule' => null,
            'warnings' => $this->warnings($normalized, $package),
        ];
    }

    /**
     * @return array{ok: false, result: null, rule: string, warnings: array<int, string>}
     */
    private function reject(string $rule): array
    {
        return ['ok' => false, 'result' => null, 'rule' => $rule, 'warnings' => []];
    }

    /**
     * Normalize strings and enforce the required shape.
     *
     * @param  array<array-key, mixed>  $result
     * @return array<string, mixed>|null
     */
    private function normalizeShape(array $result): ?array
    {
        $keys = array_keys($result);
        sort($keys);

        $expected = self::RESULT_KEYS;
        sort($expected);

        if ($keys !== $expected) {
            return null;
        }

        $summary = $result['summary'] ?? null;

        if (! is_string($summary)) {
            return null;
        }

        $patterns = $result['patterns'] ?? null;

        if (! is_array($patterns) || ! array_is_list($patterns)) {
            return null;
        }

        $experiences = $result['experiences'] ?? null;

        if (! is_array($experiences) || ! array_is_list($experiences)) {
            return null;
        }

        $normalizedPatterns = [];

        foreach ($patterns as $pattern) {
            if (! is_string($pattern)) {
                return null;
            }

            $normalizedPatterns[] = $this->normalizeText($pattern);
        }

        $normalizedExperiences = [];

        foreach ($experiences as $experience) {
            if (! is_array($experience) || array_is_list($experience)) {
                return null;
            }

            $experienceKeys = array_keys($experience);
            sort($experienceKeys);

            if ($experienceKeys !== ['body', 'title']) {
                return null;
            }

            if (! is_string($experience['title']) || ! is_string($experience['body'])) {
                return null;
            }

            $normalizedExperiences[] = [
                'title' => $this->normalizeText($experience['title']),
                'body' => $this->normalizeText($experience['body']),
            ];
        }

        return [
            'summary' => $this->normalizeText($summary),
            'patterns' => $normalizedPatterns,
            'experiences' => $normalizedExperiences,
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function checkLengths(array $normalized): ?string
    {
        $summaryLength = $this->textLength($normalized['summary']);

        if ($summaryLength < self::SUMMARY_MIN || $summaryLength > self::SUMMARY_MAX) {
            return self::RULE_LENGTH;
        }

        $patterns = $normalized['patterns'];

        if (count($patterns) < self::PATTERN_MIN_ITEMS || count($patterns) > self::PATTERN_MAX_ITEMS) {
            return self::RULE_LENGTH;
        }

        $total = $summaryLength;

        foreach ($patterns as $pattern) {
            $length = $this->textLength($pattern);

            if ($length < self::PATTERN_MIN_LENGTH || $length > self::PATTERN_MAX_LENGTH) {
                return self::RULE_LENGTH;
            }

            $total += $length;
        }

        if (count($normalized['experiences']) > self::EXPERIENCE_MAX_ITEMS) {
            return self::RULE_LENGTH;
        }

        foreach ($normalized['experiences'] as $experience) {
            $titleLength = $this->textLength($experience['title']);
            $bodyLength = $this->textLength($experience['body']);

            if ($titleLength < self::EXPERIENCE_TITLE_MIN || $titleLength > self::EXPERIENCE_TITLE_MAX) {
                return self::RULE_LENGTH;
            }

            if ($bodyLength < self::EXPERIENCE_BODY_MIN || $bodyLength > self::EXPERIENCE_BODY_MAX) {
                return self::RULE_LENGTH;
            }

            $total += $titleLength + $bodyLength;
        }

        return $total > self::TOTAL_MAX_LENGTH ? self::RULE_LENGTH : null;
    }

    /**
     * Every digit run in the output must appear, as a whole token, somewhere in
     * the evidence. Substring matching is deliberately not used, so "5" is not
     * accepted merely because "50" is present.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $package
     */
    private function numbersAreGrounded(array $normalized, array $package): bool
    {
        $allowed = $this->packageNumberTokens($package);

        foreach ($this->outputNumberTokens($normalized) as $token) {
            if (! in_array($token, $allowed, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $package
     * @return array<int, string>
     */
    private function packageNumberTokens(array $package): array
    {
        $scalars = [];

        foreach (['category', 'cluster_label'] as $key) {
            $scalars[] = isset($package[$key]) && is_scalar($package[$key]) ? (string) $package[$key] : '';
        }

        foreach (self::PACKAGE_COUNT_KEYS as $key) {
            $scalars[] = (string) ($package[$key] ?? '');
        }

        foreach ($this->packageEvidenceStrings($package) as $value) {
            $scalars[] = $value;
        }

        $tokens = [];
        $this->collectNumberTokens($tokens, implode(' ', array_filter($scalars, fn (string $v): bool => $v !== '')));

        return $tokens;
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array<int, string>
     */
    private function outputNumberTokens(array $normalized): array
    {
        $tokens = [];
        $this->collectNumberTokens($tokens, $this->flattenText($normalized));

        return $tokens;
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function collectNumberTokens(array &$tokens, string $text): void
    {
        preg_match_all('/\d+/', $text, $matches);

        foreach ($matches[0] as $match) {
            $tokens[] = $match;
        }
    }

    /**
     * Reject personal identifiers the evidence does not already contain.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $package
     */
    private function personalDataIsAbsent(array $normalized, array $package): bool
    {
        $output = $this->flattenText($normalized);
        $evidence = $this->packageText($package);

        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $output)) {
            return false;
        }

        if (str_contains($output, '@') || str_contains($output, '+63')) {
            return false;
        }

        if (preg_match('/\b09\d{9}\b/', $output)) {
            return false;
        }

        foreach (self::HONORIFICS as $honorific) {
            $pattern = '/\\b'.preg_quote($honorific, '/').'\\s+\\p{Lu}/u';

            if (! preg_match($pattern, $output)) {
                continue;
            }

            if (! preg_match($pattern, $evidence)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reject internal implementation detail the evidence does not contain.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $package
     */
    private function internalsAreAbsent(array $normalized, array $package): bool
    {
        $output = $this->flattenText($normalized);
        $evidence = $this->packageText($package);

        if (str_contains($output, '_unclassified_')) {
            return false;
        }

        if (preg_match('/\b[0-9a-f]{12,}\b/', $output)) {
            return false;
        }

        foreach (self::LEAK_WORDS as $word) {
            $pattern = '/\\b'.preg_quote($word, '/').'\\b/i';

            if (preg_match($pattern, $output) && ! preg_match($pattern, $evidence)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Warning-only checks. These never fail validation.
     *
     * @param  array<string, mixed>  $normalized
     * @param  array<string, mixed>  $package
     * @return array<int, string>
     */
    private function warnings(array $normalized, array $package): array
    {
        $evidence = strtolower($this->packageText($package));
        $unfamiliar = 0;

        preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)+\b/u', $this->flattenText($normalized), $matches);

        foreach ($matches[0] as $phrase) {
            if (! str_contains($evidence, strtolower($phrase))) {
                $unfamiliar++;
            }
        }

        return $unfamiliar > 0 ? [self::WARNING_UNFAMILIAR_NAMES] : [];
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function flattenText(array $normalized): string
    {
        $text = $normalized['summary'].' '.implode(' ', $normalized['patterns']);

        foreach ($normalized['experiences'] as $experience) {
            $text .= ' '.$experience['title'].' '.$experience['body'];
        }

        return $text;
    }

    /**
     * Every scalar text value carried by the package's evidence items.
     *
     * @param  array<string, mixed>  $package
     * @return array<int, string>
     */
    private function packageEvidenceStrings(array $package): array
    {
        $values = [];

        $evidence = $package['evidence'] ?? [];

        if (! is_array($evidence)) {
            return $values;
        }

        foreach ($evidence as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach (self::PACKAGE_TEXT_KEYS as $key) {
                if (isset($item[$key]) && is_scalar($item[$key])) {
                    $values[] = (string) $item[$key];
                }
            }

            $groups = $item['affected_group'] ?? [];

            if (is_array($groups)) {
                foreach ($groups as $group) {
                    if (is_scalar($group)) {
                        $values[] = (string) $group;
                    }
                }
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function packageText(array $package): string
    {
        $parts = [];

        foreach (['category', 'cluster_label'] as $key) {
            if (isset($package[$key]) && is_scalar($package[$key])) {
                $parts[] = (string) $package[$key];
            }
        }

        return implode(' ', array_merge($parts, $this->packageEvidenceStrings($package)));
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
