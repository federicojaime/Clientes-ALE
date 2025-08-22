<?php
?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class AdminController extends BaseController
{
    public function dashboard(Request $request, Response $response): Response
    {
        try {
            // Estadísticas generales
            $stats = [
                'usuarios_totales' => $this->getCount('usuarios', 'activo = 1'),
                'clientes_totales' => $this->getCount('usuarios', 'tipo_usuario_id = 1 AND activo = 1'),
                'contratistas_totales' => $this->getCount('usuarios', 'tipo_usuario_id = 2 AND activo = 1'),
                'solicitudes_totales' => $this->getCount('solicitudes'),
                'solicitudes_pendientes' => $this->getCount('solicitudes', "estado = 'pendiente'"),
                'citas_completadas' => $this->getCount('citas', "estado = 'completada'"),
                'pagos_exitosos' => $this->getCount('pagos', "estado_consulta = 'capturado'"),
                'evaluaciones_totales' => $this->getCount('evaluaciones')
            ];
            
            return $this->successResponse($response, [
                'estadisticas' => $stats
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo dashboard: ' . $e->getMessage(), 500);
        }
    }
    
    public function estadisticas(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $periodo = $params['periodo'] ?? 'mes';
            
            $fechaInicio = $this->getFechaInicio($periodo);
            
            // Solicitudes por período
            $solicitudesPorPeriodo = Database::execute(
                "SELECT DATE(created_at) as fecha, COUNT(*) as total
                 FROM solicitudes 
                 WHERE created_at >= ?
                 GROUP BY DATE(created_at)
                 ORDER BY fecha",
                [$fechaInicio]
            )->fetchAll();
            
            return $this->successResponse($response, [
                'periodo' => $periodo,
                'fecha_inicio' => $fechaInicio,
                'solicitudes_por_periodo' => $solicitudesPorPeriodo
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo estadísticas: ' . $e->getMessage(), 500);
        }
    }
    
    public function gestionUsuarios(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $tipo = $params['tipo'] ?? null;
            $activo = $params['activo'] ?? null;
            $limit = min((int) ($params['limit'] ?? 50), 200);
            
            $whereClause = 'WHERE 1=1';
            $queryParams = [];
            
            if ($tipo === 'cliente') {
                $whereClause .= ' AND u.tipo_usuario_id = 1';
            } elseif ($tipo === 'contratista') {
                $whereClause .= ' AND u.tipo_usuario_id = 2';
            }
            
            if ($activo !== null) {
                $whereClause .= ' AND u.activo = ?';
                $queryParams[] = (int) $activo;
            }
            
            $stmt = Database::execute(
                "SELECT u.*, tu.nombre as tipo_usuario
                 FROM usuarios u
                 JOIN tipos_usuario tu ON u.tipo_usuario_id = tu.id
                 {$whereClause}
                 ORDER BY u.created_at DESC
                 LIMIT {$limit}",
                $queryParams
            );
            
            $usuarios = $stmt->fetchAll();
            
            return $this->successResponse($response, [
                'usuarios' => $usuarios,
                'total' => count($usuarios),
                'filtros' => compact('tipo', 'activo')
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo usuarios admin: ' . $e->getMessage(), 500);
        }
    }
    
    private function getCount(string $table, string $condition = '1=1'): int
    {
        $stmt = Database::execute("SELECT COUNT(*) as total FROM {$table} WHERE {$condition}");
        return (int) $stmt->fetch()['total'];
    }
    
    private function getFechaInicio(string $periodo): string
    {
        switch ($periodo) {
            case 'dia':
                return date('Y-m-d 00:00:00');
            case 'semana':
                return date('Y-m-d 00:00:00', strtotime('-7 days'));
            case 'año':
                return date('Y-01-01 00:00:00');
            case 'mes':
            default:
                return date('Y-m-01 00:00:00');
        }
    }
}
