# InfyOm Laravel Generator - Investigación Completa

## Introducción

**InfyOm Laravel Generator** es una herramienta avanzada que automatiza la generación completa de código Laravel incluyendo migraciones, modelos, controladores, vistas, rutas y APIs. Es considerado uno de los generadores más completos del ecosistema Laravel.

## ¿Qué es InfyOm Laravel Generator?

Es un paquete que permite generar automáticamente:

- ✅ **Migraciones** de base de datos
- ✅ **Modelos** Eloquent con relaciones
- ✅ **Controladores** con métodos CRUD
- ✅ **Vistas** Blade completas
- ✅ **Rutas** web y API
- ✅ **Requests** de validación
- ✅ **Seeders** y factories
- ✅ **Tests** automatizados
- ✅ **APIs RESTful** completas

## Instalación

### Paso 1: Instalación del Paquete
```bash
composer require infyomlabs/laravel-generator
```

### Paso 2: Publicar Configuraciones
```bash
php artisan vendor:publish --provider="InfyOm\Generator\InfyOmGeneratorServiceProvider"
```

### Paso 3: Publicar Templates (Opcional)
```bash
php artisan infyom:publish
```

### Paso 4: Configurar Layout Base
```bash
php artisan infyom:publish --layout
```

## Configuración Inicial

### Archivo `config/infyom/laravel_generator.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    */
    'path' => [
        'migration'         => database_path('migrations/'),
        'model'            => app_path('Models/'),
        'datatables'       => app_path('DataTables/'),
        'repository'       => app_path('Repositories/'),
        'routes'           => base_path('routes/web.php'),
        'api_routes'       => base_path('routes/api.php'),
        'request'          => app_path('Http/Requests/'),
        'api_request'      => app_path('Http/Requests/API/'),
        'controller'       => app_path('Http/Controllers/'),
        'api_controller'   => app_path('Http/Controllers/API/'),
        'test_trait'       => base_path('tests/traits/'),
        'repository_test'  => base_path('tests/'),
        'api_test'         => base_path('tests/'),
        'views'            => resource_path('views/'),
        'schema_files'     => resource_path('model_schemas/'),
        'seeder'           => database_path('seeders/'),
        'factory'          => database_path('factories/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model extends
    |--------------------------------------------------------------------------
    */
    'model_extend_class' => 'Illuminate\Database\Eloquent\Model',

    /*
    |--------------------------------------------------------------------------
    | API routes prefix & version
    |--------------------------------------------------------------------------
    */
    'api_prefix'  => 'api',
    'api_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    */
    'options' => [
        'soft_delete' => false,
        'save_schema_file' => true,
        'localized' => false,
        'repository_pattern' => false,
        'resources' => false,
        'factory' => false,
        'seeder' => false,
        'swagger' => false,
        'tests' => false,
        'excluded_fields' => ['id', 'created_at', 'updated_at'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prefixes
    |--------------------------------------------------------------------------
    */
    'prefixes' => [
        'route' => '',
        'path' => '',
        'view' => '',
        'public' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-Ons
    |--------------------------------------------------------------------------
    */
    'add_on' => [
        'swagger'       => false,
        'tests'         => false,
        'datatables'    => false,
        'menu'          => [
            'enabled'       => false,
            'menu_file'     => 'layouts/menu.blade.php',
        ],
    ],
];
```

## Métodos de Generación

### 1. Scaffold Completo
Genera todo el CRUD completo:
```bash
php artisan infyom:scaffold NombreModelo
```

### 2. Solo API
Genera solo controladores y rutas API:
```bash
php artisan infyom:api NombreModelo
```

### 3. Componentes Individuales
```bash
# Solo modelo
php artisan infyom:model NombreModelo

# Solo controlador
php artisan infyom:controller NombreModelo

# Solo vistas
php artisan infyom:views NombreModelo

# Solo migración
php artisan infyom:migration NombreModelo

# Solo request
php artisan infyom:request NombreModelo
```

## Definición de Esquemas

### Método 1: Interactive Mode
```bash
php artisan infyom:scaffold Post --fromTable
```

### Método 2: Schema File (.json)
Crear archivo `resources/model_schemas/Post.json`:

```json
{
    "fields": [
        {
            "name": "id",
            "dbType": "increments",
            "htmlType": null,
            "validations": null,
            "searchable": false,
            "fillable": false,
            "primary": true,
            "inForm": false,
            "inIndex": false,
            "inView": false
        },
        {
            "name": "title",
            "dbType": "string",
            "htmlType": "text",
            "validations": "required|string|max:255",
            "searchable": true,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true
        },
        {
            "name": "content",
            "dbType": "text",
            "htmlType": "textarea",
            "validations": "required",
            "searchable": true,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": false,
            "inView": true
        },
        {
            "name": "category_id",
            "dbType": "integer",
            "htmlType": "select",
            "validations": "required|exists:categories,id",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true,
            "relation": "mt1,Category,category_id,id"
        },
        {
            "name": "is_published",
            "dbType": "boolean",
            "htmlType": "checkbox",
            "validations": "boolean",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true,
            "default": false
        },
        {
            "name": "published_at",
            "dbType": "datetime",
            "htmlType": "datetime-local",
            "validations": "nullable|date",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true
        }
    ],
    "relations": [
        {
            "type": "mt1",
            "inputs": "Category,category_id,id"
        }
    ],
    "tableName": "posts"
}
```

### Método 3: Línea de Comandos
```bash
php artisan infyom:scaffold Post --fieldsFile=Post.json
```

### Método 4: Desde Base de Datos Existente
```bash
php artisan infyom:scaffold Post --fromTable --table=posts
```

## Tipos de Campos Soportados

### Tipos de Base de Datos
```bash
# Tipos básicos
string, text, integer, bigInteger, float, double, decimal
boolean, date, datetime, timestamp, time
json, binary, uuid

# Tipos especiales
increments, bigIncrements
enum:value1,value2,value3
foreign:table,column
```

### Tipos HTML
```bash
# Campos de entrada
text, textarea, password, email, number, url, tel
date, time, datetime-local, month, week, color
file, hidden

# Campos de selección
select, radio, checkbox
```

### Validaciones
```bash
# Validaciones comunes
required, nullable, unique, max:255, min:3
email, url, numeric, integer, boolean
exists:table,column, in:value1,value2
regex:/pattern/, confirmed
```

## Relaciones

### Sintaxis de Relaciones
```bash
# One to Many (1:n)
relation_type:RelatedModel,foreign_key,local_key

# Many to One (n:1)
mt1:Category,category_id,id

# One to One (1:1)
1t1:Profile,user_id,id

# Many to Many (n:n)
mtm:Tag,post_tags,post_id,tag_id

# Has Many Through
hmt:Comment,Post,user_id,post_id,id
```

### Ejemplo de Modelo con Relaciones
```json
{
    "fields": [
        {
            "name": "user_id",
            "dbType": "integer",
            "relation": "mt1,User,user_id,id"
        }
    ],
    "relations": [
        {
            "type": "mt1",
            "inputs": "User,user_id,id"
        },
        {
            "type": "1tm",
            "inputs": "Comment,post_id,id"
        },
        {
            "type": "mtm",
            "inputs": "Tag,post_tags,post_id,tag_id"
        }
    ]
}
```

## Comandos Avanzados

### Con Opciones Específicas
```bash
# Con repositorio pattern
php artisan infyom:scaffold Post --repository

# Con tests
php artisan infyom:scaffold Post --tests

# Con factory y seeder
php artisan infyom:scaffold Post --factory --seeder

# Con Swagger documentation
php artisan infyom:scaffold Post --swagger

# Con DataTables
php artisan infyom:scaffold Post --datatables

# Rollback (eliminar archivos generados)
php artisan infyom:rollback Post
```

### Prefijos y Namespaces
```bash
# Con prefijo
php artisan infyom:scaffold Post --prefix=admin

# Esto generará:
# - Rutas: /admin/posts
# - Controlador: Admin\PostController
# - Vistas: admin/posts/
```

### Generación Desde Tabla Existente
```bash
# Escanear tabla existente
php artisan infyom:scaffold Post --fromTable --table=posts

# Con conexión específica
php artisan infyom:scaffold Post --fromTable --table=posts --connection=tenant
```

## Ejemplos Prácticos

### Ejemplo 1: Blog System
```bash
# Generar modelo Category
php artisan infyom:scaffold Category --fieldsFile=Category.json

# Generar modelo Post con relaciones
php artisan infyom:scaffold Post --fieldsFile=Post.json --repository --tests

# Generar modelo Comment
php artisan infyom:scaffold Comment --fieldsFile=Comment.json
```

### Ejemplo 2: E-commerce
```bash
# Productos
php artisan infyom:scaffold Product --fieldsFile=Product.json --datatables

# Categorías
php artisan infyom:scaffold Category --fieldsFile=Category.json

# Órdenes
php artisan infyom:scaffold Order --fieldsFile=Order.json --factory --seeder
```

### Ejemplo 3: Para tu Sistema Multitenancy
```bash
# Empresas
php artisan infyom:scaffold VntCompany --fieldsFile=VntCompany.json --prefix=admin

# Contactos
php artisan infyom:scaffold VntContact --fieldsFile=VntContact.json --repository

# Almacenes
php artisan infyom:scaffold VntWarehouse --fieldsFile=VntWarehouse.json --tests
```

## Estructura Generada

### Archivos Generados por Scaffold
```
app/
├── Http/
│   ├── Controllers/
│   │   └── PostController.php
│   └── Requests/
│       ├── CreatePostRequest.php
│       └── UpdatePostRequest.php
├── Models/
│   └── Post.php
└── Repositories/
    ├── PostRepository.php
    └── PostRepositoryInterface.php

database/
├── factories/
│   └── PostFactory.php
├── migrations/
│   └── 2024_01_01_000000_create_posts_table.php
└── seeders/
    └── PostSeeder.php

resources/
├── views/
│   └── posts/
│       ├── index.blade.php
│       ├── show.blade.php
│       ├── create.blade.php
│       ├── edit.blade.php
│       └── fields.blade.php
└── model_schemas/
    └── Post.json

routes/
├── web.php (rutas agregadas)
└── api.php (rutas API agregadas)

tests/
├── Feature/
│   └── PostTest.php
└── Unit/
    └── PostRepositoryTest.php
```

## Personalización de Templates

### Publicar Templates
```bash
php artisan infyom:publish --templates
```

### Estructura de Templates
```
resources/infyom/infyom-generator-templates/
├── api/
├── scaffold/
├── common/
├── model/
├── repository/
├── controller/
├── views/
├── request/
├── test/
└── seeder/
```

### Personalizar Template de Modelo
Editar `resources/infyom/infyom-generator-templates/model/model.stub`:

```php
<?php

namespace $NAMESPACE$;

use Illuminate\Database\Eloquent\Model;
$SOFT_DELETE_IMPORT$

/**
 * Class $MODEL_NAME$
 * @package $NAMESPACE$
 * @version $VERSION$
 *
$DOC_FIELDS$
 */
class $MODEL_NAME$ extends Model
{
    $SOFT_DELETE$

    public $table = '$TABLE_NAME$';

    $TIMESTAMPS$

    protected $fillable = [
        $FILLABLE$
    ];

    protected $casts = [
        $CASTS$
    ];

    public static $rules = [
        $RULES$
    ];

    $RELATIONS$
}
```

## Integración con Otros Paquetes

### Con Spatie Laravel Permission
```json
{
    "fields": [
        {
            "name": "permissions",
            "dbType": "json",
            "htmlType": "checkbox",
            "validations": "array",
            "fillable": true
        }
    ]
}
```

### Con Laravel Sanctum/Passport
```bash
php artisan infyom:api User --swagger
```

### Con DataTables
```bash
# Instalar DataTables
composer require yajra/laravel-datatables-oracle

# Generar con DataTables
php artisan infyom:scaffold Post --datatables
```

## Casos de Uso Ideales

### ✅ Cuándo Usar InfyOm
- **Prototipos rápidos**: MVPs y demos
- **Proyectos CRUD**: Aplicaciones principalmente CRUD
- **APIs RESTful**: Desarrollo de APIs completas
- **Equipos grandes**: Estandarización de código
- **Proyectos legacy**: Modernización de sistemas
- **Documentación**: Generar documentación automática

### ❌ Cuándo NO Usar
- **Lógica compleja**: Aplicaciones con lógica de negocio muy específica
- **Arquitecturas custom**: Patrones arquitectónicos no estándar
- **Performance crítica**: Aplicaciones que requieren optimización extrema
- **Microservicios**: Arquitecturas distribuidas complejas

## Ventajas y Desventajas

### ✅ Ventajas
- **Desarrollo rápido**: Genera código completo en minutos
- **Consistencia**: Código estandarizado en todo el proyecto
- **Productividad**: Reduce 80% del tiempo de desarrollo CRUD
- **Mantenibilidad**: Estructura predecible y documentada
- **Testing**: Genera tests automatizados
- **API First**: Soporte nativo para APIs RESTful
- **Customización**: Templates completamente personalizables
- **Relaciones**: Soporte completo para relaciones Eloquent

### ❌ Desventajas
- **Rigidez**: Estructura fija puede no adaptarse a todos los casos
- **Sobrecarga**: Genera más código del necesario
- **Aprendizaje**: Curva de aprendizaje para configuración avanzada
- **Dependencia**: Dependencia del paquete para mantenimiento
- **Personalización limitada**: Algunas personalizaciones requieren modificar templates

## Mejores Prácticas

### 1. Planificación de Esquemas
```bash
# Diseñar esquemas completos antes de generar
# Usar herramientas como Draw.io o Lucidchart
# Documentar relaciones entre modelos
```

### 2. Configuración de Proyecto
```bash
# Configurar paths personalizados
# Establecer convenciones de naming
# Configurar templates base
```

### 3. Generación Incremental
```bash
# Generar modelos base primero
php artisan infyom:model Category
php artisan infyom:model Product

# Luego controladores y vistas
php artisan infyom:controller Category
php artisan infyom:views Category
```

### 4. Testing y Validación
```bash
# Siempre generar con tests
php artisan infyom:scaffold Product --tests

# Ejecutar tests después de generar
php artisan test
```

### 5. Versionado
```bash
# Crear branch para código generado
git checkout -b feature/generated-models
php artisan infyom:scaffold Product
git add .
git commit -m "Generate Product CRUD with InfyOm"
```

## Troubleshooting Común

### Error: "Class not found"
```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Error: "Route already defined"
```bash
# Limpiar rutas duplicadas en web.php y api.php
# O usar --skip si ya existen
php artisan infyom:scaffold Post --skip
```

### Error: "Migration already exists"
```bash
# Eliminar migración existente o usar timestamp diferente
rm database/migrations/*_create_posts_table.php
php artisan infyom:scaffold Post
```

### Templates no encontrados
```bash
php artisan infyom:publish --templates --force
```

## Ejemplo Completo: Sistema de Ventas

### 1. Definir Esquema de Producto
`resources/model_schemas/Product.json`:
```json
{
    "fields": [
        {
            "name": "id",
            "dbType": "increments",
            "htmlType": null,
            "validations": null,
            "searchable": false,
            "fillable": false,
            "primary": true,
            "inForm": false,
            "inIndex": false,
            "inView": false
        },
        {
            "name": "name",
            "dbType": "string",
            "htmlType": "text",
            "validations": "required|string|max:255",
            "searchable": true,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true
        },
        {
            "name": "description",
            "dbType": "text",
            "htmlType": "textarea",
            "validations": "nullable|string",
            "searchable": true,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": false,
            "inView": true
        },
        {
            "name": "price",
            "dbType": "decimal:10,2",
            "htmlType": "number",
            "validations": "required|numeric|min:0",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true
        },
        {
            "name": "category_id",
            "dbType": "integer",
            "htmlType": "select",
            "validations": "required|exists:categories,id",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true,
            "relation": "mt1,Category,category_id,id"
        },
        {
            "name": "stock",
            "dbType": "integer",
            "htmlType": "number",
            "validations": "required|integer|min:0",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true,
            "default": 0
        },
        {
            "name": "is_active",
            "dbType": "boolean",
            "htmlType": "checkbox",
            "validations": "boolean",
            "searchable": false,
            "fillable": true,
            "primary": false,
            "inForm": true,
            "inIndex": true,
            "inView": true,
            "default": true
        }
    ],
    "relations": [
        {
            "type": "mt1",
            "inputs": "Category,category_id,id"
        }
    ],
    "tableName": "products"
}
```

### 2. Generar CRUD Completo
```bash
php artisan infyom:scaffold Product --fieldsFile=Product.json --repository --tests --datatables --swagger
```

### 3. Resultado Generado
- ✅ Migración de productos con todas las columnas
- ✅ Modelo Product con relación a Category
- ✅ Controlador con métodos CRUD completos
- ✅ Vistas Blade con formularios y listados
- ✅ Requests de validación
- ✅ Repository pattern implementado
- ✅ Tests automatizados
- ✅ Documentación Swagger
- ✅ Integración DataTables

## Aplicación en tu Proyecto Multitenancy

### Para Base Central
```bash
# Tipos de comercio
php artisan infyom:scaffold VntMerchantType --fieldsFile=VntMerchantType.json --prefix=admin

# Módulos
php artisan infyom:scaffold VntModul --fieldsFile=VntModul.json --prefix=admin

# Empresas
php artisan infyom:scaffold VntCompany --fieldsFile=VntCompany.json --repository --tests
```

### Para Tenants
```bash
# Productos (dentro de tenant)
php artisan infyom:scaffold Product --fieldsFile=Product.json --connection=tenant

# Ventas
php artisan infyom:scaffold Sale --fieldsFile=Sale.json --datatables
```

## Conclusión

**InfyOm Laravel Generator** es una herramienta extremadamente poderosa que puede acelerar dramáticamente el desarrollo de aplicaciones Laravel, especialmente para:

- **Sistemas CRUD complejos**
- **APIs RESTful completas**
- **Prototipos rápidos**
- **Aplicaciones empresariales**

### Para tu sistema multitenancy:
- Perfecto para generar módulos estándar
- Excelente para APIs de gestión
- Ideal para paneles administrativos
- Acelera desarrollo de funcionalidades CRUD

### Comandos recomendados para comenzar:
```bash
composer require infyomlabs/laravel-generator
php artisan vendor:publish --provider="InfyOm\Generator\InfyOmGeneratorServiceProvider"
php artisan infyom:scaffold --help
```

La inversión en tiempo para aprender InfyOm se paga rápidamente con la velocidad de desarrollo que proporciona.