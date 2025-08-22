# 📋 Documentación API Servicios Técnicos

## 🚀 Información General

**Versión:** 2.0.0  
**Base URL:** `https://api.serviciotecnicos.com` o `http://localhost:8000`  
**Autenticación:** JWT Bearer Token  
**Formato de respuesta:** JSON  

### 📋 Características Principales

- ✅ Gestión completa de usuarios (clientes y contratistas)
- ✅ Sistema de solicitudes y asignaciones
- ✅ Programación de citas y servicios
- ✅ Sistema de pagos con MercadoPago
- ✅ Evaluaciones bidireccionales
- ✅ Notificaciones WhatsApp
- ✅ Rate limiting y caching
- ✅ Logs y auditoría completa

---

## 🔐 Autenticación

### 1. Login
```http
POST /api/v1/auth/login
```

**Request Body:**
```json
{
  "email": "usuario@email.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "user": {
      "id": 1,
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@email.com",
      "tipo_usuario": "cliente",
      "tipo_usuario_id": 1,
      "verificado": true
    },
    "tokens": {
      "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
      "token_type": "Bearer",
      "expires_in": 3600
    }
  }
}
```

### 2. Registro
```http
POST /api/v1/auth/register
```

**Request Body:**
```json
{
  "nombre": "Juan",
  "apellido": "Pérez",
  "email": "juan@email.com",
  "password": "password123",
  "tipo_usuario_id": 1,
  "telefono": "+541123456789",
  "whatsapp": "+541123456789",
  "ciudad": "Buenos Aires",
  "provincia": "CABA"
}
```

### 3. Refresh Token
```http
POST /api/v1/auth/refresh
```

**Request Body:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

### 4. Perfil Usuario
```http
GET /api/v1/auth/me
Authorization: Bearer {access_token}
```

---

## 👥 Gestión de Usuarios

### 1. Obtener Todos los Usuarios
```http
GET /api/v1/usuarios?limit=50
Authorization: Bearer {access_token}
```

### 2. Obtener Usuario por ID
```http
GET /api/v1/usuarios/{id}
Authorization: Bearer {access_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Juan",
    "apellido": "Pérez",
    "email": "juan@email.com",
    "telefono": "+541123456789",
    "whatsapp": "+541123456789",
    "ciudad": "Buenos Aires",
    "provincia": "CABA",
    "tipo_usuario": "cliente",
    "verificado": true,
    "created_at": "2025-01-01 10:00:00"
  }
}
```

---

## 🔧 Contratistas

### 1. Obtener Contratistas
```http
GET /api/v1/contratistas?categoria_id=1&limit=20
```

**Query Parameters:**
- `categoria_id` (opcional): Filtrar por categoría de servicio
- `limit` (opcional): Máximo 100, default 20

**Response:**
```json
{
  "success": true,
  "data": {
    "contratistas": [
      {
        "id": 4,
        "nombre": "Roberto",
        "apellido": "Martínez",
        "email": "roberto@email.com",
        "telefono": "+541134567890",
        "whatsapp": "+541134567890",
        "ciudad": "Buenos Aires",
        "verificado": true,
        "servicios": [
          {
            "categoria_id": 1,
            "categoria_nombre": "Gasista",
            "tarifa_base": 8000,
            "experiencia_anos": 5,
            "certificado": true
          }
        ],
        "rating": {
          "promedio": 4.8,
          "total_evaluaciones": 25
        }
      }
    ],
    "total": 1
  }
}
```

### 2. Obtener Contratista por ID
```http
GET /api/v1/contratistas/{id}
```

### 3. Buscar Contratistas Disponibles
```http
POST /api/v1/contratistas/buscar-disponibles
```

**Request Body:**
```json
{
  "categoria_id": 1,
  "fecha_servicio": "2025-08-25",
  "latitud": -34.6037,
  "longitud": -58.3816,
  "radio_km": 15
}
```

---

## 📋 Configuración y Servicios

### 1. Obtener Categorías
```http
GET /api/v1/config/categorias
```

**Response:**
```json
{
  "success": true,
  "data": {
    "categorias": [
      {
        "id": 1,
        "nombre": "Gasista",
        "descripcion": "Instalación y reparación de gas, calefones, estufas",
        "icono": "gas-icon"
      },
      {
        "id": 2,
        "nombre": "Plomería",
        "descripcion": "Destapes, instalaciones, reparaciones de plomería",
        "icono": "plumber-icon"
      }
    ],
    "total": 10
  }
}
```

### 2. Obtener Servicios
```http
GET /api/v1/config/servicios
```

### 3. Obtener Servicios por Categoría
```http
GET /api/v1/config/categorias/{categoriaId}/servicios
```

---

## 📝 Solicitudes de Servicios

### 1. Crear Solicitud
```http
POST /api/v1/solicitudes
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "cliente_id": 1,
  "categoria_id": 2,
  "servicio_id": 6,
  "titulo": "Destape urgente de baño",
  "descripcion": "Baño completamente tapado, no drena nada",
  "direccion_servicio": "Av. Corrientes 1234, CABA",
  "latitud": -34.6037,
  "longitud": -58.3816,
  "urgencia": "alta",
  "fecha_preferida": "2025-08-25",
  "hora_preferida": "14:00:00",
  "flexible_horario": true,
  "presupuesto_maximo": 15000
}
```

**Response:**
```json
{
  "success": true,
  "message": "Solicitud creada exitosamente",
  "data": {
    "solicitud_id": 123
  }
}
```

### 2. Obtener Solicitudes
```http
GET /api/v1/solicitudes?estado=pendiente&categoria_id=1&urgencia=alta&limit=20
Authorization: Bearer {access_token}
```

### 3. Obtener Solicitud por ID
```http
GET /api/v1/solicitudes/{id}
Authorization: Bearer {access_token}
```

### 4. Actualizar Estado de Solicitud
```http
PUT /api/v1/solicitudes/{id}/estado
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "estado": "completada"
}
```

**Estados válidos:** `pendiente`, `asignada`, `confirmada`, `en_progreso`, `completada`, `cancelada`

---

## 📋 Asignaciones

### 1. Obtener Asignaciones
```http
GET /api/v1/asignaciones?estado=enviada&limit=20
Authorization: Bearer {access_token}
```

### 2. Obtener Asignaciones por Contratista
```http
GET /api/v1/asignaciones/contratista/{contratistaId}?estado=enviada
Authorization: Bearer {access_token}
```

### 3. Aceptar Asignación
```http
POST /api/v1/asignaciones/{id}/aceptar
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "precio_propuesto": 8000,
  "fecha_propuesta": "2025-08-25",
  "hora_propuesta": "14:00:00",
  "tiempo_estimado": 120,
  "comentarios": "Puedo realizar el trabajo mañana por la tarde"
}
```

### 4. Rechazar Asignación
```http
POST /api/v1/asignaciones/{id}/rechazar
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "motivo": "No tengo disponibilidad en esa fecha"
}
```

---

## 📅 Citas

### 1. Crear Cita
```http
POST /api/v1/citas
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "solicitud_id": 1,
  "contratista_id": 4,
  "cliente_id": 1,
  "fecha_servicio": "2025-08-25",
  "hora_inicio": "14:00:00",
  "hora_fin": "16:00:00",
  "precio_acordado": 8000,
  "notas_cliente": "Portero 24hs, timbre depto 4B"
}
```

### 2. Obtener Citas
```http
GET /api/v1/citas?estado=programada&fecha=2025-08-25&limit=20
Authorization: Bearer {access_token}
```

### 3. Obtener Cita por ID
```http
GET /api/v1/citas/{id}
Authorization: Bearer {access_token}
```

### 4. Confirmar Cita
```http
POST /api/v1/citas/{id}/confirmar
Authorization: Bearer {access_token}
```

### 5. Iniciar Servicio
```http
POST /api/v1/citas/{id}/iniciar
Authorization: Bearer {access_token}
```

### 6. Completar Servicio
```http
POST /api/v1/citas/{id}/completar
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "notas_final": "Trabajo completado exitosamente. Se reemplazó la canilla principal."
}
```

---

## 💳 Pagos

### 1. Crear Pago de Consulta
```http
POST /api/v1/pagos/consulta
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "cita_id": 1,
  "monto_consulta": 3000,
  "notification_url": "https://miapp.com/webhook/mercadopago",
  "success_url": "https://miapp.com/pago-exitoso",
  "failure_url": "https://miapp.com/pago-fallido"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Pago creado exitosamente",
  "data": {
    "pago_id": 1,
    "preference_id": "123456789-abcd-1234-abcd-123456789abc",
    "init_point": "https://www.mercadopago.com.ar/checkout/v1/redirect?pref_id=...",
    "sandbox_init_point": "https://sandbox.mercadopago.com.ar/checkout/v1/redirect?pref_id=..."
  }
}
```

### 2. Webhook MercadoPago
```http
POST /api/v1/pagos/webhook/mercadopago
```

### 3. Obtener Pagos por Cita
```http
GET /api/v1/pagos/cita/{citaId}
Authorization: Bearer {access_token}
```

---

## ⭐ Evaluaciones

### 1. Crear Evaluación
```http
POST /api/v1/evaluaciones
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "cita_id": 1,
  "evaluado_id": 4,
  "tipo_evaluador": "cliente",
  "calificacion": 5,
  "comentario": "Excelente trabajo, muy profesional",
  "puntualidad": 5,
  "calidad_trabajo": 5,
  "comunicacion": 4,
  "limpieza": 5
}
```

### 2. Obtener Evaluaciones por Cita
```http
GET /api/v1/evaluaciones/cita/{citaId}
```

### 3. Obtener Evaluaciones por Contratista
```http
GET /api/v1/evaluaciones/contratista/{contratistaId}?limit=10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "evaluaciones": [
      {
        "id": 1,
        "calificacion": 5,
        "comentario": "Excelente trabajo",
        "puntualidad": 5,
        "calidad_trabajo": 5,
        "comunicacion": 4,
        "limpieza": 5,
        "cliente_nombre": "Juan",
        "fecha_servicio": "2025-08-20"
      }
    ],
    "estadisticas": {
      "promedio_general": 4.8,
      "promedio_puntualidad": 4.9,
      "promedio_calidad": 4.7,
      "promedio_comunicacion": 4.6,
      "promedio_limpieza": 4.8,
      "total_evaluaciones": 25
    },
    "total": 25
  }
}
```

---

## 🕐 Horarios

### 1. Obtener Horarios por Contratista
```http
GET /api/v1/horarios/contratista/{contratistaId}?fecha=2025-08-25&limit=30
```

### 2. Crear Horario
```http
POST /api/v1/horarios
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "contratista_id": 4,
  "fecha": "2025-08-25",
  "hora_inicio": "08:00:00",
  "hora_fin": "17:00:00",
  "disponible": true
}
```

### 3. Actualizar Disponibilidad
```http
PUT /api/v1/horarios/{id}
Authorization: Bearer {access_token}
```

### 4. Obtener Disponibilidad
```http
GET /api/v1/horarios/contratista/{contratistaId}/disponibilidad?fecha_inicio=2025-08-25&fecha_fin=2025-08-31
```

---

## 🔔 Notificaciones

### 1. Obtener Notificaciones por Usuario
```http
GET /api/v1/notificaciones/usuario/{userId}?leida=0&limit=20
Authorization: Bearer {access_token}
```

### 2. Marcar como Leída
```http
PUT /api/v1/notificaciones/{id}/leida
Authorization: Bearer {access_token}
```

### 3. Enviar Notificación Manual
```http
POST /api/v1/notificaciones/enviar
Authorization: Bearer {access_token}
```

**Request Body:**
```json
{
  "usuario_id": 1,
  "tipo": "manual",
  "titulo": "Recordatorio de Cita",
  "mensaje": "Tu cita es mañana a las 14:00"
}
```

---

## 📊 Administración

### 1. Dashboard Estadísticas
```http
GET /api/v1/admin/dashboard
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "estadisticas": {
      "usuarios_totales": 150,
      "clientes_totales": 120,
      "contratistas_totales": 30,
      "solicitudes_totales": 80,
      "solicitudes_pendientes": 5,
      "citas_completadas": 65,
      "pagos_exitosos": 58,
      "evaluaciones_totales": 45
    }
  }
}
```

### 2. Estadísticas por Período
```http
GET /api/v1/admin/estadisticas?periodo=mes
Authorization: Bearer {admin_token}
```

### 3. Gestión de Usuarios
```http
GET /api/v1/admin/usuarios?tipo=contratista&activo=1&limit=50
Authorization: Bearer {admin_token}
```

---

## 🧪 Testing

### Ejecutar Tests
```php
// Crear instancia de tests
$tests = new Tests\ApiTestSuite('http://localhost:8000');

// Ejecutar todos los tests
$results = $tests->runAllTests();

// Ver resultados
foreach ($results as $result) {
    echo "{$result['test']}: {$result['status']} - {$result['message']}\n";
}
```

---

## 📋 Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa |
| 201 | Created - Recurso creado |
| 400 | Bad Request - Datos inválidos |
| 401 | Unauthorized - Token inválido |
| 403 | Forbidden - Sin permisos |
| 404 | Not Found - Recurso no encontrado |
| 409 | Conflict - Conflicto de datos |
| 429 | Too Many Requests - Rate limit |
| 500 | Internal Server Error - Error interno |

---

## 🔒 Rate Limiting

**Límites por defecto:**
- 100 requests por IP cada 15 minutos
- Headers de respuesta: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

---

## 🌐 Variables de Entorno

```env
# Base de datos
DB_HOST=srv1597.hstgr.io
DB_NAME=u565673608_clientes
DB_USER=u565673608_clientes
DB_PASS=6i0e#LG1c?

# JWT
JWT_SECRET=mi_clave_super_secreta_2024

# MercadoPago
MERCADOPAGO_ACCESS_TOKEN=tu_access_token_aqui
MERCADOPAGO_PUBLIC_KEY=tu_public_key_aqui

# WhatsApp Business API
WHATSAPP_ACCESS_TOKEN=tu_whatsapp_token_aqui
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id_aqui

# App URL
APP_URL=http://localhost:8000

# Configuraciones
DEBUG_MODE=true
CACHE_ENABLED=true
RATE_LIMIT_ENABLED=true
LOGGING_ENABLED=true
```

---

## 🚀 Instalación y Configuración

### 1. Requisitos
- PHP 8.0+
- MySQL 5.7+
- Composer
- Extensiones: PDO, CURL, JSON

### 2. Instalación
```bash
# Clonar repositorio
git clone [repo-url]

# Instalar dependencias
composer install

# Configurar variables de entorno
cp .env-example .env

# Crear base de datos
mysql < database_complete.sql

# Iniciar servidor
composer run start
```

### 3. Estructura del Proyecto
```
src/
├── Controllers/     # Controladores de la API
├── Middleware/      # Middlewares (Auth, RateLimit, etc.)
├── Services/        # Servicios (JWT, WhatsApp, MercadoPago)
└── Utils/           # Utilidades (Database)

public/
├── index.php        # Punto de entrada
└── .htaccess        # Configuración Apache

tests/
└── ApiTestSuite.php # Tests de la API
```

---

## 📞 Soporte

Para soporte técnico y consultas:
- Email: soporte@serviciotecnicos.com
- WhatsApp: +54 11 xxxx-xxxx
- Documentación: [docs.serviciotecnicos.com]