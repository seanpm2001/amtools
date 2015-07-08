<?php
namespace Craft;

class AmTools_ImageOptimService extends BaseApplicationComponent
{
	private $tools = array('gifsicle' => false, 'jpegoptim' => false, 'jpegtran' => false, 'advpng' => false, 'optipng' => false, 'pngcrush' => false, 'pngquant' => false, 'pngout' => false);
	
	public function setToolAvailability()
	{
		foreach ($this->tools as $tool => $val)
		{
			if ($location = shell_exec('which ' . $tool))
			{
				$this->tools[$tool] = trim($location);
			}
		}
	}

	private function optimizeBase($type, $tools, &$imageOptim)
	{
		if (count($tools) > 0)
		{
			foreach ($tools as $toolName => $className)
			{
				if(isset($this->tools[$toolName]) && $this->tools[$toolName])
				{
					Craft::import('plugins.amtools.libraries.PHPImageOptim.Tools.' . $type . '.' . $className, true);
					$toolClass = '\\PHPImageOptim\\Tools\\' . $type . '\\' . $className;
					$tool = new $toolClass();
					$tool->setBinaryPath($this->tools[$toolName]);
					$imageOptim->chainCommand($tool);
				}
			}
		}

		return $imageOptim;
	}

	private function optimizeJpeg($imageOptim)
	{
		$tools = array('jpegoptim' => 'JpegOptim', 'jpegtran' => 'JpegTran');
		$this->optimizeBase('Jpeg', $tools, $imageOptim);

		return $imageOptim->optimise();
	}

	private function optimizeGif($imageOptim)
	{
		$tools = array('gifsicle' => 'Gifsicle');
		$this->optimizeBase('Gif', $tools, $imageOptim);

		return $imageOptim->optimise();
	}

	private function optimizePng($imageOptim)
	{
		$tools = array('optipng' => 'OptiPng');
		$this->optimizeBase('Png', $tools, $imageOptim);

		return $imageOptim->optimise();
	}

	public function getAssetPath(AssetFileModel $asset)
	{
		$assetSourceFolder = $asset->folder->source->getAttributes();
		$path = craft()->templates->renderObjectTemplate($assetSourceFolder['settings']['path'], craft()->config->get('environmentVariables'));
		$folderAttributes = $asset->folder->getAttributes();
		if ($folderAttributes['path'] != '')
		{
			$path .= $folderAttributes['path'];
		}

		$path .= $asset->filename;

		return $path;
	}

	public function optimizeImage($imageToOptimize)
	{
		$this->setToolAvailability();
		Craft::import('plugins.amtools.libraries.PHPImageOptim.PHPImageOptim', true);
		Craft::import('plugins.amtools.libraries.PHPImageOptim.Tools.Common', true);
		Craft::import('plugins.amtools.libraries.PHPImageOptim.Tools.ToolsInterface', true);
		$imageOptim = new \PHPImageOptim\PHPImageOptim();
		$imageOptim->setImage($imageToOptimize);

		switch(strtolower(pathinfo($imageToOptimize, PATHINFO_EXTENSION)))
		{
			case 'gif':
				return $this->optimizeGif($imageOptim);
			break;
			case 'png':
				return $this->optimizePng($imageOptim);
			break;
			case 'jpg':
			case 'jpeg':
				return $this->optimizeJpeg($imageOptim);
			break;
		}

		return false;
	}

	public function registerEvents()
	{
		// Start task when an asset gets saved
		$events = array('assets.onSaveAsset', 'assets.onReplaceFile');

		foreach ($events as $event)
		{
			craft()->on($event, function(Event $event) {
				$asset = $event->params['asset'];

				if (!empty($asset) && is_a($asset, 'Craft\\AssetFileModel'))
				{
					$path = craft()->amTools_imageOptim->getAssetPath($asset);
					if (!empty($path) && in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), array('gif', 'png', 'jpg', 'jpeg')))
					{
						craft()->tasks->createTask('AmTools_ImageOptim', 'Optimizing asset: ' . $asset->filename, array('asset' => $asset));
					}
				}
			});
		}
	}
}

