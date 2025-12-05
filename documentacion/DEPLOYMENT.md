# Guía de Despliegue y Git

## 📤 Subir el Proyecto a Git

### Primera vez (inicializar repositorio)

```bash
# 1. Inicializar Git (si no está inicializado)
git init

# 2. Agregar todos los archivos
git add .

# 3. Crear el primer commit
git commit -m "Initial commit: Sistema Multitenancy RAP completo"

# 4. Conectar con repositorio remoto
git remote add origin <URL_DE_TU_REPOSITORIO>

# 5. Subir al repositorio
git push -u origin main
```

### Actualizaciones posteriores

```bash
# 1. Ver cambios
git status

# 2. Agregar cambios
git add .

# 3. Commit con mensaje descriptivo
git commit -m "Descripción de los cambios"

# 4. Subir cambios
git push
```

## 🔄 Clonar en Otro Servidor/Computadora

### Configuración completa desde cero

```bash
# 1. Clonar el repositorio
git clone <URL_DEL_REPOSITORIO>
cd tienda-multitenancy

# 2. Instalar dependencias de PHP
composer install

# 3. Instalar dependencias de Node.js
npm install

# 4. Configurar el archivo .env
cp .env.example .env

# Editar .env con tu editor preferido
nano .env  # o vim .env o code .env

# Configurar estas variables:
# DB_DATABASE=rap
# DB_USERNAME=root
# DB_PASSWORD=tu_password_mysql

# 5. Generar clave de aplicación
php artisan key:generate

# 6. Crear la base de datos central
mysql -u root -p -e "CREATE DATABASE rap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Ejecutar migraciones (SOLO de la base central)
php artisan migrate

# 8. Compilar assets de frontend
npm run build

# 9. Dar permisos a carpetas de Laravel
chmod -R 775 storage bootstrap/cache

# 10. Iniciar el servidor de desarrollo
php artisan serve
```

## 🌐 Despliegue en Producción

### Preparación del servidor

```bash
# 1. Clonar repositorio
cd /var/www
git clone <URL_DEL_REPOSITORIO>
cd tienda-multitenancy

# 2. Instalar dependencias
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 3. Configurar .env para producción
cp .env.example .env
nano .env

# Configurar para producción:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_DATABASE=rap
DB_USERNAME=usuario_produccion
DB_PASSWORD=contraseña_segura

# 4. Optimizaciones de Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Permisos
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 6. Configurar cron para tareas programadas (opcional)
crontab -e
# Agregar:
# * * * * * cd /var/www/tienda-multitenancy && php artisan schedule:run >> /dev/null 2>&1
```

### Configuración de Apache/Nginx

**Apache:**
```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/tienda-multitenancy/public

    <Directory /var/www/tienda-multitenancy/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/rap-error.log
    CustomLog ${APACHE_LOG_DIR}/rap-access.log combined
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/tienda-multitenancy/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 🔧 Actualizar en Producción

```bash
# 1. Ir al directorio del proyecto
cd /var/www/tienda-multitenancy

# 2. Poner en modo mantenimiento
php artisan down

# 3. Obtener últimos cambios
git pull origin main

# 4. Actualizar dependencias
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 5. Ejecutar migraciones si hay nuevas
php artisan migrate --force

# 6. Limpiar cachés
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Salir del modo mantenimiento
php artisan up
```

## 🗄️ Backup de Bases de Datos

### Backup manual

```bash
# Backup de la base central
mysqldump -u root -p rap > backup_rap_$(date +%Y%m%d).sql

# Backup de todas las bases de tenant
mysql -u root -p -e "SHOW DATABASES LIKE 'tenant_%'" | grep tenant_ | while read db; do
    mysqldump -u root -p $db > backup_${db}_$(date +%Y%m%d).sql
done
```

### Restaurar backup

```bash
# Restaurar base central
mysql -u root -p rap < backup_rap_20250115.sql

# Restaurar tenant específico
mysql -u root -p tenant_xxxxx < backup_tenant_xxxxx_20250115.sql
```

## 📊 Monitoreo

### Logs importantes

```bash
# Logs de Laravel
tail -f storage/logs/laravel.log

# Logs de Apache
tail -f /var/log/apache2/rap-error.log

# Logs de Nginx
tail -f /var/log/nginx/error.log
```

### Comandos útiles

```bash
# Ver tenants activos
php artisan tinker
>>> App\Models\Tenant::where('is_active', true)->count();

# Ver usuarios totales
>>> App\Models\User::count();

# Listar bases de datos de tenants
>>> DB::select("SHOW DATABASES LIKE 'tenant_%'");
```

## 🔐 Seguridad

### Checklist de seguridad en producción

- [ ] `APP_DEBUG=false` en `.env`
- [ ] `APP_ENV=production` en `.env`
- [ ] HTTPS configurado con certificado SSL
- [ ] Firewall configurado (solo puertos 80, 443, 22)
- [ ] Backups automáticos programados
- [ ] Permisos correctos en archivos (775 para storage)
- [ ] `php artisan config:cache` ejecutado
- [ ] Archivos `.env` y `.git` no accesibles desde web
- [ ] Rate limiting habilitado en rutas de login
- [ ] Logs rotativos configurados
