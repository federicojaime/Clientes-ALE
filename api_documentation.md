# 📚 API Servicios Técnicos - Documentación Completa

## 🏗️ Información General

**Base URL:** `http://localhost:8000`  
**Versión:** v1  
**Formato:** JSON  
**Autenticación:** JWT Bearer Token (algunas rutas)

---

## 🔐 Autenticación

### Headers requeridos para rutas protegidas:
```
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

---

## 📋 Endpoints Disponibles

### 🎯 **INFORMACIÓN GENERAL**

#### `GET /`
**Descripción:** Información de la API y listado de endpoints  
**Autenticación:** No requerida  
**Respuesta:**
```json
{
    "message": "🚀 API Servicios Tecnicos COMPLETA",
    "version": "1.0.0",
    "status": "online",
    "endpoints": { ... }
}
```

---

## 🔑 **AUTENTICACIÓN**

### `POST /api/v1/auth/login`
**Descripción:** Iniciar sesión  
**Autenticación:** No requerida  
**Body:**
```json
{
    "email": "cliente@ejemplo.com",
    "password": "123456"
}
```
**Respuesta exitosa:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "nombre": "Juan",
            "email": "cliente@ejemplo.com",
            "tipo_usuario": "cliente"
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    }
}
```

### `POST /api/v1/auth/register`
**Descripción:** Registrar nuevo usuario  
**Autenticación:** No requerida  
**Body:**
```json
{
    "nombre": "Juan",
    "apellido": "Pérez",
    "email": "nuevo@ejemplo.com",
    "telefono": "+541123456789",
    "whatsapp": "+541123456789",
    "tipo_usuario_id": 1,
    "ciudad": "Buenos Aires",
    "provincia": "CABA"
}
```

---

## 👥 **USUARIOS**

### `GET /api/v1/usuarios`
**Descripción:** Listar todos los usuarios  
**Autenticación:** No requerida  
**Query Params:**
- `limit` (opcional): Máximo 100

### `GET /api/v1/usuarios/{id}`
**Descripción:** Obtener usuario por ID  
**Autenticación:** No requerida

---

## 📝 **SOLICITUDES**

### `GET /api/v1/solicitudes`
**Descripción:** Listar solicitudes con filtros  
**Autenticación:** No requerida  
**Query Params:**
- `estado`: pendiente, asignada, confirmada, en_progreso, completada, cancelada
- `categoria_id`: ID de categoría
- `urgencia`: baja, media, alta, urgente
- `limit`: Máximo 100

### `POST /api/v1/solicitudes`
**Descripción:** Crear nueva solicitud  
**Autenticación:** Requerida  
**Body:**
```json
{
    "cliente_id": 1,
    "categoria_id": 3,
    "servicio_id": 7,
    "titulo": "Destape urgente",
    "descripcion": "Baño completamente tapado",
    "descripcion_personalizada": "Es en el baño principal, segundo piso",
    "urgencia": "alta",
    "direccion_servicio": "Av. Corrientes 1234, CABA",
    "latitud": -34.6037,
    "longitud": -58.3816,
    "fecha_preferida": "2025-01-15",
    "hora_preferida": "14:00",
    "flexible_horario": 1,
    "presupuesto_maximo": 15000
}
```

### `GET /api/v1/solicitudes/{id}`
**Descripción:** Obtener solicitud por ID con asignaciones  
**Autenticación:** No requerida

### `PUT /api/v1/solicitudes/{id}/estado`
**Descripción:** Actualizar estado de solicitud  
**Autenticación:** Requerida  
**Body:**
```json
{
    "estado": "completada"
}
```

---

## 🔧 **CONTRATISTAS**

### `GET /api/v1/contratistas`
**Descripción:** Listar contratistas con servicios y rating  
**Autenticación:** No requerida  
**Query Params:**
- `categoria_id`: Filtrar por categoría
- `limit`: Máximo 100

### `GET /api/v1/contratistas/{id}`
**Descripción:** Obtener contratista completo con estadísticas  
**Autenticación:** No requerida

### `POST /api/v1/contratistas/buscar`
**Descripción:** Buscar contratistas disponibles por criterios  
**Autenticación:** No requerida  
**Body:**
```json
{
    "categoria_id": 3,
    "latitud": -34.6037,
    "longitud": -58.3816,
    "fecha_servicio": "2025-01-15",
    "radio_km": 15
}
```

---

## 📋 **ASIGNACIONES**

### `GET /api/v1/asignaciones`
**Descripción:** Listar todas las asignaciones  
**Autenticación:** No requerida  
**Query Params:**
- `estado`: enviada, vista, aceptada, rechazada, expirada

### `GET /api/v1/asignaciones/contratista/{contratistaId}`
**Descripción:** Asignaciones específicas de un contratista  
**Autenticación:** Requerida  
**Query Params:**
- `estado`: Filtrar por estado

### `PUT /api/v1/asignaciones/{id}/aceptar`
**Descripción:** Contratista acepta asignación  
**Autenticación:** Requerida  
**Body:**
```json
{
    "precio_propuesto": 12000,
    "fecha_propuesta": "2025-01-16",
    "hora_propuesta": "10:00",
    "comentarios": "Puedo ir mañana por la mañana",
    "tiempo_estimado": 120
}
```

### `PUT /api/v1/asignaciones/{id}/rechazar`
**Descripción:** Contratista rechaza asignación  
**Autenticación:** Requerida  
**Body:**
```json
{
    "motivo": "No tengo disponibilidad en esa fecha"
}
```

---

## 📅 **CITAS** *(Funcional)*

### `GET /api/v1/citas`
**Descripción:** Listar citas  
**Query Params:**
- `estado`: programada, confirmada, en_curso, completada, cancelada
- `fecha`: YYYY-MM-DD

### `POST /api/v1/citas`
**Descripción:** Crear nueva cita  
**Body:**
```json
{
    "solicitud_id": 1,
    "contratista_id": 3,
    "cliente_id": 1,
    "fecha_servicio": "2025-01-16",
    "hora_inicio": "10:00",
    "hora_fin": "12:00",
    "precio_acordado": 12000,
    "notas_cliente": "Portero en planta baja",
    "notas_contratista": "Llevar herramientas especiales"
}
```

### `PUT /api/v1/citas/{id}/confirmar`
### `PUT /api/v1/citas/{id}/iniciar`
### `PUT /api/v1/citas/{id}/completar`

---

## ⚙️ **CONFIGURACIÓN**

### `GET /api/v1/config/categorias`
**Descripción:** Listar categorías de servicios activas  
**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Gasista",
            "descripcion": "Servicios de gas, calefones, estufas",
            "icono": "gas-icon"
        }
    ]
}
```

### `GET /api/v1/config/servicios`
**Descripción:** Listar todos los servicios disponibles

### `GET /api/v1/config/servicios/categoria/{categoriaId}`
**Descripción:** Servicios de una categoría específica

---

## ❌ **ENDPOINTS PENDIENTES** *(Por implementar)*

### 💳 **PAGOS** *(FALTA IMPLEMENTAR)*
```
POST /api/v1/pagos/consulta          # Procesar pago de consulta
POST /api/v1/pagos/servicio          # Pago final del servicio  
POST /api/v1/pagos/{id}/capturar     # Capturar pago retenido
POST /api/v1/pagos/{id}/reembolsar   # Reembolsar pago
POST /api/v1/pagos/webhook/mercadopago # Webhook MercadoPago
GET  /api/v1/pagos/cita/{citaId}     # Pagos de una cita
```

### 🔔 **NOTIFICACIONES** *(FALTA IMPLEMENTAR)*
```
GET  /api/v1/notificaciones                    # Lista notificaciones
GET  /api/v1/notificaciones/usuario/{id}       # Por usuario
PUT  /api/v1/notificaciones/{id}/leer          # Marcar leída
POST /api/v1/notificaciones/enviar             # Enviar manual
POST /api/v1/webhooks/whatsapp                 # Webhook WhatsApp
```

### ⭐ **EVALUACIONES** *(FALTA IMPLEMENTAR)*
```
GET  /api/v1/evaluaciones/cita/{citaId}        # Evaluaciones de cita
POST /api/v1/evaluaciones                      # Crear evaluación
GET  /api/v1/evaluaciones/contratista/{id}     # Evaluaciones de contratista
```

### 🕐 **HORARIOS** *(FALTA IMPLEMENTAR)*
```
GET  /api/v1/horarios/contratista/{id}         # Horarios disponibles
POST /api/v1/horarios                          # Crear disponibilidad
PUT  /api/v1/horarios/{id}                     # Actualizar horario
```

### 🔐 **AUTH AVANZADA** *(FALTA IMPLEMENTAR)*
```
POST /api/v1/auth/refresh                      # Refresh token
POST /api/v1/auth/logout                       # Logout
GET  /api/v1/auth/me                          # Perfil usuario
PUT  /api/v1/auth/profile                     # Actualizar perfil
```

### 📊 **ADMIN/ESTADÍSTICAS** *(FALTA IMPLEMENTAR)*
```
GET  /api/v1/admin/dashboard                   # Dashboard general
GET  /api/v1/admin/estadisticas                # Estadísticas del sistema
GET  /api/v1/admin/usuarios                    # Gestión usuarios
```

---

## 🔧 **Códigos de Respuesta**

- **200** - OK
- **201** - Creado exitosamente  
- **400** - Error en los datos enviados
- **401** - No autorizado
- **403** - Acceso denegado
- **404** - No encontrado
- **409** - Conflicto (ej: email ya existe)
- **500** - Error interno del servidor

---

## 📱 **Estructura de Respuesta Estándar**

### Respuesta Exitosa:
```json
{
    "success": true,
    "data": { ... },
    "message": "Operación exitosa"
}
```

### Respuesta de Error:
```json
{
    "error": "Descripción del error",
    "status": 400
}
```