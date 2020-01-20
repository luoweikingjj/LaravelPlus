<?php

namespace App\Http\Controllers;

use App\Exceptions\FrameworkException;
use App\Presenters\ViewPresenter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException as ReflectionExceptionAlias;

/**
 * Class FrameworkController
 *
 * @package App\Http\Controllers
 */
class FrameworkController extends Controller
{
    /**
     * @var
     */
    private $file;
    /**
     * @var string
     */
    private $framework_name;
    /**
     * @var string
     */
//    private $framework_name_plural;
    /**
     * @var string
     */
    private $framework_name_low;
    /**
     * @var string
     */
    private $framework_name_low_plural;
    /**
     * @var
     */
    private $file_path;

    /**
     * @var bool
     */
    private $is_static_render = false;

    /**
     * FrameworkController constructor.
     *
     * @param $framework_name
     */
    public function __construct($framework_name)
    {
        parent::__construct();
        $this->framework_name = ucfirst($framework_name);
        //        $this->framework_name_plural = Str::plural($this->framework_name);
        $this->framework_name_low = strtolower($this->framework_name);
        $this->framework_name_low_plural = Str::plural($this->framework_name_low);
    }

    /**
     * @param $framework_file_type
     * @param $is_delete
     * @param $is_static_render
     *
     * @throws FrameworkException
     * @throws ReflectionExceptionAlias
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle($framework_file_type, $is_delete, $is_static_render)
    {
        $this->init($framework_file_type);
        $this->is_static_render = $is_static_render;
        $this->file = app_path("{$this->file_path}/{$this->framework_name}{$framework_file_type}.php");
        if ($is_delete) {
            $this->delete($framework_file_type);
        } else {
            $this->checkFileExistence($framework_file_type);
            $this->create($framework_file_type);
        }
    }

    /**
     * @param $framework_file_type
     */
    public function init($framework_file_type): void
    {
        switch ($framework_file_type) {
            case 'Controller':
                $this->file_path = 'Http/Controllers';
                break;
            case 'Repository':
                $this->file_path = 'Repositories';
                break;
            case 'Service':
            case 'Presenter':
            case 'Transformer':
            case 'Formatter':
            case 'Export':
                $this->file_path = $framework_file_type . 's';
                break;
        }
    }

    /**
     * @param $framework_file_type
     *
     * @throws FrameworkException
     */
    private function checkFileExistence($framework_file_type): void
    {
        if (is_file($this->file)) {
            throw new FrameworkException("{$this->framework_name}{$framework_file_type}.php existing!");
        }
    }

    /**
     * @param $framework_file_type
     */
    public function delete($framework_file_type): void
    {
        $file = app_path("{$this->file_path}/{$this->framework_name}{$framework_file_type}.php");
        if (file_exists($file)) {
            unlink($file);
        }
        if ('Controller' === $framework_file_type) {
            $new_directory = base_path("resources/views/{$this->framework_name}");
            exec("rm -rf {$new_directory}");
            $route_types = ['web', 'api'];
            foreach ($route_types as $route_type) {
                $this->deleteRoute($route_type);
            }
        }
        usleep(10000);
    }

    /**
     * @param $route_type
     */
    private function deleteRoute($route_type): void
    {
        switch ($route_type) {
            case 'api':
                $resource_type = 'apiResource';
                break;
            case 'web':
            default:
                $resource_type = 'resource';
        }
        $route_web_path = base_path("routes/{$route_type}.php");
        $route_string = "Route::{$resource_type}('{$this->framework_name_low_plural}'," .
            " '{$this->framework_name}Controller');";
        $file_get_contents = file_get_contents($route_web_path);
        $file_get_contents = str_replace($route_string, '', $file_get_contents);
        file_put_contents($route_web_path, $file_get_contents);
    }

    /**
     * @param $framework_file_type
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws ReflectionExceptionAlias
     */
    public function create($framework_file_type): void
    {
        $framework_name_plural = Str::plural($this->framework_name);
        $Storage = Storage::disk('local');
        $body = $Storage->get("tmpl/framework/{$framework_file_type}.php");
        $body = str_replace(
            [
                'Tmpls',
                'Tmpl',
                'tmpls',
                'tmpl',
            ],
            [
                $framework_name_plural,
                $this->framework_name,
                $this->framework_name_low_plural,
                $this->framework_name_low,
            ],
            $body
        );
        $file = app_path("{$this->file_path}/{$this->framework_name}{$framework_file_type}.php");
        if (! is_file($file)) {
            file_put_contents($file, $body);
        }
        if ('Controller' === $framework_file_type) {
            $tmpl_resources_directory = storage_path('app/tmpl/views');
            $resources_directory = base_path("resources/views/{$this->framework_name_low}");
            exec("cp -r {$tmpl_resources_directory} {$resources_directory}");
            $route_types = ['web', 'api'];
            foreach ($route_types as $route_type) {
                $this->insertRoute($route_type);
            }
            $framework_view_files = scandir($resources_directory);
            foreach ($framework_view_files as $framework_view_file) {
                if (! in_array($framework_view_file, ['.', '..'])) {
                    $route_web_path = $resources_directory . '/' . $framework_view_file;
                    $file_get_contents = file_get_contents($route_web_path);
                    $file_get_contents = str_replace(
                        ['tmpls', 'tmpl'],
                        ['$this->framework_name_low_plural', $this->framework_name_low],
                        $file_get_contents
                    );
                    $file_get_contents = $this->generateStaticView($framework_view_file, $file_get_contents);
                    file_put_contents($route_web_path, $file_get_contents);
                }
            }
        }
        usleep(10000);
    }

    /**
     * @param $file_name
     * @param $data
     *
     * @return string|string[]
     * @throws ReflectionExceptionAlias
     */
    private function generateStaticView($file_name, $data)
    {
        $replace_data = '';
        switch ($file_name) {
            case '_list.blade.php':
                $replace_data = $this->generateListView();
                break;
            case '_detail.blade.php':
                //                $replace_data = $this->generateDetailView($data);
                break;
            case '_search.blade.php':
                //                $replace_data = $this->generateSearchView($data);
                break;
        }
        $data = str_replace('%Placeholder%', $replace_data, $data);
        return $data;
    }

    /**
     * @return string
     * @throws ReflectionExceptionAlias
     */
    private function generateListView(): string
    {
        $ViewPresenter = new ViewPresenter();
        $list_map = [];
        if ($this->is_static_render) {
            $list_map = $this->getModelMap();
        }
        return $ViewPresenter->lists($list_map, $this->is_static_render);
    }

    /**
     * @return array
     * @throws ReflectionExceptionAlias
     */
    private function getModelMap(): array
    {
        $argument = "\App\Models\{$this->framework_name}";
        $ReflectionClass = new ReflectionClass($argument);
        $list_map = $this->getTableCommentMap($this->framework_name);
        $child_map_lists = $this->assemblyChildMapList($ReflectionClass->getMethods());
        $this->appendAssociationModelMap($list_map, $child_map_lists);
        return $list_map;
    }

    /**
     * @param array $data
     * @param array $child_map_lists
     *
     * @return array
     */
    private function assemblyChildMapList(array $data, array $child_map_lists = []): array
    {
        foreach ($data as $datum) {
            $child_map_lists[] = [
                'prop'  => $datum,
                'label' => $datum,
            ];
        }
        return $child_map_lists;
    }

    /**
     * @param $route_type
     */
    private function insertRoute($route_type): void
    {
        switch ($route_type) {
            case 'api':
                $resource_type = 'apiResource';
                break;
            case 'web':
            default:
                $resource_type = 'resource';
        }
        $route_web_path = base_path("routes/{$route_type}.php");
        $route_string = "Route::{$resource_type}('{$this->framework_name_low_plural}'," .
            " '{$this->framework_name}Controller');";
        file_put_contents($route_web_path, $route_string . PHP_EOL, FILE_APPEND);
    }
}
