<?php

class ProperCSV
{
	// Set to true to have field names in 
	// column one and data in column two
	private $vertical		= false; 
	private $csvDelimiter	= ',';
	private $csvEncapsulate	= '"';

	public function addEventHandlers(RequestContext $ctx)
	{
		$ctx->addEventHandler(RendererEvent::CSV, $this, 'rebuildCSV');
	}
	
	public function rebuildCSV(RendererEvent $event)
	{
		$fh			= fopen($event->file, 'w');
		$vertical	= array();
		$header		= array();
		$content	= array();
	
		foreach($event->document->pages as $page)
		{
			foreach($page->fields as $field)
			{
				if(!$this->vertical)
				{
					$header[]	= $field->key;
					$content[]	= $field->value;
					
					continue;
				}
				
				$vertical[] = array($field->key, $field->value);
			}
		}
		
		if(!$this->vertical)
		{
			fputcsv($fh, $header, $this->csvDelimiter, $this->csvEncapsulate);
			fputcsv($fh, $content, $this->csvDelimiter, $this->csvEncapsulate);
		}
		else
		{
			foreach($vertical as $line)
			{
				fputcsv($fh, $line, $this->csvDelimiter, $this->csvEncapsulate);
			}
		}
	
		fclose($fh);
	}
}