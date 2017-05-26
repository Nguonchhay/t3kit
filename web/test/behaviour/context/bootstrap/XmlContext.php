<?php

/***************************************************************
 *
 * Copyright (C) Web Essentials
 *
 * @author Nguonchhay Touch <nguonchhay@web-essentials.asia>
 *
 ***************************************************************/

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Context\Context as ContextInterface;

/**
 * Xml context
 */
class XmlContext extends RawMinkContext implements ContextInterface {



	/**
	 * @return \DOMDocument
	 */
	public function getXmlDataFromCurrentPage() {
		$rawSource = $this->getSession()->getPage()->getContent();
		$domDocument = new DOMDocument();
		$domDocument->loadXML($rawSource);
		return $domDocument;
	}

	/**
	 * @param \DOMDocument $domDocument
	 *
	 * @return array
	 */
	public function adjustXmlSiteampToArrayOfLinks($domDocument) {
		/** @var \DOMNodeList $xmlLinks */
		$xmlLinks = $domDocument->getElementsByTagName('loc');
		$links = [];
		/** @var \DOMElement $xmlLink */
		foreach ($xmlLinks as $xmlLink) {
			$links[] = $xmlLink->nodeValue;
		}
		return $links;
	}

	/**
	 * Checks, that xml response contain specified string
	 *
	 * @param $text
	 * @throws ExpectationException
	 *
	 * @Then /^the xml response should contain "([^"]*)"$/
	 */
	public function xmlResponseShouldContain($text) {
		$actual = $this->getSession()->getDriver()->getContent();
		$regex  = '/'.preg_quote($text, '/').'/ui';

		if (!preg_match($regex, $actual)) {
			$message = sprintf('The string "%s" was not found anywhere in the XML response of the current page.', $text);
			throw new ExpectationException($message, $this->getSession());
		}
	}

	/**
	 * @param $xmlElement
	 * @throws \Exception
	 *
	 * @Then /^I should see xml element "([^"]*)"$/
	 */
	public function iShouldSeeXmlElement($xmlElement) {
		/** @var \DOMDocument $domDocument */
		$domDocument = $this->getXmlDataFromCurrentPage();
		$xmlElementNodes = $domDocument->getElementsByTagName($xmlElement);
		if ($xmlElementNodes->length == 0) {
			throw new Exception($xmlElement . ' is not found in xml source!');
		}
	}

	/**
	 * @param $xmlEncoding
	 * @throws \Exception
	 *
	 * @Then /^the xml encoding should be "([^"]*)"$/
	 */
	public function xmlEncodingShouldBe($xmlEncoding) {
		/** @var \DOMDocument $domDocument */
		$domDocument = $this->getXmlDataFromCurrentPage();
		$domDocumentEncoding = $domDocument->encoding;
		if ($xmlEncoding !== $domDocumentEncoding) {
			throw new Exception($xmlEncoding . ' is not found in xml source!');
		}
	}

	/**
	 * Example: the xml sitemap should contain links "/,/company.html,/blog.html"
	 *
	 * @param $stringLinks
	 * @throws \Exception
	 *
	 * @Then /^the xml sitemap should contain links "([^"]*)"$/
	 */
	public function xmlShouldContainLinks($stringLinks) {
		/** @var \DOMDocument $domDocument */
		$domDocument = $this->getXmlDataFromCurrentPage();
		/** @var array $xmlLinks */
		$xmlLinks = $this->adjustXmlSiteampToArrayOfLinks($domDocument);
		if (count($xmlLinks) == 0) {
			throw new Exception("There is no links in sitemap.xml");
		}

		$baseUrl = rtrim($this->getMinkParameter('base_url'), "/");
		$links = explode(',', $stringLinks);
		foreach ($links as $link) {
			$link = $baseUrl . trim($link);
			if (! in_array($link, $xmlLinks)) {
				throw new Exception(sprintf("The link '%s' does not exist in sitemap.xml", $link));
			}
		}
	}
}
