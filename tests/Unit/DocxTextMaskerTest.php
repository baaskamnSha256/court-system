<?php

use App\Services\DocxTextMasker;
use ZipArchive;

it('masks phrases split across word runs in the same paragraph', function () {
    $sourcePath = tempnam(sys_get_temp_dir(), 'docx-source-');
    $outputPath = tempnam(sys_get_temp_dir(), 'docx-masked-');

    expect($sourcePath)->not->toBeFalse();
    expect($outputPath)->not->toBeFalse();

    $zip = new ZipArchive;
    $opened = $zip->open($sourcePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    expect($opened)->toBeTrue();

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $zip->addFromString('word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p>
      <w:r><w:t>Шүүгдэгч: Бат-</w:t></w:r>
      <w:r><w:t>Эрдэнэ</w:t></w:r>
    </w:p>
  </w:body>
</w:document>
XML);
    $zip->close();

    $masker = new DocxTextMasker;
    $masker->mask($sourcePath, $outputPath, ['Бат-Эрдэнэ'], [], '*');
    $plain = $masker->extractPlainText($outputPath);

    expect($plain)->not->toContain('Бат-Эрдэнэ')
        ->and($plain)->toContain('**********');

    @unlink($sourcePath);
    @unlink($outputPath);
});

it('does not mask excluded judge prosecutor and lawyer names', function () {
    $sourcePath = tempnam(sys_get_temp_dir(), 'docx-source-');
    $outputPath = tempnam(sys_get_temp_dir(), 'docx-masked-');

    expect($sourcePath)->not->toBeFalse();
    expect($outputPath)->not->toBeFalse();

    $zip = new ZipArchive;
    $opened = $zip->open($sourcePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    expect($opened)->toBeTrue();

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $zip->addFromString('word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p>
      <w:r><w:t>Шүүгч Г.Батсүх, улсын яллагч Д.Сарангэрэл, өмгөөлөгч Н.Оюун, шүүгдэгч Г.Цэндмаа</w:t></w:r>
    </w:p>
  </w:body>
</w:document>
XML);
    $zip->close();

    $masker = new DocxTextMasker;
    $masker->mask(
        $sourcePath,
        $outputPath,
        [],
        ['initial_name' => true],
        '*',
        ['Г.Батсүх', 'Д.Сарангэрэл', 'Н.Оюун']
    );
    $plain = $masker->extractPlainText($outputPath);

    expect($plain)->toContain('Г.Батсүх')
        ->and($plain)->toContain('Д.Сарангэрэл')
        ->and($plain)->toContain('Н.Оюун')
        ->and($plain)->not->toContain('Г.Цэндмаа')
        ->and($plain)->toContain('Г.Ц******');

    @unlink($sourcePath);
    @unlink($outputPath);
});

it('masks ovogt legal name forms used in court decisions', function () {
    $sourcePath = tempnam(sys_get_temp_dir(), 'docx-source-');
    $outputPath = tempnam(sys_get_temp_dir(), 'docx-masked-');

    expect($sourcePath)->not->toBeFalse();
    expect($outputPath)->not->toBeFalse();

    $zip = new ZipArchive();
    $opened = $zip->open($sourcePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    expect($opened)->toBeTrue();

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $zip->addFromString('word/document.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p>
      <w:r><w:t>Аргалчин овогт Цэдмаагийн Гантогтоход яллах дүгнэлт үйлдэж ирүүлсэн</w:t></w:r>
    </w:p>
  </w:body>
</w:document>
XML);
    $zip->close();

    $masker = new DocxTextMasker();
    $masker->mask($sourcePath, $outputPath, [], ['ovogt_name' => true], '*');
    $plain = $masker->extractPlainText($outputPath);

    expect($plain)->not->toContain('Цэдмаагийн')
        ->and($plain)->not->toContain('Гантогтоход')
        ->and($plain)->toContain('Аргалчин овогт');

    @unlink($sourcePath);
    @unlink($outputPath);
});
