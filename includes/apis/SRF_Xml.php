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
		$heads = array();
		$first = true;
		foreach ( $res->getPrintRequests() as $pr ) {
			$head = $pr->getText( $outputmode, null );
			if ( $first ) {
				if ( !$head ) $head = 'mainlabel';
				$first = false;
			}

			$heads[] = str_replace( ' ', '_', str_replace( '/', '_', $head ) );
		}

		$xml = '<res>' . "\n";
		// print all result rows
		while ( $row = $res->getNext() ) {
			$xml .= '<row>' . "\n";
			$firstcol = true;
			$idx = 0;
			foreach ( $row as $field ) {
				$xml .= "<{$heads[$idx]}>\n";
//				$growing = array();
				while ( ( $object = $field->getNextObject() ) !== false ) {
					$text = Sanitizer::decodeCharReferences( $object->getWikiValue() );
					$xml .= "<val>{$text}</val>";
//					$growing[] = $text;
				} // while...
//				$xml .= implode( ',', $growing );
				$xml .= "</{$heads[$idx]}>\n";
				++ $idx;
			} // foreach...
			$xml .= '</row>' . "\n";
		}
		$xml .= '</res>' . "\n";

		return $xml;
	}
}