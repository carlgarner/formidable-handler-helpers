<?php

class StripAttributes
{
	public function addEventHandlers(RequestContext $ctx)
	{
		$ctx->addEventHandler(RendererEvent::XML, $this, 'stripAttribs');
	}
	
	public function stripAttribs(RendererEvent $event)
	{
		$xml = simplexml_load_file($event->file);
		
		foreach($xml->field as $field)
		{
			if(isset($field['normalized-score']))
			{
				unset($field['normalized-score']);
			}

			if(isset($field['resemblance-score']))
			{
				unset($field['resemblance-score']);
			}

			if(isset($field['result-details']))
			{
				unset($field['result-details']);
			}
		}
		
		file_put_contents($event->file, $xml->asXML());
	}
}