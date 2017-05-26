<?php

/***************************************************************
 *
 * Copyright (C) Web Essentials
 *
 * @author Nguonchhay Touch <nguonchhay@web-essentials.asia>
 *
 ***************************************************************/

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines application features from the specific context.
 */
class ApplicationContext extends RawMinkContext implements Context {

	protected $dummyData = [
		'user', 'policy', 'invoice', 'claim', 'mail'
	];



	/**
	 * Initializes context.
	 * Every scenario gets its own context instance.
	 * You can also pass arbitrary arguments to the
	 * context constructor through behat.yml.
	 */
	public function __construct() {

	}

	/**
	 * @param BeforeScenarioScope $scope
	 *
	 * @BeforeScenario @fixtures
	 */
	public function before(BeforeScenarioScope $scope) {

	}

	/**
	 * @param AfterScenarioScope $scope
	 *
	 * @AfterScenario
	 */
	public function after(AfterScenarioScope $scope) {
		/**
		 * Uncomment this line in order to clear database schema after finish testing
		 */

	}


}
