<?php

namespace App\Services;

use App\Models\Feedback;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the grounded evidence package for one already-decided DSS cluster.
 *
 * The DSS decides; this only describes what the DSS already decided from. The
 * cluster is never re-derived here: the report ids come exclusively from the
 * 'evidence' key the DSS attached to the idea, so the package can only ever
 * describe the exact collection the DSS scored. If that set cannot be loaded
 * in full, the whole package is dropped rather than partially substituted.
 *
 * This class is deliberately free of any AI, HTTP or queue dependency: it
 * performs one read-only query and returns plain data. It never clusters,
 * scores, qualifies, persists, dispatches, or writes.
 */
class ClusterEvidenceService
{
    /**
     * Evidence items included in the package.
     */
    public const MAX_ITEMS = 6;

    public const MAX_TITLE_LENGTH = 120;

    public const MAX_DESCRIPTION_LENGTH = 400;

    public const MAX_IMPACT_LENGTH = 240;

    public const MAX_SHORT_VALUE_LENGTH = 80;

    public const MAX_AFFECTED_GROUPS = 4;

    public const MAX_AFFECTED_GROUP_LENGTH = 40;

    public const MAX_PACKAGE_LENGTH = 4000;

    /**
     * Share of a report that the DSS must reach before the cluster is worth
     * describing as "what people are experiencing". A single report is one
     * person's account, not a pattern.
     */
    public const MINIMUM_REPORTS = 2;

    private const SCHEMA_VERSION = '1';

    /**
     * Duplicate titles are collapsed at the same similarity the DSS already
     * uses for its own intra-run idea de-duplication.
     */
    private const DUPLICATE_SIMILARITY_THRESHOLD = 85.0;

    private const FREQUENCY_RANKS = [
        'Everyday' => 4,
        'Often' => 3,
        'Sometimes' => 2,
        'Rarely' => 1,
    ];

    private const AFFECTED_USERS_RANKS = [
        'More than 500' => 4,
        '200-500' => 3,
        '50-200' => 2,
        'Less than 50' => 1,
    ];

    /**
     * Build the evidence package for one idea's cluster.
     *
     * @param  array<string, mixed>  $idea  One idea entry from the DSS pipeline.
     * @param  string  $category  The category the idea was generated for.
     * @return array<string, mixed>|null Null when the cluster cannot be resolved exactly.
     */
    public function buildPackage(array $idea, string $category): ?array
    {
        $feedbackIds = $this->evidenceIds($idea);

        if ($feedbackIds === []) {
            return null;
        }

        $feedbacks = Feedback::query()
            ->whereIn('id', $feedbackIds)
            ->where('status', 'approved')
            ->notFlagged()
            ->get();

        // The loaded set must be exactly the id set the DSS scored. A report
        // that was withdrawn, flagged or removed since that run means the
        // evidence no longer matches the decision, so nothing is described.
        if ($this->resolvedIds($feedbacks) !== $feedbackIds) {
            return null;
        }

        $evidenceTotal = $feedbacks->count();

        $items = $this->buildItems($feedbacks);

        // Drop trailing items until the serialised package fits. If a single
        // item still does not fit, nothing is described at all.
        for ($count = count($items); $count >= 1; $count--) {
            $package = $this->compose($idea, $category, array_slice($items, 0, $count), $evidenceTotal);

            if ($this->fits($package)) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Whether a cluster carries enough reports to describe as shared experience.
     *
     * @param  array<string, mixed>  $package
     */
    public function isSynthesizable(array $package): bool
    {
        return (int) ($package['report_count'] ?? 0) >= self::MINIMUM_REPORTS;
    }

    /**
     * Content-addressed key for a package.
     *
     * Identical evidence under the same prompt version, model and language
     * always resolves to the same key, so the same evidence is never described
     * twice. Any change to the evidence, or to how it would be described,
     * resolves to a different key.
     *
     * @param  array<string, mixed>  $package
     */
    public function evidenceKey(array $package, string $promptVersion, string $model, string $language): string
    {
        $canonical = json_encode(
            $this->sortKeysRecursively($package),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );

        return hash(
            'sha256',
            'v1'."\x1F".$promptVersion."\x1F".$model."\x1F".$language."\x1F".$canonical
        );
    }

    /**
     * The report ids the DSS attached to this idea, normalised and ordered.
     *
     * @param  array<string, mixed>  $idea
     * @return array<int, int>
     */
    private function evidenceIds(array $idea): array
    {
        $ids = $idea['evidence']['feedback_ids'] ?? null;

        if (! is_array($ids) || $ids === []) {
            return [];
        }

        $normalised = array_map(
            fn ($id): int => (int) $id,
            array_values(array_unique(array_filter($ids, 'is_numeric')))
        );

        sort($normalised);

        return array_values($normalised);
    }

    /**
     * @param  Collection<int, Feedback>  $feedbacks
     * @return array<int, int>
     */
    private function resolvedIds(Collection $feedbacks): array
    {
        $ids = $feedbacks->pluck('id')->map(fn ($id): int => (int) $id)->all();

        sort($ids);

        return array_values($ids);
    }

    /**
     * Order the cluster deterministically, collapse repeated reports, apply
     * the field caps and keep at most MAX_ITEMS entries.
     *
     * Ordering never relies on a report id, so the same cluster always
     * produces the same package regardless of insertion order.
     *
     * @param  Collection<int, Feedback>  $feedbacks
     * @return array<int, array<string, mixed>>
     */
    private function buildItems(Collection $feedbacks): array
    {
        // Ranks are negated so that ascending comparison yields the strongest
        // frequency and reach first, with the normalized title as the final
        // deterministic tiebreak. No report id takes part in the ordering.
        $ordered = $feedbacks
            ->sort(function (Feedback $left, Feedback $right): int {
                return [
                    -$this->frequencyRank($left),
                    -$this->affectedUsersRank($left),
                    $this->normalized($left->translated_title),
                ] <=> [
                    -$this->frequencyRank($right),
                    -$this->affectedUsersRank($right),
                    $this->normalized($right->translated_title),
                ];
            })
            ->values();

        $items = [];
        $seenTitles = [];

        foreach ($ordered as $feedback) {
            $title = $this->truncate((string) $feedback->translated_title, self::MAX_TITLE_LENGTH);
            $normalizedTitle = $this->normalized($title);

            if ($this->isRepeated($normalizedTitle, $seenTitles)) {
                continue;
            }

            $seenTitles[] = $normalizedTitle;

            $items[] = $this->item($feedback, $title);
        }

        return array_slice($items, 0, self::MAX_ITEMS);
    }

    /**
     * @return array<string, mixed>
     */
    private function item(Feedback $feedback, string $title): array
    {
        return [
            'title' => $title,
            'description' => $this->truncate((string) $feedback->translated_description, self::MAX_DESCRIPTION_LENGTH),
            'impact' => $this->truncate((string) ($feedback->translated_impact ?? ''), self::MAX_IMPACT_LENGTH),
            'frequency' => $this->truncate((string) ($feedback->frequency ?? ''), self::MAX_SHORT_VALUE_LENGTH),
            'affected_users' => $this->truncate((string) ($feedback->affected_users ?? ''), self::MAX_SHORT_VALUE_LENGTH),
            'affected_group' => $this->affectedGroups($feedback),
            'current_process' => $this->truncate((string) ($feedback->current_process ?? ''), self::MAX_SHORT_VALUE_LENGTH),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function affectedGroups(Feedback $feedback): array
    {
        $groups = $feedback->affected_group;
        $groups = is_array($groups) ? $groups : array_filter([$groups]);

        $normalised = collect($groups)
            ->filter(fn ($group): bool => is_string($group) && trim($group) !== '')
            ->map(fn (string $group): string => $this->truncate(trim($group), self::MAX_AFFECTED_GROUP_LENGTH))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_slice($normalised, 0, self::MAX_AFFECTED_GROUPS);
    }

    /**
     * @param  array<string, mixed>  $idea
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function compose(array $idea, string $category, array $items, int $evidenceTotal): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'category' => $category,
            'cluster_label' => (string) ($idea['cluster_label'] ?? ''),
            'report_count' => (int) ($idea['evidence']['reports_count'] ?? $evidenceTotal),
            'evidence_shown' => count($items),
            'evidence_total' => $evidenceTotal,
            'evidence' => $items,
        ];
    }

    /**
     * Whether an item repeats a title already kept, exactly or closely.
     *
     * @param  array<int, string>  $seenTitles
     */
    private function isRepeated(string $normalizedTitle, array $seenTitles): bool
    {
        if ($normalizedTitle === '') {
            return false;
        }

        foreach ($seenTitles as $seenTitle) {
            if ($seenTitle === $normalizedTitle) {
                return true;
            }

            similar_text($seenTitle, $normalizedTitle, $percentage);

            if ($percentage >= self::DUPLICATE_SIMILARITY_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function fits(array $package): bool
    {
        return strlen((string) json_encode($package, JSON_UNESCAPED_UNICODE)) <= self::MAX_PACKAGE_LENGTH;
    }

    private function frequencyRank(Feedback $feedback): int
    {
        return self::FREQUENCY_RANKS[(string) ($feedback->frequency ?? '')] ?? 0;
    }

    private function affectedUsersRank(Feedback $feedback): int
    {
        return self::AFFECTED_USERS_RANKS[(string) ($feedback->affected_users ?? '')] ?? 0;
    }

    private function truncate(string $value, int $limit): string
    {
        return Str::limit($value, $limit, '');
    }

    private function normalized(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower($value ?? '')) ?? '');
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private function sortKeysRecursively(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->sortKeysRecursively($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
