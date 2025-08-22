<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\Database;

class HorariosController extends BaseController
{
    public function getByContratista(Request $request, Response $response, array $args): Response
    {
        try {
            $contratistaId = (int) $args['contratistaId'];
            $params = $request->getQueryParams();
            $fecha = $params['fecha'] ?? date('Y-m-d');
            $limite = min((int) ($params['limit'] ?? 30), 100);

            $stmt = Database::execute(
                "SELECT h.*, u.nombre as contratista_nombre 
                 FROM horarios_disponibilidad h
                 JOIN usuarios u ON h.contratista_id = u.id
                 WHERE h.contratista_id = ? 
                 AND h.fecha >= ?
                 ORDER BY h.fecha ASC, h.hora_inicio ASC
                 LIMIT {$limite}",
                [$contratistaId, $fecha]
            );

            $horarios = $stmt->fetchAll();

            // Agrupar por fecha
            $horariosPorFecha = [];
            foreach ($horarios as $horario) {
                $horariosPorFecha[$horario['fecha']][] = $horario;
            }

            return $this->successResponse($response, [
                'horarios' => $horarios,
                'horarios_por_fecha' => $horariosPorFecha,
                'total' => count($horarios)
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo horarios: ' . $e->getMessage(), 500);
        }
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            $error = $this->validateRequired($data, ['contratista_id', 'fecha', 'hora_inicio', 'hora_fin']);
            if ($error) {
                return $this->errorResponse($response, $error);
            }

            $horarioData = [
                'contratista_id' => (int) $data['contratista_id'],
                'fecha' => $data['fecha'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'disponible' => $data['disponible'] ?? 1,
                'motivo_no_disponible' => $data['motivo_no_disponible'] ?? null
            ];

            $horarioId = Database::insert('horarios_disponibilidad', $horarioData);

            return $this->successResponse($response, [
                'horario_id' => $horarioId
            ], 'Horario creado exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error creando horario: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $updateData = [
                'disponible' => $data['disponible'] ?? 1,
                'motivo_no_disponible' => $data['motivo_no_disponible'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $updated = Database::update('horarios_disponibilidad', $id, $updateData);

            if (!$updated) {
                return $this->errorResponse($response, 'Horario no encontrado', 404);
            }

            return $this->successResponse($response, null, 'Horario actualizado');
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error actualizando horario: ' . $e->getMessage(), 500);
        }
    }

    public function getDisponibilidad(Request $request, Response $response, array $args): Response
    {
        try {
            $contratistaId = (int) $args['contratistaId'];
            $params = $request->getQueryParams();
            $fechaInicio = $params['fecha_inicio'] ?? date('Y-m-d');
            $fechaFin = $params['fecha_fin'] ?? date('Y-m-d', strtotime('+7 days'));

            // Horarios disponibles
            $disponibles = Database::execute(
                "SELECT * FROM horarios_disponibilidad 
                 WHERE contratista_id = ? AND fecha BETWEEN ? AND ? 
                 AND disponible = 1
                 ORDER BY fecha, hora_inicio",
                [$contratistaId, $fechaInicio, $fechaFin]
            )->fetchAll();

            // Citas ocupadas
            $ocupadas = Database::execute(
                "SELECT fecha_servicio, hora_inicio, hora_fin FROM citas 
                 WHERE contratista_id = ? AND fecha_servicio BETWEEN ? AND ?
                 AND estado IN ('programada', 'confirmada', 'en_curso')
                 ORDER BY fecha_servicio, hora_inicio",
                [$contratistaId, $fechaInicio, $fechaFin]
            )->fetchAll();

            return $this->successResponse($response, [
                'disponibles' => $disponibles,
                'ocupadas' => $ocupadas,
                'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Error obteniendo disponibilidad: ' . $e->getMessage(), 500);
        }
    }
}
