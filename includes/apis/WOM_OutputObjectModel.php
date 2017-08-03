<?php
if ( !defined( 'MEDIAWIKI' ) ) {
	exit( 1 );
}

global $wgOMIP;
require_once( $wgOMIP . '/includes/apis/WOM_OutputProcessor.php' );

/**
 * @addtogroup API
 */
class ApiWOMOutputObjectModel extends ApiBase {

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

		$page_name = $params['title'];
		$xpath = $params['xpath'];
		$type = $params['type'];
		$rid = $params['rid'];


		$articleTitle = Title::newFromText( $page_name );
		if ( !$articleTitle )
			$this->dieUsage( "Can't create title object ($page_name)", 2 );

		$article = new Article( $articleTitle );
		if ( !$article->exists() )
			$this->dieUsage( "Article doesn't exist ($page_name)", 3 );

		try {
			$page_obj = WOMOutputProcessor::getOutputData( $articleTitle, $rid );
			$objs = WOMProcessor::getObjIdByXPath2( $page_obj, $xpath );
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

			// pay attention to special xml tag, e.g., <property><value>...</value></property>
			$result['return'] = array();
			if ( $type == 'count' ) {
				$count = 0;
				foreach ( $objs as $id ) {
					if ( $id == '' ) continue;
					++ $count;
				}
				if ( defined( 'ApiResult::META_CONTENT' ) ) {
					ApiResult::setContentValue( $result['return'], 'count', $count );
				} else {
					ApiResult::setContent( $result['return'], $count );
				}
			} else {
				$xml = '';
				foreach ( $objs as $id ) {
					if ( $id == '' ) continue;
					$wobj = $page_obj->getObject( $id );
					$result['return'][$id] = array();
					if ( $type == 'xml' ) {
						$xml .= "<{$id} xml:space=\"preserve\">{$wobj->toXML()}</{$id}>";
					} else {
						if ( defined( 'ApiResult::META_CONTENT' ) ) {
							ApiResult::setContentValue( $result['return'][$id], 'wikitext', $wobj->getWikiText() );
						} else {
							ApiResult::setContent( $result['return'][$id], $wobj->getWikiText() );
						}
					}
				}
				if ( $type == 'xml' ) {
					header ( "Content-Type: application/rdf+xml" );
					echo <<<OUTPUT
<?xml version="1.0" encoding="UTF-8" ?>
<api><womoutput result="Success" revisionID="{$page_obj->getRevisionID()}"><return>
{$xml}
</return></womoutput></api>
OUTPUT;
					exit( 1 );
				}
			}
		}
		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	protected function getAllowedParams() {
		return array (
			'title' => null,
			'xpath' => null,
			'type' => array(
				ApiBase :: PARAM_DFLT => 'wiki',
				ApiBase :: PARAM_TYPE => array(
					'wiki',
					'count',
					'xml',
				),
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			),
			'rid' => array (
                                ApiBase :: PARAM_TYPE => 'integer',
                                ApiBase :: PARAM_DFLT => 0,
                                ApiBase :: PARAM_MIN => 0
                        ),
		);
	}

	protected function getExamples() {
		return array (
			'api.php?action=womoutput&title=Somepage&xpath=//template[@name=SomeTempate]/template_field[@key=templateparam]'
		);
	}
}
