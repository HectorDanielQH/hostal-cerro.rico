<?php

use ColorlibHQ\AdminLte\Menu\Filters\ActiveFilter;
use ColorlibHQ\AdminLte\Menu\Filters\GateFilter;
use ColorlibHQ\AdminLte\Menu\Filters\HrefFilter;
use ColorlibHQ\AdminLte\Menu\Filters\SearchFilter;

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | The default page title, and an optional prefix/postfix applied to every
    | page title set with @section('title', ...).
    |
    */

    'title' => 'Hostal Cerro Rico',
    'title_prefix' => '.::',
    'title_postfix' => '::.',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */

    'use_ico_only' => true,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | AdminLTE 4 uses Source Sans 3. Set to false to self-host or skip.
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | The brand logo shown in the sidebar. `logo` accepts HTML and is
    | rendered UNESCAPED ({!! !!}) — only ever put trusted, hardcoded
    | markup here, never user-supplied or database-driven content.
    |
    */

    'logo' => '<b>Hostal</b> Cerro Rico',
    'logo_img' => 'storage/images/logo.png',
    'logo_img_class' => 'brand-image opacity-75 shadow',
    'logo_img_alt' => 'AdminLTE Logo',

    /*
    |--------------------------------------------------------------------------
    | Authentication logo
    |--------------------------------------------------------------------------
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User menu (topbar dropdown)
    |--------------------------------------------------------------------------
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Body-level layout switches. These map directly to AdminLTE 4 body classes.
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => true,   // .layout-fixed
    'layout_fixed_navbar' => true,    // .fixed-header
    'layout_fixed_footer' => null,    // .fixed-footer
    'layout_dark_mode' => null,       // null = respect system / user toggle
    'layout_rtl' => false,            // Enable right-to-left layout

    /*
    |--------------------------------------------------------------------------
    | Footer & Preloader
    |--------------------------------------------------------------------------
    |
    | `footer_left` / `footer_right` accept HTML and are rendered UNESCAPED
    | ({!! !!}) — only ever put trusted, hardcoded markup here, never
    | user-supplied or database-driven content.
    |
    */

    'footer_left' => '
        <span class="text-muted">
            © ' . date('Y') . ' 
            <strong>Hostal Cerro Rico</strong> · Potosí, Bolivia
        </span>
    ',
    'footer_right' => '
        <span class="text-muted">
            Sistema de Gestión
        </span>
    ',
    'preloader' => false,
    'control_sidebar' => false,
    'control_sidebar_theme' => 'dark',

    // Documentation URL used by the navbar "Documentation" link and the sidebar
    // "View documentation" CTA (false to hide the CTA). Defaults to the in-app
    // docs viewer served at /docs (see the `docs` keys below).
    
    'sidebar_docs_url' => false,

    // Bundled demo/showcase pages (Dashboard v2/v3, Widgets, UI, Forms, Tables,
    // Layout Options, Theme Generate, auth variants, error pages). Set false to
    // skip registering their routes in production.
    'demo' => false,
    'demo_middleware' => ['web', 'auth'],

    // In-app documentation viewer: renders this package's docs/*.md files at
    // /docs and /docs/{page}. Set 'docs' => false to disable the route.
    'docs' => false,
    'docs_middleware' => ['web'],

    'sidebar_breakpoint' => 'lg',     // sidebar-expand-{breakpoint}
    'sidebar_mini' => true,           // .sidebar-mini
    'sidebar_collapse' => false,      // start collapsed
    'sidebar_collapse_auto_size' => false,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'leave',

    /*
    |--------------------------------------------------------------------------
    | Color theme
    |--------------------------------------------------------------------------
    |
    | The sidebar uses data-bs-theme="dark" by default (dark sidebar on a
    | light page, matching the AdminLTE 4 demos). Set to 'light' for a light
    | sidebar.
    |
    */

    'sidebar_theme' => 'light',  // 'dark' | 'light'

    /*
    |--------------------------------------------------------------------------
    | Custom body / element classes
    |--------------------------------------------------------------------------
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => 'fw-light',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'bg-body-secondary shadow',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-expand bg-body',
    'classes_topnav_nav' => 'navbar',
    'classes_topnav_container' => 'container-fluid',

    /*
    |--------------------------------------------------------------------------
    | Color mode toggle
    |--------------------------------------------------------------------------
    |
    | Shows the Light/Dark/Auto dropdown in the topbar (AdminLTE 4 feature).
    |
    */

    'color_mode_toggle' => true,

    /*
    |--------------------------------------------------------------------------
    | Menu
    |--------------------------------------------------------------------------
    |
    | The sidebar (and optional top-nav) menu. Each item is an array. Supported
    | keys:
    |
    |   'header'      => 'SECTION LABEL'            // a section header
    |   'text'        => 'Dashboard'               // link label (required for links)
    |   'route'       => 'dashboard'               // named route  -> url
    |   'url'         => 'admin/users'             // raw url (relative or absolute)
    |   'icon'        => 'bi bi-speedometer'       // Bootstrap Icons class
    |   'icon_color'  => 'primary'                 // optional text-{color}
    |   'label'       => 5                         // badge value
    |   'label_color' => 'primary'                 // badge color
    |   'active'      => ['admin/users*']          // url patterns that mark active
    |   'target'      => '_blank'                  // anchor target
    |   'can'         => 'view-users'              // gate/permission to show item
    |   'submenu'     => [ ...child items... ]     // nested items (treeview)
    |
    */

    'menu' => [
        // ---- Sidebar: mirrors the AdminLTE 4 demo sidebar ----
        /*[
            'text' => 'Dashboard',
            'icon' => 'bi bi-speedometer',
            'submenu' => [
                ['text' => 'Dashboard v1', 'url' => '/', 'icon' => 'bi bi-circle'],
                ['text' => 'Dashboard v2', 'url' => 'demo/dashboard-v2', 'icon' => 'bi bi-circle'],
                ['text' => 'Dashboard v3', 'url' => 'demo/dashboard-v3', 'icon' => 'bi bi-circle'],
            ],
        ],
        [
            'text' => 'Theme Generate',
            'url' => 'demo/theme-generator',
            'icon' => 'bi bi-palette',
        ],
        [
            'text' => 'Widgets',
            'icon' => 'bi bi-box-seam-fill',
            'submenu' => [
                ['text' => 'Small Box', 'url' => 'demo/widgets/small-box', 'icon' => 'bi bi-circle'],
                ['text' => 'Info Box', 'url' => 'demo/widgets/info-box', 'icon' => 'bi bi-circle'],
                ['text' => 'Cards', 'url' => 'demo/widgets/cards', 'icon' => 'bi bi-circle'],
            ],
        ],
        [
            'text' => 'Layout Options',
            'url' => 'demo/layout-options',
            'icon' => 'bi bi-clipboard-fill',
        ],
        [
            'text' => 'UI Elements',
            'icon' => 'bi bi-tree-fill',
            'submenu' => [
                ['text' => 'General', 'url' => 'demo/ui/general', 'icon' => 'bi bi-circle'],
                ['text' => 'Icons', 'url' => 'demo/ui/icons', 'icon' => 'bi bi-circle'],
                ['text' => 'Timeline', 'url' => 'demo/ui/timeline', 'icon' => 'bi bi-circle'],
            ],
        ],
        [
            'text' => 'Mailbox',
            'icon' => 'bi bi-envelope',
            'submenu' => [
                ['text' => 'Inbox', 'url' => 'admin/mailbox', 'icon' => 'bi bi-circle'],
                ['text' => 'Compose', 'url' => 'admin/mailbox/compose', 'icon' => 'bi bi-circle'],
            ],
        ],
        [
            'text' => 'Forms',
            'icon' => 'bi bi-pencil-square',
            'submenu' => [
                ['text' => 'Elements', 'url' => 'demo/forms/elements', 'icon' => 'bi bi-circle'],
                ['text' => 'Layout', 'url' => 'demo/forms/layout', 'icon' => 'bi bi-circle'],
                ['text' => 'Validation', 'url' => 'demo/forms/validation', 'icon' => 'bi bi-circle'],
                ['text' => 'Wizard', 'url' => 'demo/forms/wizard', 'icon' => 'bi bi-circle'],
            ],
        ],
        [
            'text' => 'Tables',
            'icon' => 'bi bi-table',
            'submenu' => [
                ['text' => 'Simple Tables', 'url' => 'demo/tables/simple', 'icon' => 'bi bi-circle'],
                ['text' => 'Data Tables', 'url' => 'demo/tables/data', 'icon' => 'bi bi-circle'],
            ],
        ],

        ['header' => 'PAGES'],
        [
            'text' => 'Pages',
            'icon' => 'bi bi-file-earmark-text',
            'submenu' => [
                ['text' => 'Profile', 'url' => 'admin/profile', 'icon' => 'bi bi-circle'],
                ['text' => 'Settings', 'url' => 'admin/settings', 'icon' => 'bi bi-circle'],
                ['text' => 'Invoice', 'url' => 'admin/invoice', 'icon' => 'bi bi-circle'],
                ['text' => 'Calendar', 'url' => 'admin/calendar', 'icon' => 'bi bi-circle'],
                ['text' => 'Kanban', 'url' => 'admin/kanban', 'icon' => 'bi bi-circle'],
                ['text' => 'Chat', 'url' => 'admin/chat', 'icon' => 'bi bi-circle'],
                ['text' => 'File Manager', 'url' => 'admin/file-manager', 'icon' => 'bi bi-circle'],
                ['text' => 'Projects', 'url' => 'admin/projects', 'icon' => 'bi bi-circle'],
                ['text' => 'Pricing', 'url' => 'admin/pricing', 'icon' => 'bi bi-circle'],
                ['text' => 'FAQ', 'url' => 'admin/faq', 'icon' => 'bi bi-circle'],
                [
                    'text' => 'Error',
                    'icon' => 'bi bi-circle',
                    'submenu' => [
                        ['text' => '404', 'url' => 'demo/errors/404', 'icon' => 'bi bi-circle'],
                        ['text' => '500', 'url' => 'demo/errors/500', 'icon' => 'bi bi-circle'],
                        ['text' => 'Maintenance', 'url' => 'demo/errors/maintenance', 'icon' => 'bi bi-circle'],
                    ],
                ],
            ],
        ],*/
        [
            'header' => 'Inicio del turno',
            'can' => 'view-reception-menu',
        ],
        [
            'text' => 'Dashboard',
            'route' => 'adminlte.dashboard',
            'icon' => 'bi bi-speedometer2',
            'can' => 'dashboard.ver',
        ],
        [
            'text' => 'Recepcion',
            'route' => 'adminlte.front-desk.index',
            'icon' => 'bi bi-reception-4',
            'can' => 'reservas.ver',
        ],
        [
            'header' => 'Preparar la venta',
            'can' => 'view-reception-menu',
        ],
        [
            'text' => 'Tipos de habitacion',
            'route' => 'adminlte.room-types.index',
            'icon' => 'bi bi-house-heart',
            'can' => 'tipos_habitacion.ver',
        ],
        [
            'text' => 'Habitaciones',
            'route' => 'adminlte.rooms.index',
            'icon' => 'bi bi-door-open',
            'can' => 'habitaciones.ver',
        ],
        [
            'text' => 'Promociones',
            'route' => 'adminlte.promotions.index',
            'icon' => 'bi bi-tags',
            'can' => 'promociones.ver',
        ],
        [
            'header' => 'Reservar y hospedar',
            'can' => 'view-reception-menu',
        ],
        [
            'text' => 'Clientes / Huespedes',
            'route' => 'adminlte.customers.index',
            'icon' => 'bi bi-person-lines-fill',
            'can' => 'clientes.ver',
        ],
        [
            'text' => 'Reservas',
            'route' => 'adminlte.reservations.index',
            'icon' => 'bi bi-calendar2-check',
            'can' => 'reservas.ver',
        ],
        [
            'header' => 'Cobrar y cuadrar',
            'can' => 'view-reception-menu',
        ],
        [
            'text' => 'Pagos / Comprobantes',
            'route' => 'adminlte.payments.index',
            'icon' => 'bi bi-receipt',
            'can' => 'pagos.ver',
        ],
        [
            'text' => 'Caja',
            'route' => 'adminlte.cash-registers.index',
            'icon' => 'bi bi-cash-stack',
            'can' => 'caja.ver',
        ],
        [
            'header' => 'Control y cierre',
            'can' => 'view-reports-menu',
        ],
        [
            'text' => 'Reportes',
            'route' => 'adminlte.reports.index',
            'icon' => 'bi bi-graph-up-arrow',
            'can' => 'reportes.ver',
        ],
        [
            'header' => 'Administracion interna',
            'can' => 'view-admin-menu',
        ],
        [
            'text' => 'Turnos de recepcion',
            'route' => 'adminlte.work-shifts.index',
            'icon' => 'bi bi-clock-history',
            'can' => 'usuarios.ver',
        ],
        [
            'text' => 'Usuarios',
            'route' => 'adminlte.users.index',
            'icon' => 'bi bi-people',
            'can' => 'usuarios.ver',
        ],
        [
            'text' => 'Roles y permisos',
            'route' => 'adminlte.roles.index',
            'icon' => 'bi bi-shield-lock',
            'can' => 'roles.ver',
        ],
        [
            'header' => 'Web y configuracion',
            'can' => 'view-settings-menu',
        ],
        [
            'text' => 'Datos del hotel',
            'route' => 'adminlte.settings.edit',
            'icon' => 'bi bi-building',
            'can' => 'configuracion.ver',
        ],
        [
            'text' => 'Anuncios web',
            'route' => 'adminlte.announcements.index',
            'icon' => 'bi bi-megaphone',
            'can' => 'configuracion.ver',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu filters
    |--------------------------------------------------------------------------
    |
    | Filters transform each menu item before rendering. Add your own classes
    | here (must implement ColorlibHQ\AdminLte\Menu\Filters\FilterInterface).
    | The defaults handle gates, active state, hrefs, and search items.
    |
    */

    'filters' => [
        GateFilter::class,
        HrefFilter::class,
        ActiveFilter::class,
        SearchFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins
    |--------------------------------------------------------------------------
    |
    | Optional JavaScript libraries integrated with AdminLTE 4. Disable plugins
    | you don't use to avoid loading unnecessary assets.
    |
    */

    'plugins' => [
        'flatpickr' => [
            'enabled' => false,
            'css' => 'vendor/flatpickr/flatpickr.min.css',
            'js' => 'vendor/flatpickr/flatpickr.min.js',
        ],
        'tom_select' => [
            'enabled' => false,
            'css' => 'vendor/tom-select/tom-select.bootstrap5.min.css',
            'js' => 'vendor/tom-select/tom-select.complete.min.js',
        ],
        'tabulator' => [
            'enabled' => false,
            'css' => 'vendor/tabulator-tables/tabulator.min.css',
            'js' => 'vendor/tabulator-tables/tabulator.min.js',
        ],
        'quill' => [
            'enabled' => false,
            'css' => 'vendor/quill/quill.snow.css',
            'js' => 'vendor/quill/quill.min.js',
        ],
        'apexcharts' => [
            'enabled' => false,
            'js' => 'vendor/apexcharts/apexcharts.min.js',
        ],
        'jsvectormap' => [
            'enabled' => false,
            'css' => 'vendor/jsvectormap/jsvectormap.min.css',
            // The library first, then the world map data (registers the 'world' map).
            'js' => [
                'vendor/jsvectormap/jsvectormap.min.js',
                'vendor/jsvectormap/maps/world.js',
            ],
        ],
        'fullcalendar' => [
            'enabled' => false,
            'css' => 'vendor/fullcalendar/index.global.min.css',
            'js' => 'vendor/fullcalendar/index.global.min.js',
        ],
        'sortablejs' => [
            'enabled' => false,
            'js' => 'vendor/sortablejs/sortablejs.min.js',
        ],
    ],

];
