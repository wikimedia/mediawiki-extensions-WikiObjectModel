<?php
/**
 * @author Ning
 *
 * @file
 * @ingroup WikiObjectModels
 */

abstract class WOMParserFunctionParameterParser {
	protected $m_parserId;

	/**
	 * Array of error text messages. Private to allow us to track error insertion
	 * (PHP's count() is too slow when called often) by using $mHasErrors.
	 * @var array
	 */
	protected $mErrors = array();

	/**
	 * Boolean indicating if there where any errors.
	 * Should be modified accordingly when modifying $mErrors.
	 * @var boolean
	 */
	protected $mHasErrors = false;

// /// Processing methods /////
	public abstract function parseParserFunctionParameter ( $text, WikiObjectModelCollection $parentObj );

	public abstract function validate( $obj );

	public abstract function getValidModelTypes();

// /// Get methods /////
	public function getParserID() {
		return $this->m_parserId;
	}

	// specified next parser. e.g., template parser -> parameter parser
	public function getSubParserID( $obj ) { return ''; }

	/**
	 * Return a string that displays all error messages as a tooltip, or
	 * an empty string if no errors happened.
	 */
	public function getErrorText() {
		if ( defined( 'SMW_VERSION' ) )
			return smwfEncodeMessages( $this->mErrors );

		return $this->mErrors;
	}

	/**
	 * Return an array of error messages, or an empty array
	 * if no errors occurred.
	 */
	public function getErrors() {
		return $this->mErrors;
	}
}
