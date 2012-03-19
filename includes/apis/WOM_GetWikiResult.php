<?php

/**
 * @addtogroup API
 */
class ApiWOMGetWikiResult extends ApiBase {

	public function __construct( $main, $action ) {
		parent :: __construct( $main, $action );
	}

	public function execute() {
		global $wgUser;

		$params = $this->extractRequestParams();
		if ( is_null( $params['wiki'] ) )
			$this->dieUsage( 'Must specify wiki text', 0 );
		$wiki = $params['wiki'];
		$type = $params['type'];

		global $wgParser;
		$popt = new ParserOptions();
		$popt->setEditSection( false );
		$title = Title::newFromText( '__TEMPWIKITITLE__' );
		if ( strtolower( $type ) == 'ask' ) {
			$wiki = "{$wiki}|format=xml";

			global $wgOMIP, $smwgResultFormats, $wgAutoloadClasses;
			$smwgResultFormats['xml'] = 'SRFXml';
			$wgAutoloadClasses['SRFXml'] = $wgOMIP . '/includes/apis/SRF_Xml.php';

			$s = $wgParser->preprocess( $wiki, $title, $popt );
			$b = 0;
			for ( $i = 0; $i < strlen( $s ); ++$i ) {
				if ( $s { $i } == '[' ) {
					++ $b;
				} elseif ( $s { $i } == ']' ) {
					-- $b;
				} elseif ( $s { $i } == '|' ) {
					if ( $b == 0 ) break;
				}
			}
			$rawparams = array( substr( $s, 0, $i ) );
			if ( $i < strlen( $s ) ) $rawparams = array_merge( $rawparams, explode( '|', substr( $s, $i + 1 ) ) );
			$result = SMWQueryProcessor::getResultFromFunctionParams( $rawparams, SMW_OUTPUT_WIKI );
		} else {
			$pout = $wgParser->parse( $wiki, $title, $popt );
			$result = "<![CDATA[{$pout->getText()}]]>";
		}

		header ( "Content-Type: application/rdf+xml" );
		echo <<<OUTPUT
<?xml version="1.0" encoding="UTF-8" ?>
<api><womwiki result="Success">
<wiki><![CDATA[{$wiki}]]></wiki>
<return>
{$result}
</return></womwiki></api>
OUTPUT;
		exit( 1 );
	}

	protected function getAllowedParams() {
		$types = defined( 'SMW_VERSION' ) ? array( 'wiki', 'ask' ) : array( 'wiki' );
		return array (
			'wiki' => null,
			'type' => array(
				ApiBase :: PARAM_DFLT => 'wiki',
				ApiBase :: PARAM_TYPE => $types
			),
		);
	}

	protected function getParamDescription() {
		$types = defined( 'SMW_VERSION' ) ? array(
				'Type to fetch wiki parse result',
				'type = wiki, get parser result of wiki text',
				'type = ask, get parser result of ask query, in xml format'
			) : array(
				'Type to fetch wiki parse result',
				'type = wiki, get parser result of wiki text',
			);
		return array (
			'wiki' => 'Wiki text',
			'type' => $types
		);
	}

	protected function getDescription() {
		return 'Call to get parse result of wiki';
	}

	protected function getExamples() {
		return array (
			'api.php?action=womwiki&wiki=[[Hello]]',
			'api.php?action=womwiki&wiki=[[Category:Hello]]&type=ask'
		);
	}

//	public function mustBePosted() {
//		return true;
//	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
