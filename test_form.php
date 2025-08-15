<?php
/**
 * Script de teste para verificar o formulário de configuração do LAPS
 */

// Simular dados POST
$_POST = [
    'update' => '1',
    'laps_server_url' => 'https://laps.mogimirim.sp.gov.br/api.php',
    'laps_api_key' => '5deeb8a3-e591-4bd4-8bfb-f9d8b117844c',
    'connection_timeout' => '30',
    'cache_duration' => '300',
    'is_active' => '1'
];

// Simular GET debug
$_GET['debug'] = '1';

echo "<h2>Teste do Formulário LAPS</h2>";
echo "<p>Dados POST simulados:</p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<p>Testando processamento...</p>";

// Incluir o arquivo de configuração
try {
    include 'front/config.form.php';
    echo "<p style='color: green;'>✓ Arquivo processado com sucesso!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erro: " . $e->getMessage() . "</p>";
}
?>