<?php namespace WebEd\Base\ACL\Repositories;

use WebEd\Base\Caching\Services\Traits\Cacheable;
use WebEd\Base\Repositories\Eloquent\EloquentBaseRepository;

use WebEd\Base\ACL\Repositories\Contracts\PermissionRepositoryContract;
use WebEd\Base\Caching\Services\Contracts\CacheableContract;

class PermissionRepository extends EloquentBaseRepository implements PermissionRepositoryContract, CacheableContract
{
    use Cacheable;

    public function get(array $columns = ['*'])
    {
        $this->model = $this->model->orderBy('module', 'ASC');
        return parent::get($columns);
    }

    /**
     * @param string $name
     * @param string $alias
     * @param string $module
     * @return $this
     */
    public function registerPermission($name, $alias, $module)
    {
        $this->findWhereOrCreate([
            'slug' => $alias
        ], [
            'name' => $name,
            'module' => $module,
        ]);
        return $this;
    }

    /**
     * @param string|array $alias
     * @return $this
     */
    public function unsetPermission($alias, $force = false)
    {
        if (is_string($alias)) {
            $alias = [$alias];
        }
        $method = $force ? 'forceDelete' : 'delete';
        $this->model->whereIn('slug', $alias)->$method();
        $this->resetModel();
        return $this;
    }

    /**
     * @param string|array $module
     * @param bool $force
     * @return $this
     */
    public function unsetPermissionByModule($module, $force = false)
    {
        if (is_string($module)) {
            $module = [$module];
        }
        $method = $force ? 'forceDelete' : 'delete';
        $this->model->whereIn('module', $module)->$method();
        $this->resetModel();
        return $this;
    }
}
