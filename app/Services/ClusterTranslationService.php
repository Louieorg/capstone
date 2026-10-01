<?php

namespace App\Services;

use App\Models\ClusterExplanation;

/**
 * Canonical addressing for a Filipino rendering of a stored English cluster
 * explanation.
 *
 * The translation hash is content-addressed by the stored English synthesis
 * plus the translation configuration — never by raw reports, clustering,
 * scores or timestamps. The same English source plus the same translation
 * configuration always resolves to the same hash. Any change to the English
 * source, the model or the prompt version resolves to a different hash, so a
 * stale translation is never presented as current.
 *
 * This class is deliberately free of any AI, HTTP or queue dependency: it
 * performs reads only and returns plain data. It never clusters, scores,
 * qualifies, persists, dispatches, or writes.
 */
class ClusterTranslationService
{
    private const TRANSLATION_SCHEMA_VERSION = 'fil-1';

    /**
     * Canonical stored English synthesis, in a fixed shape.
     *
     * @return array<string, mixed>|null Null when the row is not usable as a source.
     */
    public function canonicalSource(?ClusterExplanation $english): ?array
    {
        if ($english === null
            || $english->status !== 'complete'
            || ! filled($english->summary)
            || $english->language !== OllamaService::SYNTHESIS_LANGUAGE) {
            return null;
        }

        return [
            'summary' => (string) $english->summary,
            'patterns' => array_values((array) ($english->patterns ?? [])),
            'experiences' => array_values((array) ($english->experiences ?? [])),
        ];
    }

    /**
     * @param  array<string, mixed>  $source  Canonical stored English synthesis.
     * @param  array{model: string, prompt_version: string, language: string}  $profile
     */
    public function translationKey(array $source, string $promptVersion, string $model, string $language): string
    {
        return hash('sha256', (string) json_encode([
            'translation_schema_version' => self::TRANSLATION_SCHEMA_VERSION,
            'prompt_version' => $promptVersion,
            'model' => $model,
            'language' => $language,
            'source' => $this->sortKeysRecursively($source),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
