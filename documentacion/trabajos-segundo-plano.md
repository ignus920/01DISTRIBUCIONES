# Trabajos en Segundo Plano - Sistema TAT Distribuidora

## 📋 Índice
- [¿Qué son los Jobs en Segundo Plano?](#qué-son-los-jobs-en-segundo-plano)
- [¿Para qué sirven en nuestro sistema?](#para-qué-sirven-en-nuestro-sistema)
- [Tablas de Base de Datos](#tablas-de-base-de-datos)
- [Configuración del Sistema](#configuración-del-sistema)
- [Configuración de Cron Job](#configuración-de-cron-job)
- [Jobs Implementados](#jobs-implementados)
- [Monitoreo y Troubleshooting](#monitoreo-y-troubleshooting)

## ¿Qué son los Jobs en Segundo Plano?

Los **Jobs** (trabajos) en segundo plano son procesos que se ejecutan de forma asíncrona, es decir, **no bloquean la interfaz de usuario** mientras se realizan tareas que pueden tomar mucho tiempo.

### Ventajas:
- ✅ **Respuesta rápida**: El usuario ve confirmación inmediata
- ✅ **No bloqueo**: La aplicación sigue funcionando mientras procesa
- ✅ **Mejor experiencia**: No hay tiempos de espera largos
- ✅ **Escalabilidad**: Maneja múltiples procesos simultáneamente

## ¿Para qué sirven en nuestro sistema?

### Caso Principal: Creación de Usuarios TAT

Cuando se crea un **nuevo usuario TAT (Tienda)**, el sistema debe:

1. ✅ **Crear el usuario** (rápido - 1 segundo)
2. ⏳ **Copiar TODOS los productos** del distribuidor a la tienda TAT (lento - puede ser 1000+ productos)

**Sin Jobs:**
```
Usuario → Crear Cliente → [ESPERA 5-10 MINUTOS] → "Cliente creado"
```

**Con Jobs:**
```
Usuario → Crear Cliente → "Cliente creado" (inmediato)
                      ↓
                 [En segundo plano copia productos]
```

## Tablas de Base de Datos

Para que funcionen los Jobs, se crearon las siguientes tablas:

### Tabla `jobs`
```sql
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Propósito**: Almacena los trabajos pendientes de ejecución.

### Tabla `failed_jobs` (Opcional)
```sql
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Propósito**: Registra los trabajos que fallaron para poder revisarlos.

## Configuración del Sistema

### 1. Archivo `.env`
```env
# Configuración de Colas
QUEUE_CONNECTION=database
```

### 2. Configuración de Cola
El sistema usa la **cola de base de datos** para almacenar y procesar los trabajos.

### 3. Driver de Cola
Laravel maneja automáticamente:
- **Almacenamiento**: En tabla `jobs`
- **Procesamiento**: Via comando `queue:work`
- **Reintentos**: En caso de errores
- **Logging**: Registro de actividad

## Configuración de Cron Job

### En Webmin

1. **Acceder**: `System` → `Scheduled Cron Jobs`
2. **Crear**: `Create a new scheduled cron job`

### Configuración del Cron Job

| Campo | Valor |
|-------|-------|
| **Command** | `cd /ruta/a/tu/proyecto/01DISTRIBUCIONES && php artisan queue:work --stop-when-empty >/dev/null 2>&1` |
| **Execute as user** | `tu_usuario` (mismo usuario del sitio web) |
| **Minutes** | `*` (cada minuto) |
| **Hours** | `*` (todas las horas) |
| **Days** | `*` (todos los días) |
| **Months** | `*` (todos los meses) |
| **Weekdays** | `*` (todos los días de semana) |

### Ejemplo de Comando Completo
```bash
*/1 * * * * cd /home/usuario/public_html/01DISTRIBUCIONES && php artisan queue:work --stop-when-empty >/dev/null 2>&1
```

### ¿Qué hace este comando?

- **`*/1 * * * *`**: Ejecuta cada minuto
- **`cd /ruta/proyecto`**: Cambia al directorio del proyecto
- **`php artisan queue:work`**: Ejecuta el procesador de trabajos
- **`--stop-when-empty`**: Se detiene cuando no hay más trabajos
- **`>/dev/null 2>&1`**: No muestra salida (ejecución silenciosa)

## Jobs Implementados

### 1. CopyProductsToClientJob

**Archivo**: `app/Jobs/CopyProductsToClientJob.php`

**Función**: Copia todos los productos del distribuidor a una nueva tienda TAT.

**Trigger**: Se ejecuta automáticamente cuando se crea un usuario con `profile_id = 17` (Tienda TAT).

**Proceso**:
1. Obtiene todos los productos activos del distribuidor
2. Los copia a la base de datos de la tienda TAT
3. Mantiene las relaciones y estructura
4. Registra logs del proceso

**Código de ejecución**:
```php
// En VntCompanyForm.php
\App\Jobs\CopyProductsToClientJob::dispatch($company->id, $tenantId);
```

## Monitoreo y Troubleshooting

### 1. Verificar Estado de Jobs

**Ver trabajos pendientes**:
```bash
# En la base de datos
SELECT * FROM jobs;
```

**Ver trabajos fallidos**:
```bash
# En la base de datos
SELECT * FROM failed_jobs;
```

### 2. Logs del Sistema

**Archivo de logs**: `storage/logs/laravel.log`

**Buscar logs relacionados**:
```bash
grep "CopyProductsToClientJob" storage/logs/laravel.log
```

### 3. Comandos Útiles

**Ejecutar worker manualmente**:
```bash
php artisan queue:work
```

**Procesar un job específico**:
```bash
php artisan queue:work --once
```

**Limpiar trabajos fallidos**:
```bash
php artisan queue:flush
```

**Reintentar trabajos fallidos**:
```bash
php artisan queue:retry all
```

### 4. Verificación de Funcionamiento

#### Después de crear un usuario TAT:

1. **Verificar tabla jobs**: Debe estar vacía (job procesado)
2. **Verificar productos copiados**: En la tienda TAT debe haber productos
3. **Revisar logs**: Debe mostrar "Usuario creado exitosamente"

#### Si algo falla:

1. **Revisar logs** en `storage/logs/laravel.log`
2. **Verificar cron job** está corriendo
3. **Comprobar permisos** de archivos y directorios
4. **Verificar configuración** de base de datos

### 5. Troubleshooting Común

| Problema | Solución |
|----------|----------|
| Jobs no se procesan | Verificar que cron job esté configurado |
| Error de permisos | Ajustar permisos del usuario web |
| Jobs fallan | Revisar logs y configuración de BD |
| Productos no se copian | Verificar conexiones multi-tenant |

## Beneficios del Sistema

### Para los Usuarios
- ✅ Creación instantánea de clientes
- ✅ No esperas innecesarias
- ✅ Interfaz siempre responsiva

### Para el Sistema
- ✅ Mejor rendimiento general
- ✅ Procesos escalables
- ✅ Manejo de errores robusto
- ✅ Logs detallados para debugging

### Para Administradores
- ✅ Monitoreo de procesos en segundo plano
- ✅ Control de trabajos fallidos
- ✅ Posibilidad de reintentar procesos
- ✅ Logs detallados para troubleshooting

---

**Fecha de creación**: Diciembre 2024
**Última actualización**: Diciembre 2024
**Responsable**: Sistema TAT Distribuidora