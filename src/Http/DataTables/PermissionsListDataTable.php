<?php namespace WebEd\Base\ACL\Http\DataTables;

use WebEd\Base\ACL\Models\Permission;
use WebEd\Base\Http\DataTables\AbstractDataTables;
use Yajra\Datatables\Engines\CollectionEngine;
use Yajra\Datatables\Engines\EloquentEngine;
use Yajra\Datatables\Engines\QueryBuilderEngine;

class PermissionsListDataTable extends AbstractDataTables
{
    /**
     * @var Permission
     */
    protected $model;

    public function __construct()
    {
        $this->model = Permission::select(['name', 'slug', 'module', 'id']);
    }

    /**
     * @return array
     */
    public function headings()
    {
        return [
            'id' => [
                'title' => 'ID',
                'width' => '1%',
            ],
            'name' => [
                'title' => trans('webed-acl::datatables.permission.heading.name'),
                'width' => '40%',
            ],
            'slug' => [
                'title' => trans('webed-acl::datatables.permission.heading.slug'),
                'width' => '30%',
            ],
            'module' => [
                'title' => trans('webed-acl::datatables.permission.heading.module'),
                'width' => '30%',
            ],
        ];
    }

    /**
     * @return array
     */
    public function columns()
    {
        return [
            ['data' => 'id', 'name' => 'id'],
            ['data' => 'name', 'name' => 'name'],
            ['data' => 'slug', 'name' => 'slug'],
            ['data' => 'module', 'name' => 'module'],
        ];
    }

    /**
     * @return string
     */
    public function run()
    {
        $this->setAjaxUrl(route('admin::acl-permissions.index.post'), 'POST');

        $this
            ->addFilter(1, form()->text('name', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => trans('webed-core::datatables.search') . '...',
            ]))
            ->addFilter(2, form()->text('slug', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => trans('webed-core::datatables.search') . '...',
            ]))
            ->addFilter(3, form()->text('module', '', [
                'class' => 'form-control form-filter input-sm',
                'placeholder' => trans('webed-core::datatables.search') . '...',
            ]));

        return $this->view();
    }

    /**
     * @return CollectionEngine|EloquentEngine|QueryBuilderEngine|mixed
     */
    public function fetchDataForAjax()
    {
        return datatable()->of($this->model)
            ->editColumn('name', function ($item) {
                if (lang()->has($item->module . '::permissions.' . $item->slug)) {
                    return trans($item->module . '::permissions.' . $item->slug);
                }
                return $item->name;
            });
    }
}
