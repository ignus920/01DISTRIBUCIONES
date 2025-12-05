# DOCUMENTACIÓN COMPLETA - SISTEMA ERP MULTI-TENANT

**Proyecto:** Sistema ERP Multi-Tenancy RAP
**Versión:** 1.0
**Fecha:** Octubre 2025
**Stack:** Laravel 12 + Livewire 3 + MySQL 8 + Stancl Tenancy

---

## TABLA DE CONTENIDOS

1. [Visión General del Sistema](#1-visión-general-del-sistema)
2. [Arquitectura del Sistema](#2-arquitectura-del-sistema)
3. [Estructura Completa de Archivos](#3-estructura-completa-de-archivos)
4. [Sistema de Multi-Tenancy](#4-sistema-de-multi-tenancy)
5. [Sistema de Plugins](#5-sistema-de-plugins)
6. [Sistema de Templates de Negocio](#6-sistema-de-templates-de-negocio)
7. [Módulos del Sistema](#7-módulos-del-sistema)
8. [Base de Datos](#8-base-de-datos)
9. [Componentes Livewire](#9-componentes-livewire)
10. [Cronograma de Desarrollo](#10-cronograma-de-desarrollo)
11. [Estado Actual del Proyecto](#11-estado-actual-del-proyecto)

---

## 1. VISIÓN GENERAL DEL SISTEMA

### 1.1 Descripción del Proyecto

Sistema ERP multi-tenant diseñado para ofrecer soluciones de gestión empresarial a PyMEs de diferentes sectores (retail, restaurantes, servicios, vehículos, etc.) mediante una arquitectura modular y escalable.

### 1.2 Características Principales

- **Multi-Tenancy**: Cada empresa tiene su propia base de datos aislada
- **Arquitectura Modular**: Módulos independientes que se pueden activar/desactivar
- **Sistema de Plugins**: Conectores externos enchufables (facturación electrónica, pasarelas de pago, etc.)
- **Templates de Negocio**: Configuraciones pre-diseñadas por tipo de negocio
- **Livewire 3**: Interfaz reactiva sin JavaScript pesado
- **Seguridad**: Autenticación 2FA, roles y permisos por Spatie

### 1.3 Alcance del MVP

**POS Básico** (10 semanas):
- Multi-tenancy funcional
- Gestión de productos e inventario
- Punto de venta táctil
- Clientes básicos
- Caja (apertura/cierre)
- Facturación simple
- Reportes básicos

**POS Institucional** (14-16 semanas):
- Todo lo anterior +
- Multi-sucursales
- Multi-bodegas
- Remisiones y cotizaciones
- Compras y proveedores
- Facturación electrónica
- Reportes avanzados

### 1.4 Tipos de Negocio Soportados

1. **POS Básico**: Tiendas pequeñas, mini-markets
2. **POS Institucional**: Distribuidoras, empresas medianas
3. **Restaurante**: Restaurantes, cafeterías, bares
4. **Vehicular**: Talleres, concesionarios
5. **Ventas TAT**: Venta por catálogo
6. **Servicios a Domicilio**: Delivery, servicios técnicos
7. **Producción**: Manufactura, transformación
8. **Transportes**: Logística, fletes

---

## 2. ARQUITECTURA DEL SISTEMA

### 2.1 Diagrama de Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                     │
├─────────────────────────────────────────────────────────────┤
│  Web App (Livewire)  │  Admin Panel  │  Mobile (futuro)    │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   API GATEWAY / ROUTER                      │
├─────────────────────────────────────────────────────────────┤
│  - Autenticación (Laravel Sanctum)                          │
│  - Resolución de Tenants                                    │
│  - Rate Limiting                                            │
│  - Middleware Stack                                         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    CAPA DE APLICACIÓN                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   CORE ERP   │  │   MÓDULOS    │  │   PLUGINS    │     │
│  ├──────────────┤  ├──────────────┤  ├──────────────┤     │
│  │ - Tenancy    │  │ - POS        │  │ - Fact. Elec.│     │
│  │ - Auth       │  │ - Inventario │  │ - Pagos      │     │
│  │ - Usuarios   │  │ - Ventas     │  │ - WhatsApp   │     │
│  │ - Settings   │  │ - Compras    │  │ - Email      │     │
│  │ - Templates  │  │ - CRM        │  │ - Reportes   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                  CAPA DE SERVICIOS COMPARTIDOS              │
├─────────────────────────────────────────────────────────────┤
│  - TenantManager     - PluginManager                        │
│  - ModuleManager     - TemplateManager                      │
│  - NotificationService - FileStorageService                 │
│  - TaxCalculator     - ReportGenerator                      │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     CAPA DE DATOS                           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐         ┌──────────────────────────┐  │
│  │  MASTER DB      │         │   TENANT DATABASES       │  │
│  │  (rap)          │         │   (tenant_uuid)          │  │
│  ├─────────────────┤         ├──────────────────────────┤  │
│  │ - users         │         │ - products               │  │
│  │ - tenants       │         │ - sales                  │  │
│  │ - user_tenants  │         │ - customers              │  │
│  │ - subscriptions │         │ - invoices               │  │
│  │ - modules       │         │ - inventory              │  │
│  │ - plugins       │         │ - cash_registers         │  │
│  └─────────────────┘         │ - permissions            │  │
│                              │ - [más tablas por módulo]│  │
│                              └──────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    INFRAESTRUCTURA                          │
├─────────────────────────────────────────────────────────────┤
│  - MySQL 8.0         - Redis (cache/sessions)               │
│  - Queue Workers     - Storage (local/S3)                   │
│  - Email (SMTP)      - Backups automáticos                  │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Flujo de Autenticación y Tenancy

```
Usuario → Login → 2FA (opcional) → Selección Tenant → Dashboard Tenant
   │         │            │                │                    │
   │         │            │                │                    │
   ├─ BD: users          │                │                    │
   │         └─ Genera código 2FA         │                    │
   │                      │                │                    │
   │                      └─ Valida código │                    │
   │                                       │                    │
   │                      ┌────────────────┘                    │
   │                      │                                     │
   │              BD: user_tenants                              │
   │              (obtiene tenants del usuario)                 │
   │                      │                                     │
   │                      └─ Establece tenant_id en sesión      │
   │                                       │                    │
   │                                       └─ Middleware: SetTenantConnection
   │                                                            │
   │                                          ┌─────────────────┘
   │                                          │
   │                              Config DB tenant: tenant_uuid
   │                                          │
   └──────────────────────────────────────────┴─ Acceso a BD del tenant
```

### 2.3 Decisión Arquitectónica: Monolito Modular vs Microservicios

**Decisión: MONOLITO MODULAR**

**Justificación:**

| Criterio | Monolito Modular | Microservicios |
|----------|------------------|----------------|
| Complejidad inicial | ⭐ Baja | ⭐⭐⭐⭐⭐ Muy Alta |
| Time to market | ⭐⭐⭐⭐⭐ 6-10 semanas | ⭐⭐ 16-24 semanas |
| Costo infraestructura | $40-400/mes | $2000+/mes |
| Mantenibilidad | ⭐⭐⭐⭐ Alta | ⭐⭐ Media |
| Escalabilidad | ⭐⭐⭐ Suficiente para 1000+ empresas | ⭐⭐⭐⭐⭐ Ilimitada |
| Debugging | ⭐⭐⭐⭐⭐ Fácil | ⭐⭐ Complejo |
| Transacciones | ⭐⭐⭐⭐⭐ ACID garantizado | ⭐⭐ Eventual consistency |

**Estrategia de Escalamiento:**

1. **Fase 1** (0-100 empresas): 1 servidor monolítico
2. **Fase 2** (100-500 empresas): Multi-instancia con load balancer
3. **Fase 3** (500-1000 empresas): Sharding por tenant_id
4. **Fase 4** (1000+ empresas): Extracción selectiva de microservicios (solo módulos críticos)

---

## 3. ESTRUCTURA COMPLETA DE ARCHIVOS

### 3.1 Estructura Raíz del Proyecto

```
erp-sistema/
├── app/                           # Código de la aplicación
├── bootstrap/                     # Archivos de arranque
├── config/                        # Configuración
├── database/                      # Migraciones, seeders, factories
├── public/                        # Assets públicos
├── resources/                     # Vistas, JS, CSS
├── routes/                        # Definición de rutas
├── storage/                       # Archivos generados
├── tests/                         # Tests automatizados
├── vendor/                        # Dependencias Composer
├── .env                          # Variables de entorno
├── composer.json                 # Dependencias PHP
├── package.json                  # Dependencias NPM
├── artisan                       # CLI de Laravel
├── phpunit.xml                   # Configuración tests
├── tailwind.config.js            # Configuración Tailwind
├── vite.config.js                # Configuración Vite
└── README.md                     # Documentación
```

### 3.2 Estructura Detallada de /app

```
app/
├── Console/
│   ├── Commands/
│   │   ├── Tenant/
│   │   │   ├── TenantCreateCommand.php
│   │   │   ├── TenantMigrateCommand.php
│   │   │   ├── TenantSeedCommand.php
│   │   │   ├── TenantDeleteCommand.php
│   │   │   └── TenantListCommand.php
│   │   │
│   │   ├── Module/
│   │   │   ├── ModuleEnableCommand.php
│   │   │   ├── ModuleDisableCommand.php
│   │   │   ├── ModuleMakeCommand.php
│   │   │   └── ModuleListCommand.php
│   │   │
│   │   ├── Plugin/
│   │   │   ├── PluginInstallCommand.php
│   │   │   ├── PluginUninstallCommand.php
│   │   │   ├── PluginListCommand.php
│   │   │   └── PluginPublishCommand.php
│   │   │
│   │   └── Maintenance/
│   │       ├── BackupCommand.php
│   │       ├── CleanupCommand.php
│   │       └── HealthCheckCommand.php
│   │
│   └── Kernel.php
│
├── Core/                          # 🔥 NÚCLEO DEL SISTEMA
│   │
│   ├── Tenant/
│   │   ├── TenantManager.php          # Gestor principal de tenants
│   │   ├── TenantResolver.php         # Resuelve tenant actual
│   │   ├── TenantMiddleware.php       # Middleware de tenancy
│   │   ├── TenantScope.php            # Global scope para queries
│   │   ├── Traits/
│   │   │   ├── BelongsToTenant.php
│   │   │   └── HasTenantConnection.php
│   │   └── Events/
│   │       ├── TenantCreated.php
│   │       ├── TenantDeleted.php
│   │       └── TenantSwitched.php
│   │
│   ├── Module/                    # 🔥 SISTEMA DE MÓDULOS
│   │   ├── ModuleManager.php          # Gestor de módulos
│   │   ├── ModuleServiceProvider.php  # Provider base
│   │   ├── ModuleMiddleware.php       # Verifica acceso a módulos
│   │   ├── ModuleInterface.php        # Contrato de módulos
│   │   ├── ModuleRepository.php
│   │   └── Exceptions/
│   │       ├── ModuleNotFoundException.php
│   │       └── ModuleNotEnabledException.php
│   │
│   ├── Plugin/                    # 🔥 SISTEMA DE PLUGINS
│   │   ├── PluginManager.php          # Gestor de plugins
│   │   ├── PluginRepository.php       # Repositorio de plugins
│   │   ├── PluginInstaller.php        # Instalador de plugins
│   │   ├── PluginLoader.php           # Cargador dinámico
│   │   ├── PluginDiscovery.php        # Descubrimiento de plugins
│   │   ├── Contracts/
│   │   │   ├── PluginInterface.php
│   │   │   ├── ConnectorInterface.php
│   │   │   ├── HookInterface.php
│   │   │   └── ConfigurableInterface.php
│   │   ├── Events/
│   │   │   ├── PluginInstalled.php
│   │   │   ├── PluginUninstalled.php
│   │   │   ├── PluginActivated.php
│   │   │   └── ConnectorActivated.php
│   │   └── Exceptions/
│   │       ├── PluginNotFoundException.php
│   │       ├── PluginNotInstalledException.php
│   │       ├── ConnectorTestFailedException.php
│   │       └── InvalidPluginException.php
│   │
│   ├── Template/                  # 🔥 SISTEMA DE TEMPLATES
│   │   ├── TemplateManager.php        # Gestor de templates
│   │   ├── TemplateApplicator.php     # Aplica templates
│   │   ├── TemplateValidator.php      # Valida templates
│   │   ├── BusinessTemplates/
│   │   │   ├── POSBasicoTemplate.php
│   │   │   ├── POSInstitucionalTemplate.php
│   │   │   ├── RestauranteTemplate.php
│   │   │   ├── VehicularTemplate.php
│   │   │   └── BaseTemplate.php
│   │   └── TemplateInterface.php
│   │
│   ├── Auth/
│   │   ├── MultiTenantGuard.php
│   │   ├── TwoFactorService.php
│   │   ├── Traits/
│   │   │   ├── HasTenant.php
│   │   │   ├── HasModuleAccess.php
│   │   │   └── HasTwoFactor.php
│   │   └── Controllers/
│   │       ├── LoginController.php
│   │       ├── TenantSelectionController.php
│   │       └── TwoFactorController.php
│   │
│   ├── Shared/                    # SERVICIOS COMPARTIDOS
│   │   ├── Services/
│   │   │   ├── FileStorageService.php
│   │   │   ├── NotificationService.php
│   │   │   ├── ReportGeneratorService.php
│   │   │   ├── TaxCalculatorService.php
│   │   │   ├── PDFGeneratorService.php
│   │   │   ├── EmailService.php
│   │   │   └── WhatsAppService.php
│   │   │
│   │   ├── Traits/
│   │   │   ├── HasUuid.php
│   │   │   ├── Auditable.php
│   │   │   ├── SoftDeletesWithUser.php
│   │   │   ├── HasSettings.php
│   │   │   └── Searchable.php
│   │   │
│   │   └── Helpers/
│   │       ├── MoneyHelper.php
│   │       ├── DateHelper.php
│   │       ├── TaxHelper.php
│   │       ├── NumberHelper.php
│   │       └── StringHelper.php
│   │
│   └── Base/                      # CLASES BASE
│       ├── BaseController.php
│       ├── BaseModel.php
│       ├── BaseService.php
│       ├── BaseRepository.php
│       ├── BaseRequest.php
│       └── BaseResource.php
│
├── Modules/                       # 🔥 MÓDULOS DEL ERP
│   │
│   ├── Dashboard/
│   │   ├── Controllers/
│   │   │   └── DashboardController.php
│   │   ├── Livewire/
│   │   │   ├── DashboardStats.php
│   │   │   ├── RecentSales.php
│   │   │   └── LowStockAlert.php
│   │   ├── views/
│   │   │   └── dashboard.blade.php
│   │   └── ModuleServiceProvider.php
│   │
│   ├── POS/                       # 🔥 PUNTO DE VENTA
│   │   ├── Controllers/
│   │   │   ├── SaleController.php
│   │   │   ├── CashRegisterController.php
│   │   │   ├── QuickSaleController.php
│   │   │   └── RefundController.php
│   │   │
│   │   ├── Models/
│   │   │   ├── Sale.php
│   │   │   ├── SaleDetail.php
│   │   │   ├── CashRegister.php
│   │   │   ├── CashMovement.php
│   │   │   └── Quotation.php
│   │   │
│   │   ├── Services/
│   │   │   ├── SaleService.php
│   │   │   ├── PaymentProcessor.php
│   │   │   ├── ReceiptGenerator.php
│   │   │   ├── QuotationService.php
│   │   │   └── CashRegisterService.php
│   │   │
│   │   ├── Repositories/
│   │   │   ├── SaleRepository.php
│   │   │   └── CashRegisterRepository.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── CreateSaleRequest.php
│   │   │   ├── CreateQuotationRequest.php
│   │   │   └── RefundRequest.php
│   │   │
│   │   ├── Resources/
│   │   │   ├── SaleResource.php
│   │   │   └── SaleCollection.php
│   │   │
│   │   ├── Livewire/
│   │   │   ├── POSScreen.php              # Pantalla principal POS
│   │   │   ├── ProductSelector.php        # Selector de productos táctil
│   │   │   ├── ShoppingCart.php           # Carrito de compra
│   │   │   ├── QuotationBuilder.php       # Constructor de cotizaciones
│   │   │   ├── MultiPaymentForm.php       # Formulario multi-pago
│   │   │   ├── CashRegisterManagement.php # Gestión de caja
│   │   │   └── SalesList.php              # Lista de ventas
│   │   │
│   │   ├── Events/
│   │   │   ├── SaleCompleted.php
│   │   │   ├── SaleCancelled.php
│   │   │   ├── CashRegisterOpened.php
│   │   │   └── CashRegisterClosed.php
│   │   │
│   │   ├── Listeners/
│   │   │   ├── UpdateInventoryOnSale.php
│   │   │   ├── GenerateAccountingEntry.php
│   │   │   └── NotifyLowStock.php
│   │   │
│   │   ├── routes/
│   │   │   ├── web.php
│   │   │   └── api.php
│   │   │
│   │   ├── views/
│   │   │   ├── pos-screen.blade.php
│   │   │   ├── cash-register/
│   │   │   │   ├── index.blade.php
│   │   │   │   ├── open.blade.php
│   │   │   │   └── close.blade.php
│   │   │   └── components/
│   │   │       ├── product-card.blade.php
│   │   │       └── cart-item.blade.php
│   │   │
│   │   ├── config/
│   │   │   └── pos.php
│   │   │
│   │   └── ModuleServiceProvider.php
│   │
│   ├── Inventory/                 # 🔥 INVENTARIO
│   │   ├── Controllers/
│   │   │   ├── ProductController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── BrandController.php
│   │   │   ├── WarehouseController.php
│   │   │   ├── StockMovementController.php
│   │   │   ├── TransferController.php
│   │   │   └── InventoryCountController.php
│   │   │
│   │   ├── Models/
│   │   │   ├── Product.php
│   │   │   ├── Category.php
│   │   │   ├── Brand.php
│   │   │   ├── Warehouse.php
│   │   │   ├── StockMovement.php
│   │   │   ├── ProductWarehouse.php      # Stock por bodega
│   │   │   ├── Transfer.php
│   │   │   ├── TransferDetail.php
│   │   │   └── InventoryCount.php
│   │   │
│   │   ├── Services/
│   │   │   ├── StockService.php
│   │   │   ├── InventoryValuationService.php
│   │   │   ├── ReorderService.php
│   │   │   ├── TransferService.php
│   │   │   └── InventoryCountService.php
│   │   │
│   │   ├── Livewire/
│   │   │   ├── ProductList.php
│   │   │   ├── ProductCreate.php
│   │   │   ├── ProductEdit.php
│   │   │   ├── StockMovements.php
│   │   │   ├── TransferManagement.php
│   │   │   ├── WarehouseManagement.php
│   │   │   └── InventoryCountForm.php
│   │   │
│   │   ├── Events/
│   │   │   ├── StockUpdated.php
│   │   │   ├── LowStockDetected.php
│   │   │   └── TransferCompleted.php
│   │   │
│   │   ├── routes/
│   │   │   ├── web.php
│   │   │   └── api.php
│   │   │
│   │   ├── views/
│   │   ├── config/
│   │   │   └── inventory.php
│   │   │
│   │   └── ModuleServiceProvider.php
│   │
│   ├── Billing/                   # 🔥 FACTURACIÓN
│   │   ├── Controllers/
│   │   │   ├── InvoiceController.php
│   │   │   ├── CreditNoteController.php
│   │   │   ├── DebitNoteController.php
│   │   │   └── ElectronicBillingController.php
│   │   │
│   │   ├── Models/
│   │   │   ├── Invoice.php
│   │   │   ├── InvoiceDetail.php
│   │   │   ├── CreditNote.php
│   │   │   ├── DebitNote.php
│   │   │   └── TaxDocument.php
│   │   │
│   │   ├── Services/
│   │   │   ├── InvoiceService.php
│   │   │   ├── ElectronicInvoiceService.php
│   │   │   ├── TaxService.php
│   │   │   ├── PDFGeneratorService.php
│   │   │   └── NumerationService.php
│   │   │
│   │   ├── Livewire/
│   │   │   ├── InvoiceCreate.php
│   │   │   ├── InvoiceList.php
│   │   │   ├── InvoicePreview.php
│   │   │   ├── CreditNoteForm.php
│   │   │   └── BillingSettings.php
│   │   │
│   │   ├── routes/
│   │   ├── views/
│   │   ├── config/
│   │   │   └── billing.php
│   │   │
│   │   └── ModuleServiceProvider.php
│   │
│   ├── CRM/                       # 🔥 GESTIÓN DE CLIENTES
│   │   ├── Controllers/
│   │   │   ├── CustomerController.php
│   │   │   ├── SupplierController.php
│   │   │   └── ContactController.php
│   │   │
│   │   ├── Models/
│   │   │   ├── Customer.php
│   │   │   ├── Supplier.php
│   │   │   ├── Contact.php
│   │   │   └── CustomerNote.php
│   │   │
│   │   ├── Services/
│   │   │   ├── CustomerService.php
│   │   │   └── SupplierService.php
│   │   │
│   │   ├── Livewire/
│   │   │   ├── CustomerList.php
│   │   │   ├── CustomerCreate.php
│   │   │   ├── CustomerQuickCreate.php   # Creación rápida
│   │   │   ├── CustomerSelector.php      # Para POS
│   │   │   └── SupplierManagement.php
│   │   │
│   │   ├── routes/
│   │   ├── views/
│   │   └── ModuleServiceProvider.php
│   │
│   ├── Purchasing/                # 🔥 COMPRAS
│   │   ├── Controllers/
│   │   │   ├── PurchaseOrderController.php
│   │   │   ├── PurchaseController.php
│   │   │   └── RequisitionController.php
│   │   │
│   │   ├── Models/
│   │   │   ├── PurchaseOrder.php
│   │   │   ├── PurchaseOrderDetail.php
│   │   │   ├── Purchase.php
│   │   │   ├── PurchaseDetail.php
│   │   │   └── Requisition.php
│   │   │
│   │   ├── Services/
│   │   │   ├── PurchaseService.php
│   │   │   └── RequisitionService.php
│   │   │
│   │   ├── Livewire/
│   │   ├── routes/
│   │   ├── views/
│   │   └── ModuleServiceProvider.php
│   │
│   ├── Accounting/                # 🔥 CONTABILIDAD
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Livewire/
│   │   ├── routes/
│   │   ├── views/
│   │   └── ModuleServiceProvider.php
│   │
│   ├── Reports/                   # 🔥 REPORTES
│   │   ├── Controllers/
│   │   │   └── ReportController.php
│   │   │
│   │   ├── Services/
│   │   │   ├── SalesReportService.php
│   │   │   ├── InventoryReportService.php
│   │   │   ├── FinancialReportService.php
│   │   │   └── CustomReportService.php
│   │   │
│   │   ├── Exports/               # Laravel Excel
│   │   │   ├── SalesExport.php
│   │   │   ├── ProductsExport.php
│   │   │   └── CustomersExport.php
│   │   │
│   │   ├── Livewire/
│   │   │   ├── SalesReport.php
│   │   │   ├── InventoryReport.php
│   │   │   ├── CashFlowReport.php
│   │   │   └── CustomReportBuilder.php
│   │   │
│   │   ├── routes/
│   │   ├── views/
│   │   └── ModuleServiceProvider.php
│   │
│   └── [Módulos Opcionales]/
│       ├── Restaurant/            # Extensión para restaurantes
│       ├── Vehicle/               # Gestión vehicular
│       ├── Production/            # Producción/manufactura
│       └── Transport/             # Transportes/logística
│
├── Plugins/                       # 🔥 PLUGINS EXTERNOS
│   │
│   ├── BillingElectronic/         # Plugin Facturación Electrónica
│   │   ├── plugin.json            # ⭐ Metadata del plugin
│   │   │
│   │   ├── Connectors/            # Conectores por país
│   │   │   ├── DIANColombia/
│   │   │   │   ├── DIANConnector.php
│   │   │   │   ├── DIANSoapClient.php
│   │   │   │   ├── XMLGenerator.php
│   │   │   │   ├── XMLSigner.php
│   │   │   │   ├── config.php
│   │   │   │   └── views/
│   │   │   │       └── credentials-form.blade.php
│   │   │   │
│   │   │   ├── SUNATPeru/
│   │   │   │   ├── SUNATConnector.php
│   │   │   │   ├── SUNATSoapClient.php
│   │   │   │   └── ...
│   │   │   │
│   │   │   ├── SATMexico/
│   │   │   │   ├── SATConnector.php
│   │   │   │   └── ...
│   │   │   │
│   │   │   └── SRIEcuador/
│   │   │       └── ...
│   │   │
│   │   ├── Services/
│   │   │   ├── ElectronicInvoiceService.php
│   │   │   ├── XMLGeneratorService.php
│   │   │   └── CertificateManager.php
│   │   │
│   │   ├── Controllers/
│   │   │   ├── ConfigurationController.php
│   │   │   └── TestConnectionController.php
│   │   │
│   │   ├── Models/
│   │   │   └── ElectronicInvoiceLog.php
│   │   │
│   │   ├── Hooks/
│   │   │   ├── SendToGovernment.php
│   │   │   └── CancelInGovernment.php
│   │   │
│   │   ├── database/
│   │   │   └── migrations/
│   │   │       └── create_electronic_invoice_logs_table.php
│   │   │
│   │   ├── routes/
│   │   │   └── plugin.php
│   │   │
│   │   ├── views/
│   │   │   └── settings/
│   │   │       ├── connection-wizard.blade.php
│   │   │       └── dashboard.blade.php
│   │   │
│   │   ├── config/
│   │   │   └── billing-electronic.php
│   │   │
│   │   └── PluginServiceProvider.php
│   │
│   ├── PaymentGateways/           # Plugin Pasarelas de Pago
│   │   ├── plugin.json
│   │   │
│   │   ├── Connectors/
│   │   │   ├── Stripe/
│   │   │   │   ├── StripeConnector.php
│   │   │   │   ├── StripeWebhookHandler.php
│   │   │   │   └── config.php
│   │   │   │
│   │   │   ├── PayU/
│   │   │   ├── Mercadopago/
│   │   │   ├── Wompi/
│   │   │   └── Nequi/
│   │   │
│   │   ├── Services/
│   │   │   ├── PaymentProcessorService.php
│   │   │   └── WebhookService.php
│   │   │
│   │   ├── routes/
│   │   ├── views/
│   │   └── PluginServiceProvider.php
│   │
│   ├── WhatsAppIntegration/       # Plugin WhatsApp
│   │   ├── plugin.json
│   │   │
│   │   ├── Connectors/
│   │   │   ├── TwilioConnector.php
│   │   │   ├── MetaAPIConnector.php
│   │   │   └── WaAPIConnector.php
│   │   │
│   │   ├── Services/
│   │   │   ├── WhatsAppService.php
│   │   │   └── MessageTemplateService.php
│   │   │
│   │   └── PluginServiceProvider.php
│   │
│   ├── AccountingPremium/         # Contabilidad avanzada
│   ├── POSRestaurant/             # Extensión POS restaurante
│   ├── VehicleManagement/         # Gestión vehicular
│   └── DeliveryManagement/        # Gestión domicilios
│
├── Templates/                     # 🔥 DEFINICIONES DE TEMPLATES
│   ├── pos_basico.json
│   ├── pos_institucional.json
│   ├── restaurante.json
│   ├── vehicular.json
│   ├── ventas_tat.json
│   ├── servicios_domicilio.json
│   ├── produccion.json
│   └── transportes.json
│
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Admin/
│   │   └── API/
│   │
│   ├── Middleware/
│   │   ├── SetTenantConnection.php
│   │   ├── ModuleAccessMiddleware.php
│   │   ├── CheckSubscriptionMiddleware.php
│   │   ├── TwoFactorMiddleware.php
│   │   └── LocalizationMiddleware.php
│   │
│   └── Kernel.php
│
├── Models/                        # Modelos del Sistema Central
│   ├── User.php
│   ├── Tenant.php
│   ├── UserTenant.php
│   ├── TwoFactorCode.php
│   ├── Subscription.php
│   ├── Module.php
│   ├── TenantModule.php
│   ├── Plugin.php
│   └── TenantPlugin.php
│
├── Livewire/                      # Componentes Livewire Globales
│   ├── Auth/
│   │   ├── Login.php
│   │   ├── Register.php
│   │   ├── Verify2FA.php
│   │   ├── SelectTenant.php
│   │   └── Enable2FA.php
│   │
│   └── Admin/
│       ├── TenantManagement.php
│       ├── UserManagement.php
│       └── PluginMarketplace.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── ModuleServiceProvider.php
│   ├── PluginServiceProvider.php
│   ├── TenantServiceProvider.php
│   └── EventServiceProvider.php
│
└── View/
    └── Components/
        ├── AppLayout.php
        ├── GuestLayout.php
        └── TenantLayout.php
```

---

*Continúa en el siguiente archivo debido a la extensión...*