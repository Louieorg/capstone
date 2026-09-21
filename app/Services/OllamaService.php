<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    public function enhance(array $idea): array
    {
        $prompt = <<<PROMPT
Rewrite the following capstone recommendation.

You MUST produce a new version. Do not copy the original text.

The meaning, problem, purpose, facts, and scope MUST stay the same.

Use:
- simple English
- short sentences
- clear wording
- professional but student-friendly language
- no unnecessary technical terms

TITLE:
Create a new, strong, and professional capstone title.

TITLE:
Create a new, specific, and professional capstone title.

Do NOT start the title with:
- Streamlining
- Enhancing
- Improving
- Optimizing
- Developing
- Designing
- Smart
- Efficient

Do not repeatedly use the same title pattern.

Choose the title wording based on the actual problem and proposed system.

The title should:
- clearly identify the institutional problem or process being addressed.
- clearly indicate the proposed system or solution.
- preserve the original scope, users, offices, and purpose.
- be concise and suitable for an academic capstone project.
- use natural and straightforward wording.
- be different from the original title.

Do not add technologies, features, users, offices, statistics, or requirements that are not in the original.
Do not use cluster names, IDs, hashes, scores, or "Unclassified".

DESCRIPTION:
Rewrite the entire description in different words.
Explain the problem and the proposed system clearly.
Do not copy the original sentences.

GENERAL OBJECTIVE:
Rewrite it in different words while keeping the same purpose.
Start with "To".

SPECIFIC OBJECTIVES:
Rewrite every objective in different words while keeping the same intended action.
Start each objective with "To".

DO NOT:
- invent information
- add features
- add technologies
- add users
- add statistics
- change the proposed solution
- use the original title
- copy the original description

IMPORTANT:
The output MUST be different from the input while keeping the same meaning.

ORIGINAL TITLE:
{$idea['title']}

ORIGINAL DESCRIPTION:
{$idea['description']}

ORIGINAL GENERAL OBJECTIVE:
{$idea['general_objective']}

ORIGINAL SPECIFIC OBJECTIVES:
{$this->formatObjectives($idea['specific_objectives'])}

Return ONLY this JSON:

{
    "title": "new rewritten title",
    "description": "new rewritten description",
    "general_objective": "new rewritten objective",
    "specific_objectives": [
        "new objective 1",
        "new objective 2",
        "new objective 3"
    ]
}
PROMPT;

        try {
            $response = Http::connectTimeout(config('services.ollama.connect_timeout'))
                ->timeout(config('services.ollama.timeout'))
                ->post(config('services.ollama.url').'/api/generate', [
                    'model' => config('services.ollama.model'),
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                    'options' => [
                        'temperature' => 0.7,
                    ],
                ]);

            if ($response->failed()) {
                Log::error('Ollama request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $idea;
            }

            $text = trim($response->json()['response'] ?? '');

            Log::info('OLLAMA RESPONSE', [
                'response' => $text,
            ]);

            // Remove markdown code fences if present
            $text = preg_replace('/```json/i', '', $text);
            $text = preg_replace('/```/', '', $text);
            $text = trim($text);

            // Extract JSON object
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $text = $matches[0];
            }

            $result = json_decode($text, true);

            if (! is_array($result)) {
                Log::warning('Invalid JSON returned by Ollama.', [
                    'response' => $text,
                ]);

                return $idea;
            }

            $isUnchanged = ($result['title'] ?? null) === $idea['title']
                && ($result['description'] ?? null) === $idea['description']
                && ($result['general_objective'] ?? null) === $idea['general_objective']
                && ($result['specific_objectives'] ?? null) === $idea['specific_objectives'];

            if ($isUnchanged) {
                Log::warning('Ollama returned the original text unchanged.');

                return $idea;
            }

            return [
                ...$idea,
                'title' => $result['title'] ?? $idea['title'],
                'description' => $result['description'] ?? $idea['description'],
                'general_objective' => $result['general_objective'] ?? $idea['general_objective'],
                'specific_objectives' => $result['specific_objectives'] ?? $idea['specific_objectives'],
            ];

        } catch (\Throwable $e) {
            Log::error('Ollama Exception', [
                'message' => $e->getMessage(),
            ]);

            return $idea;
        }
    }

    public function translate(array $fields): array
    {
        $prompt = <<<PROMPT
You are translating institutional problem reports for internal processing.

IMPORTANT RULES

- Translate Filipino or Taglish (mixed Filipino-English) text into clear, natural English.
- If the text is ALREADY in English, return it UNCHANGED.
- NEVER invent, add, or remove information.
- NEVER change the meaning.
- Return ONLY valid JSON.
- Do NOT use markdown.
- Do NOT explain your answer.

JSON FORMAT

{
    "title":"",
    "description":"",
    "impact":""
}

TEXT TO PROCESS

Title:
{$fields['title']}

Description:
{$fields['description']}

Impact:
{$fields['impact']}

PROMPT;

        try {
            $response = Http::timeout(180)
                ->post(config('services.ollama.url').'/api/generate', [
                    'model' => config('services.ollama.model'),
                    'prompt' => $prompt,
                    'stream' => false,
                    'options' => ['temperature' => 0.2],
                ]);

            if ($response->failed()) {
                Log::error('Ollama translation request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = trim($response->json()['response'] ?? '');
            $text = preg_replace('/```json/i', '', $text);
            $text = preg_replace('/```/', '', $text);

            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $text = $matches[0];
            }

            $result = json_decode($text, true);

            if (! is_array($result) || ! isset($result['title'], $result['description'], $result['impact'])) {
                Log::warning('Invalid JSON returned by Ollama translation.', ['response' => $text]);

                return null;
            }

            return [
                'title' => $result['title'],
                'description' => $result['description'],
                'impact' => $result['impact'],
            ];
        } catch (\Throwable $e) {
            Log::error('Ollama translation exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function formatObjectives(array $objectives): string
    {
        return collect($objectives)
            ->map(fn ($objective) => "- {$objective}")
            ->implode("\n");
    }
}
