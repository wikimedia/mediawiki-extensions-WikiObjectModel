<?php
/**
 * A query printer for xml
 *
 * @note AUTOLOADED
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

class SRFXml extends SMWResultPrinter {
	protected function getResultText( $res, $outputmode ) {
		$xml = '';
		if ( $this->mShowHeaders != SMW_HEADERS_HIDE ) {
			$xml .= '<head>' . "\n";
			foreach ( $res->getPrintRequests() as $pr ) {
				$xml .= "<item>{$pr->getText( $outputmode, null )}</item>\n";
			}
			$xml .= '</head>' . "\n";
		}

		$xml .= '<res>' . "\n";
		// print all result rows
		while ( $row = $res->getNext() ) {
			$xml .= '<row>' . "\n";
			$firstcol = true;
			foreach ( $row as $field ) {
				$xml .= '<item>' . "\n";
				$growing = array();
				while ( ( $object = $field->getNextObject() ) !== false ) {
					$text = Sanitizer::decodeCharReferences( $object->getWikiValue() );
					$growing[] = $text;
				} // while...
				$xml .= implode( ',', $growing );
				$xml .= '</item>' . "\n";
			} // foreach...
			$xml .= '</row>' . "\n";
		}
		$xml .= '</res>' . "\n";

		return $xml;
	}
}