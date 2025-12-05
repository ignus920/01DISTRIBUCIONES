# 📋 Guía Completa de Laravel Blueprint

## 🎯 ¿Qué es Laravel Blueprint?

Laravel Blueprint es una herramienta que permite **generar automáticamente** modelos, migraciones, factories, controladores y más archivos de Laravel usando un **archivo YAML** como definición.

## 🚀 Instalación

```bash
composer require laravel-shift/blueprint --dev
```

## 📁 Estructura del Archivo `draft.yaml`

El archivo `draft.yaml` es el **archivo de definición** donde describes tu aplicación usando una sintaxis YAML simple.

### 📂 Ubicación
```
proyecto/
├── draft.yaml           ← Archivo de definición Blueprint
├── app/
├── database/
└── ...
```

## 🔧 Comandos Principales

### 1. **Generar desde draft.yaml**
```bash
php artisan blueprint:build
```
**¿Qué hace?**
- Lee el archivo `draft.yaml`
- Genera modelos, migraciones, factories, controladores
- Crea relaciones automáticamente
- Genera tests básicos

### 2. **Generar solo migraciones**
```bash
php artisan blueprint:build --only=migrations
```

### 3. **Generar solo modelos**
```bash
php artisan blueprint:build --only=models
```

### 4. **Excluir ciertos archivos**
```bash
php artisan blueprint:build --skip=tests,factories
```

### 5. **Ver qué se generaría (sin crear archivos)**
```bash
php artisan blueprint:build --dry-run
```

### 6. **Erase - Eliminar archivos generados**
```bash
php artisan blueprint:erase
```

## 📋 Sintaxis del draft.yaml

### **Estructura Básica**
```yaml
models:
  NombreModelo:
    campo: tipo:longitud modificadores
    timestamps: true/false
    relationships:
      tipoRelacion: ModeloRelacionado
```

### **Tipos de Campos**
```yaml
models:
  Producto:
    # Campos de texto
    nombre: string:255
    codigo: string:100 unique
    descripcion: text nullable

    # Campos numéricos
    precio: decimal:10,2
    stock: integer default:0
    activo: boolean default:true

    # Campos de fecha
    fecha_creacion: datetime
    fecha_venta: date
    hora_inicio: time

    # Campos especiales
    email: string unique
    password: string:60
    usuario_id: id foreign
    imagen: string nullable

    # Timestamps automáticos
    timestamps: true
```

### **Modificadores de Campos**
```yaml
campo: string nullable          # Permite NULL
campo: string unique           # Índice único
campo: string default:valor    # Valor por defecto
campo: string index           # Crear índice
campo: decimal:10,2           # Precisión y escala
campo: enum:opcion1,opcion2   # Campo enum
```

### **Tipos de Relaciones**
```yaml
relationships:
  # Uno a muchos (1:N)
  hasMany: OtroModelo

  # Muchos a uno (N:1)
  belongsTo: ModeloPadre

  # Uno a uno (1:1)
  hasOne: ModeloUnico

  # Muchos a muchos (N:N)
  belongsToMany: ModeloRelacionado

  # Polimórficas
  morphTo: comentable
  morphMany: Comentario
```

## 🎯 Ejemplo Práctico: Tu draft.yaml

Tu archivo actual define un **sistema de tienda multitenancy**:

```yaml
models:
  Cliente:
    nombre: string:200
    email: string unique nullable
    telefono: string:20 nullable
    direccion: text nullable
    ciudad: string:100 nullable
    nit: string:50 nullable
    tipo: enum:natural,juridico default:natural
    activo: boolean default:true
    timestamps: true
    relationships:
      hasMany: Venta
```

### 🔄 Lo que genera Blueprint:

**1. Migración:** `create_clientes_table.php`
```php
Schema::create('clientes', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 200);
    $table->string('email')->unique()->nullable();
    $table->string('telefono', 20)->nullable();
    $table->text('direccion')->nullable();
    $table->string('ciudad', 100)->nullable();
    $table->string('nit', 50)->nullable();
    $table->enum('tipo', ['natural', 'juridico'])->default('natural');
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

**2. Modelo:** `app/Models/Cliente.php`
```php
class Cliente extends Model
{
    protected $fillable = [
        'nombre', 'email', 'telefono', 'direccion',
        'ciudad', 'nit', 'tipo', 'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
```

**3. Factory:** `database/factories/ClienteFactory.php`
```php
public function definition()
{
    return [
        'nombre' => $this->faker->name(),
        'email' => $this->faker->unique()->safeEmail(),
        'telefono' => $this->faker->phoneNumber(),
        // ... más campos
    ];
}
```

## 🚀 Flujo de Trabajo Completo

### 1. **Crear/Editar draft.yaml**
```yaml
models:
  Post:
    title: string:255
    content: text
    published: boolean default:false
    user_id: id foreign
    timestamps: true
    relationships:
      belongsTo: User
      hasMany: Comment
```

### 2. **Generar archivos**
```bash
php artisan blueprint:build
```

### 3. **Ejecutar migraciones**
```bash
php artisan migrate
```

### 4. **Si necesitas cambios, edita draft.yaml y regenera**
```bash
php artisan blueprint:erase    # Elimina archivos generados
# Editar draft.yaml
php artisan blueprint:build    # Regenera con cambios
```

## 🎛️ Comandos Avanzados

### **Generar en directorios específicos**
```yaml
# En draft.yaml
controllers:
  Api/PostController:
    resource: Post
    api: true
```

### **Seeders automáticos**
```bash
php artisan blueprint:build --with-seeds
```

### **Tests automáticos**
```bash
php artisan blueprint:build --with-tests
```

## 🔍 Comandos de Inspección

### **Ver configuración actual**
```bash
php artisan blueprint:trace
```

### **Validar sintaxis del draft.yaml**
```bash
php artisan blueprint:build --dry-run
```

## ⚙️ Configuración Blueprint

**Archivo:** `config/blueprint.php`
```php
return [
    'app_path' => 'app/',
    'namespace' => 'App',
    'models_namespace' => 'Models',
    'generate' => [
        'models',
        'migrations',
        'factories',
        'controllers',
        'requests',
        'tests'
    ],
];
```

## 💡 Tips y Mejores Prácticas

### ✅ **Buenas Prácticas**
- Usa nombres en singular para modelos (`Cliente`, no `Clientes`)
- Define relaciones claramente
- Usa `timestamps: true` para auditoría
- Especifica longitudes de campos de texto
- Usa `nullable` solo cuando sea necesario

### 🚫 **Evita**
- Campos con nombres reservados de PHP/Laravel
- Relaciones circulares complejas
- Cambiar nombres de campos después de generar (mejor regenerar)

## 🔄 Workflow con tu Sistema Multitenancy

Para tu proyecto, el flujo sería:

1. **Editar draft.yaml** con nuevos modelos o cambios
2. **Generar para tenant:**
   ```bash
   php artisan blueprint:build --path=database/migrations/tenant
   ```
3. **Mover modelos generados** al directorio correcto
4. **Ejecutar migraciones en tenants:**
   ```bash
   php artisan tenants:migrate
   ```

## 📚 Ejemplos de Casos de Uso

### **E-commerce Básico**
```yaml
models:
  Product:
    name: string:255
    slug: string unique
    price: decimal:10,2
    stock: integer default:0
    category_id: id foreign
    relationships:
      belongsTo: Category
      hasMany: OrderItem

  Order:
    number: string:20 unique
    total: decimal:10,2
    status: enum:pending,paid,shipped
    user_id: id foreign
    relationships:
      belongsTo: User
      hasMany: OrderItem
```

### **Blog**
```yaml
models:
  Post:
    title: string:255
    slug: string unique
    content: text
    published_at: datetime nullable
    user_id: id foreign
    relationships:
      belongsTo: User
      hasMany: Comment
      belongsToMany: Tag
```

Blueprint es una herramienta **muy poderosa** que acelera significativamente el desarrollo Laravel, especialmente para proyectos con muchos modelos como tu sistema multitenancy. ¡Es perfecto para prototipar rápidamente y mantener consistencia en el código generado!

## 🎯 Para tu Proyecto

Dado que ya tienes las migraciones creadas, podrías usar Blueprint para:
1. **Documentar** la estructura actual
2. **Generar nuevos módulos** rápidamente
3. **Crear factories y tests** automáticamente
4. **Mantener consistencia** en el código



grep "2025-10-24" /home/api.ticsia.com/public_html/storage/logs/laravel.log | tail -200