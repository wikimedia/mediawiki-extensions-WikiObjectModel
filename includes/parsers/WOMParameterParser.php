<?php
/**
 * @author Ning
 *
 * @file
 * @ingroup WikiObjectModels
 */

class WOMParameterParser extends WikiObjectModelParser {

	public function __construct() {
		parent::__construct();
		$this->m_parserId = WOM_PARSER_ID_PARAMETER;
	}

	public function getValidModelTypes() {
		return array( WOM_TYPE_PARAMETER, WOM_TYPE_TMPL_FIELD );
	}

	public function parseNext( $text, WikiObjectModelCollection $parentObj, $offset = 0 ) {
		if ( !( ( $parentObj instanceof WOMTemplateModel )
			|| ( $parentObj instanceof WOMParserFunctionModel ) ) )
				return null;

		$text = substr( $text, $offset );

		if ( $parentObj instanceof WOMParserFunctionModel ) {
			foreach ( WOMProcessor::$parserFuncParsers as $p ) {
				$ret = $p->parseParserFunctionParameter ( $text, $parentObj );
				if ( $ret != null ) return $ret;
			}
		}

		$r = preg_match( '/^([^=|}]*)(\||=|\}|$)/', $text, $m );
		if ( !$r ) return null;

		if ( $m[2] == '=' ) {
			$len = strlen( $m[0] );
			$key = trim( $m[1] );
		} else {
			$len = 0;
			$key = '';
		}
		if ( $parentObj instanceof WOMTemplateModel ) {
			// templates
			return array( 'len' => $len, 'obj' => new WOMTemplateFieldModel( $key ) );
		} else {
			// parser function, unknown parameter containers, etc
			return array( 'len' => $len, 'obj' => new WOMParameterModel( $key ) );
		}
	}

	public function getSubParserID( $obj ) {
		foreach ( WOMProcessor::$parserFuncParsers as $p ) {
			$ret = $p->getSubParserID ( $obj );
			if ( $ret !== null ) return $ret;
		}

		return WOM_PARSER_ID_PARAM_VALUE;
	}

	public function isObjectClosed( $obj, $text, $offset ) {
		$valid = false;
		foreach ( WOMProcessor::$parserFuncParsers as $p ) {
			if ( $p->validate ( $obj ) ) {
				$valid = true;
				break;
			}
		}

		if ( !( $valid ||
			( $obj instanceof WOMTemplateFieldModel ) ||
			( $obj instanceof WOMParameterModel ) ) )
				return false;

		if ( ( strlen( $text ) >= $offset + 1 ) && $text { $offset } == '|' ) {
			return 1;
		}
		$parentClose = WOMProcessor::getObjectParser( $obj->getParent() )
			->isObjectClosed( $obj->getParent(), $text, $offset );
		if ( $parentClose !== false ) {
			$obj->setPipe( false );
			return 0;
		}

		return false;
	}
}
