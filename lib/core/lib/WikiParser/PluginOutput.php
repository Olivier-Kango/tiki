<?php

class WikiParser_PluginOutput
{
	private $format;
	private $data;

	private function __construct( $format, $data ) {
		$this->format = $format;
		$this->data = $data;
	}

	public static function wiki( $text ) {
		return new self( 'wiki', $text );
	}

	public static function html( $html ) {
		return new self( 'html', $html );
	}

	public static function internalError( $message ) {
		return self::error( tra('Internal error' ), $message );
	}

	public static function userError( $message ) {
		return self::error( tra('User error' ), $message );
	}

	public static function argumentError( $missingArguments ) {
		$content = tra('Plugin argument(s) missing:');
		
		$content .= '<ul>';

		foreach( $missingArguments as $arg ) {
			$content .= "<li>$arg</li>";
		}

		$content .= '</ul>';

		return self::userError( $content );
	}

	public static function error( $label, $message ) {
		global $smarty;
		require_once 'lib/smarty_tiki/block.remarksbox.php';
		
		return new self( 'html', smarty_block_remarksbox( array(
			'type' => 'error',
			'title' => $label,
		), $message, $smarty ) );
	}

	function toWiki() {
		switch( $this->format ) {
		case 'wiki':
			return $this->data;
		case 'html':
			return "~np~{$this->data}~/np~";
		}
	}

	function toHtml() {
		switch( $this->format ) {
		case 'wiki':
			return $this->parse( $this->data );
		case 'html':
			return $this->data;
		}
	}

	private function parse( $data ) {
		global $tikilib;

		return $tikilib->parse_data( $data );
	}
}

