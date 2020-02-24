<?php namespace WebEd\Base\ACL\Http\Middleware;

use \Closure;

class BootstrapModuleMiddleware
{
    public function __construct()
    {

    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  array|string $params
     * @return mixed
     */
    public function handle($request, Closure $next, ...$params)
    {
        /**
         * Register to dashboard menu
         */
        dashboard_menu()->registerItem([
            'id' => 'webed-acl-roles',
            'priority' => 5.1,
            'parent_id' => null,
            'heading' => null,
            'title' => trans('webed-acl::base.roles'),
            'font_icon' => 'icon-lock',
            'link' => route('admin::acl-roles.index.get'),
            'css_class' => null,
            'permissions' => ['view-roles'],
        ])->registerItem([
            'id' => 'webed-acl-permissions',
            'priority' => 5.2,
            'parent_id' => null,
            'heading' => null,
            'title' => trans('webed-acl::base.permissions'),
            'font_icon' => 'icon-shield',
            'link' => route('admin::acl-permissions.index.get'),
            'css_class' => null,
            'permissions' => ['view-permissions'],
        ]);

        admin_quick_link()->register('role', [
            'title' => trans('webed-acl::base.role'),
            'url' => route('admin::acl-roles.create.get'),
            'icon' => 'icon-lock',
        ]);

        return $next($request);
    }
}
