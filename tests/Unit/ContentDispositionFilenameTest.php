<?php

use App\Support\ContentDispositionFilename;

it('parses quoted filename', function () {
    expect(ContentDispositionFilename::fromHeader('attachment; filename="report.xlsx"'))->toBe('report.xlsx');
});

it('parses unquoted filename', function () {
    expect(ContentDispositionFilename::fromHeader('attachment; filename=export.csv'))->toBe('export.csv');
});

it('parses rfc5987 filename star', function () {
    $header = "attachment; filename*=UTF-8''%D0%A2%D0%B5%D1%81%D1%82.xlsx";
    expect(ContentDispositionFilename::fromHeader($header))->toBe('Тест.xlsx');
});

it('returns null for empty header', function () {
    expect(ContentDispositionFilename::fromHeader(null))->toBeNull();
    expect(ContentDispositionFilename::fromHeader(''))->toBeNull();
});
