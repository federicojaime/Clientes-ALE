# Pruebas rápidas de la API
Write-Host "🧪 Probando API..." -ForegroundColor Green

$baseUrl = "http://localhost:8000"

try {
    # Test 1: Info general
    Write-Host "📍 Probando endpoint principal..." -ForegroundColor Yellow
    $response = Invoke-RestMethod -Uri "$baseUrl/" -Method GET
    Write-Host "✅ API Online: $($response.message)" -ForegroundColor Green
    
    # Test 2: Categorías
    Write-Host "📍 Probando categorías..." -ForegroundColor Yellow
    $categorias = Invoke-RestMethod -Uri "$baseUrl/api/v1/config/categorias" -Method GET
    Write-Host "✅ Categorías: $($categorias.data.total) encontradas" -ForegroundColor Green
    
    Write-Host "`n🎉 ¡API funcionando!" -ForegroundColor Green
    
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "💡 Ejecuta: composer start" -ForegroundColor Yellow
}