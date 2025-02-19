<?php

namespace Sokeio;

use Sokeio\Action;
use Sokeio\Laravel\Hook\ActionHook;
use Sokeio\ArrayStatus;
use Sokeio\DataInfo;
use Sokeio\Events\PlatformChanged;
use Sokeio\Facades\Assets;
use Sokeio\Facades\Platform;
use Sokeio\Facades\Shortcode;
use Sokeio\RouteEx;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;

class ThemeManager extends ActionHook
{
    use \Sokeio\Concerns\WithSystemExtend;
    private $isHtmlAjax = true;
    public function DisableHtmlAjax(): self
    {
        $this->isHtmlAjax = false;
        return $this;
    }
    public function enableHtmlAjax(): self
    {
        $this->isHtmlAjax = true;
        return $this;
    }
    public function LayoutDefault()
    {
        return 'default';
    }

    public function isRegisterBeforeLoad()
    {
        return false;
    }
    public function getName()
    {
        return "theme";
    }
    private $layout;
    private $title_lock = false;
    private ?DataInfo $data_active;
    public function setTitle($title, $lock = false)
    {
        if ($this->title_lock) return;
        Assets::SetData('page_title', $title);
        $this->title_lock = $lock;
    }
    public function getTitle()
    {
        return apply_filters(PLATFORM_PAGE_TITLE, Assets::GetData('page_title'));
    }

    public function setLayout($layout)
    {
        $this->layout = $layout != '' ? ('theme::layouts.' . $layout) : '';
    }
    public function AdminId()
    {
        return ArrayStatus::Key(PLATFORM_THEME_ADMIN)->getFirstOrDefault('admin');
    }
    public function SiteId()
    {
        return ArrayStatus::Key(PLATFORM_THEME_WEB)->getFirstOrDefault('none');
    }
    public function AdminDataInfo()
    {
        return $this->find($this->AdminId());
    }
    public function SiteDataInfo()
    {
        return $this->find($this->SiteId());
    }
    public function getStatusData($theme)
    {
        if (isset($theme['admin']) && $theme['admin'] == 1) {
            return $this->AdminId() == $theme->getId() ? 1 : 0;
        } else {
            return $this->SiteId() == $theme->getId() ? 1 : 0;
        }
    }

    public function setStatusData($theme, $value)
    {
        if (isset($theme['admin']) && $theme['admin'] === 1) {
            $site = $this->AdminDataInfo();
            $site?->getOptionHook()?->changeStatus($site, 0);
            $theme?->getOptionHook()?->changeStatus($theme, $value);
            ArrayStatus::Key(PLATFORM_THEME_ADMIN)->Active($theme->getId(), true);
        } else {
            $site = $this->SiteDataInfo();

            if ($site == $theme) {
                ArrayStatus::Key(PLATFORM_THEME_WEB)->Active($site->getId(), $site->status ? false : true);
            } else {
                $site?->getOptionHook()?->changeStatus($site, 0);
                $theme?->getOptionHook()?->changeStatus($theme, $value);
                ArrayStatus::Key(PLATFORM_THEME_WEB)->Active($theme->getId(), true);
            }
        }
        PlatformChanged::dispatch($theme);
        Platform::makeLink();
    }
    public function getLayouts()
    {
        return $this->data_layouts;
    }
    private $data_layouts = [];
    public $data_themes = [];
    private function findAndRegister($theme, $parentId = null)
    {
        if (!$parentId) $parentId = $theme;
        if (!isset($this->data_themes[$parentId])) $this->data_themes[$parentId] = [];
        $theme_data = $this->find($theme);
        if ($theme_data == null) return null;
        $this->data_themes[$parentId][] = $theme_data;
        if ($parent = $theme_data['parent']) {
            $this->findAndRegister($parent, $parentId);
        }
        $theme_data->DoRegister();
        foreach ($theme_data->getLayouts() as $layout) {
            $this->data_layouts[] = 'theme::' . $layout;
        }
        return $theme_data;
    }
    private function findAndRegisterRoute($theme, $parentId = null)
    {
        if (!$parentId) $parentId = $theme;
        if (!isset($this->data_themes[$parentId])) $this->data_themes[$parentId] = [];
        $theme_data = $this->find($theme);
        if ($theme_data == null) return null;
        $this->data_themes[$parentId][] = $theme_data;
        if ($parent = $theme_data['parent']) {
            $this->findAndRegisterRoute($parent, $parentId);
        }

        $filenames = glob($theme_data->getPath('src/Crud/*.php'));
        if ($filenames) {
            foreach ($filenames as $filename) {
                require_once $filename;
            }
        }
        RouteEx::Load($theme_data->getPath('routes/'));
        if (isset($theme_data['alias']) && $theme_data['alias'] != '' && File::exists($theme_data->getPath('config/' . $theme_data['alias'] . '.php'))) {
            $config = include $theme_data->getPath('config/' . $theme_data['alias'] . '.php');
            if (isset($config['shortcodes']) && $shortcodes = $config['shortcodes']) {
                if (is_array($shortcodes) && count($shortcodes) > 0) {
                    Shortcode::Register($shortcodes, 'theme');
                }
            }
            if (isset($config['actions']) && $actionTypes = $config['actions']) {
                if (is_array($actionTypes) && count($actionTypes) > 0) {
                    Action::Register($actionTypes, 'theme');
                }
            }
        }


        return $theme_data;
    }
    private function findLocations($locations, $theme, $parentId = null)
    {
        if (!$parentId) $parentId = $theme;
        if (!isset($this->data_themes[$parentId])) $this->data_themes[$parentId] = [];
        $theme_data = $this->find($theme);
        if ($theme_data == null) return $locations;
        $this->data_themes[$parentId][] = $theme_data;
        if ($parent = $theme_data['parent']) {
            $locations  =  $this->findLocations($locations, $parent, $parentId);
        }
        if (isset($theme_data['locations'])) {
            foreach ($theme_data['locations'] as $item) {
                $locations[$item] = $item;
            }
        }
        return  $locations;
    }
    private $locations = null;
    public function getLocations()
    {
        if ($this->locations) return $this->locations;
        $temps = $this->findLocations([], apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->SiteId(), 0));
        $this->locations = array_keys($temps);
        return $this->locations;
    }
    public function RegisterRoute()
    {
        $this->findAndRegisterRoute(apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->AdminId(), 1));
        $this->findAndRegisterRoute(apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->SiteId(), 0));
    }
    public function RegisterTheme()
    {
        $this->findAndRegister(env('PLATFORM_THEME_DEFAULT', 'none'));
        $this->findAndRegister(apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->AdminId(), 1));
        $this->findAndRegister(apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->SiteId(), 0));
    }
    public function ThemeCurrent()
    {
        if (!isset($this->data_active) || !$this->data_active) {
            if (byte_is_admin()) {
                $this->data_active = $this->findAndRegister(apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->AdminId(), 1));
            } else {
                $this->data_active = $this->findAndRegister(apply_filters(PLATFORM_THEME_FILTER_LAYOUT, $this->SiteId(), 0));
            }
            if ($this->data_active == null) {
                $this->data_active = $this->findAndRegister(env('PLATFORM_THEME_DEFAULT', 'none'));
            }
        }
        return $this->data_active;
    }
    public function reTheme()
    {
        $this->data_active = null;
        $this->ThemeCurrent();
    }

    public function isHtml()
    {
        if ($this->isHtmlAjax) return Request::ajax();
        return false;
    }
    public function Layout($layout = '')
    {
        if ($layout != '') {
            $this->setLayout($layout);
        }
        $theme = $this->ThemeCurrent();
        if ($this->isHtml()) {
            return 'sokeio::html';
        }
        if ($theme) {
            if ($this->layout == '') {
                $this->setLayout($theme['layout'] ?? $this->LayoutDefault());
            }
        }
        if ($this->layout == '') {
            $this->setLayout($this->LayoutDefault());
        }
        return $this->layout;
    }
}
