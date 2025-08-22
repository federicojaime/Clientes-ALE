<?php
?php
// Tests básicos para validar funcionalidad
namespace Tests;

class ApiTestSuite
{
    private string $baseUrl;
    private ?string $token = null;
    
    public function __construct(string $baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = $baseUrl;
    }
    
    public function runAllTests(): array
    {
        $results = [];
        
        $results[] = $this->testApiOnline();
        $results[] = $this->testGetCategorias();
        $results[] = $this->testGetContratistas();
        $results[] = $this->testHorarios();
        
        return $results;
    }
    
    private function testApiOnline(): array
    {
        $response = $this->makeRequest('GET', '/');
        
        if ($response['status'] === 200 && isset($response['data']['message'])) {
            return ['test' => 'API Online', 'status' => 'PASS', 'message' => 'API responde correctamente'];
        }
        
        return ['test' => 'API Online', 'status' => 'FAIL', 'message' => 'API no responde'];
    }
    
    private function testGetCategorias(): array
    {
        $response = $this->makeRequest('GET', '/api/v1/config/categorias');
        
        if ($response['status'] === 200) {
            return ['test' => 'Get Categorias', 'status' => 'PASS', 'message' => 'Categorías obtenidas'];
        }
        
        return ['test' => 'Get Categorias', 'status' => 'FAIL', 'message' => 'Error obteniendo categorías'];
    }
    
    private function testGetContratistas(): array
    {
        $response = $this->makeRequest('GET', '/api/v1/contratistas');
        
        if ($response['status'] === 200) {
            return ['test' => 'Get Contratistas', 'status' => 'PASS', 'message' => 'Contratistas obtenidos'];
        }
        
        return ['test' => 'Get Contratistas', 'status' => 'FAIL', 'message' => 'Error obteniendo contratistas'];
    }
    
    private function testHorarios(): array
    {
        $response = $this->makeRequest('GET', '/api/v1/horarios/contratista/1');
        
        if ($response['status'] === 200) {
            return ['test' => 'Horarios', 'status' => 'PASS', 'message' => 'Horarios funcionando'];
        }
        
        return ['test' => 'Horarios', 'status' => 'FAIL', 'message' => 'Error en horarios'];
    }
    
    private function makeRequest(string $method, string $endpoint, ?array $data = null, ?string $token = null): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        
        $headers = ['Content-Type: application/json'];
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true) ?? []
        ];
    }
}
