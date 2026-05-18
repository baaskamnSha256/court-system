<?php

use App\Support\DefendantRegistryResolver;
use App\Support\MongolianRegistryDemographics;

it('parses mongolian registry into age and gender labels', function () {
    $parsed = MongolianRegistryDemographics::parse('ЙС96072608', '2026-05-18');

    expect($parsed)->not->toBeNull()
        ->and($parsed['gender'])->toBe('Эмэгтэй')
        ->and($parsed['age'])->toBeGreaterThan(0);
});

it('resolves registry from hearing defendant registries when sentence registry is empty', function () {
    $hearing = (object) [
        'defendant_names' => ['Шүүгдэгч А'],
        'defendant_registries' => ['ЙС96072608'],
        'defendants' => null,
    ];

    $registry = DefendantRegistryResolver::registryForSentence(
        ['defendant_name' => 'Шүүгдэгч А', 'defendant_registry' => ''],
        $hearing,
        0
    );

    expect($registry)->toBe('ЙС96072608');
});
