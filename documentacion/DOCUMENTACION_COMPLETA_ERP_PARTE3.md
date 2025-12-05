# DOCUMENTACIÓN COMPLETA - PARTE 3
## SISTEMA DE TEMPLATES Y COMPONENTES LIVEWIRE

---

## 6. SISTEMA DE TEMPLATES DE NEGOCIO

### 6.1 Concepto de Templates

Los templates son configuraciones predefinidas que agrupan:
- Módulos necesarios
- Plugins recomendados
- Configuración inicial
- Datos de ejemplo
- Wizard de onboarding personalizado
- Widgets de dashboard específicos

### 6.2 Estructura de un Template

**Archivo:** `app/Templates/pos_basico.json`

```json
{
  "id": "pos_basico",
  "name": "POS Básico - Tienda Pequeña",
  "description": "Ideal para mini-markets, tiendas de barrio, papelerías",
  "icon": "shopping-cart",
  "target_business": ["retail", "small_shop", "convenience_store"],
  "difficulty": "beginner",

  "included_modules": [
    "dashboard",
    "pos",
    "inventory",
    "customers",
    "basic_billing",
    "cash_register"
  ],

  "recommended_plugins": [
    {
      "plugin": "payment-gateways",
      "connectors": ["wompi", "nequi", "daviplata"],
      "optional": true,
      "highlight": "Acepta pagos con QR y transferencias",
      "priority": 1
    },
    {
      "plugin": "whatsapp-integration",
      "connectors": ["meta_api"],
      "optional": true,
      "highlight": "Envía facturas y notificaciones por WhatsApp",
      "priority": 2
    },
    {
      "plugin": "billing-electronic",
      "connectors": ["dian_colombia"],
      "optional": true,
      "highlight": "Facturación electrónica DIAN",
      "priority": 3
    }
  ],

  "pre_configuration": {
    "pos": {
      "sucursales_limit": 2,
      "bodegas_per_sucursal": 2,
      "usuarios_per_sucursal": 4,
      "control_serial": false,
      "allow_negative_stock": false,
      "require_customer_on_sale": false,
      "default_payment_method": "cash",
      "tax_included_in_price": true,
      "default_tax_rate": 0
    },
    "inventory": {
      "multi_warehouse": false,
      "batch_control": false,
      "serial_control": false,
      "low_stock_alert": true,
      "low_stock_threshold": 5,
      "valuation_method": "average_cost"
    },
    "billing": {
      "electronic": false,
      "types": ["factura_simple", "nota_credito"],
      "numeration_prefix": "F",
      "numeration_start": 1,
      "auto_increment": true
    },
    "cash_register": {
      "require_opening_balance": true,
      "allow_multiple_open": false,
      "max_cash_difference": 5000
    },
    "customers": {
      "require_tax_id": false,
      "allow_credit": false,
      "default_credit_days": 0
    }
  },

  "onboarding_wizard": {
    "enabled": true,
    "skip_allowed": false,
    "steps": [
      {
        "id": "business_info",
        "title": "Información de tu Negocio",
        "description": "Completa los datos básicos de tu tienda",
        "component": "BusinessInfoStep",
        "fields": [
          {
            "name": "business_name",
            "type": "text",
            "label": "Nombre del negocio",
            "required": true
          },
          {
            "name": "nit",
            "type": "text",
            "label": "NIT o Cédula",
            "required": false
          },
          {
            "name": "address",
            "type": "textarea",
            "label": "Dirección",
            "required": true
          },
          {
            "name": "phone",
            "type": "tel",
            "label": "Teléfono",
            "required": true
          },
          {
            "name": "email",
            "type": "email",
            "label": "Email",
            "required": false
          }
        ],
        "completion_percentage": 20
      },
      {
        "id": "cash_register_setup",
        "title": "Configurar Caja",
        "description": "Define cómo funcionará tu caja registradora",
        "component": "CashRegisterSetup",
        "fields": [
          {
            "name": "register_name",
            "type": "text",
            "label": "Nombre de la caja",
            "default": "Caja Principal",
            "required": true
          },
          {
            "name": "opening_balance",
            "type": "number",
            "label": "Monto inicial de caja",
            "default": 0,
            "min": 0,
            "required": true
          }
        ],
        "completion_percentage": 40
      },
      {
        "id": "product_import",
        "title": "Agregar Productos",
        "description": "Importa o crea tus primeros productos",
        "component": "QuickProductImport",
        "optional": true,
        "actions": [
          {
            "type": "upload_excel",
            "label": "Subir Excel",
            "template_download": "/templates/productos_basico.xlsx"
          },
          {
            "type": "manual_create",
            "label": "Crear Manualmente",
            "min_products": 5
          },
          {
            "type": "demo_data",
            "label": "Usar Datos de Ejemplo",
            "products_count": 20
          }
        ],
        "completion_percentage": 60
      },
      {
        "id": "payment_methods",
        "title": "Métodos de Pago",
        "description": "Selecciona cómo recibirás pagos",
        "component": "PaymentMethodsSelector",
        "default_methods": [
          {
            "name": "Efectivo",
            "slug": "cash",
            "enabled": true,
            "requires_reference": false
          },
          {
            "name": "Tarjeta Débito",
            "slug": "debit_card",
            "enabled": true,
            "requires_reference": true
          },
          {
            "name": "Tarjeta Crédito",
            "slug": "credit_card",
            "enabled": true,
            "requires_reference": true
          },
          {
            "name": "Transferencia",
            "slug": "transfer",
            "enabled": false,
            "requires_reference": true
          }
        ],
        "recommended_plugins": ["payment-gateways"],
        "completion_percentage": 80
      },
      {
        "id": "first_sale",
        "title": "¡Listo para Vender!",
        "description": "Tu sistema está configurado. Haz tu primera venta de prueba",
        "component": "FirstSaleDemo",
        "actions": [
          {
            "type": "demo_sale",
            "label": "Hacer Venta de Prueba"
          },
          {
            "type": "skip_to_dashboard",
            "label": "Ir al Panel"
          }
        ],
        "completion_percentage": 100
      }
    ]
  },

  "dashboard_widgets": [
    {
      "id": "daily_sales",
      "name": "Ventas de Hoy",
      "component": "DailySalesWidget",
      "position": {"row": 1, "col": 1},
      "size": {"width": 1, "height": 1},
      "refresh_interval": 60000
    },
    {
      "id": "cash_status",
      "name": "Estado de Caja",
      "component": "CashStatusWidget",
      "position": {"row": 1, "col": 2},
      "size": {"width": 1, "height": 1}
    },
    {
      "id": "top_products",
      "name": "Productos Más Vendidos",
      "component": "TopProductsWidget",
      "position": {"row": 2, "col": 1},
      "size": {"width": 2, "height": 1},
      "config": {"limit": 5, "period": "today"}
    },
    {
      "id": "low_stock_alert",
      "name": "Alertas de Stock Bajo",
      "component": "LowStockAlertWidget",
      "position": {"row": 3, "col": 1},
      "size": {"width": 2, "height": 1},
      "config": {"threshold": 5}
    },
    {
      "id": "quick_sale_button",
      "name": "Nueva Venta Rápida",
      "component": "QuickSaleButtonWidget",
      "position": {"row": 1, "col": 3},
      "size": {"width": 1, "height": 2}
    }
  ],

  "sample_data": {
    "categories": [
      {"name": "Abarrotes", "description": "Productos de primera necesidad"},
      {"name": "Bebidas", "description": "Refrescos y bebidas"},
      {"name": "Snacks", "description": "Dulces y mecato"},
      {"name": "Aseo", "description": "Productos de limpieza"}
    ],
    "products": [
      {
        "name": "Arroz 500g",
        "code": "ARR500",
        "category": "Abarrotes",
        "price": 2500,
        "cost": 2000,
        "stock": 50,
        "min_stock": 10
      },
      {
        "name": "Aceite 1L",
        "code": "ACE1L",
        "category": "Abarrotes",
        "price": 8500,
        "cost": 7000,
        "stock": 20,
        "min_stock": 5
      }
    ],
    "customers": [
      {
        "name": "Cliente General",
        "email": null,
        "phone": null,
        "type": "individual"
      }
    ]
  },

  "pricing": {
    "monthly": 9.99,
    "annual": 99.00,
    "currency": "USD",
    "trial_days": 15,
    "features_included": [
      "Hasta 2 usuarios",
      "1 sucursal",
      "Productos ilimitados",
      "Ventas ilimitadas",
      "Soporte por email"
    ]
  },

  "help_resources": {
    "video_tutorial": "https://youtube.com/watch?v=xxx",
    "documentation": "https://docs.tuapp.com/pos-basico",
    "community_forum": "https://community.tuapp.com/pos-basico"
  }
}
```

### 6.3 Template para Restaurante

**Archivo:** `app/Templates/restaurante.json`

```json
{
  "id": "restaurante",
  "name": "Restaurante / Cafetería",
  "description": "Gestión completa para restaurantes, bares y cafeterías",
  "icon": "utensils",
  "target_business": ["restaurant", "cafe", "bar", "food_truck"],
  "difficulty": "intermediate",

  "included_modules": [
    "dashboard",
    "pos",
    "inventory",
    "customers",
    "basic_billing",
    "cash_register"
  ],

  "required_plugins": [
    {
      "plugin": "pos-restaurant",
      "required": true,
      "features": [
        "Gestión de mesas y zonas",
        "Comandas a cocina",
        "División de cuentas",
        "Propinas configurables",
        "Control de turnos",
        "Modificadores de platillos"
      ]
    }
  ],

  "recommended_plugins": [
    {
      "plugin": "delivery-management",
      "optional": true,
      "highlight": "Gestiona pedidos a domicilio",
      "priority": 1
    },
    {
      "plugin": "whatsapp-integration",
      "optional": true,
      "highlight": "Recibe pedidos por WhatsApp",
      "priority": 2
    },
    {
      "plugin": "payment-gateways",
      "connectors": ["stripe", "mercadopago", "wompi"],
      "optional": true,
      "priority": 3
    }
  ],

  "pre_configuration": {
    "pos_restaurant": {
      "table_management": true,
      "kitchen_display": true,
      "split_bills": true,
      "tips_enabled": true,
      "suggested_tip_percentages": [10, 15, 20],
      "zones": ["Terraza", "Interior", "Barra"],
      "table_prefix": "Mesa",
      "auto_print_kitchen": true,
      "preparation_time_tracking": true
    },
    "inventory": {
      "recipe_management": true,
      "ingredient_tracking": true,
      "portion_control": true,
      "waste_tracking": true,
      "multi_warehouse": false
    },
    "pos": {
      "require_customer_on_sale": false,
      "allow_modifiers": true,
      "allow_discounts": true,
      "max_discount_percent": 20,
      "service_charge_enabled": false,
      "service_charge_percent": 0
    }
  },

  "onboarding_wizard": {
    "enabled": true,
    "skip_allowed": false,
    "steps": [
      {
        "id": "restaurant_info",
        "title": "Información del Restaurante",
        "component": "RestaurantInfoStep",
        "fields": [
          {"name": "restaurant_name", "type": "text", "required": true},
          {"name": "cuisine_type", "type": "select", "options": [
            "Comida Rápida", "Gourmet", "Cafetería",
            "Bar", "Comida Típica", "Internacional"
          ]},
          {"name": "seating_capacity", "type": "number", "min": 1},
          {"name": "phone", "type": "tel", "required": true}
        ],
        "completion_percentage": 15
      },
      {
        "id": "table_layout",
        "title": "Configurar Mesas y Zonas",
        "description": "Diseña la distribución de tu restaurante",
        "component": "TableLayoutDesigner",
        "features": [
          "Drag & drop de mesas",
          "Múltiples zonas",
          "Capacidad por mesa",
          "Numeración automática"
        ],
        "completion_percentage": 30
      },
      {
        "id": "menu_creation",
        "title": "Crear Menú",
        "description": "Define categorías y platillos",
        "component": "MenuCreator",
        "features": [
          "Categorías (Entradas, Platos Fuertes, Postres, Bebidas)",
          "Modificadores (Sin cebolla, Extra queso, etc.)",
          "Combos y promociones",
          "Recetas con ingredientes"
        ],
        "completion_percentage": 60
      },
      {
        "id": "kitchen_setup",
        "title": "Configurar Cocina",
        "component": "KitchenSetup",
        "fields": [
          {
            "name": "kitchen_printers",
            "type": "array",
            "label": "Impresoras de Cocina",
            "fields": [
              {"name": "name", "type": "text"},
              {"name": "ip", "type": "text"},
              {"name": "categories", "type": "multi-select"}
            ]
          },
          {
            "name": "stations",
            "type": "array",
            "label": "Estaciones de Cocina",
            "default": ["Fría", "Caliente", "Postres", "Bar"]
          }
        ],
        "completion_percentage": 80
      },
      {
        "id": "staff_setup",
        "title": "Configurar Personal",
        "component": "StaffSetup",
        "roles": [
          "Mesero",
          "Cajero",
          "Cocina",
          "Bartender",
          "Administrador"
        ],
        "completion_percentage": 100
      }
    ]
  },

  "dashboard_widgets": [
    {
      "id": "table_status",
      "name": "Estado de Mesas",
      "component": "TableStatusWidget",
      "position": {"row": 1, "col": 1},
      "size": {"width": 2, "height": 2},
      "config": {
        "colors": {
          "available": "green",
          "occupied": "red",
          "reserved": "yellow"
        }
      }
    },
    {
      "id": "active_orders",
      "name": "Órdenes Activas",
      "component": "ActiveOrdersWidget",
      "position": {"row": 1, "col": 3},
      "size": {"width": 1, "height": 1}
    },
    {
      "id": "kitchen_queue",
      "name": "Cola de Cocina",
      "component": "KitchenQueueWidget",
      "position": {"row": 2, "col": 3},
      "size": {"width": 1, "height": 1},
      "config": {"show_preparation_time": true}
    },
    {
      "id": "daily_revenue",
      "name": "Ingresos del Día",
      "component": "DailyRevenueWidget",
      "position": {"row": 3, "col": 1},
      "size": {"width": 1, "height": 1}
    },
    {
      "id": "popular_dishes",
      "name": "Platillos Populares",
      "component": "PopularDishesWidget",
      "position": {"row": 3, "col": 2},
      "size": {"width": 1, "height": 1},
      "config": {"limit": 5}
    },
    {
      "id": "waiter_performance",
      "name": "Desempeño de Meseros",
      "component": "WaiterPerformanceWidget",
      "position": {"row": 3, "col": 3},
      "size": {"width": 1, "height": 1}
    }
  ],

  "sample_data": {
    "categories": [
      {"name": "Entradas", "icon": "🥗"},
      {"name": "Platos Fuertes", "icon": "🍽️"},
      {"name": "Postres", "icon": "🍰"},
      {"name": "Bebidas", "icon": "🥤"}
    ],
    "products": [
      {
        "name": "Ensalada César",
        "category": "Entradas",
        "price": 15000,
        "cost": 8000,
        "preparation_time": 10,
        "modifiers": ["Sin cebolla", "Extra pollo"]
      },
      {
        "name": "Bandeja Paisa",
        "category": "Platos Fuertes",
        "price": 28000,
        "cost": 15000,
        "preparation_time": 20
      }
    ],
    "tables": [
      {"number": 1, "zone": "Interior", "capacity": 4},
      {"number": 2, "zone": "Interior", "capacity": 2},
      {"number": 3, "zone": "Terraza", "capacity": 6}
    ]
  },

  "pricing": {
    "monthly": 29.99,
    "annual": 299.00,
    "trial_days": 15,
    "features_included": [
      "Usuarios ilimitados",
      "Mesas ilimitadas",
      "Gestión de comandas",
      "División de cuentas",
      "Soporte prioritario"
    ]
  }
}
```

### 6.4 Template POS Institucional

**Archivo:** `app/Templates/pos_institucional.json`

```json
{
  "id": "pos_institucional",
  "name": "POS Institucional",
  "description": "Para distribuidoras, empresas medianas con múltiples sucursales",
  "icon": "building-office",
  "target_business": ["distributor", "wholesaler", "medium_business"],
  "difficulty": "advanced",

  "included_modules": [
    "dashboard",
    "pos",
    "inventory",
    "customers",
    "suppliers",
    "billing",
    "purchasing",
    "accounting_basic",
    "cash_register",
    "reports"
  ],

  "required_plugins": [],

  "recommended_plugins": [
    {
      "plugin": "billing-electronic",
      "connectors": ["dian_colombia", "sunat_peru"],
      "priority": 1,
      "highlight": "Facturación electrónica obligatoria"
    },
    {
      "plugin": "accounting-premium",
      "priority": 2,
      "highlight": "Contabilidad completa con estados financieros"
    },
    {
      "plugin": "advanced-reports",
      "priority": 3,
      "highlight": "Reportes personalizables y Business Intelligence"
    }
  ],

  "pre_configuration": {
    "pos": {
      "multi_sucursales": true,
      "sucursales_limit": null,
      "bodegas_per_sucursal": 5,
      "usuarios_per_sucursal": null,
      "control_serial": true,
      "allow_negative_stock": false,
      "require_customer_on_sale": true,
      "credit_sales_enabled": true,
      "quotation_enabled": true,
      "remission_enabled": true,
      "composite_invoice": true
    },
    "inventory": {
      "multi_warehouse": true,
      "batch_control": true,
      "serial_control": true,
      "expiration_control": true,
      "transfer_between_warehouses": true,
      "transfer_approval_required": true,
      "inventory_count_required": "monthly",
      "valuation_method": "fifo"
    },
    "billing": {
      "electronic": true,
      "types": [
        "factura",
        "factura_electronica",
        "nota_credito",
        "nota_debito",
        "remision",
        "cotizacion"
      ],
      "numeration_by_branch": true,
      "resolution_control": true
    },
    "purchasing": {
      "purchase_orders_enabled": true,
      "purchase_approval_required": true,
      "approval_amount_threshold": 5000000,
      "supplier_evaluation": true,
      "receive_by_warehouse": true
    },
    "customers": {
      "require_tax_id": true,
      "credit_management": true,
      "credit_limit_check": true,
      "aging_report": true,
      "customer_categories": ["VIP", "Regular", "Nuevo"]
    },
    "users": {
      "permissions_per_module": true,
      "permissions_per_branch": true,
      "audit_log_enabled": true,
      "session_timeout": 60
    }
  },

  "onboarding_wizard": {
    "enabled": true,
    "skip_allowed": true,
    "steps": [
      {
        "id": "company_info",
        "title": "Información de la Empresa",
        "component": "CompanyInfoStep",
        "fields": [
          {"name": "company_name", "type": "text", "required": true},
          {"name": "tax_id", "type": "text", "label": "NIT", "required": true},
          {"name": "regime", "type": "select", "options": ["Simplificado", "Común"], "required": true},
          {"name": "legal_address", "type": "textarea", "required": true},
          {"name": "main_phone", "type": "tel", "required": true},
          {"name": "email", "type": "email", "required": true}
        ],
        "completion_percentage": 10
      },
      {
        "id": "branches_setup",
        "title": "Configurar Sucursales",
        "component": "BranchesSetup",
        "fields": [
          {
            "name": "branches",
            "type": "array",
            "min": 1,
            "fields": [
              {"name": "name", "type": "text", "required": true},
              {"name": "code", "type": "text", "required": true},
              {"name": "address", "type": "text", "required": true},
              {"name": "phone", "type": "tel"},
              {"name": "manager_name", "type": "text"}
            ]
          }
        ],
        "completion_percentage": 25
      },
      {
        "id": "warehouses_setup",
        "title": "Configurar Bodegas",
        "component": "WarehousesSetup",
        "description": "Define las bodegas por sucursal",
        "completion_percentage": 40
      },
      {
        "id": "users_roles",
        "title": "Usuarios y Roles",
        "component": "UsersRolesSetup",
        "default_roles": [
          {"name": "Administrador General", "permissions": "all"},
          {"name": "Administrador Sucursal", "permissions": "branch"},
          {"name": "Vendedor", "permissions": "sales"},
          {"name": "Bodeguero", "permissions": "inventory"},
          {"name": "Contador", "permissions": "accounting"}
        ],
        "completion_percentage": 60
      },
      {
        "id": "billing_setup",
        "title": "Configurar Facturación",
        "component": "BillingSetup",
        "fields": [
          {
            "name": "enable_electronic_billing",
            "type": "checkbox",
            "label": "Habilitar facturación electrónica",
            "default": true
          },
          {
            "name": "dian_configuration",
            "type": "group",
            "visible_if": "enable_electronic_billing",
            "fields": [
              {"name": "software_id", "type": "text"},
              {"name": "test_set_id", "type": "text"},
              {"name": "certificate", "type": "file"}
            ]
          }
        ],
        "completion_percentage": 80
      },
      {
        "id": "data_import",
        "title": "Importar Datos",
        "component": "DataImportStep",
        "optional": true,
        "import_types": [
          {
            "type": "products",
            "template": "/templates/productos_institucional.xlsx",
            "max_rows": 10000
          },
          {
            "type": "customers",
            "template": "/templates/clientes_institucional.xlsx",
            "max_rows": 5000
          },
          {
            "type": "suppliers",
            "template": "/templates/proveedores.xlsx"
          }
        ],
        "completion_percentage": 100
      }
    ]
  },

  "dashboard_widgets": [
    {
      "id": "sales_by_branch",
      "name": "Ventas por Sucursal",
      "component": "SalesByBranchWidget",
      "position": {"row": 1, "col": 1},
      "size": {"width": 2, "height": 1},
      "config": {"chart_type": "bar", "period": "today"}
    },
    {
      "id": "inventory_valuation",
      "name": "Valorización de Inventario",
      "component": "InventoryValuationWidget",
      "position": {"row": 1, "col": 3},
      "size": {"width": 1, "height": 1}
    },
    {
      "id": "pending_approvals",
      "name": "Aprobaciones Pendientes",
      "component": "PendingApprovalsWidget",
      "position": {"row": 2, "col": 1},
      "size": {"width": 1, "height": 1},
      "types": ["purchase_orders", "transfers", "refunds"]
    },
    {
      "id": "accounts_receivable",
      "name": "Cartera por Cobrar",
      "component": "AccountsReceivableWidget",
      "position": {"row": 2, "col": 2},
      "size": {"width": 1, "height": 1},
      "config": {"aging_periods": [30, 60, 90, 120]}
    },
    {
      "id": "top_customers",
      "name": "Mejores Clientes",
      "component": "TopCustomersWidget",
      "position": {"row": 2, "col": 3},
      "size": {"width": 1, "height": 1},
      "config": {"limit": 10, "period": "month"}
    },
    {
      "id": "low_stock_multi_warehouse",
      "name": "Stock Bajo por Bodega",
      "component": "LowStockMultiWarehouseWidget",
      "position": {"row": 3, "col": 1},
      "size": {"width": 2, "height": 1}
    },
    {
      "id": "sales_commissions",
      "name": "Comisiones de Vendedores",
      "component": "SalesCommissionsWidget",
      "position": {"row": 3, "col": 3},
      "size": {"width": 1, "height": 1}
    }
  ],

  "pricing": {
    "monthly": 79.99,
    "annual": 799.00,
    "per_additional_user": 10.00,
    "per_additional_branch": 20.00,
    "trial_days": 30,
    "features_included": [
      "Hasta 5 usuarios incluidos",
      "Hasta 3 sucursales incluidas",
      "Bodegas ilimitadas",
      "Facturación electrónica",
      "Soporte prioritario 24/7",
      "Capacitación inicial incluida"
    ]
  }
}
```

### 6.5 TemplateManager - Gestor de Templates

**Archivo:** `app/Core/Template/TemplateManager.php`

```php
<?php

namespace App\Core\Template;

use App\Models\Tenant;
use Illuminate\Support\Facades\File;

class TemplateManager
{
    private $templatesPath;

    public function __construct()
    {
        $this->templatesPath = app_path('Templates');
    }

    /**
     * Cargar template por nombre.
     */
    public function load(string $templateName): ?array
    {
        $templateFile = $this->templatesPath . "/{$templateName}.json";

        if (!File::exists($templateFile)) {
            return null;
        }

        return json_decode(File::get($templateFile), true);
    }

    /**
     * Obtener todos los templates disponibles.
     */
    public function all(): array
    {
        $templates = [];

        foreach (File::files($this->templatesPath) as $file) {
            if ($file->getExtension() === 'json') {
                $template = json_decode(File::get($file), true);
                if ($template && isset($template['id'])) {
                    $templates[] = $template;
                }
            }
        }

        return $templates;
    }

    /**
     * Aplicar template a un tenant.
     */
    public function apply(array $template, Tenant $tenant): bool
    {
        DB::beginTransaction();

        try {
            // 1. Habilitar módulos incluidos
            foreach ($template['included_modules'] as $moduleSlug) {
                app(\App\Core\Module\ModuleManager::class)
                    ->enableForTenant($tenant, $moduleSlug);
            }

            // 2. Instalar plugins requeridos
            if (isset($template['required_plugins'])) {
                foreach ($template['required_plugins'] as $pluginConfig) {
                    app(\App\Core\Plugin\PluginManager::class)
                        ->install($pluginConfig['plugin'], $tenant);
                }
            }

            // 3. Aplicar pre-configuración
            if (isset($template['pre_configuration'])) {
                $this->applyConfiguration($template['pre_configuration'], $tenant);
            }

            // 4. Crear datos de ejemplo si existen
            if (isset($template['sample_data'])) {
                $this->createSampleData($template['sample_data'], $tenant);
            }

            // 5. Configurar dashboard widgets
            if (isset($template['dashboard_widgets'])) {
                $this->configureDashboard($template['dashboard_widgets'], $tenant);
            }

            // 6. Guardar template aplicado en settings
            $settings = $tenant->settings ?? [];
            $settings['applied_template'] = $template['id'];
            $settings['template_version'] = $template['version'] ?? '1.0.0';
            $settings['onboarding_completed'] = false;

            $tenant->update(['settings' => $settings]);

            DB::commit();

            event(new \App\Core\Template\Events\TemplateApplied($tenant, $template));

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Aplicar configuración del template.
     */
    protected function applyConfiguration(array $config, Tenant $tenant): void
    {
        $tenant->run(function () use ($config) {
            foreach ($config as $moduleSlug => $moduleConfig) {
                // Guardar configuración en tabla module_settings
                DB::table('module_settings')->updateOrInsert(
                    ['module' => $moduleSlug],
                    ['config' => json_encode($moduleConfig), 'updated_at' => now()]
                );
            }
        });
    }

    /**
     * Crear datos de ejemplo.
     */
    protected function createSampleData(array $data, Tenant $tenant): void
    {
        $tenant->run(function () use ($data) {
            // Crear categorías
            if (isset($data['categories'])) {
                foreach ($data['categories'] as $category) {
                    \App\Modules\Inventory\Models\Category::create($category);
                }
            }

            // Crear productos
            if (isset($data['products'])) {
                foreach ($data['products'] as $product) {
                    $category = null;
                    if (isset($product['category'])) {
                        $category = \App\Modules\Inventory\Models\Category::where('name', $product['category'])->first();
                        $product['category_id'] = $category?->id;
                        unset($product['category']);
                    }

                    \App\Modules\Inventory\Models\Product::create($product);
                }
            }

            // Crear clientes
            if (isset($data['customers'])) {
                foreach ($data['customers'] as $customer) {
                    \App\Modules\CRM\Models\Customer::create($customer);
                }
            }

            // Crear mesas (para restaurantes)
            if (isset($data['tables'])) {
                foreach ($data['tables'] as $table) {
                    DB::table('restaurant_tables')->insert($table);
                }
            }
        });
    }

    /**
     * Configurar widgets del dashboard.
     */
    protected function configureDashboard(array $widgets, Tenant $tenant): void
    {
        $tenant->run(function () use ($widgets) {
            foreach ($widgets as $widget) {
                DB::table('dashboard_widgets')->insert([
                    'widget_id' => $widget['id'],
                    'name' => $widget['name'],
                    'component' => $widget['component'],
                    'position' => json_encode($widget['position']),
                    'size' => json_encode($widget['size']),
                    'config' => json_encode($widget['config'] ?? []),
                    'refresh_interval' => $widget['refresh_interval'] ?? null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Verificar si un tenant completó el onboarding.
     */
    public function hasCompletedOnboarding(Tenant $tenant): bool
    {
        return $tenant->settings['onboarding_completed'] ?? false;
    }

    /**
     * Marcar onboarding como completado.
     */
    public function completeOnboarding(Tenant $tenant): void
    {
        $settings = $tenant->settings ?? [];
        $settings['onboarding_completed'] = true;
        $settings['onboarding_completed_at'] = now()->toIso8601String();

        $tenant->update(['settings' => $settings]);
    }
}
```

---

*Continúa en PARTE 4 con Componentes Livewire y Base de Datos...*
