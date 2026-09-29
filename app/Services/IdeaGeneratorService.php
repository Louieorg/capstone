<?php

namespace App\Services;

use App\Models\IdeaEvaluation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class IdeaGeneratorService
{
    private const MIN_TITLE_WORDS = 6;

    private const MAX_TITLE_WORDS = 12;

    private const MINIMUM_CONCEPT_SCORE = 2.0;

    private const CLOSE_CANDIDATE_MARGIN = 1.0;

    private const SCOPE_TIE_BREAK_BONUS = 0.5;

    /**
     * A generated idea exposes exactly one meaningful alternative direction.
     * Further scored candidates stay internal directions and are never
     * presented as separate ideas.
     */
    private const MAX_ALTERNATIVES = 1;

    /**
     * @param  mixed  $groupFeedbacks
     * @return array<string, mixed>
     */
    public function generate($groupName, $category, $groupFeedbacks, $reports, $votes, $frequencyScore, $impactScore, ?string $clusterKey = null)
    {
        // Build a rich text corpus from all feedback in this group
        $allTitles = $groupFeedbacks->pluck('translated_title')->implode(' ');
        $allDescriptions = $groupFeedbacks->pluck('translated_description')->implode(' ');
        $allImpacts = $groupFeedbacks->pluck('translated_impact')->filter()->implode(' ');

        $text = strtolower($groupName.' '.$allTitles.' '.$allDescriptions.' '.$allImpacts);
        $displayGroupName = Str::headline($groupName);

        // Dominant affected group
        $topGroup = $this->resolveTopGroup($groupFeedbacks);

        // Dominant current process (what's failing right now)
        $currentProcess = $this->resolveCurrentProcess($groupFeedbacks);

        // Dominant department where the issue occurs
        $dominantDepartment = $this->resolveDominantDepartment($groupFeedbacks);

        // Detect problem signals from text
        $signals = $this->detectSignals($text);

        // Rule-based solution concept — the DSS decides, AI only explains later.
        $solution = $this->generateSolutionConcept([
            'cluster_key' => $this->resolveClusterKey($clusterKey, (string) $groupName),
            'category' => (string) $category,
            'group_name' => $displayGroupName,
            'text' => $text,
            'process' => $currentProcess,
            'group' => $topGroup,
            'department' => $dominantDepartment,
            'frequency' => (float) $frequencyScore,
            'impact' => (float) $impactScore,
        ]);

        // Build all parts
        $title = $solution['title'];
        $description = $this->generateDescription($displayGroupName, $category, $signals, $reports, $votes, $topGroup, $currentProcess, $frequencyScore, $impactScore, $dominantDepartment);
        $objectives = $this->generateObjectives($signals, $category, $displayGroupName, $topGroup, $groupFeedbacks, $dominantDepartment);
        $explanation = $this->generateExplanation($displayGroupName, $topGroup, $reports, $votes, $frequencyScore, $impactScore);

        return [
            'title' => $title,
            'description' => $description,
            'general_objective' => $objectives['general'],
            'specific_objectives' => $objectives['specific'],
            'explanation' => $explanation,
            'top_group' => $topGroup,
            'concept' => $solution['concept'],
            'project_name' => $solution['project_name'],
            'cluster_key' => $solution['cluster_key'],
        ];
    }

    // ══════════════════════════════════════════════
    // SIGNAL DETECTION
    // Reads the corpus and flags what kind of problem this is
    // ══════════════════════════════════════════════
    private function detectSignals(string $text): array
    {
        return [
            'is_slow' => $this->has($text, ['slow', 'delay', 'wait', 'queue', 'long line', 'hour', 'hours']),
            'is_manual' => $this->has($text, ['manual', 'paper', 'physical', 'form', 'handwritten', 'walk-in']),
            'is_no_system' => $this->has($text, ['no system', 'no platform', 'no portal', 'no online', 'no way to track']),
            'is_access' => $this->has($text, ['access', 'login', 'password', 'account', 'locked', 'unavailable']),
            'is_tracking' => $this->has($text, ['track', 'status', 'update', 'progress', 'monitor', 'check']),
            'is_scheduling' => $this->has($text, ['schedule', 'conflict', 'clash', 'overlap', 'slot', 'booking']),
            'is_record' => $this->has($text, ['record', 'file', 'document', 'grade', 'transcript', 'lost']),
            'is_booking' => $this->has($text, ['book', 'reserve', 'reservation', 'appointment', 'slot']),
            'is_complaint' => $this->has($text, ['complaint', 'report', 'issue', 'concern', 'feedback']),
            'is_crowded' => $this->has($text, ['crowd', 'congested', 'full', 'packed', 'overloaded']),
            'is_wifi' => $this->has($text, ['wifi', 'internet', 'connectivity', 'network', 'signal', 'connection']),
            'is_notification' => $this->has($text, ['notify', 'notification', 'alert', 'inform', 'update']),
        ];
    }

    private function has(string $text, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }

    // ══════════════════════════════════════════════
    // SOLUTION CONCEPT ENGINE
    // Cluster key → cluster evidence → current process → affected group
    // → department context → up to 3 candidate concepts → rule-based
    // scoring → primary + alternative concept → project name → title
    // pattern → exact existing-title check → final idea.
    //
    // The DSS decides the concept. AI never chooses the concept, the
    // project type, the project name, or the title.
    // ══════════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $context
     * @return array{title: string, concept: array<string, mixed>, project_name: ?string, cluster_key: ?string}
     */
    private function generateSolutionConcept(array $context): array
    {
        $profile = $this->resolveSolutionProfile($context['cluster_key'], (string) $context['group_name']);

        if ($profile === null) {
            return $this->presentFallback(
                $this->categoryFallback((string) $context['category'], (string) $context['cluster_key']),
                $context,
                $context['cluster_key'],
                'Fallback concept: no specific problem profile matched this cluster.'
            );
        }

        $scored = $this->scoreSolutionCandidates($profile, $context);

        if ($scored === [] || (float) $scored[0]['score'] < self::MINIMUM_CONCEPT_SCORE) {
            return $this->presentFallback(
                $this->profileFallback($profile),
                $context,
                $profile['key'],
                'Fallback concept: the cluster-specific safety direction was used.'
            );
        }

        $existingTitles = $this->existingTitles();
        $reservedNames = [];
        $selection = $this->selectSolutionConcepts($scored);
        $primary = $selection['primary'];
        $combined = $selection['combined'];

        // Combine at most two concepts — never a feature list.
        $conceptLabel = $combined === []
            ? $primary['concept']
            : ($primary['combined_concept'] ?? $primary['concept']);

        $primaryCandidate = $primary;
        $primaryCandidate['concept'] = $conceptLabel;

        $presented = $this->presentConcept(
            $primaryCandidate,
            $profile,
            $profile['key'].'|'.$primary['key'],
            $existingTitles,
            $reservedNames
        );

        $alternatives = [];

        foreach ($scored as $candidate) {
            if (count($alternatives) >= self::MAX_ALTERNATIVES) {
                break;
            }

            if ($candidate['key'] === $primary['key'] || in_array($candidate['key'], $combined, true)) {
                continue;
            }

            if ($candidate['score'] < self::MINIMUM_CONCEPT_SCORE) {
                continue;
            }

            $alternatives[] = $this->presentConcept(
                $candidate,
                $profile,
                $profile['key'].'|'.$candidate['key'],
                $existingTitles,
                $reservedNames
            );
        }

        return [
            'title' => $presented['title'],
            'project_name' => $presented['project_name'],
            'cluster_key' => $profile['key'],
            'concept' => [
                'primary' => $presented['concept'],
                'primary_key' => $primary['key'],
                'score' => $primary['score'],
                'evidence' => $primary['evidence'],
                'combined' => $combined,
                'alternatives' => $alternatives,
                'fallback_used' => false,
            ],
        ];
    }

    /**
     * Fallback concept for clusters without enough specific evidence.
     *
     * @param  array<string, mixed>  $fallback
     * @param  array<string, mixed>  $context
     * @return array{title: string, concept: array<string, mixed>, project_name: ?string, cluster_key: ?string}
     */
    private function presentFallback(array $fallback, array $context, ?string $clusterKey, string $evidenceLabel): array
    {
        $fallback['score'] = 0.0;
        $fallback['evidence'] = [$evidenceLabel];

        $reservedNames = [];

        $presented = $this->presentConcept(
            $fallback,
            $fallback,
            $this->fallbackSeed($fallback, $context),
            $this->existingTitles(),
            $reservedNames
        );

        return [
            'title' => $presented['title'],
            'project_name' => $presented['project_name'],
            'cluster_key' => $clusterKey,
            'concept' => [
                'primary' => $presented['concept'],
                'primary_key' => $fallback['key'],
                'score' => 0.0,
                'evidence' => $fallback['evidence'],
                'combined' => [],
                'alternatives' => [],
                'fallback_used' => true,
            ],
        ];
    }

    /**
     * The presentation seed of a fallback concept. It carries the cluster key
     * so two fallback clusters in the same category do not land on the same
     * project name, and it stays reproducible for the same evidence.
     *
     * @param  array<string, mixed>  $fallback
     * @param  array<string, mixed>  $context
     */
    private function fallbackSeed(array $fallback, array $context): string
    {
        return implode('|', [
            (string) $fallback['key'],
            Str::lower(trim((string) $context['category'])),
            (string) ($context['cluster_key'] ?? ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function profileFallback(array $profile): array
    {
        $fallback = $profile['fallback'];
        $fallback['scope'] = $fallback['scope'] ?? $profile['scope'];
        $fallback['patterns'] = $fallback['patterns'] ?? $profile['patterns'];

        return $fallback;
    }

    private function resolveClusterKey(?string $clusterKey, string $groupName): ?string
    {
        $value = trim((string) ($clusterKey ?? ''));

        if ($value === '') {
            $value = trim($groupName);
        }

        $normalized = Str::lower($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveSolutionProfile(?string $clusterKey, string $groupName): ?array
    {
        $profiles = $this->solutionProfiles();

        // Unclassified clusters never rediscover a profile from raw keywords.
        if ($clusterKey !== null && $this->isUnclassifiedClusterKey($clusterKey)) {
            return null;
        }

        if ($clusterKey !== null && isset($profiles[$clusterKey])) {
            return $profiles[$clusterKey];
        }

        $label = Str::lower(trim($groupName));

        foreach ($profiles as $profile) {
            if (Str::lower($profile['label']) === $label) {
                return $profile;
            }
        }

        return null;
    }

    private function isUnclassifiedClusterKey(string $clusterKey): bool
    {
        return str_contains($clusterKey, '_unclassified_');
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    private function scoreSolutionCandidates(array $profile, array $context): array
    {
        $scored = [];

        foreach ($profile['candidates'] as $index => $candidate) {
            $score = (float) $candidate['weight'];
            $evidence = ['Cluster evidence: '.$profile['label']];

            $matchedSignals = [];

            foreach ($candidate['signals'] as $signal) {
                if (str_contains((string) $context['text'], $signal)) {
                    $matchedSignals[] = $signal;
                }
            }

            $matchedSignals = array_slice($matchedSignals, 0, 3);

            if ($matchedSignals !== []) {
                $score += count($matchedSignals);
                $evidence[] = 'Matched signals: '.implode(', ', $matchedSignals);
            }

            $processPoints = $this->matchWeightedValue($context['process'], $candidate['process'] ?? []);

            if ($processPoints > 0) {
                $score += $processPoints;
                $evidence[] = 'Current process: '.(string) $context['process'];
            }

            $groupPoints = $this->matchWeightedValue($context['group'], $candidate['groups'] ?? []);

            if ($groupPoints > 0) {
                $score += $groupPoints;
                $evidence[] = 'Affected group: '.(string) $context['group'];
            }

            $departmentPoints = $this->matchWeightedValue($context['department'], $candidate['departments'] ?? []);

            if ($departmentPoints > 0) {
                $score += $departmentPoints;
                $evidence[] = 'Department context: '.(string) $context['department'];
            }

            $scored[] = [
                ...$candidate,
                'index' => $index,
                'score' => round($score, 2),
                'evidence' => $evidence,
            ];
        }

        $scored = $this->sortScoredCandidates($scored);

        return $this->applyScopeTieBreak($scored, $context);
    }

    /**
     * @param  array<int, array<string, mixed>>  $scored
     * @return array<int, array<string, mixed>>
     */
    private function sortScoredCandidates(array $scored): array
    {
        usort($scored, fn (array $left, array $right): int => ($right['score'] <=> $left['score']) ?: ($left['index'] <=> $right['index']));

        return $scored;
    }

    /**
     * Frequency and impact may only break a close race — they never
     * override strong problem evidence.
     *
     * @param  array<int, array<string, mixed>>  $scored
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    private function applyScopeTieBreak(array $scored, array $context): array
    {
        if (count($scored) < 2) {
            return $scored;
        }

        if (($scored[0]['score'] - $scored[1]['score']) > self::CLOSE_CANDIDATE_MARGIN) {
            return $scored;
        }

        $preferredScope = ((float) $context['frequency'] >= 3.0 && (float) $context['impact'] >= 3.0)
            ? 'wide'
            : 'focused';

        foreach ($scored as $index => $candidate) {
            if (($candidate['scope_kind'] ?? 'focused') !== $preferredScope) {
                continue;
            }

            $scored[$index]['score'] = round((float) $candidate['score'] + self::SCOPE_TIE_BREAK_BONUS, 2);
            $scored[$index]['evidence'][] = 'Close race — frequency and impact favour the '.$preferredScope.' solution direction.';
        }

        return $this->sortScoredCandidates($scored);
    }

    /**
     * @param  array<int, array<string, mixed>>  $scored
     * @return array{primary: array<string, mixed>, combined: array<int, string>}
     */
    private function selectSolutionConcepts(array $scored): array
    {
        $primary = $scored[0];
        $combined = [];
        $partnerKey = $primary['combines_with'] ?? null;

        if (is_string($partnerKey) && $partnerKey !== '') {
            foreach ($scored as $candidate) {
                if ($candidate['key'] === $partnerKey && $this->isStronglySupported($candidate)) {
                    $combined = [$primary['key'], $candidate['key']];
                    break;
                }
            }
        }

        return ['primary' => $primary, 'combined' => $combined];
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function isStronglySupported(array $candidate): bool
    {
        return (float) $candidate['score'] >= ((float) $candidate['weight']) + 1.0;
    }

    /**
     * Exact, case-insensitive match against a declared weight map.
     *
     * @param  array<string, int|float|string>  $weights
     */
    private function matchWeightedValue(?string $value, array $weights): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }

        $needle = Str::lower(trim($value));

        foreach ($weights as $key => $points) {
            if (Str::lower(trim((string) $key)) === $needle) {
                return (float) $points;
            }
        }

        return 0.0;
    }

    /**
     * Attach a project name and a title to a selected concept.
     *
     * @param  array<string, mixed>  $candidate
     * @param  array<string, mixed>  $blueprint
     * @param  Collection<int, string>  $existingTitles
     * @param  array<int, string>  $reservedNames
     * @return array<string, mixed>
     */
    private function presentConcept(array $candidate, array $blueprint, string $seed, Collection $existingTitles, array &$reservedNames): array
    {
        $concept = (string) $candidate['concept'];
        $scope = (string) ($candidate['scope'] ?? $blueprint['scope'] ?? '');
        $patterns = $candidate['patterns'] ?? $blueprint['patterns'] ?? $this->defaultTitlePatterns();
        $names = array_values($candidate['names'] ?? []);

        // The concept identity is (concept, scope, declared names, seed). The
        // presentation for that identity is deterministic, so a title this
        // concept already owns is reconstructed and reused instead of
        // rotating onto another project name and creating a second record.
        $existing = $this->existingPresentationFor($names, $concept, $scope, $patterns, $seed, $existingTitles);

        if ($existing !== null) {
            if ($existing['name'] !== null) {
                $reservedNames[] = $existing['name'];
            }

            return [
                'concept' => $concept,
                'concept_key' => (string) $candidate['key'],
                'project_name' => $existing['name'],
                'title' => $existing['title'],
                'pattern' => $existing['pattern'],
                'score' => (float) ($candidate['score'] ?? 0.0),
                'evidence' => $candidate['evidence'] ?? [],
            ];
        }

        $selected = $this->selectProjectName(
            $names,
            $concept,
            $scope,
            $patterns,
            $seed,
            $existingTitles,
            $reservedNames
        );

        if ($selected === null) {
            // Every declared name is already taken — keep the concept, drop the brand.
            return [
                'concept' => $concept,
                'concept_key' => (string) $candidate['key'],
                'project_name' => null,
                'title' => $this->finalizeTitle($this->descriptiveTitle($concept, $scope)),
                'pattern' => trim($scope) === '' ? '{concept}' : '{concept} for {scope}',
                'score' => (float) ($candidate['score'] ?? 0.0),
                'evidence' => $candidate['evidence'] ?? [],
            ];
        }

        $reservedNames[] = $selected['name'];

        return [
            'concept' => $concept,
            'concept_key' => (string) $candidate['key'],
            'project_name' => $selected['name'],
            'title' => $selected['title'],
            'pattern' => $selected['pattern'],
            'score' => (float) ($candidate['score'] ?? 0.0),
            'evidence' => $candidate['evidence'] ?? [],
        ];
    }

    /**
     * The presentation this concept already owns, if one is persisted.
     *
     * Each candidate title is rebuilt with the same deterministic rules that
     * produced it and then compared exactly, so the identity is the concept's
     * own seed and naming vocabulary rather than loose text similarity. The
     * declared-name rotation order is honoured, and the descriptive
     * brand-less title is checked last so a concept that was already presented
     * without a project name is reused instead of being regenerated.
     *
     * @param  array<int, mixed>  $names
     * @param  array<int, string>  $patterns
     * @param  Collection<int, string>  $existingTitles
     * @return array{name: string|null, title: string, pattern: string}|null
     */
    private function existingPresentationFor(array $names, string $concept, string $scope, array $patterns, string $seed, Collection $existingTitles): ?array
    {
        if ($existingTitles->isEmpty()) {
            return null;
        }

        $names = array_values(array_filter($names, fn ($name): bool => is_string($name) && trim($name) !== ''));

        if ($names !== []) {
            $count = count($names);
            $start = crc32($seed) % $count;

            for ($offset = 0; $offset < $count; $offset++) {
                $name = $names[($start + $offset) % $count];
                $rendered = $this->buildTitle($name, $concept, $scope, $patterns, $seed);

                if ($this->isTitleTaken($rendered['title'], $existingTitles)) {
                    return ['name' => $name, ...$rendered];
                }
            }
        }

        $descriptive = $this->finalizeTitle($this->descriptiveTitle($concept, $scope));

        if ($this->isTitleTaken($descriptive, $existingTitles)) {
            return [
                'name' => null,
                'title' => $descriptive,
                'pattern' => trim($scope) === '' ? '{concept}' : '{concept} for {scope}',
            ];
        }

        return null;
    }

    /**
     * Deterministic rotation over the declared names, with exact
     * project-name/title uniqueness fallback.
     *
     * @param  array<int, mixed>  $names
     * @param  array<int, string>  $patterns
     * @param  Collection<int, string>  $existingTitles
     * @param  array<int, string>  $reservedNames
     * @return array{name: string, title: string, pattern: string}|null
     */
    private function selectProjectName(array $names, string $concept, string $scope, array $patterns, string $seed, Collection $existingTitles, array $reservedNames): ?array
    {
        $names = array_values(array_filter($names, fn ($name): bool => is_string($name) && trim($name) !== ''));

        if ($names === []) {
            return null;
        }

        $count = count($names);
        $start = crc32($seed) % $count;

        for ($offset = 0; $offset < $count; $offset++) {
            $name = $names[($start + $offset) % $count];

            if (in_array($name, $reservedNames, true) || $this->isProjectNameTaken($name, $existingTitles)) {
                continue;
            }

            $rendered = $this->buildTitle($name, $concept, $scope, $patterns, $seed);

            if ($this->isTitleTaken($rendered['title'], $existingTitles)) {
                continue;
            }

            return ['name' => $name, ...$rendered];
        }

        return null;
    }

    /**
     * Choose a declared title pattern that keeps the title medium-length.
     *
     * @param  array<int, string>  $patterns
     * @return array{title: string, pattern: string}
     */
    private function buildTitle(string $name, string $concept, string $scope, array $patterns, string $seed): array
    {
        $rendered = [];

        foreach ($patterns as $pattern) {
            $title = $this->renderTitlePattern($pattern, $name, $concept, $scope);

            if ($title === '') {
                continue;
            }

            $rendered[] = ['pattern' => $pattern, 'title' => $title, 'words' => $this->wordCount($title)];
        }

        if ($rendered === []) {
            return ['title' => $this->finalizeTitle($name.': '.$concept), 'pattern' => '{name}: {concept}'];
        }

        $inRange = array_values(array_filter(
            $rendered,
            fn (array $item): bool => $item['words'] >= self::MIN_TITLE_WORDS && $item['words'] <= self::MAX_TITLE_WORDS
        ));

        $pool = $inRange !== [] ? $inRange : $rendered;
        $choice = $pool[crc32($seed.'|pattern') % count($pool)];

        return ['title' => $this->finalizeTitle($choice['title']), 'pattern' => $choice['pattern']];
    }

    private function renderTitlePattern(string $pattern, string $name, string $concept, string $scope): string
    {
        if (str_contains($pattern, '{scope}') && trim($scope) === '') {
            return '';
        }

        $title = strtr($pattern, [
            '{name}' => $name,
            '{concept}' => $concept,
            '{scope}' => $scope,
            '{article}' => $this->articleFor($concept),
        ]);

        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);

        return str_contains($title, '{') ? '' : $title;
    }

    private function articleFor(string $concept): string
    {
        return preg_match('/^[aeiou]/i', $concept) === 1 ? 'An' : 'A';
    }

    private function finalizeTitle(string $title): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);

        return Str::limit($title, 255, '');
    }

    private function descriptiveTitle(string $concept, string $scope): string
    {
        return trim($scope) === '' ? $concept : $concept.' for '.$scope;
    }

    private function wordCount(string $title): int
    {
        $words = preg_split('/\s+/', trim($title)) ?: [];

        return count(array_filter($words, fn (string $word): bool => $word !== ''));
    }

    /**
     * @param  Collection<int, string>  $existingTitles
     */
    private function isTitleTaken(string $title, Collection $existingTitles): bool
    {
        $needle = Str::lower(trim($title));

        foreach ($existingTitles as $existing) {
            if (Str::lower(trim((string) $existing)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Collection<int, string>  $existingTitles
     */
    private function isProjectNameTaken(string $projectName, Collection $existingTitles): bool
    {
        $needles = [Str::lower(trim($projectName)).':', Str::lower(trim($projectName)).' —'];

        foreach ($existingTitles as $existing) {
            if (Str::startsWith(Str::lower(trim((string) $existing)), $needles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Existing generated titles. One lightweight query per generated cluster.
     * Generation still succeeds when the database is not reachable, so the
     * rule engine also works in DB-free unit tests.
     *
     * @return Collection<int, string>
     */
    protected function existingTitles(): Collection
    {
        if (Model::getConnectionResolver() === null) {
            return new Collection;
        }

        try {
            return IdeaEvaluation::query()
                ->pluck('idea_title')
                ->map(fn ($title): string => (string) $title)
                ->values();
        } catch (QueryException) {
            return new Collection;
        }
    }

    // ══════════════════════════════════════════════
    // CONCEPT CONFIGURATION
    // Declared title patterns, category fallbacks, and naming vocabulary.
    // ══════════════════════════════════════════════

    /**
     * Every declared pattern keeps the solution concept visible — a title
     * without the concept tells a student nothing about what to build.
     *
     * @return array<int, string>
     */
    private function defaultTitlePatterns(): array
    {
        return [
            '{name}: {concept}',
            '{name} — {concept}',
            '{name}: {concept} for {scope}',
            '{name} — {concept} for {scope}',
            '{name}: {article} {concept}',
            '{name}: {article} {concept} for {scope}',
        ];
    }

    /**
     * Patterns that always keep the scope visible. A fallback concept name
     * on its own never tells the reader which service the system serves.
     *
     * @return array<int, string>
     */
    private function scopeBoundTitlePatterns(): array
    {
        return [
            '{name}: {concept} for {scope}',
            '{name} — {concept} for {scope}',
        ];
    }

    /**
     * Declared acronym expansions — acronyms are never random letters. Each
     * expansion matches the concept the acronym is declared for.
     *
     * @return array<string, string>
     */
    private function acronymExpansions(): array
    {
        return [
            'CAMS' => 'Condition Assessment and Maintenance System',
            'DARS' => 'Digital Archive and Retrieval System',
            'LENS' => 'Laboratory Equipment Usage System',
        ];
    }

    /**
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    private function categoryFallback(string $category, ?string $clusterKey = null): array
    {
        $key = Str::lower(trim($category));

        return $this->resolveFallbackDirection(
            $this->categoryFallbacks()[$key] ?? $this->generalFallback(),
            $clusterKey
        );
    }

    /**
     * Rotate the declared fallback directions deterministically, so two
     * unclassified clusters in the same category never present the same safe
     * capstone direction. Cluster keys are derived from the report text, so
     * the rotation stays reproducible for the same evidence.
     *
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    private function resolveFallbackDirection(array $fallback, ?string $clusterKey): array
    {
        $directions = $fallback['directions'] ?? null;

        if (! is_array($directions) || $directions === []) {
            return $fallback;
        }

        $direction = $directions[abs(crc32((string) $clusterKey)) % count($directions)];

        unset($fallback['directions']);

        return [...$fallback, ...$direction];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function categoryFallbacks(): array
    {
        return [
            'enrollment' => [
                'key' => 'enrollment_service',
                'concept' => 'Enrollment Service System',
                'scope' => 'Student Registration Requests',
                'names' => ['EnrollEase', 'EnrollTrack', 'EnrollHub'],
            ],
            'academic process' => [
                'key' => 'academic_process_service',
                'concept' => 'Academic Process Service System',
                'scope' => 'Student Course Requests',
                'names' => ['AcadFlow', 'AcadTrack', 'ProcessHub'],
            ],
            'facilities' => [
                'key' => 'facility_service',
                'concept' => 'Facility Service System',
                'scope' => 'Campus Facilities',
                'names' => ['FacilDesk', 'FacilTrack', 'FacilHub'],
            ],
            'library' => [
                'key' => 'library_service',
                'concept' => 'Library Service System',
                'scope' => 'Library Assistance',
                'names' => ['LibPortal', 'LibDesk', 'LibLink'],
                // Three safe, non-repetitive directions for unclassified library clusters.
                'directions' => [
                    [
                        'concept' => 'Student Inquiry System',
                        'scope' => 'Library Assistance',
                        'names' => ['AskAide', 'InquiryDesk', 'InquiryHub'],
                        'patterns' => $this->scopeBoundTitlePatterns(),
                    ],
                    [
                        'concept' => 'Library Service Portal',
                        'scope' => 'Book Lending and Circulation',
                        'names' => ['LibPortal', 'LibDesk', 'LibLink'],
                        'patterns' => $this->scopeBoundTitlePatterns(),
                    ],
                    [
                        'concept' => 'Library Resource Request System',
                        'scope' => 'Borrowing and Reservations',
                        'names' => ['LibSource', 'BookRequest', 'ResourceHub'],
                        'patterns' => $this->scopeBoundTitlePatterns(),
                    ],
                ],
            ],
            'scheduling' => [
                'key' => 'scheduling_service',
                'concept' => 'Scheduling and Coordination System',
                'scope' => 'Campus Schedules',
                'names' => ['TimeSync', 'SlotEase', 'ScheduleHub'],
            ],
            'other' => [
                'key' => 'campus_service',
                'concept' => 'Campus Service System',
                'scope' => 'Campus Operations',
                'names' => ['CampusLink', 'CampusHub', 'CampusFlow'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generalFallback(): array
    {
        return [
            'key' => 'campus_service_improvement',
            'concept' => 'Campus Service Improvement System',
            'scope' => 'Campus Operations',
            'names' => ['CampusLink', 'CampusHub', 'CampusFlow'],
        ];
    }

    /**
     * Cluster-specific solution concepts, evidence rules, naming vocabulary,
     * and cluster fallbacks. Keys mirror the ClusteringService cluster keys.
     *
     * @return array<string, array<string, mixed>>
     */
    private function solutionProfiles(): array
    {
        $patterns = $this->defaultTitlePatterns();

        return [
            'facility_inspection_records' => [
                'key' => 'facility_inspection_records',
                'label' => 'Facility Inspection & Records',
                'scope' => 'Campus Facilities',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'inspection_records',
                        'concept' => 'Facility Inspection and Records System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        'signals' => ['inspection', 'checklist', 'inspection result', 'maintenance history', 'historical record', 'facility record'],
                        'process' => ['Manual or paper-based process' => 2, 'No solution exists at all' => 2, 'Send an email or message' => 1],
                        'groups' => ['Staff' => 1, 'Administration' => 1],
                        'departments' => ['CICS' => 1, 'CHM' => 1, 'CIT' => 1, 'CBEA' => 1],
                        'names' => ['InspectPro', 'FacilCheck', 'SiteLog'],
                        'combines_with' => 'maintenance_records',
                        'combined_concept' => 'Facility Inspection and Maintenance Records System',
                    ],
                    [
                        'key' => 'maintenance_records',
                        'concept' => 'Maintenance Records Platform',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'signals' => ['maintenance', 'repair', 'maintenance schedule', 'logbook', 'record keeping', 'service history'],
                        'process' => ['Manual or paper-based process' => 2],
                        'groups' => ['Staff' => 1],
                        'departments' => ['CICS' => 1, 'CHM' => 1],
                        'names' => ['MaintLog', 'CAMS'],
                    ],
                    [
                        'key' => 'inspection_monitoring',
                        'concept' => 'Facility Inspection Monitoring System',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'signals' => ['monitor', 'monitoring', 'status', 'overdue', 'follow up', 'track'],
                        'process' => ['No solution exists at all' => 2, 'Just wait and hope it gets fixed' => 1],
                        'groups' => ['Administration' => 1],
                        'departments' => ['CICS' => 1],
                        'names' => ['InspectWatch', 'FacilMonitor'],
                    ],
                ],
                'fallback' => [
                    'key' => 'facility_inspection_fallback',
                    'concept' => 'Facility Inspection and Records System',
                    'names' => ['InspectPro', 'FacilCheck', 'SiteLog', 'InspectWatch'],
                ],
            ],
            'facility_request_tracking' => [
                'key' => 'facility_request_tracking',
                'label' => 'Facility Request Tracking',
                'scope' => 'Campus Facilities',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'facility_request_tracking',
                        'concept' => 'Facility Request Tracking System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        'signals' => ['facility request', 'maintenance request', 'service request', 'request status', 'status update', 'request tracking'],
                        'process' => ['Send an email or message' => 2, 'Report verbally to staff' => 2, 'Manual or paper-based process' => 1],
                        'groups' => ['Students' => 1, 'Staff' => 1, 'Faculty' => 1],
                        'departments' => ['CICS' => 1, 'CHM' => 1, 'CIT' => 1],
                        'names' => ['RequestHub', 'TrackFix'],
                        'combines_with' => 'maintenance_request_management',
                        'combined_concept' => 'Facility Request Tracking and Maintenance Management System',
                    ],
                    [
                        'key' => 'maintenance_request_management',
                        'concept' => 'Maintenance Request Management Platform',
                        'weight' => 2,
                        'scope_kind' => 'wide',
                        'signals' => ['maintenance', 'repair', 'assigned', 'received', 'resolved', 'technician'],
                        'process' => ['Report verbally to staff' => 2, 'Just wait and hope it gets fixed' => 1],
                        'groups' => ['Staff' => 1],
                        'departments' => ['CICS' => 1, 'CHM' => 1],
                        'names' => ['MaintDesk', 'ServiceSync'],
                    ],
                    [
                        'key' => 'service_request_portal',
                        'concept' => 'Service Request Portal',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'signals' => ['walk-in', 'submit', 'request form', 'window', 'counter'],
                        'process' => ['Manual or paper-based process' => 2, 'No solution exists at all' => 2],
                        'groups' => ['Students' => 1],
                        'departments' => ['Registrar' => 1, 'Guidance Office' => 1],
                        'names' => ['FacilDesk', 'RequestOne'],
                    ],
                ],
                'fallback' => [
                    'key' => 'facility_request_fallback',
                    'concept' => 'Facility Request Management System',
                    'names' => ['FacilDesk', 'RequestHub', 'TrackFix'],
                ],
            ],
            'laboratory_equipment_monitoring' => [
                'key' => 'laboratory_equipment_monitoring',
                'label' => 'Laboratory Equipment Monitoring',
                'scope' => 'Campus Laboratory Services',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'equipment_monitoring',
                        'concept' => 'Equipment Monitoring System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        'signals' => ['equipment status', 'monitor', 'monitoring', 'under maintenance', 'malfunction', 'not functional', 'condition'],
                        'process' => ['No solution exists at all' => 2, 'Just wait and hope it gets fixed' => 2, 'Manual or paper-based process' => 1],
                        'groups' => ['Staff' => 1, 'Faculty' => 1],
                        'departments' => ['CICS' => 1, 'CIT' => 1],
                        'names' => ['LabWatch', 'EquipGuard', 'LabMonitor'],
                        'combines_with' => 'equipment_availability',
                        'combined_concept' => 'Equipment Monitoring and Availability System',
                    ],
                    [
                        'key' => 'equipment_availability',
                        'concept' => 'Equipment Availability System',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'scope' => 'Equipment and Workstation Availability',
                        'signals' => ['availability', 'available', 'in use', 'occupancy', 'occupied', 'vacant'],
                        'process' => ['Report verbally to staff' => 2, 'Manual or paper-based process' => 2],
                        'groups' => ['Students' => 1, 'Faculty' => 1],
                        'departments' => ['CICS' => 1, 'CIT' => 1],
                        'names' => ['EquipReady', 'LabReady'],
                    ],
                    [
                        'key' => 'laboratory_usage_monitoring',
                        'concept' => 'Laboratory Usage Monitoring System',
                        'weight' => 2,
                        'scope_kind' => 'wide',
                        'signals' => ['usage', 'utilization', 'computer usage', 'workstation', 'log sheet', 'laboratory hours'],
                        'process' => ['Manual or paper-based process' => 2],
                        'groups' => ['Staff' => 1, 'Administration' => 1],
                        'departments' => ['CICS' => 1, 'CIT' => 1, 'CCJE' => 1],
                        'names' => ['LabTrack', 'LabUsage', 'LENS'],
                    ],
                ],
                'fallback' => [
                    'key' => 'laboratory_fallback',
                    'concept' => 'Laboratory Equipment Monitoring System',
                    'names' => ['LabWatch', 'LabMonitor', 'LabTrack'],
                ],
            ],
            'campus_network_connectivity' => [
                'key' => 'campus_network_connectivity',
                'label' => 'Campus Network Connectivity',
                'scope' => 'Campus Networks',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'network_monitoring',
                        'concept' => 'Network Monitoring System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        'signals' => ['wifi', 'wi-fi', 'signal', 'network', 'connectivity', 'disconnect', 'coverage'],
                        'process' => ['No solution exists at all' => 2, 'Just wait and hope it gets fixed' => 2, 'Broken or unreliable online system' => 2],
                        'groups' => ['Students' => 1, 'Faculty' => 1, 'Staff' => 1],
                        'departments' => ['CICS' => 1, 'CIT' => 1],
                        'names' => ['NetWatch', 'SignalGuard', 'NetPulse'],
                        'combines_with' => 'incident_reporting',
                        'combined_concept' => 'Network Monitoring and Incident Reporting System',
                    ],
                    [
                        'key' => 'connectivity_monitoring',
                        'concept' => 'Connectivity Monitoring Platform',
                        'weight' => 2,
                        'scope_kind' => 'wide',
                        'signals' => ['slow internet', 'speed', 'bandwidth', 'latency', 'slow connection', 'unstable'],
                        'process' => ['Broken or unreliable online system' => 2],
                        'groups' => ['Students' => 1, 'Faculty' => 1],
                        'departments' => ['CICS' => 1],
                        'names' => ['SpeedWatch', 'NetQuality'],
                    ],
                    [
                        'key' => 'incident_reporting',
                        'concept' => 'Network Incident Reporting System',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'signals' => ['report', 'complaint', 'ticket', 'no internet', 'cannot connect', 'concern'],
                        'process' => ['Report verbally to staff' => 2, 'Send an email or message' => 2],
                        'groups' => ['Students' => 1, 'Staff' => 1],
                        'departments' => ['CICS' => 1, 'CIT' => 1],
                        'names' => ['NetReport', 'IncidentDesk'],
                    ],
                ],
                'fallback' => [
                    'key' => 'network_fallback',
                    'concept' => 'Campus Network Monitoring System',
                    'names' => ['NetWatch', 'NetPulse', 'NetGuard'],
                ],
            ],
            'request_tracking' => [
                'key' => 'request_tracking',
                'label' => 'Request Tracking',
                'scope' => 'Campus Requests',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'request_tracking',
                        'concept' => 'Request Tracking System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        'scope' => 'Student Request Status Updates',
                        'signals' => ['track', 'status', 'update', 'follow up', 'progress', 'pending', 'no update'],
                        'process' => ['Send an email or message' => 2, 'Report verbally to staff' => 2, 'Manual or paper-based process' => 1],
                        'groups' => ['Students' => 1, 'Faculty' => 1],
                        'departments' => ['Registrar' => 1, 'Guidance Office' => 1],
                        'names' => ['TrackPoint', 'TrackDesk', 'StatusHub'],
                        'combines_with' => 'service_request_portal',
                        'combined_concept' => 'Service Request Tracking Portal',
                    ],
                    [
                        'key' => 'service_request_portal',
                        'concept' => 'Service Request Portal',
                        'weight' => 2,
                        'scope_kind' => 'wide',
                        'scope' => 'Student Service Submissions',
                        'signals' => ['submit', 'online request', 'portal', 'request form', 'walk-in', 'counter'],
                        'process' => ['Manual or paper-based process' => 2, 'No solution exists at all' => 2],
                        'groups' => ['Students' => 1],
                        'departments' => ['Registrar' => 1, 'Guidance Office' => 1],
                        'names' => ['RequestHub', 'ServiceLink', 'OneDesk'],
                    ],
                    [
                        'key' => 'workflow_management',
                        'concept' => 'Workflow Management System',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'scope' => 'Request Approval Routing',
                        'signals' => ['workflow', 'approval', 'routing', 'endorse', 'process step', 'delay'],
                        'process' => ['Just wait and hope it gets fixed' => 2, 'Report verbally to staff' => 1],
                        'groups' => ['Staff' => 1, 'Administration' => 1],
                        'departments' => ['Registrar' => 1],
                        'names' => ['FlowPoint', 'ProcessPro'],
                    ],
                ],
                'fallback' => [
                    'key' => 'request_fallback',
                    'concept' => 'Service Request Management System',
                    'names' => ['RequestHub', 'TrackPoint', 'ServiceLink'],
                ],
            ],
            'records_management' => [
                'key' => 'records_management',
                'label' => 'Records Management',
                'scope' => 'Campus Documents',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'records_management',
                        'concept' => 'Records Management System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        'signals' => ['record', 'records', 'file', 'files', 'filing', 'storage', 'cabinet'],
                        'process' => ['Manual or paper-based process' => 2, 'No solution exists at all' => 2],
                        'groups' => ['Staff' => 1, 'Administration' => 1],
                        'departments' => ['Registrar' => 1, 'Guidance Office' => 1],
                        'names' => ['DocuTrack', 'DocuFlow', 'RecordHub'],
                        'combines_with' => 'document_retrieval',
                        'combined_concept' => 'Records Management and Retrieval System',
                    ],
                    [
                        'key' => 'document_retrieval',
                        'concept' => 'Document Retrieval System',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'signals' => ['retriev', 'search', 'find', 'locate', 'copy', 'release of'],
                        'process' => ['Send an email or message' => 2, 'Manual or paper-based process' => 1],
                        'groups' => ['Students' => 1, 'Faculty' => 1],
                        'departments' => ['Registrar' => 1],
                        'names' => ['DocuFind', 'RetrieveHub'],
                    ],
                    [
                        'key' => 'digital_archive',
                        'concept' => 'Digital Archive System',
                        'weight' => 2,
                        'scope_kind' => 'wide',
                        'signals' => ['archive', 'scan', 'digitize', 'backup', 'old record', 'historical'],
                        'process' => ['No solution exists at all' => 2],
                        'groups' => ['Administration' => 1],
                        'departments' => ['Registrar' => 1, 'Guidance Office' => 1],
                        'names' => ['Archiva', 'DARS'],
                    ],
                ],
                'fallback' => [
                    'key' => 'records_fallback',
                    'concept' => 'Records Management System',
                    'names' => ['DocuTrack', 'DocuFlow', 'RecordHub'],
                ],
            ],
            'scheduling_coordination' => [
                'key' => 'scheduling_coordination',
                'label' => 'Scheduling & Coordination',
                'scope' => 'Scheduling and Coordination Services',
                'patterns' => $patterns,
                'candidates' => [
                    [
                        'key' => 'scheduling_system',
                        'concept' => 'Scheduling System',
                        'weight' => 3,
                        'scope_kind' => 'wide',
                        // Scope is evidence: only nouns this direction's own signals prove.
                        'scope' => 'Timetable and Time Slot Coordination',
                        'signals' => ['schedule', 'timetable', 'time slot', 'conflict', 'overlap', 'clash'],
                        'process' => ['Manual or paper-based process' => 2, 'Send an email or message' => 2],
                        'groups' => ['Students' => 1, 'Faculty' => 1],
                        'departments' => ['Registrar' => 1, 'Guidance Office' => 1],
                        'names' => ['TimeSync', 'SlotEase', 'ScheduleHub'],
                        'combines_with' => 'appointment_system',
                        'combined_concept' => 'Scheduling and Appointment System',
                    ],
                    [
                        'key' => 'appointment_system',
                        'concept' => 'Appointment System',
                        'weight' => 2,
                        'scope_kind' => 'focused',
                        'scope' => 'Student Booking and Reservations',
                        'signals' => ['appointment', 'book', 'booking', 'reserve', 'reservation', 'set a schedule'],
                        'process' => ['Report verbally to staff' => 2, 'Just wait and hope it gets fixed' => 2],
                        'groups' => ['Students' => 1],
                        'departments' => ['Guidance Office' => 1, 'Registrar' => 1],
                        'names' => ['QueueEase', 'AppointEase', 'CareQueue'],
                    ],
                    [
                        'key' => 'coordination_platform',
                        'concept' => 'Coordination Platform',
                        'weight' => 2,
                        'scope_kind' => 'wide',
                        'scope' => 'Room and Venue Availability',
                        'signals' => ['coordinate', 'coordination', 'venue', 'room', 'availability', 'assign'],
                        'process' => ['Send an email or message' => 2],
                        'groups' => ['Faculty' => 1, 'Staff' => 1],
                        'departments' => ['Registrar' => 1],
                        'names' => ['CoordHub', 'RoomSync', 'SpaceSync'],
                    ],
                ],
                'fallback' => [
                    'key' => 'scheduling_fallback',
                    'concept' => 'Scheduling and Coordination System',
                    'names' => ['CoordHub', 'TimeSync', 'RoomSync'],
                ],
            ],
        ];
    }

    // ══════════════════════════════════════════════
    // DESCRIPTION GENERATION
    // Rich, specific, reads like a project abstract
    // ══════════════════════════════════════════════
    private function generateDescription($groupName, $category, array $signals, $reports, $votes, $topGroup, $currentProcess, $frequencyScore, $impactScore, ?string $dominantDepartment): string
    {
        $name = ucwords($groupName);
        $group = $topGroup ?? 'campus users';
        $process = $currentProcess ? "Currently, the process relies on {$currentProcess}, " : '';
        $departmentContext = $this->departmentContextPhrase($dominantDepartment);

        // Problem framing sentence
        $problem = $this->describeProblem($name, $signals, $group, $departmentContext);

        // Current gap sentence
        $gap = $process
            ? "{$process}which contributes to inefficiencies and delays that affect {$group} on a recurring basis."
            : "The absence of a dedicated system forces {$group} to rely on fragmented or manual workarounds.";

        // Evidence sentence
        $freq = $frequencyScore >= 3 ? 'frequently occurring' : 'reported';
        $support = $votes >= 10
            ? "Backed by {$reports} community reports and {$votes} upvotes"
            : "Based on {$reports} submitted reports";

        $evidence = "{$support}, this is identified as a {$freq} concern with measurable impact on campus operations.";

        // Solution framing
        $solution = $this->describeSolution($name, $signals, $impactScore, $departmentContext);

        return "{$problem} {$gap} {$evidence} {$solution}";
    }

    private function describeProblem($name, array $signals, $group, string $departmentContext): string
    {
        if ($signals['is_slow'] && $signals['is_manual']) {
            return "Campus {$group}{$departmentContext} consistently experience delays caused by manual {$name} processes that are slow, prone to error, and difficult to scale.";
        } elseif ($signals['is_slow']) {
            return "Long waiting times in {$name}-related services have become a persistent source of frustration for {$group}{$departmentContext}.";
        } elseif ($signals['is_manual']) {
            return "The reliance on paper-based and manual procedures for {$name} creates bottlenecks that slow down service delivery for {$group}{$departmentContext}.";
        } elseif ($signals['is_no_system']) {
            return "The lack of a centralized system for {$name} leaves {$group}{$departmentContext} without a reliable way to access, track, or manage their requests.";
        } elseif ($signals['is_wifi']) {
            return "Unreliable internet connectivity disrupts the academic and administrative activities of {$group}{$departmentContext} daily.";
        } elseif ($signals['is_scheduling']) {
            return "Scheduling conflicts and the absence of a coordinated system for {$name} cause recurring disruptions for {$group}{$departmentContext}.";
        } elseif ($signals['is_tracking']) {
            return "{$group}{$departmentContext} have no reliable way to monitor the status or progress of their {$name}-related requests and transactions.";
        } else {
            return "Recurring issues in {$name} services have been reported by {$group}{$departmentContext}, pointing to systemic gaps in how these processes are currently managed.";
        }
    }

    private function describeSolution($name, array $signals, $impactScore, string $departmentContext): string
    {
        $impact = $impactScore >= 3 ? 'a significant portion of the campus community' : 'the directly affected users';
        $scope = $departmentContext === '' ? $impact : "{$impact}{$departmentContext}";

        if ($signals['is_manual'] || $signals['is_no_system']) {
            return "A digital solution targeting {$name} processes would automate key workflows, reduce manual effort, and provide {$scope} with a more reliable and accessible service experience.";
        } elseif ($signals['is_tracking'] || $signals['is_notification']) {
            return "A system with real-time tracking and notification capabilities would give {$scope} visibility into their requests, reducing uncertainty and improving satisfaction.";
        } elseif ($signals['is_slow']) {
            return "Implementing an optimized, queue-aware system for {$name} would measurably reduce wait times and improve throughput for {$scope}.";
        } else {
            return "Addressing these issues through a structured software solution would improve the overall {$name} experience for {$scope} and reduce recurring operational friction.";
        }
    }

    // ══════════════════════════════════════════════
    // OBJECTIVES GENERATION
    // Specific, measurable, tied to actual problem signals
    // ══════════════════════════════════════════════
    private function generateObjectives(array $signals, $category, $groupName, $topGroup, $groupFeedbacks, ?string $dominantDepartment): array
    {
        $name = ucwords($groupName);
        $group = $topGroup ?? 'campus users';

        // General objective — action-verb framing
        $verb = 'develop and implement';
        if ($signals['is_slow']) {
            $verb = 'design and deploy';
        }
        if ($signals['is_manual']) {
            $verb = 'develop and automate';
        }

        $general = "To {$verb} a {$name} system that addresses recurring campus issues and improves service delivery for {$group}.";

        if ($dominantDepartment === 'across multiple departments') {
            $general = "To {$verb} a {$name} system that improves service delivery across multiple departments.";
        } elseif ($dominantDepartment !== null) {
            $general = "To {$verb} a {$name} system for improving services in the {$dominantDepartment}.";
        }

        // Specific objectives — drawn from real signals, no generic fallbacks
        $specific = [];

        if ($signals['is_slow'] || $signals['is_crowded']) {
            $specific[] = "To reduce average waiting and processing time for {$name} transactions by at least 40%.";
        }
        if ($signals['is_manual']) {
            $specific[] = "To digitize and automate manual {$name} workflows, eliminating paper-based processes.";
        }
        if ($signals['is_tracking'] || $signals['is_notification']) {
            $specific[] = "To provide {$group} with real-time tracking and status updates for their {$name} requests.";
        }
        if ($signals['is_record']) {
            $specific[] = "To establish a centralized, searchable digital repository for {$name}-related records and documents.";
        }
        if ($signals['is_scheduling']) {
            $specific[] = "To implement an automated scheduling engine that detects and prevents conflicts for {$group}.";
        }
        if ($signals['is_booking']) {
            $specific[] = "To enable {$group} to view availability and book {$name} resources online without manual coordination.";
        }
        if ($signals['is_wifi'] || $signals['is_access']) {
            $specific[] = 'To provide administrators with a dashboard for monitoring connectivity and access issues in real time.';
        }
        if ($signals['is_complaint']) {
            $specific[] = "To create a structured complaint submission and resolution workflow with status tracking for {$group}.";
        }

        // Always include: user experience + evaluation objectives
        $specific[] = "To improve overall satisfaction of {$group} with {$name} services through accessible and responsive system design.";
        $specific[] = 'To evaluate system effectiveness through user acceptance testing and post-deployment feedback.';

        // Cap at 5 specific objectives — most capstone panels expect 3–5
        $specific = array_slice($specific, 0, 5);

        return ['general' => $general, 'specific' => $specific];
    }

    // ══════════════════════════════════════════════
    // EXPLANATION
    // ══════════════════════════════════════════════
    private function generateExplanation($groupName, $topGroup, $reports, $votes, $frequencyScore, $impactScore): array
    {
        $freqLabel = match (true) {
            $frequencyScore >= 3.5 => 'Everyday', $frequencyScore >= 2.5 => 'Often', $frequencyScore >= 1.5 => 'Sometimes', default => 'Rarely'
        };
        $impactLabel = match (true) {
            $impactScore >= 3.5 => 'More than 500 users', $impactScore >= 2.5 => '200–500 users', $impactScore >= 1.5 => '50–200 users', default => 'Less than 50 users'
        };

        return [
            'summary' => "This idea surfaces from {$reports} reports of recurring {$groupName} issues. "
                       ."The problem occurs {$freqLabel} and affects an estimated {$impactLabel}, "
                       .'making it a strong candidate for a capstone project with measurable real-world impact.',

            'factors' => [
                'reports' => $reports,
                'votes' => $votes,
                'frequency_score' => round($frequencyScore, 2),
                'impact_score' => round($impactScore, 2),
                'top_affected_group' => $topGroup,
            ],

            'reasoning' => [
                'impact' => $impactScore >= 3
                    ? "Affects a large portion of campus — {$impactLabel} — giving a proposed solution wide reach and measurable benefit."
                    : 'Currently affects a smaller group, but the issue may expand if left unaddressed.',

                'frequency' => $frequencyScore >= 3
                    ? "Reported as occurring {$freqLabel}, indicating this is not an isolated incident but a systemic gap."
                    : 'Occurs occasionally, but the pattern across multiple submissions confirms it as a genuine recurring concern.',

                'reports' => $reports >= 5
                    ? "{$reports} independent reports validate that this is a shared experience, not an individual complaint."
                    : "Early-stage data with {$reports} reports — additional submissions would strengthen confidence further.",

                'votes' => $votes >= 10
                    ? "{$votes} community upvotes confirm broad awareness and shared frustration with this issue."
                    : "Currently {$votes} votes — as more students discover the problem, support is expected to grow.",
            ],
        ];
    }

    // ══════════════════════════════════════════════
    // IMPACT SIMULATION
    // ══════════════════════════════════════════════

    private function resolveTopGroup($groupFeedbacks): ?string
    {
        $groups = $groupFeedbacks->map(function ($f) {
            $g = $f->affected_group;
            // Handle both JSON array (new) and plain string (old)
            if (is_array($g)) {
                return $g;
            }
            if (is_string($g) && str_starts_with($g, '[')) {
                return json_decode($g, true) ?? [$g];
            }

            return [$g];
        })->flatten()->filter()->countBy()->sortDesc();

        return $groups->keys()->first();
    }

    private function resolveCurrentProcess($groupFeedbacks): ?string
    {
        return $groupFeedbacks
            ->pluck('current_process')
            ->filter(fn ($p) => $p && $p !== 'No system in place')
            ->groupBy(fn ($p) => $p)
            ->map->count()
            ->sortDesc()
            ->keys()
            ->first();
    }

    private function resolveDominantDepartment($groupFeedbacks): ?string
    {
        $departments = $groupFeedbacks
            ->pluck('department')
            ->filter(fn ($department) => filled($department))
            ->countBy()
            ->sortDesc();

        if ($departments->isEmpty()) {
            return null;
        }

        $topCount = $departments->first();
        $topDepartments = $departments
            ->filter(fn ($count) => $count === $topCount)
            ->keys();

        if ($topDepartments->count() > 1) {
            return 'across multiple departments';
        }

        return $topDepartments->first();
    }

    private function departmentContextPhrase(?string $dominantDepartment): string
    {
        if ($dominantDepartment === null) {
            return '';
        }

        if ($dominantDepartment === 'across multiple departments') {
            return ' across multiple departments';
        }

        return " in the {$dominantDepartment}";
    }
}
