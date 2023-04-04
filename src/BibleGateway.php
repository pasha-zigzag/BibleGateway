<?php

namespace BibleGateway;

class BibleGateway
{
	public const URL = 'https://www.biblegateway.com';

	protected string $version;
	protected string $reference;
	protected string $text = '';
	protected string $copyright = '';
	protected string $permalink;

	public function __construct(string $version = 'RUSV')
	{
		$this->version = $version;
	}

	public function __get($name)
	{
		if ($name === 'permalink')
		{
			return $this->permalink = self::URL.'/passage?'.http_build_query(['search' => $this->reference,'version' => $this->version]);
		}
		return $this->$name;
	}

	public function __set(string $name, $value)
	{
		if (in_array($name, ['version', 'reference'])) {
			$this->$name = $value;
			$this->searchPassage($this->reference);
		}
	}

	public function searchPassage(string $passage): self
	{
		$this->reference = $passage;
		$this->text = '';
		$url = self::URL.'/passage?'.http_build_query(['search' => $passage,'version' => $this->version]);
		$html = file_get_contents($url);
		$dom = new \DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_use_internal_errors(false);
		$xpath = new \DOMXPath($dom);
		$context = $xpath->query("//div[@class='passage-wrap']")->item(0);
		$pararaphs =  $xpath->query("//div[@class='passage-wrap']//p");
		$verses = $xpath->query("//div[@class='passage-wrap']//span[contains(@class, 'text')]");
		foreach ($pararaphs as $paragraph)
		{
			if($xpath->query('.//span[contains(@class, "text")]', $paragraph)->length)
			{
				$results = $xpath->query("//sup[contains(@class, 'crossreference') or contains(@class, 'footnote')] | //div[contains(@class, 'crossrefs') or contains(@class, 'footnotes')]", $paragraph);
				foreach($results as $result)
				{
					$result->parentNode->removeChild($result);
				}
				$this->text .= $dom->saveHTML($paragraph);
			}
			else
			{
				$this->copyright = $dom->saveHTML($paragraph);
			}
		}
		return $this;
	}

	public function getVerseOfTheDay(): self
	{
		$url = self::URL.'/votd/get/?'.http_build_query(['format' => 'json', 'version' => $this->version]);
		$votd = json_decode(file_get_contents($url))->votd;
		$this->text = $votd->text;
		$this->reference = $votd->reference;
		return $this;
	}
}
