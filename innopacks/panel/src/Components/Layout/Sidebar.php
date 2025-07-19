<?php
/**
 * Copyright (c) Since 2024 InnoShop - All Rights Reserved
 *
 * @link       https://www.innoshop.com
 * @author     InnoShop <team@innoshop.com>
 * @license    https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace InnoShop\Panel\Components\Layout;

use Illuminate\View\Component;
use InnoShop\Plugin\Repositories\PluginTypeRepo;

class Sidebar extends Component
{
    public mixed $adminUser;

    public array $menuLinks = [];

    private string $currentUri;

    private string $currentRoute;

    private string $currentPrefix;

    public function __construct()
    {
        $this->adminUser = current_admin();

        $routeNameWithPrefix = request()->route()->getName();
        $this->currentRoute  = (string) str_replace(panel_name().'.', '', $routeNameWithPrefix);

        $patterns = explode('.', $this->currentRoute);
        $this->currentPrefix = $patterns[0];

        $routeUriWithPrefix = request()->route()->uri();
        $this->currentUri   = (string) str_replace(panel_name().'/', '', $routeUriWithPrefix);
    }

    public function render(): mixed
    {
        $this->menuLinks = $this->handleMenus($this->getMenus());

        return view('panel::components.layout.sidebar');
    }

    private function getMenus(): array
    {
        $menus = [
            [
                'route' => 'home.index',
                'title' => __('panel/menu.dashboard'),
                'icon'  => 'bi-speedometer2',
            ],
            [
                'title'    => __('panel/menu.top_order'),
                'icon'     => 'bi-cart',
                'prefixes' => ['orders', 'rmas'],
                'children' => $this->getOrderSubRoutes(),
            ],
            [
                'title'    => __('panel/menu.top_product'),
                'icon'     => 'bi-bag',
                'prefixes' => ['products'],
                'children' => $this->getProductSubRoutes(),
            ],
            [
                'title'    => __('panel/menu.top_customer'),
                'icon'     => 'bi-person',
                'prefixes' => ['customers'],
                'children' => $this->getCustomerSubRoutes(),
            ],

            // Operation Management Divider
            [
                'type'  => 'divider',
                'title' => __('panel/menu.divider_operation'),
            ],

            [
                'title'    => __('panel/menu.top_marketing'),
                'icon'     => 'bi-broadcast',
                'children' => $this->getMarketingSubRoutes(),
            ],
            [
                'title'    => __('panel/menu.top_content'),
                'icon'     => 'bi-sticky',
                'prefixes' => ['articles', 'catalogs', 'tags', 'pages'],
                'children' => $this->getContentSubRoutes(),
            ],
            [
                'title'    => __('panel/menu.top_design'),
                'icon'     => 'bi-palette',
                'children' => $this->getDesignSubRoutes(),
            ],
            [
                'title'    => __('panel/menu.top_analytic'),
                'icon'     => 'bi-bar-chart',
                'prefixes' => ['analytics', 'analytics_order'],
                'children' => $this->getAnalyticSubRoutes(),
            ],

            // System Management Divider
            [
                'type'  => 'divider',
                'title' => __('panel/menu.divider_system'),
            ],

            [
                'title'    => __('panel/menu.top_plugin'),
                'icon'     => 'bi-puzzle',
                'children' => $this->getPluginSubRoutes(),
            ],
            [
                'title'    => __('panel/menu.top_setting'),
                'icon'     => 'bi-gear',
                'children' => $this->getSettingSubRoutes(),
            ],
        ];

        return fire_hook_filter('panel.component.sidebar.menus', $menus);
    }

    private function handleMenus($links): array
    {
        $result      = [];
        $lastDivider = null;

        foreach ($links as $index => $link) {
            if (isset($link['type']) && $link['type'] == 'divider') {
                $lastDivider = $link;
                continue;
            }

            $topUrl   = $link['url']   ?? '';
            $topRoute = $link['route'] ?? '';
            if (empty($topUrl) && $topRoute) {
                $link['url'] = panel_route($topRoute);
            }

            $parentChecked = false;
            if (isset($link['active'])) {
                $parentChecked = $link['active'];
            } elseif ($this->checkChildActive($topRoute)) {
                $parentChecked = true;
            }

            $prefixes = $link['prefixes'] ?? [];
            $children = $link['children'] ?? [];

            $link['has_children'] = (bool) $children;
            $hasVisibleChild      = false;

            foreach ($children as $key => $item) {
                $code = str_replace('.', '_', $item['route']);
                if (! $this->adminUser->can($code)) {
                    unset($link['children'][$key]);
                    continue;
                }

                $hasVisibleChild = true;

                $url = $item['url'] ?? '';
                if (empty($url)) {
                    $item['url'] = panel_route($item['route']);
                }

                if (isset($item['active'])) {
                    if ($item['active']) {
                        $parentChecked = true;
                    }
                } elseif ($this->checkChildActive($item['route'])) {
                    $item['active'] = true;
                    $parentChecked  = true;
                } else {
                    $item['active'] = false;
                }

                if (! isset($item['blank'])) {
                    $item['blank'] = false;
                }
                $link['children'][$key] = $item;
            }

            if (! $parentChecked && $this->checkParentActive($prefixes)) {
                $parentChecked = true;
            }

            $shouldKeep = $topRoute == 'home.index' || ($link['has_children'] && $hasVisibleChild) ||
                          $this->adminUser->can(str_replace('.', '_', $topRoute));

            if ($shouldKeep) {
                if ($lastDivider) {
                    $result[]    = $lastDivider;
                    $lastDivider = null;
                }

                if ($link['has_children']) {
                    $link['children'] = array_values($link['children']);
                }

                $link['active'] = $parentChecked;
                $result[] = $link;
            }
        }

        return array_values($result);
    }

    private function checkChildActive($route): bool
    {
        if ($route == $this->currentRoute) {
            return true;
        }

        $routePart = substr($route, 0, strpos($route, '.'));
        if (empty($routePart)) {
            return false;
        }

        $currentPath = substr($this->currentRoute, 0, strpos($this->currentRoute, '.'));
        return $routePart == $currentPath;
    }

    private function checkParentActive($prefixes): bool
    {
        return $prefixes && in_array($this->currentPrefix, $prefixes);
    }

    public function getOrderSubRoutes(): array
    {
        $routes = [
            ['route' => 'orders.index', 'title' => __('panel/menu.orders'), 'icon' => 'bi-bag-check'],
            ['route' => 'order_returns.index', 'title' => __('panel/menu.order_returns'), 'icon' => 'bi-arrow-counterclockwise'],
        ];

        return fire_hook_filter('panel.component.sidebar.order.routes', $routes);
    }

    public function getProductSubRoutes(): array
    {
        $routes = [
            ['route' => 'products.index', 'title' => __('panel/menu.products'), 'icon' => 'bi-box'],
            ['route' => 'categories.index', 'title' => __('panel/menu.categories'), 'icon' => 'bi-folder'],
            ['route' => 'brands.index', 'title' => __('panel/menu.brands'), 'icon' => 'bi-tags'],
            ['route' => 'attributes.index', 'title' => __('panel/menu.attributes'), 'icon' => 'bi-list-check'],
            ['route' => 'attribute_groups.index', 'title' => __('panel/menu.attribute_groups'), 'icon' => 'bi-collection'],
            ['route' => 'reviews.index', 'title' => __('panel/menu.reviews'), 'icon' => 'bi-star'],
        ];

        return fire_hook_filter('panel.component.sidebar.product.routes', $routes);
    }

    public function getCustomerSubRoutes(): array
    {
        $routes = [
            ['route' => 'customers.index', 'title' => __('panel/menu.customers'), 'icon' => 'bi-people'],
            ['route' => 'customer_groups.index', 'title' => __('panel/menu.customer_groups'), 'icon' => 'bi-diagram-3'],
            ['route' => 'transactions.index', 'title' => __('panel/menu.transactions'), 'icon' => 'bi-credit-card'],
            ['route' => 'socials.index', 'title' => __('panel/menu.sns'), 'icon' => 'bi-share'],
        ];

        return fire_hook_filter('panel.component.sidebar.customer.routes', $routes);
    }

    public function getAnalyticSubRoutes(): array
    {
        $routes = [
            ['route' => 'analytics.index', 'title' => __('panel/menu.analytics'), 'icon' => 'bi-graph-up'],
            ['route' => 'analytics_order', 'title' => __('panel/menu.analytics_order'), 'icon' => 'bi-bar-chart'],
            ['route' => 'analytics_product', 'title' => __('panel/menu.analytics_product'), 'icon' => 'bi-box-seam'],
            ['route' => 'analytics_customer', 'title' => __('panel/menu.analytics_customer'), 'icon' => 'bi-person-lines-fill'],
        ];

        return fire_hook_filter('panel.component.sidebar.analytic.routes', $routes);
    }

    public function getContentSubRoutes(): array
    {
        $routes = [
            ['route' => 'articles.index', 'title' => __('panel/menu.articles'), 'icon' => 'bi-file-text'],
            ['route' => 'catalogs.index', 'title' => __('panel/menu.catalogs'), 'icon' => 'bi-journal'],
            ['route' => 'tags.index', 'title' => __('panel/menu.tags'), 'icon' => 'bi-tag'],
            ['route' => 'pages.index', 'title' => __('panel/menu.pages'), 'icon' => 'bi-file-earmark'],
            ['route' => 'file_manager.index', 'title' => __('panel/menu.file_manager'), 'icon' => 'bi-folder2-open'],
        ];

        return fire_hook_filter('panel.component.sidebar.content.routes', $routes);
    }

    public function getDesignSubRoutes(): array
    {
        $routes = [
            ['route' => 'themes_settings.index', 'title' => __('panel/menu.themes_settings'), 'icon' => 'bi-palette'],
            ['route' => 'themes.index', 'title' => __('panel/menu.themes'), 'icon' => 'bi-brush'],
        ];

        return fire_hook_filter('panel.component.sidebar.design.routes', $routes);
    }

    public function getPluginSubRoutes(): array
    {
        $routes = [];
        $typeMenus = PluginTypeRepo::getInstance()->getTypeMenus();
        foreach ($typeMenus as $menu) {
            $menu['active'] = request('type') === $menu['params']['type'] && $this->currentRoute === 'plugins.index';
            $menu['icon'] = $menu['icon'] ?? 'bi-puzzle'; // Default icon
            $routes[] = $menu;
        }

        $routes[] = [
            'route'  => 'plugins.settings',
            'title'  => __('panel/menu.plugin_settings'),
            'active' => $this->currentRoute === 'plugins.settings',
            'icon'   => 'bi-gear',
        ];

        return fire_hook_filter('panel.component.sidebar.plugin.routes', $routes);
    }

    public function getSettingSubRoutes(): array
    {
        $routes = [
            ['route' => 'settings.index', 'title' => __('panel/menu.settings'), 'icon' => 'bi-gear'],
            ['route' => 'account.index', 'title' => __('panel/menu.account'), 'icon' => 'bi-person-circle'],
            ['route' => 'admins.index', 'title' => __('panel/menu.admins'), 'icon' => 'bi-person-badge'],
            ['route' => 'roles.index', 'title' => __('panel/menu.roles'), 'icon' => 'bi-shield-lock'],
            ['route' => 'countries.index', 'title' => __('panel/menu.countries'), 'icon' => 'bi-flag'],
            ['route' => 'states.index', 'title' => __('panel/menu.states'), 'icon' => 'bi-geo'],
            ['route' => 'regions.index', 'title' => __('panel/menu.regions'), 'icon' => 'bi-globe'],
            ['route' => 'locales.index', 'title' => __('panel/menu.locales'), 'icon' => 'bi-translate'],
            ['route' => 'currencies.index', 'title' => __('panel/menu.currencies'), 'icon' => 'bi-currency-exchange'],
            ['route' => 'tax_rates.index', 'title' => __('panel/menu.tax_rates'), 'icon' => 'bi-percent'],
            ['route' => 'tax_classes.index', 'title' => __('panel/menu.tax_classes'), 'icon' => 'bi-collection'],
            ['route' => 'weight_classes.index', 'title' => __('panel/menu.weight_classes'), 'icon' => 'bi-weight'],
        ];

        return fire_hook_filter('panel.component.sidebar.setting.routes', $routes);
    }

    public function getMarketingSubRoutes(): array
    {
        $routes = []; // Add icons here when marketing routes are available

        return fire_hook_filter('panel.component.sidebar.marketing.routes', $routes);
    }
}
