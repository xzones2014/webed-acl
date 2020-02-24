<?php namespace WebEd\Base\ACL\Http\Controllers;

use Illuminate\Http\Request;
use WebEd\Base\ACL\Http\DataTables\RolesListDataTable;
use WebEd\Base\ACL\Http\Requests\CreateRoleRequest;
use WebEd\Base\ACL\Http\Requests\UpdateRoleRequest;
use WebEd\Base\Http\Controllers\BaseAdminController;
use WebEd\Base\ACL\Repositories\Contracts\RoleRepositoryContract;
use WebEd\Base\ACL\Repositories\Contracts\PermissionRepositoryContract;
use Yajra\Datatables\Engines\BaseEngine;

class RoleController extends BaseAdminController
{
    protected $module = 'webed-acl';

    /**
     * @var \WebEd\Base\ACL\Repositories\RoleRepository
     */
    protected $repository;

    public function __construct(RoleRepositoryContract $roleRepository)
    {
        parent::__construct();

        $this->repository = $roleRepository;

        $this->middleware(function (Request $request, $next) {
            $this->getDashboardMenu($this->module . '-roles');

            $this->breadcrumbs
                ->addLink(trans('webed-acl::base.acl'))
                ->addLink(trans('webed-acl::base.roles'), route('admin::acl-roles.index.get'));

            return $next($request);
        });
    }

    /**
     * Get index page
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(RolesListDataTable $rolesListDataTable)
    {
        $this->setPageTitle(trans('webed-acl::base.roles'));

        $this->dis['dataTable'] = $rolesListDataTable->run();

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_ACL_ROLE, 'index.get', $rolesListDataTable)->viewAdmin('roles.index');
    }

    /**
     * Get all roles
     * @param RolesListDataTable|BaseEngine $rolesListDataTable
     * @return \Illuminate\Http\JsonResponse
     */
    public function postListing(RolesListDataTable $rolesListDataTable)
    {
        $data = $rolesListDataTable->with($this->groupAction());

        return do_filter(BASE_FILTER_CONTROLLER, $data, WEBED_ACL_ROLE, 'index.post', $this);
    }

    /**
     * Handle group actions
     * @return array
     */
    protected function groupAction()
    {
        $data = [];
        if ($this->request->get('customActionType', null) == 'group_action') {
            if(!$this->userRepository->hasPermission($this->loggedInUser, ['delete-roles'])) {
                return [
                    'customActionMessage' => trans('webed-acl::base.do_not_have_permission'),
                    'customActionStatus' => 'danger',
                ];
            }

            $ids = (array)$this->request->get('id', []);

            $result = $this->repository->deleteRole($ids);

            do_action(BASE_ACTION_AFTER_DELETE, WEBED_ACL_ROLE, $ids, $result);

            $data['customActionMessage'] = $result ? trans('webed-acl::base.delete_role_success') : trans('webed-acl::base.delete_role_error');
            $data['customActionStatus'] = $result ? 'success' : 'danger';
        }
        return $data;
    }

    /**
     * @param \WebEd\Base\ACL\Repositories\PermissionRepository $permissionRepository
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getCreate(PermissionRepositoryContract $permissionRepository)
    {
        do_action(BASE_ACTION_BEFORE_CREATE, WEBED_ACL_ROLE, 'create.get');

        $this->setPageTitle(trans('webed-acl::base.create_role'));
        $this->breadcrumbs->addLink(trans('webed-acl::base.create_role'));

        $this->dis['permissions'] = $permissionRepository->get();

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_ACL_ROLE, 'create.get')->viewAdmin('roles.create');
    }

    public function postCreate(CreateRoleRequest $request)
    {
        do_action(BASE_ACTION_BEFORE_CREATE, WEBED_ACL_ROLE, 'create.post');

        $data = [
            'name' => $request->get('name'),
            'slug' => $request->get('slug'),
            'created_by' => $this->loggedInUser->id,
            'updated_by' => $this->loggedInUser->id,
        ];
        $permissions = ($request->exists('permissions') ? $request->get('permissions') : []);

        $result = $this->repository->createRole($data, $permissions);

        do_action(BASE_ACTION_AFTER_CREATE, WEBED_ACL_ROLE, $result);

        $msgType = !$result ? 'danger' : 'success';

        flash_messages()
            ->addMessages(trans('webed-acl::base.create_role_' . $msgType), $msgType)
            ->showMessagesOnSession();

        if (!$result) {
            return redirect()->back()->withInput();
        }

        if ($this->request->has('_continue_edit')) {
            return redirect()->to(route('admin::acl-roles.edit.get', ['id' => $result]));
        }

        return redirect()->to(route('admin::acl-roles.index.get'));
    }

    /**
     * @param \WebEd\Base\ACL\Repositories\PermissionRepository $permissionRepository
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function getEdit(PermissionRepositoryContract $permissionRepository, $id)
    {
        $this->dis['superAdminRole'] = false;

        $item = $this->repository->find($id);

        if (!$item) {
            flash_messages()
                ->addMessages(trans('webed-acl::base.role_not_exists'), 'danger')
                ->showMessagesOnSession();

            return redirect()->to(route('admin::acl-roles.index.get'));
        }

        $this->setPageTitle(trans('webed-acl::base.edit_role'), '#' . $id . ' ' . $item->name);
        $this->breadcrumbs->addLink(trans('webed-acl::base.edit_role'));

        $this->dis['object'] = $item;

        $this->dis['checkedPermissions'] = $this->repository->getRelatedPermissions($item);

        if ($item->slug == 'super-admin') {
            $this->dis['superAdminRole'] = true;
        }

        $this->dis['permissions'] = $permissionRepository->get();

        return do_filter(BASE_FILTER_CONTROLLER, $this, WEBED_ACL_ROLE, 'edit.get', $id)->viewAdmin('roles.edit');
    }

    public function postEdit(UpdateRoleRequest $request, $id)
    {
        $item = $this->repository->find($id);

        $item = do_filter(BASE_FILTER_BEFORE_UPDATE, $item, WEBED_ACL_ROLE, 'edit.post');

        if (!$item) {
            flash_messages()
                ->addMessages(trans('webed-acl::base.role_not_exists'), 'danger')
                ->showMessagesOnSession();

            return redirect()->to(route('admin::acl-roles.index.get'));
        }

        $data = [
            'name' => $request->get('name'),
            'updated_by' => $this->loggedInUser->id,
        ];

        $permissions = ($request->exists('permissions') ? $request->get('permissions') : []);

        $result = $this->repository->updateRole($item, $data, $permissions);

        do_action(BASE_ACTION_AFTER_UPDATE, WEBED_ACL_ROLE, $id, $result);

        $msgType = !$result ? 'danger' : 'success';

        flash_messages()
            ->addMessages(trans('webed-acl::base.update_role_' . $msgType), $msgType)
            ->showMessagesOnSession();

        if (!$result) {
            return redirect()->back();
        }

        if ($this->request->has('_continue_edit')) {
            return redirect()->back();
        }

        return redirect()->to(route('admin::acl-roles.index.get'));
    }

    /**
     * Delete role
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDelete($id)
    {
        $id = do_filter(BASE_FILTER_BEFORE_DELETE, $id, WEBED_ACL_ROLE);

        $result = $this->repository->deleteRole($id);

        do_action(BASE_ACTION_AFTER_DELETE, WEBED_ACL_ROLE, $id, $result);

        $code = $result ? \Constants::SUCCESS_NO_CONTENT_CODE : \Constants::ERROR_CODE;

        $msg = $result ? trans('webed-acl::base.delete_role_success') : trans('webed-acl::base.delete_role_error');

        return response()->json(response_with_messages($msg, !$result, $code), $code);
    }
}
