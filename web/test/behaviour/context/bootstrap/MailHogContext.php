<?php

/***************************************************************
 *
 * Copyright (C) Web Essentials
 *
 * @author Nguonchhay Touch <nguonchhay@web-essentials.asia>
 *
 ***************************************************************/

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Behat\Behat\Context\Context as ContextInterface;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Mailhog context
 */
class MailHogContext extends RawMinkContext implements ContextInterface {

	const MAIL_HOG_MESSAGE_API_URL = '/api/v1/messages';

	/** @var Client */
	protected $client;

	/** @var array */
	protected $messages;

	/**
	 * @var array
	 */
	protected $extraParams = [];



	/**
	 * Initializes the context
	 *
	 * @TODO: Workaround.
	 * The constructor takes the mailhog URL as a parameter that can be
	 * overwritten using the 'BEHAT_MAILHOG_URL' environment variable. A
	 * standard behat solution is prefarable, but not feasible. The following
	 * approaches have been tried:
	 *
	 * - Context Parameter: this is the default value. However, when using
	 *   a different profile for e.g. latest, this value cannot be overwritten
	 *   but all contexts need to be specified again (which are many), as
	 *   contexts are not inherited from the default profile, see
	 *   - http://docs.behat.org/en/v3.0/guides/4.contexts.html#context-parameters
	 *   - http://docs.behat.org/en/v3.0/guides/4.contexts.html#multiple-contexts
	 *   Repeating all contexts for every profile makes behat.yml messy.
	 *
	 * - Environment Varialbe for Behat: It is possible to set behat parameters
	 *   in the environment, see
	 *   - http://docs.behat.org/en/v3.0/guides/6.profiles.html#environment-variable-behat-params
	 *   However, behat.yml takes precedence over ENV so we cannot overwrite the
	 *   value from outside. Also, we cannot specify just the context parameter
	 *   but need to repeat all contexts, which is messy again.
	 *
	 *  - Custom Extension: Writing a custom behat extension allows arbitrary
	 *    configuration in behat.yml. While documentation exist on how to write
	 *    an extension for behat 2.x, the API seems to have changed alot for
	 *    behat 3.x and hardly any documentation is available.
	 *
	 * @param string $mailhogUrl
	 * @throws
	 */
	public function __construct($mailhogUrl = '') {
		/* Check if a mailhog URL is provided in the environment. */
		$envMailhogUrl = getenv('BEHAT_MAILHOG_URL');
		if ($envMailhogUrl) {
			$mailhogUrl = $envMailhogUrl;
		}

		/* Make sure we have a valid mailhog URL */
		if ($mailhogUrl == '') {
			throw new \Exception(sprintf('Please configure MailHog in behat.yml or provide BEHAT_MAILHOG_URL'));
		}

		/* Initialize MailHog Client */
		$this->client = new Client(['base_uri' => $mailhogUrl]);

		/* Clear all messages in mailhog */
		$this->clearMessagesInMailHog();
	}

	/**
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @throws string
	 */
	public function clearMessagesInMailHog() {
		try {
			$response = $this->client->delete(self::MAIL_HOG_MESSAGE_API_URL);
		} catch (RequestException $exception) {
			throw new \Exception(sprintf("Mailhog Request Error:\n%s", $exception->getMessage()));
		}
	}

	/**
	 * @param $message
	 *
	 * @return array
	 */
	public function getAdjustingMessageFromMailHog($message) {
		$adjustingMessage = NULL;
		if (isset($message['Content'])) {
			$content = $message['Content'];
			foreach ($content['Headers'] as $key => $value) {
				$adjustingMessage[strtolower($key)] = trim($value[0]);
			}

			$messageBody = isset($content['Body']) ? trim($content['Body']) : NULL;

			/* Check whether to decode the message body to normal string */
			if (isset($adjustingMessage['content-transfer-encoding']) && $adjustingMessage['content-transfer-encoding'] == 'quoted-printable' && $messageBody != NULL) {
				$messageBody = quoted_printable_decode($messageBody);
			}
			$adjustingMessage['body'] = $messageBody;
		}
		return $adjustingMessage;
	}

	/**
	 * Check whether email was sent to mailhog
	 *
	 * @throws \Exception
	 *
	 * @When /^an email was sent$/
	 */
	public function emailWasSent() {
		$response = $this->client->get(self::MAIL_HOG_MESSAGE_API_URL);
		$messages = json_decode($response->getBody(TRUE), TRUE);
		if (count($messages) == 0) {
			throw new \Exception(sprintf('Email was not sent.'));
		}

		/* Adjust all response messages to the desire format */
		$this->messages = [];
		foreach ($messages as $message) {
			$this->messages[] = $this->getAdjustingMessageFromMailHog($message);
		}
	}

	/**
	 * @param $email
	 *
	 * @throws \Exception
	 *
	 * @Then /^the email receiver is "([^"]*)"$/
	 */
	public function emailReceiverIs($email) {
		$isMatch = FALSE;
		foreach ($this->messages as $message) {
			if (strpos($message['to'], $email) !== FALSE) {
				$isMatch = TRUE;
				break;
			}
		}
		if (! $isMatch) {
			throw new \Exception(sprintf('Email was not sent to %s', $email));
		}
	}

	/**
	 * @param $email
	 *
	 * @throws \Exception
	 *
	 * @Then /^the email sender is "([^"]*)"$/
	 */
	public function emailSenderIs($email) {
		$isMatch = FALSE;
		foreach ($this->messages as $message) {
			if (strpos($message['from'], $email) !== FALSE) {
				$isMatch = TRUE;
				break;
			}
		}
		if (!$isMatch) {
			throw new \Exception(sprintf('Email was not sent from %s', $email));
		}
	}

	/**
	 * @param $subject
	 *
	 * @throws \Exception
	 *
	 * @Then /^the email subject is "([^"]*)"$/
	 */
	public function subjectIs($subject) {
		$isMatch = FALSE;
		$messageSubject = $this->messages;
		foreach ($messageSubject as $message) {
			if (strpos($message['subject'], $subject) !== FALSE) {
				$isMatch = TRUE;
				break;
			}
		}
		if (!$isMatch) {
			throw new \Exception(sprintf('Email was not sent from %s', $subject));
		}
	}

	/**
	 * @param $body
	 *
	 * @When /^the email message contains "([^"]*)"$/
	 *
	 * @throws \Exception
	 */
	public function bodyContains($body) {
		$isMatch = FALSE;
		foreach ($this->messages as $message) {
			$rawBody = $this->removeHtmlTags($message['body']);
			if (strpos($rawBody, $body) !== FALSE) {
				$isMatch = TRUE;
				break;
			}
		}
		if (!$isMatch) {
			throw new \Exception(sprintf('Body does not contain body %s', $body));
		}
	}

	/**
	 * @param $link
	 *
	 * @throws \Exception
	 *
	 * @When /^I follow the email link containing "([^"]*)"$/
	 */
	public function clickLinkContainsStringInEmail($link) {
		$messageBody = $this->messages[0]['body'];

		/* Extract all url from the message body */
		$urlPattern = '/(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,8})(:\d+)?([\/?=#&%\w\.\-\+]*)*\/?/';
		preg_match_all($urlPattern, $messageBody, $urls);

		$emailLink = '';
		foreach ($urls[0] as $url) {
			if (strpos($url, $link) !== FALSE) {
				$emailLink = trim($url);
				break;
			}
		}

		if ($emailLink == '') {
			throw new \Exception(sprintf('Link "%s" cannot be found in the email.', $link));
		}

		/* Go to the link */
		$this->visitPath($emailLink);
	}

	/**
	 * @param $html
	 *
	 * @return string
	 */
	public static function removeHtmlTags($html) {
		return strip_tags($html);
	}
}
