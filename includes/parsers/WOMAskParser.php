<?php
/**
 * @author Ning
 *
 * @file
 * @ingroup WikiObjectModels
 */

class WOMAskParser extends WOMParserFunctionParameterParser {

	public function __construct() {
		$this->m_parserId = WOM_PFPARSER_ID_ASK;
	}

	public function getValidModelTypes() {
		return array(
			WOM_TYPE_QUERYSTRING,
			WOM_TYPE_QUERYPRINTOUT
		);
	}

	private function parseAskParameters ( $text, WikiObjectModelCollection $parentObj ) {
		if ( defined( 'SMW_AGGREGATION_VERSION' ) ) {
			$r = preg_match( '/^(\s*\?([^>=|}]+)(?:\>([^=|}]*))?(?:=([^|}]*))?)(\||\}|$)/', $text, $m );
			if ( !$r ) return null;
			return array(
				'len' => strlen( $m[5] == '|' ? $m[0] : $m[1] ),
				'obj' => new WOMQueryPrintoutModel( trim( $m[2] ), trim( $m[4] ), trim( $m[3] ) ) );
		} else {
			$r = preg_match( '/^(\s*\?([^=|}]+)(?:=([^|}]*))?)(\||\}|$)/', $text, $m );
			if ( !$r ) return null;
			return array(
				'len' => strlen( $m[4] == '|' ? $m[0] : $m[1] ),
				'obj' => new WOMQueryPrintoutModel( trim( $m[2] ), trim( $m[3] ) ) );
		}
	}

	public function parseParserFunctionParameter ( $text, WikiObjectModelCollection $parentObj ) {
		if ( !defined( 'SMW_VERSION' )
			|| !( $parentObj instanceof WOMParserFunctionModel ) )
				return null;

		if ( trim( strtolower( $parentObj->getFunctionKey() ) ) != 'ask' ) return null;

		if ( count ( $parentObj->getObjects() ) == 0 ) {
			return array( 'len' => 0, 'obj' => new WOMQuerystringModel() );
		}

		return $this->parseAskParameters( $text, $parentObj );
	}

	public function getSubParserID( $obj ) {
		if ( ( $obj instanceof WOMQuerystringModel )
			|| ( $obj instanceof WOMQueryPrintoutModel ) )
				return '';

		return null;
	}

	public function validate ( $obj ) {
		if ( !( ( $obj instanceof WOMQuerystringModel )
			|| ( $obj instanceof WOMQueryPrintoutModel ) ) )
				return false;

		return true;
	}
}
