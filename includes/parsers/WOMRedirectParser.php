<?php
/**
 * @author Ning
 *
 * @file
 * @ingroup WikiObjectModels
 */

class WOMRedirectParser extends WikiObjectModelParser {

	public function __construct() {
		parent::__construct();
		$this->m_parserId = WOM_PARSER_ID_REDIRECT;
	}

	public function getValidModelTypes() {
		return array( WOM_TYPE_REDIRECT );
	}

	public function parseNext( $text, WikiObjectModelCollection $parentObj, $offset = 0 ) {
		if ( $offset != 0 ) return null;

		$redirect = MagicWord::get( 'redirect' );
		if ( !preg_match( '/^\s*(?:' . $redirect->getBaseRegex() . ')/' . $redirect->getRegexCase(), $text, $m ) )
			return null;
		$len = strlen( $m[0] );
		$text = substr( $text, $len );
		if ( !preg_match( '/^\s*\[\[:?(.*?)(\|(.*?))*\]\]/', $text, $m ) )
			return null;

		return array( 'len' => $len + strlen( $m[0] ), 'obj' => new WOMRedirectModel( $m[1] ) );
	}
}
