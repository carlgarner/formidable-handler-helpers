<?php

class MergePrepop
{
	private $ignore	= array('propertymapping', 'property', 'object', 'processors', 'settings');
	private $added	= array('hidepattern');
	
	public function addEventHandlers(RequestContext $ctx)
	{
		$ctx->addEventHandler(RendererEvent::XML, $this, 'mergeFiles');
	}
	
	public function mergeFiles(RendererEvent $event)
	{
		$xml	= simplexml_load_file($event->file);
		$ppfile	= $event->document->getPrepopFile();
		
		if(file_exists($ppfile))
		{
			g_Log(__METHOD__ . ':' . __CLASS__ . ': Merging prepop file into output XML');
			
			$prepop = simplexml_load_file($ppfile);

			$xmlprepop = $xml->addChild('prepop');
			$this->append_simplexml($xmlprepop, $prepop);
		}
		
		file_put_contents($event->file, $xml->asXML());
	}
	
	//
	// based on http://stackoverflow.com/questions/3418019/simplexml-append-one-tree-to-another/22099078#22099078
	//
	private function append_simplexml(&$simplexmlto, &$simplexmlfrom)
	{
		foreach($simplexmlfrom->children() as $simplexmlchild)
		{
			if(!in_array((string)$simplexmlchild->getName(), $this->ignore))
			{
				$this->added[] = $simplexmlchild->getName();
				
				$simplexmltemp = $simplexmlto->addChild($simplexmlchild->getName(), str_replace('&', '&amp;', (string)$simplexmlchild));
				foreach($simplexmlchild->attributes() as $key => $val)
				{
					$simplexmltemp->addAttribute($key ,$val);
				}

				$this->append_simplexml($simplexmltemp, $simplexmlchild);
			}
		}
	}
}