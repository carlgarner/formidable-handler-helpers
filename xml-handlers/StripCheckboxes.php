<?php

class StripCheckboxes
{
	private $checkboxes	= array();
	private $strip		= array('sendpidget', 'hidepattern', 'anotostatement');
	
	public function addEventHandlers(RequestContext $ctx)
	{
		$ctx->addEventHandler(RendererEvent::XML, $this, 'stripFields');
	}
	
	public function stripFields(RendererEvent $event)
	{
		$xml = simplexml_load_file($event->file);
		
		foreach($xml->children as $node)
		{
			$nodename = (string) $node['name'];
			$nodetype = (string) $node['type'];
			
			if(in_array($nodetype, $this->strip))
			{
				$dom = dom_import_simplexml($node);
				$dom->parentNode->removeChild($dom);
			}
			
			if(in_array($nodename, $this->checkboxes))
			{
				g_Log(__METHOD__ . ':' . __CLASS__ . ': Removing group checkbox member "' . $nodename . '"');
				
				$dom = dom_import_simplexml($node);
				$dom->parentNode->removeChild($dom);
			}
		}
		
		file_put_contents($event->file, $xml->asXML());
	}
	
	public function __construct()
	{
		$path	= dirname(__FILE__);
		$file	= str_replace('custom', 'config.xml', $path);
		$config	= simplexml_load_file($file);
	
		foreach($config->Group as $group)
		{
			foreach($config->Widget as $widget)
			{
				if($widget['group'] == $group['name'])
				{
					$this->checkboxes[] = $widget['name'];
				}
			}
		}
	}
}