<?php
?php
namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

class LoggerService
{
    private static ?Logger $logger = null;
    
    public static function getInstance(): Logger
    {
        if (self::$logger === null) {
            self::$logger = new Logger('servicios-tecnicos');
            
            // Handler para archivos rotativos
            $fileHandler = new RotatingFileHandler(
                __DIR__ . '/../../logs/app.log',
                0,
                Logger::INFO
            );
            $fileHandler->setFormatter(new JsonFormatter());
            
            // Handler para errores críticos
            $errorHandler = new StreamHandler(
                __DIR__ . '/../../logs/errors.log',
                Logger::ERROR
            );
            $errorHandler->setFormatter(new JsonFormatter());
            
            self::$logger->pushHandler($fileHandler);
            self::$logger->pushHandler($errorHandler);
        }
        
        return self::$logger;
    }
    
    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }
    
    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }
    
    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }
    
    public static function logApiRequest(string $method, string $uri, array $data = [], ?int $userId = null): void
    {
        self::info('API Request', [
            'method' => $method,
            'uri' => $uri,
            'user_id' => $userId,
            'data_size' => sizeof($data),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function logError(\Exception $e, array $context = []): void
    {
        self::error('Exception occurred', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'context' => $context
        ]);
    }
}
