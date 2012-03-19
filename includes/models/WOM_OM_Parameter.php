<?php
/**
 * This model implements key value models.
 *
 * @author Ning
 * @file
 * @ingroup WikiObjectModels
 *
 */

class WOMParameterModel extends WikiObjectModelCollection {
	protected $m_key;
	protected $m_pipe;

	public function __construct( $key = '' ) {
		parent::__construct( WOM_TYPE_PARAMETER );
		$this->m_key = $key;
		$this->m_pipe = true;
	}

	public function getKey() {
		return $this->m_key;
	}

	public function setPipe( $pipe ) {
		$this->m_pipe = $pipe;
	}

	public function getWikiText() {
		return ( $this->m_key == '' ? "" : ( $this->m_key . '=' ) ) .
			$this->getValueText() .
			( $this->m_pipe ? '|' : '' );
	}

	public function getValueText() {
		return parent::getWikiText();
	}

	public function setXMLAttribute( $key, $value ) {
		if ( $value == '' ) throw new MWException( __METHOD__ . ": value cannot be empty" );

		if ( $key == 'key' ) {
			$this->m_key = $value;
		} else {
			throw new MWException( __METHOD__ . ": invalid key/value pair: key=key_string" );
		}
	}

	protected function getXMLAttributes() {
		return 'key="' . self::xml_entities( $this->m_key ) . '"';
	}
}
