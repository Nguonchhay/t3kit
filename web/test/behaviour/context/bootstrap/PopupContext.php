<?php

/***************************************************************
 *
 * Copyright (C) Web Essentials
 *
 * @author Nguonchhay Touch <nguonchhay@web-essentials.asia>
 *
 ***************************************************************/

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Context\Context as ContextInterface;

/**
 * Popup context
 */
class PopupContext extends RawMinkContext implements ContextInterface {

	/**
	 * @When /^(?:|I )confirm the popup$/
	 */
	public function confirmPopup() {
		$this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
	}

	/**
	 * @When /^(?:|I )cancel the popup$/
	 */
	public function cancelPopup() {
		$this->getSession()->getDriver()->getWebDriverSession()->dismiss_alert();
	}

	/**
	 * @param string $message The message.
	 *
	 * @return bool
	 *
	 * @When /^(?:|I )should see "([^"]*)" in popup$/
	 */
	public function assertPopupMessage($message) {
		return ($message == $this->getSession()->getDriver()->getWebDriverSession()->getAlert_text());
	}

	/**
	 * @param string $message The message.
	 *
	 * @When /^(?:|I )fill "([^"]*)" in popup$/
	 */
	public function setPopupText($message) {
		$this->getSession()->getDriver()->getWebDriverSession()->postAlert_text($message);
	}
}
