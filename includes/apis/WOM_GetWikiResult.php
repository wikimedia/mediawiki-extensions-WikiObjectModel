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

		$result = array(
			'wiki' => array(),
			'message' => array(),
			'return' => array(),
		);
		$this->getResult()->setContent( $result['wiki'], $wiki );

		global $wgParser;
		$popt = new ParserOptions();
		$popt->setEditSection( false );
		$title = Title::newFromText( '__TEMPWIKITITLE__' );
		if ( strtolower( $type ) == 'ask' ) {
			$_wiki = "{$wiki}|format=xml";

			global $wgOMIP, $smwgResultFormats, $wgAutoloadClasses;
			$smwgResultFormats['xml'] = 'SRFXml';
			$wgAutoloadClasses['SRFXml'] = $wgOMIP . '/includes/apis/SRF_Xml.php';

			$s = $wgParser->preprocess( $_wiki, $title, $popt );
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
			$xml = SMWQueryProcessor::getResultFromFunctionParams( $rawparams, SMW_OUTPUT_WIKI );

			$xObj = simplexml_load_string( $xml );
			try {
				$rows = array();
				foreach ( $xObj->xpath( '/res/row' ) as $objs ) {
					$row = array();
					foreach ( $objs as $label => $vals ) {
						$vs = array();
						foreach ( $vals as $v ) {
							$vs[] = strval( $v );
						}
	            		$this->getResult()->setIndexedTagName( $vs, 'value' );
						$row[$label] = $vs;
					}
//					$this->getResult()->setIndexedTagName($rows, 'list-item');
					$rows[] = $row;
				}
	            $this->getResult()->setIndexedTagName( $rows, 'item' );
	            $result['return'] = $rows;
//	            $this->getResult()->addValue(array($this->getModuleName(), 'result'), 'items', $rows);
			} catch ( Exception $e ) {
				$err = $e->getMessage();
			}
		} else {
			$pout = $wgParser->parse( $wiki, $title, $popt );
			$this->getResult()->setContent( $result['return'], $pout->getText() );
		}
		if ( isset( $err ) ) {
			$result['result'] = 'Failure';
			$this->getResult()->setContent( $result['message'], $err );
		} else {
			$result['result'] = 'Success';
			$this->getResult()->setContent( $result['message'], 'no error' );
		}
		$this->getResult()->addValue( null, $this->getModuleName(), $result );
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
				'type = ask, get parser result of ask query'
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
