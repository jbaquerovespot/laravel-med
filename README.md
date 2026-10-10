# 🏥 API FHIR - Vespot HCE

API FHIR R4 para el sistema de Historias Clínicas Electrónicas de Vespot SAS, cumpliendo con la **Resolución 1888 de 2025** (RDA) y la **Ley 2015 de 2020**.

[![Status](https://img.shields.io/badge/estado-producci%C3%B3n-green)]()
[![FHIR](https://img.shields.io/badge/FHIR-R4-blue)](https://hl7.org/fhir/R4/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple)]()
[![Laravel](https://img.shields.io/badge/Laravel-11-red)]()

---

## 🚀 Inicio Rápido

### Prerrequisitos

- ✅ PHP 8.2 o superior
- ✅ Composer
- ✅ PostgreSQL 14+
- ✅ Git

### 1. Clonar Repositorio

```bash
git clone <url-del-repositorio>
cd laravel-med
```

### 2. Instalar Dependencias

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configurar Variables de Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con la configuración de tu base de datos:

```env
# Aplicación
APP_NAME="Vespot HCE"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.tudominio.com

# Base de Datos PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=tu-host-postgresql
DB_PORT=5432
DB_DATABASE=medhis
DB_USERNAME=medhis
DB_PASSWORD=tu-password-seguro
DB_SCHEMA=medhis

# FHIR API
FHIR_ENABLED=true
FHIR_MAX_PAGE_SIZE=100
FHIR_DEFAULT_PAGE_SIZE=20
FHIR_ENABLE_SCHEMA_VALIDATION=true
FHIR_ENABLE_BUSINESS_RULES=true
FHIR_RATE_LIMIT_MAX=100
FHIR_RATE_LIMIT_DECAY=1
FHIR_AUDIT_ENABLED=true
FHIR_RDA_ENABLED=true
```

### 4. Ejecutar Migraciones

```bash
php artisan migrate --force
```

### 5. Iniciar Servidor (Desarrollo)

```bash
php artisan serve
```

Accede a: `http://localhost:8000`

---

## 📦 Despliegue a Producción

### Checklist Pre-Producción

- [ ] SSL/TLS configurado
- [ ] Base de datos PostgreSQL 14+
- [ ] Variables de entorno configuradas
- [ ] `APP_DEBUG=false`
- [ ] Caché de configuración generado
- [ ] Caché de rutas generado
- [ ] Backup de base de datos configurado
- [ ] Monitoreo configurado
- [ ] Logs rotados diariamente

### Pasos de Despliegue

#### 1. Configurar Servidor Web (Nginx)

```nginx
server {
    listen 443 ssl http2;
    server_name api.tudominio.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/laravel-med/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Rate limiting
    limit_req zone=api burst=20 nodelay;
}

# Rate limiting zone
limit_req_zone $binary_remote_addr zone=api:10m rate=100r/m;
```

#### 2. Optimizar para Producción

```bash
# Instalar sin dependencias de desarrollo
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Cachear configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Ejecutar migraciones
php artisan migrate --force

# Reiniciar queue workers (si aplica)
php artisan queue:restart
```

#### 3. Configurar Permisos

```bash
# Directorios con permisos de escritura
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

#### 4. Configurar Queue Worker (Systemd)

```ini
# /etc/systemd/system/laravel-queue.service
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/laravel-med
ExecStart=/usr/bin/php /var/www/laravel-med/artisan queue:work --sleep=3 --tries=3 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable laravel-queue
systemctl start laravel-queue
```

#### 5. Configurar Supervisor (Opcional)

```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/laravel-med/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/laravel-med/storage/logs/worker.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*
```

#### 6. Configurar Cron para Tareas Programadas

```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar cada minuto)
* * * * * cd /var/www/laravel-med && php artisan schedule:run >> /dev/null 2>&1
```

#### 7. Health Check

```bash
# Verificar que la API responde
curl -I https://api.tudominio.com/up

# Verificar endpoint FHIR
curl -H "Accept: application/fhir+json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     https://api.tudominio.com/api/fhir/metadata
```

---

## 🔐 Configuración de Seguridad

### Variables de Entorno Críticas

```env
# NUNCA poner APP_DEBUG=true en producción
APP_DEBUG=false

# Key única por instalación
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXX

# HTTPS forzado
APP_URL=https://api.tudominio.com

# Rate limiting más estricto en producción
FHIR_RATE_LIMIT_MAX=50
FHIR_RATE_LIMIT_DECAY=1

# Logs deshabilitados en producción (usar servicio externo)
LOG_CHANNEL=errorlog
LOG_LEVEL=error
```

### Configuración de Base de Datos

```env
# Usar conexión encriptada
DB_CONNECTION=pgsql
DB_HOST=postgresql-host.internal
DB_PORT=5432
DB_DATABASE=medhis
DB_USERNAME=medhis_prod
DB_PASSWORD=password-complejo-min-16-caracteres
DB_SCHEMA=medhis

# SSL para conexión PostgreSQL (recomendado)
DB_SCHEMA=medhis
```

### Permisos y Usuarios

```bash
# Usuario dedicado para PostgreSQL
psql -U postgres -c "CREATE USER medhis_prod WITH PASSWORD 'password-seguro';"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE medhis TO medhis_prod;"
psql -U postgres -c "GRANT ALL ON SCHEMA medhis TO medhis_prod;"
```

---

## 📊 Monitoreo y Logs

### Logs de Aplicación

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver logs de auditoría FHIR
tail -f storage/logs/laravel.log | grep "FHIR API"

# Logs de errores
tail -f storage/logs/laravel.log | grep ERROR
```

### Logs de Auditoría (Base de Datos)

```sql
-- Ver últimas 100 operaciones FHIR
SELECT * FROM activity_log 
WHERE log_name = 'fhir-api' 
ORDER BY created_at DESC 
LIMIT 100;

-- Ver operaciones por usuario
SELECT causer_id, COUNT(*) as operations 
FROM activity_log 
WHERE log_name = 'fhir-api'
GROUP BY causer_id 
ORDER BY operations DESC;

-- Ver errores
SELECT * FROM activity_log 
WHERE properties->>'status_code' >= '400'
ORDER BY created_at DESC;
```

### Health Checks

```bash
# Endpoint básico
GET /up

# Endpoint FHIR metadata
GET /api/fhir/metadata

# Verificar base de datos
php artisan db:show

# Verificar colas
php artisan queue:monitor database
```

### Alertas Recomendadas

Configurar alertas para:
- ❌ Errores 500 > 10 en 5 minutos
- ❌ Tiempo de respuesta > 2 segundos
- ❌ Queue size > 1000 jobs
- ❌ Disco > 80% utilizado
- ❌ Memoria > 90% utilizada
- ❌ Fallos de autenticación > 50 en 5 minutos

---

## 🗄️ Base de Datos

### Backups Automáticos

```bash
#!/bin/bash
# /usr/local/bin/backup-fhir-db.sh

BACKUP_DIR="/backups/fhir"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="medhis"
DB_USER="medhis_prod"

# Crear backup
pg_dump -h localhost -U $DB_USER $DB_NAME | gzip > $BACKUP_DIR/backup_$DATE.sql.gz

# Eliminar backups antiguos (> 7 días)
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

# Subir a S3 (opcional)
aws s3 cp $BACKUP_DIR/backup_$DATE.sql.gz s3://tu-bucket/backups/
```

```bash
# Agregar a crontab (diario a las 2 AM)
0 2 * * * /usr/local/bin/backup-fhir-db.sh
```

### Restaurar Backup

```bash
# Descomprimir
gunzip backup_20260317_020000.sql.gz

# Restaurar
psql -h localhost -U medhis_prod medhis < backup_20260317_020000.sql
```

---

## 🔑 Gestión de Tokens de Usuario

### Crear Usuario y Token

```bash
php artisan tinker
```

```php
// Crear usuario
$user = App\Models\User::create([
    'name' => 'Dr. Usuario',
    'email' => 'usuario@vespot.com',
    'password' => bcrypt('password-seguro'),
]);

// Crear token
$token = $user->createToken('fhir-api')->plainTextToken;
echo $token;
```

### Revocar Tokens

```bash
php artisan tinker
```

```php
// Revocar todos los tokens de un usuario
$user = App\Models\User::find(1);
$user->tokens()->delete();

// Revocar token específico
$user->tokens()->where('id', $tokenId)->delete();
```

---

## 📈 Escalamiento

### Balanceo de Carga

```nginx
upstream fhir_api {
    least_conn;
    server 10.0.1.1:8000;
    server 10.0.1.2:8000;
    server 10.0.1.3:8000;
}

server {
    location / {
        proxy_pass http://fhir_api;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

### Cache Redis

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

```bash
# Instalar Redis
apt install redis-server

# Configurar Laravel
composer require predis/predis

# php artisan config:cache
```

---

## 🐛 Solución de Problemas

### Comandos Útiles

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerar autoload
composer dump-autoload --optimize

# Ver migraciones pendientes
php artisan migrate:status

# Revertir última migración
php artisan migrate:rollback --step=1

# Ver logs de errores recientes
tail -100 storage/logs/laravel.log | grep ERROR

# Verificar conexiones de base de datos
php artisan db:show

# Testear endpoint
curl -H "Accept: application/fhir+json" \
     -H "Authorization: Bearer TOKEN" \
     https://api.tudominio.com/api/fhir/Patient
```

### Problemas Comunes

#### Error 403 Forbidden
```bash
# Verificar permisos del usuario
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $user->can('fhir.Patient.view');
```

#### Error 500 Internal Server Error
```bash
# Activar debug temporalmente
APP_DEBUG=true

# Revisar logs
tail -f storage/logs/laravel.log

# Verificar conexión a BD
php artisan db:show
```

#### Queue no procesa jobs
```bash
# Reiniciar worker
php artisan queue:restart

# Verificar worker
php artisan queue:work --once --verbose

# Ver cola
php artisan queue:monitor database
```

---

## 📞 Soporte

### Contactos

- **Soporte Técnico**: soporte@vespot.com
- **Emergencias**: emergencias@vespot.com
- **Documentación**: https://docs.vespot.com

### Recursos

- [Laravel Docs](https://laravel.com/docs)
- [FHIR R4 Spec](https://hl7.org/fhir/R4/)
- [PostgreSQL Docs](https://www.postgresql.org/docs/)

---

## 📄 Licencia

Propietario - Vespot SAS © 2026

---

**Última actualización**: 17 de marzo de 2026  
**Versión**: 1.0.0  
**Estado**: ✅ Producción
