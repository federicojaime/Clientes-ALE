# Script de verificación de la API
Write-Host "🔍 Verificando API..." -ForegroundColor Green

# Verificar sintaxis PHP
Write-Host "
�� Verificando sintaxis PHP..."
$phpErrors = 0
Get-ChildItem -Path "src" -Filter "*.php" -Recurse | ForEach-Object {
    $result = php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Error en $($_.Name): $result" -ForegroundColor Red
        $phpErrors++
    }
}

if ($phpErrors -eq 0) {
    Write-Host "✅ Sintaxis PHP OK" -ForegroundColor Green
} else {
    Write-Host "❌ $phpErrors archivos con errores" -ForegroundColor Red
}

# Verificar estructura de archivos
Write-Host "
📁 Verificando estructura..."
$requiredFiles = @(
    "src/Controllers/BaseController.php",
    "src/Controllers/AuthController.php",
    "src/Middleware/SecurityMiddleware.php",
    "public/index.php",
    ".env"
)

foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "✅ $file" -ForegroundColor Green
    } else {
        Write-Host "❌ $file faltante" -ForegroundColor Red
    }
}

Write-Host "
🎉 Verificación completada!" -ForegroundColor Green
