# 🚀 API Servicios Técnicos

API REST completa para plataforma de servicios técnicos desarrollada con Slim Framework.

## ✅ Características Implementadas

- ✅ **Autenticación JWT** (token simple por ahora)
- ✅ **CRUD completo** de usuarios, solicitudes, contratistas
- ✅ **Sistema de asignaciones** automático
- ✅ **Gestión de citas** con estados
- ✅ **Middleware de autenticación**
- ✅ **Validaciones** y manejo de errores
- ✅ **CORS configurado**
- ✅ **Respuestas JSON estandarizadas**

## 🚀 Inicio Rápido

1. **Instalar dependencias:**
```bash
composer install
```

2. **Configurar base de datos:**
   - Editar `.env` con tus credenciales
   - Importar `u565673608_clientes.sql`

3. **Ejecutar servidor:**
```bash
composer start
```

4. **Probar API:**
```bash
./test-api.ps1
```

## 📚 Endpoints Principales

### Autenticación
- `POST /api/v1/auth/login` - Iniciar sesión
- `POST /api/v1/auth/register` - Registrar usuario

### Usuarios
- `GET /api/v1/usuarios` - Listar usuarios
- `GET /api/v1/usuarios/{id}` - Usuario por ID

### Solicitudes  
- `GET /api/v1/solicitudes` - Listar solicitudes
- `POST /api/v1/solicitudes` - Crear solicitud (🔒 Auth)
- `GET /api/v1/solicitudes/{id}` - Solicitud por ID
- `PUT /api/v1/solicitudes/{id}/estado` - Actualizar estado (🔒 Auth)

### Contratistas
- `GET /api/v1/contratistas` - Listar contratistas
- `GET /api/v1/contratistas/{id}` - Contratista por ID  
- `POST /api/v1/contratistas/buscar` - Buscar disponibles

### Asignaciones
- `GET /api/v1/asignaciones` - Listar asignaciones
- `GET /api/v1/asignaciones/contratista/{id}` - Por contratista (🔒 Auth)
- `PUT /api/v1/asignaciones/{id}/aceptar` - Aceptar (🔒 Auth)
- `PUT /api/v1/asignaciones/{id}/rechazar` - Rechazar (🔒 Auth)

### Citas
- `GET /api/v1/citas` - Listar citas
- `POST /api/v1/citas` - Crear cita (🔒 Auth)
- `PUT /api/v1/citas/{id}/confirmar` - Confirmar (🔒 Auth)
- `PUT /api/v1/citas/{id}/iniciar` - Iniciar servicio (🔒 Auth)
- `PUT /api/v1/citas/{id}/completar` - Completar (🔒 Auth)

### Configuración
- `GET /api/v1/config/categorias` - Categorías de servicios
- `GET /api/v1/config/servicios` - Todos los servicios
- `GET /api/v1/config/servicios/categoria/{id}` - Servicios por categoría

## 🔧 Mejoras Implementadas

### Arquitectura
- **BaseController** con métodos comunes
- **Middleware de autenticación** reutilizable
- **Validaciones centralizadas**
- **Manejo de errores mejorado**
- **Transacciones de base de datos**

### Seguridad
- Validación de entrada en todos los endpoints
- Sanitización de datos
- Protección CORS
- Tokens con expiración

### Base de Datos
- Conexión con pooling
- Prepared statements
- Manejo de transacciones
- Logging de errores

## 📝 Notas de Desarrollo

- Los endpoints marcados con 🔒 requieren header `Authorization: Bearer {token}`
- Respuestas estandarizadas con `success`, `data`, `message`, `timestamp`
- Validación automática de tipos de usuario y permisos
- Sistema de asignación automática a contratistas disponibles

## 🔄 Próximos Pasos

- [ ] Implementar JWT real con refresh tokens
- [ ] Sistema de pagos (MercadoPago)
- [ ] Notificaciones (WhatsApp/Email)  
- [ ] Sistema de evaluaciones
- [ ] Gestión de horarios
- [ ] Panel de administración
- [ ] WebSockets para tiempo real

---

**Creado con ❤️ usando Slim Framework 4**
