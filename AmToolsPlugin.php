<?php
namespace Craft;

class AmToolsPlugin extends BasePlugin
{
    public function getName()
    {
         return 'a&m Tools';
    }

    public function getVersion()
    {
        return '1.3.1';
    }

    public function getDeveloper()
    {
        return 'a&m impact';
    }

    public function getDeveloperUrl()
    {
        return 'http://www.am-impact.nl';
    }

    public function init()
    {
        if (craft()->request->isCpRequest())
        {
            craft()->amTools_imageOptim->registerEvents();
        }
    }

    public function addTwigExtension()
    {
        Craft::import('plugins.amtools.twigextensions.ToolsTwigExtension');

        return new ToolsTwigExtension();
    }
}
