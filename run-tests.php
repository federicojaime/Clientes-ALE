#!/usr/bin/env php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/tests/ApiTestSuite.php';

echo "🧪 EJECUTANDO TESTS DE API...\n";
echo "=====================================\n\n";

$tester = new \Tests\ApiTestSuite();
$results = $tester->runAllTests();

$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($results as $result) {
    $icon = $result['status'] === 'PASS' ? '✅' : ($result['status'] === 'FAIL' ? '❌' : '⏩');
    echo "{$icon} {$result['test']}: {$result['message']}\n";
    
    if ($result['status'] === 'PASS') $passed++;
    elseif ($result['status'] === 'FAIL') $failed++;
    else $skipped++;
}

echo "\n=====================================\n";
echo "📊 RESUMEN DE TESTS:\n";
echo "✅ Pasaron: {$passed}\n";
echo "❌ Fallaron: {$failed}\n";
echo "⏩ Omitidos: {$skipped}\n";
echo "📈 Total: " . count($results) . "\n";

if ($failed > 0) {
    echo "\n⚠️  Algunos tests fallaron. Revisa la configuración.\n";
    exit(1);
} else {
    echo "\n🎉 Todos los tests pasaron exitosamente!\n";
    exit(0);
}
