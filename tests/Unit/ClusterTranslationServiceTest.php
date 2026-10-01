<?php

use App\Services\ClusterTranslationService;
use App\Services\OllamaService;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function (): void {
    Model::unsetConnectionResolver();
});

function translationService(): ClusterTranslationService
{
    return app(ClusterTranslationService::class);
}

/**
 * @return array<string, mixed>
 */
function translatedEnglish(): array
{
    return [
        'summary' => 'Students repeatedly follow up in person because they cannot see whether a request was received or resolved.',
        'patterns' => [
            'Status updates are shared verbally instead of being published.',
            'The same concern is raised again because nothing is tracked.',
        ],
        'experiences' => [
            [
                'title' => 'Queuing for an answer',
                'body' => 'Students wait during class hours to ask staff whether their request was received or already resolved.',
            ],
        ],
    ];
}

test('the translation hash is deterministic for the same source and profile', function (): void {
    $source = translatedEnglish();
    $profile = app(OllamaService::class)->translationProfile();

    $first = translationService()->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);
    $second = translationService()->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);

    expect($first)->toBe($second)->and($first)->toMatch('/^[0-9a-f]{64}$/');
});

test('changing the english source changes the translation hash', function (): void {
    $profile = app(OllamaService::class)->translationProfile();
    $source = translatedEnglish();

    $before = translationService()->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);

    $source['summary'] .= ' Extra wording changes the source.';

    $after = translationService()->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);

    expect($after)->not->toBe($before);
});

test('changing the model changes the translation hash', function (): void {
    $profile = app(OllamaService::class)->translationProfile();
    $source = translatedEnglish();

    $before = translationService()->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);
    $after = translationService()->translationKey($source, $profile['prompt_version'], 'other-model:latest', $profile['language']);

    expect($after)->not->toBe($before);
});

test('changing the prompt version changes the translation hash', function (): void {
    $profile = app(OllamaService::class)->translationProfile();
    $source = translatedEnglish();

    $before = translationService()->translationKey($source, $profile['prompt_version'], $profile['model'], $profile['language']);
    $after = translationService()->translationKey($source, '999', $profile['model'], $profile['language']);

    expect($after)->not->toBe($before);
});

test('translation uses its own language and profile', function (): void {
    $profile = app(OllamaService::class)->translationProfile();

    expect($profile['language'])->toBe('fil')
        ->and(OllamaService::TRANSLATION_PROMPT_VERSION)->toBe('2')
        ->and(OllamaService::TRANSLATION_LANGUAGE)->toBe('fil')
        ->and(config('services.ollama.translation.queue'))->toBe('ai-filipino');
});
