<?php

/**
 * @addtogroup API
 */
class ApiWOMWikiObjectApi extends ApiBase {
	private $m_apiInst = null;

	public function __construct( $main, $action ) {
		parent :: __construct( $main, $action );
	}

	public function execute() {
		global $wgUser;

		$params = $this->extractRequestParams();
		if ( is_null( $params['title'] ) )
			$this->dieUsage( 'Must specify page title', 0 );
		if ( is_null( $params['xpath'] ) )
			$this->dieUsage( 'Must specify xpath', 1 );
		if ( is_null( $params['api'] ) )
			$this->dieUsage( 'Must specify api action name', 2 );
		if ( is_null( $params['wommap'] ) )
			$this->dieUsage( 'Must specify wom => api mapping info', 3 );

		$page_name = $params['title'];
		$xpath = $params['xpath'];
		$api = $params['api'];
		$rid = $params['rid'];

		$wommaps = $this->parseMapInfo( $params['wommap'] );

		$articleTitle = Title::newFromText( $page_name );
		if ( !$articleTitle )
			$this->dieUsage( "Can't create title object ($page_name)", 2 );

		$article = new Article( $articleTitle );
		if ( !$article->exists() )
			$this->dieUsage( "Article doesn't exist ($page_name)", 3 );

		try {
			$page_obj = WOMProcessor::getPageObject( $articleTitle, $rid );
			$objs = WOMProcessor::getObjIdByXPath2( $page_obj, $xpath );

			$vals = array();
			$first = true;
			foreach ( $objs as $id ) {
				if ( $id == '' ) continue;
				$wobj = $page_obj->getObject( $id );

				foreach ( $wommaps as $idx => $map ) {
					if ( !$map['setting']['multiple'] && !$first ) continue;
					$val = '';
					if ( strtolower( $map['xpath'] ) == 'innerwiki' ) {
						$val = ( $wobj instanceof WikiObjectModelCollection ) ? $wobj->getInnerWikiText() : $wobj->getWikiText();
					} else {
						$xObj = simplexml_load_string( $wobj->toXML() );
						$os = $xObj->xpath( $map['xpath'] );
						if ( count( $os ) > 0 ) {
							foreach ( $os as $o ) $val .= strval( $o );
						}
					}
					$wommaps[$idx]['value'] .=
						( $map['setting']['prefix'] ? $map['setting']['prefix']:'' ) .
						$val .
						( $map['setting']['delimiter'] ? $map['setting']['delimiter']:'' );
				}
				if ( $first ) $first = false;
			}
		} catch ( Exception $e ) {
			$err = $e->getMessage();
		}

		$result = array();
		if ( isset( $err ) ) {
			$result = array(
				'result' => 'Failure',
				'message' => array(),
			);
			if ( defined( 'ApiResult::META_CONTENT' ) ) {
				ApiResult::setContentValue( $result['message'], 'message', $err );
			} else {
				ApiResult::setContent( $result['message'], $err );
			}
		} else {
			$result['result'] = 'Success';
			$result['revisionID'] = $page_obj->getRevisionID();
			foreach ( $wommaps as $map ) {
				$this->getMain()->getRequest()->setVal( $map['param'], $map['value'] );
			}
			$this->m_apiInst->profileIn();
			$this->m_apiInst->execute();
			$this->m_apiInst->profileOut();
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	private function parseMapInfo( $mapinfo ) {
		$wommaps = array();
		foreach ( explode( '|', str_replace( '||', '___PIPEPLACEHOLDER__', $mapinfo ) ) as $map ) {
			$s = explode( '=', str_replace( '___PIPEPLACEHOLDER__', '|', $map ), 2 );
			$settings = array();
			$first = true;
			$param = '';
			foreach ( explode( ',', str_replace( ',,', '___DELIMITERPLACEHOLDER__', $s[1] ) ) as $set ) {
				$set = str_replace( '___DELIMITERPLACEHOLDER__', '|', $set );
				if ( $first ) {
					$param = $set;
					$first = false;
					continue;
				}
				if ( strtolower( $set { 0 } ) == 'm' ) {
					$settings['multiple'] = true;
					$settings['delimiter'] = substr( $set, 1 );
				} elseif ( strtolower( $set { 0 } ) == 'p' ) {
					$settings['prefix'] = substr( $set, 1 );
				}
			}
			$wommaps[] = array(
				'xpath' => $s[0],
				'param' => $param,
				'setting' => $settings,
				'value' => ''
			);
		}
		return $wommaps;
	}

	protected function getAllowedParams() {
		// tricky here
		$mainInst = $this->getMain();
		$tmp = array_keys( $mainInst->getModules() );
		$ids = array(
			array_search( 'womset', $tmp ),
			array_search( 'womget', $tmp ),
			array_search( 'womapi', $tmp ),
			array_search( 'womoutput', $tmp ),
			array_search( 'womwiki', $tmp ),
			array_search( 'womquery', $tmp )
		);
		foreach ( $ids as $id ) unset( $tmp[$id] );

		$params = array (
			'title' => null,
			'xpath' => null,
			'rid' => array (
				ApiBase :: PARAM_TYPE => 'integer',
				ApiBase :: PARAM_DFLT => 0,
				ApiBase :: PARAM_MIN => 0
			),
			'wommap' => null,
			'api' => array(
				ApiBase :: PARAM_DFLT => 'help',
				ApiBase :: PARAM_TYPE => $tmp
			),
		);

		$api = $mainInst->getRequest()->getVal( $this->encodeParamName( 'api' ), 'help' );
		if ( $api == 'help' ) {
			// apply description
			$params['...'] = null;
		} else {
//			$mainInst = new ApiMain($mainInst->getRequest());
			$modules = $mainInst->getModules();
			$this->m_apiInst = new $modules[$api] ( $mainInst, $api );
			$params = $params + $this->m_apiInst->getFinalParams();
		}

		return $params;
	}

	protected function getParamDescription() {
		return array (
			'title' => 'Title of the page to modify',
			'xpath' => 'DOM-like xpath to locate WOM object instances (http://www.w3schools.com/xpath/xpath_syntax.asp)',
			'wommap' => array (
				'Settings to map WOM result on parameter(s) of api actions',
				'"|" as delimiter, to separate map items',
				'format: xpath of values=api parameter name,other settings',
				'    for "|" inside map item, use "||" to escape',
				'    for "," inside map item, use ",," to escape',
				'  xpath:',
				'    "@property" as property name,',
				'    "innerwiki" as wiki text inside xml object',
				'  settings:',
				'    "m<delimiter>", multiple field, if multiple flag is not set, always use the first result,',
				'    "p<prefix>", prefix',
			),
			'api' => 'Api (action) name of common Wiki',
			'rid' => 'Revision id of specified page - by dafault latest updated revision (0) is used',
			'...' => 'standard api parameters',
		);
	}

	protected function getDescription() {
		return 'Call to execute MW apis upon Wiki objects inside title';
	}

	protected function getExamples() {
		return array (
			'api.php?action=womapi&title=Somepage&xpath=//template&wommap=@name=titles,m||,ptemplate:&api=query&prop=info'
		);
	}
}
