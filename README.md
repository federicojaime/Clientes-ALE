# 🚀 API Servicios Técnicos - FASE 1 COMPLETA

API REST completa para plataforma de servicios técnicos con **todas las funcionalidades críticas implementadas**.

## 🎯 FASE 1 - FUNCIONALIDADES CRÍTICAS ✅

### ✅ **1. JWT Real con Refresh Tokens**
- Autenticación segura con tokens de acceso (1 hora)
- Refresh tokens para renovación automática (7 días)
- Middleware de seguridad robusto
- Endpoints protegidos

### ✅ **2. Sistema de Pagos (MercadoPago)**
- Integración completa con MercadoPago
- Pagos de consulta con retención
- Webhooks para confirmación automática
- Estados de pago tracking completo

### ✅ **3. Notificaciones WhatsApp**
- Integración con WhatsApp Business API
- Notificaciones automáticas de estado
- Mensajes personalizados por evento
- Sistema de templates

### ✅ **4. Sistema de Evaluaciones**
- Evaluaciones bidireccionales (cliente ↔ contratista)
- Ratings de 1-5 estrellas con categorías
- Estadísticas automáticas de calificaciones
- Sistema de comentarios

## 🛠️ **CONFIGURACIÓN RÁPIDA**

### 1. **Instalar dependencias:**
```bash
composer install
```

### 2. **Configurar .env:**
```env
# Base de datos
DB_HOST=tu_host
DB_NAME=tu_bd
DB_USER=tu_usuario
DB_PASS=tu_password
JWT_SECRET=tu_clave_secreta_super_segura

# MercadoPago
MERCADOPAGO_ACCESS_TOKEN=tu_access_token
MERCADOPAGO_PUBLIC_KEY=tu_public_key

# WhatsApp Business API
WHATSAPP_ACCESS_TOKEN=tu_whatsapp_token
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id

# App URL
APP_URL=http://localhost:8000
```

### 3. **Actualizar base de datos:**
```sql
-- Ejecutar: database-updates-fase1.sql
```

### 4. **Iniciar servidor:**
```bash
composer start
```

### 5. **Probar funcionalidades:**
```bash
./test-fase1.ps1
```

## 📚 **ENDPOINTS PRINCIPALES**

### 🔐 **Autenticación JWT:**
- `POST /api/v1/auth/login` - Login con JWT
- `POST /api/v1/auth/register` - Registro con password
- `POST /api/v1/auth/refresh` - Renovar access token
- `GET /api/v1/auth/me` - Perfil del usuario 🔒
- `POST /api/v1/auth/logout` - Logout 🔒

### 💳 **Sistema de Pagos:**
- `POST /api/v1/pagos/consulta` - Crear pago consulta 🔒
- `POST /api/v1/pagos/webhook/mercadopago` - Webhook MP
- `GET /api/v1/pagos/cita/{id}` - Pagos por cita 🔒

### 📱 **Notificaciones:**
- `GET /api/v1/notificaciones/usuario/{id}` - Por usuario 🔒
- `PUT /api/v1/notificaciones/{id}/leer` - Marcar leída 🔒
- `POST /api/v1/notificaciones/enviar` - Enviar manual 🔒

### ⭐ **Evaluaciones:**
- `POST /api/v1/evaluaciones` - Crear evaluación 🔒
- `GET /api/v1/evaluaciones/cita/{id}` - Por cita
- `GET /api/v1/evaluaciones/contratista/{id}` - Por contratista

### 📋 **CRUD Completo (Ya implementado):**
- Usuarios, Solicitudes, Contratistas, Asignaciones, Citas, Configuración

## 🔥 **FLUJO COMPLETO IMPLEMENTADO:**

1. **Cliente se registra** → JWT tokens generados
2. **Cliente crea solicitud** → Notificación WhatsApp a contratistas
3. **Contratista acepta** → Se crea cita automáticamente
4. **Cliente paga consulta** → MercadoPago + webhook confirmation
5. **Servicio se realiza** → Estados actualizados automáticamente
6. **Cliente evalúa servicio** → Rating y estadísticas actualizadas
7. **Notificaciones automáticas** en cada paso

## 🎯 **PRÓXIMAS FASES:**

### **Fase 2 - Gestión Avanzada:**
- Panel de administración
- Gestión de horarios disponibles
- Sistema de archivos/imágenes
- Estadísticas y reportes avanzados

### **Fase 3 - Escalamiento:**
- Chat en tiempo real
- App móvil
- Geolocalización avanzada
- Analytics y BI

## 🔧 **Tecnologías Utilizadas:**

- **Backend:** PHP 8+ con Slim Framework 4
- **Base de datos:** MySQL/MariaDB
- **Autenticación:** JWT con Firebase/JWT
- **Pagos:** MercadoPago API
- **Notificaciones:** WhatsApp Business API
- **Arquitectura:** RESTful API con middleware

## 📞 **Soporte:**

Tu API está **100% lista para producción** con todas las funcionalidades críticas implementadas.

**🎉 ¡Felicitaciones! Tienes una API de nivel empresarial.** 🚀