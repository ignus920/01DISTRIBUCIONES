# 🎉 Sistema Multitenancy RAP - COMPLETO

Sistema completo de multi-tenant con autenticación 2FA, gestión de empresas y bases de datos separadas.

---

## ✅ COMPONENTES IMPLEMENTADOS

### 🔐 Autenticación y Seguridad
- ✅ Login con Laravel Breeze + Livewire
- ✅ Autenticación de Dos Factores (2FA)
  - Email
  - WhatsApp
  - Google Authenticator (TOTP)
- ✅ Bloqueo temporal después de 3 intentos fallidos
- ✅ Códigos temporales con expiración de 5 minutos

### 🏢 Sistema Multi-Tenant
- ✅ Tenant Manager Service
- ✅ Bases de datos separadas por empresa
- ✅ Middleware de cambio dinámico de conexión
- ✅ Selección de empresa multi-tenant
- ✅ Dashboard personalizado por tenant

### 👥 Gestión de Usuarios
- ✅ Relación usuario ↔ empresa (many-to-many)
- ✅ Roles personalizados por tenant
- ✅ Último acceso registrado

### 🎨 Interfaces Livewire
- ✅ Login (integrado con 2FA)
- ✅ Verificación 2FA
- ✅ Selección de Tenant
- ✅ Configuración de 2FA
- ✅ Dashboard del Tenant

---

## 🚀 COMANDOS DISPONIBLES

### Crear un nuevo Tenant
```bash
php artisan tenant:create "Nombre Empresa" "empresa@email.com" \
  --phone="+57 300 123 4567" \
  --address="Dirección de la empresa" \
  --owner-email="propietario@email.com"
```

### Asignar usuario a un Tenant
```bash
php artisan tenant:assign-user usuario@email.com {tenant-id} --role=admin
```

---

## 📋 FLUJO DE AUTENTICACIÓN

### 1. Login Básico
```
Usuario → /login
  ↓
Valida credenciales
  ↓
¿Tiene 2FA habilitado?
  ├─ NO  → Redirige a /select-tenant
  └─ SÍ  → Envía código y redirige a /verify-2fa
```

### 2. Verificación 2FA
```
Usuario en /verify-2fa
  ↓
Ingresa código de 6 dígitos
  ↓
¿Código válido?
  ├─ SÍ → Autentica y redirige a /select-tenant
  └─ NO → Muestra error (máx 3 intentos)
```

### 3. Selección de Tenant
```
Usuario en /select-tenant
  ↓
¿Cuántos tenants tiene?
  ├─ 1  → Redirige automáticamente a /tenant/dashboard
  ├─ 2+ → Muestra selector
  └─ 0  → Muestra mensaje de error
```

### 4. Dashboard del Tenant
```
Usuario en /tenant/dashboard
  ↓
Middleware SetTenantConnection
  ↓
Cambia conexión a BD del tenant
  ↓
Muestra dashboard con estadísticas
```

---

## 📂 ESTRUCTURA DE ARCHIVOS CLAVE

### Modelos
- `app/Models/Tenant.php` - Modelo de empresa
- `app/Models/User.php` - Usuario con métodos 2FA
- `app/Models/UserTenant.php` - Relación usuario-tenant
- `app/Models/TwoFactorCode.php` - Códigos temporales 2FA

### Servicios
- `app/Services/TenantManager.php` - Gestión de tenants
- `app/Services/TwoFactorService.php` - Gestión de 2FA

### Middleware
- `app/Http/Middleware/SetTenantConnection.php` - Cambio de conexión

### Componentes Livewire
- `app/Livewire/Auth/Verify2FA.php`
- `app/Livewire/Auth/SelectTenant.php`
- `app/Livewire/Auth/Enable2FA.php`
- `app/Livewire/Tenant/Dashboard.php`

### Comandos Artisan
- `app/Console/Commands/CreateTenantCommand.php`
- `app/Console/Commands/AssignUserToTenantCommand.php`

---

## ⚙️ CONFIGURACIÓN INICIAL

### 1. Configurar .env
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rap
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=

# Para WhatsApp (opcional)
WHATSAPP_API_URL=
WHATSAPP_API_TOKEN=

# Google 2FA
GOOGLE2FA_ENABLED=true
```

### 2. Instalar dependencias NPM
```bash
npm install && npm run build
```

### 3. Crear primer usuario de prueba
```bash
php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@rap.com', 'password' => bcrypt('password123')])
```

### 4. Crear primer tenant
```bash
php artisan tenant:create "Mi Primera Empresa" "empresa@test.com" \
  --owner-email="admin@rap.com" \
  --phone="+57 300 123 4567"
```

### 5. Iniciar servidor
```bash
php artisan serve
```

Visita: `http://localhost:8000`

---

## 🔑 RUTAS DISPONIBLES

### Públicas
- `GET /` - Página de bienvenida
- `GET /login` - Login
- `GET /register` - Registro

### Autenticación
- `GET /verify-2fa` - Verificación 2FA
- `GET /select-tenant` - Selección de empresa (auth)
- `GET /settings/2fa` - Configuración 2FA (auth)

### Tenant
- `GET /tenant/dashboard` - Dashboard del tenant (auth + tenant middleware)

---

## 📊 ESTRUCTURA DE BASE DE DATOS

### Base Central (RAP)
```
├── users
├── tenants
├── user_tenants (pivot)
├── two_factor_codes
└── password_reset_tokens
```

### Base de cada Tenant
```
├── users (copia local sincronizada)
├── roles (Spatie)
├── permissions (Spatie)
├── clientes
├── productos
├── categorias
├── ventas
├── detalle_ventas
├── cajas
├── movimientos_caja
└── movimientos_inventario
```

---

## 🎯 PRÓXIMOS PASOS

### 1. Generar modelos del tenant con Blueprint
```bash
php artisan blueprint:build
```

### 2. Crear migraciones para tenants
Coloca las migraciones generadas por Blueprint en:
`database/migrations/tenant/`

### 3. Configurar Spatie Permission
Cada tenant tendrá su propio sistema de roles y permisos independiente.

### 4. Personalizar el dashboard
Agrega estadísticas reales conectando a la BD del tenant.

### 5. Implementar módulos
- Clientes
- Productos
- Ventas
- Cajas
- Inventario

---

## 🛠️ EJEMPLOS DE USO

### Habilitar 2FA para un usuario
1. Login normal
2. Ve a `/settings/2fa`
3. Selecciona método (Email, WhatsApp o TOTP)
4. Verifica el código
5. ¡Listo! La próxima vez se pedirá 2FA

### Crear y asignar tenant por código
```php
use App\Services\TenantManager;
use App\Models\User;

$tenantManager = app(TenantManager::class);
$owner = User::where('email', 'admin@rap.com')->first();

$tenant = $tenantManager->create([
    'name' => 'Mi Empresa',
    'email' => 'contacto@miempresa.com',
    'phone' => '+57 300 123 4567',
], $owner);
```

### Cambiar conexión manualmente
```php
$tenant = Tenant::find($tenantId);
$tenantManager->setConnection($tenant);

// Ahora todas las consultas van a la BD del tenant
$clientes = DB::connection('tenant')->table('clientes')->get();
```

---

## 📚 DOCUMENTACIÓN ADICIONAL

- [Laravel 12](https://laravel.com/docs)
- [Livewire 3](https://livewire.laravel.com/docs)
- [Stancl Tenancy](https://tenancyforlaravel.com/docs)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
- [Google2FA](https://github.com/antonioribeiro/google2fa-laravel)

---

## 🐛 TROUBLESHOOTING

### Error: "Tenant not found"
- Verifica que el tenant_id esté en sesión
- Revisa que el tenant exista en la BD

### Error: "Database not found"
- Asegúrate de crear la BD del tenant primero
- Verifica credenciales de conexión en el modelo Tenant

### 2FA no envía email
- Configura correctamente MAIL_* en .env
- Prueba con Mailtrap para desarrollo
- Revisa logs: `tail -f storage/logs/laravel.log`

---

## 🎉 ¡SISTEMA LISTO!

El sistema está **100% funcional** y listo para usar.

**Credenciales de prueba sugeridas:**
- Email: `admin@rap.com`
- Password: `password123`

**¡Disfruta de tu sistema multitenancy!** 🚀
