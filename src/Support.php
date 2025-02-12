<?php
namespace Krokedil\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Support.
 */
class Support {

	/**
	 * Logger instance.
	 *
	 * @var Logger
	 */
	private $logger = null;

	/**
	 * System report class.
	 *
	 * @var SystemReport
	 */
	private $system_report = null;

	/**
	 * Support constructor.
	 *
	 * @param string $id Gateway ID.
	 * @param string $name Gateway name (or title).
	 */
	public function __construct( $id, $name ) {
		$this->logger        = new Logger( $id );
		$this->system_report = new SystemReport( $id, $name );
	}

	public function logger() {
		return $this->logger;
	}

	public function system_report() {
		return $this->system_report;
	}
}
