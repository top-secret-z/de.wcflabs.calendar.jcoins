<?php
namespace calendar\system\event\listener;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * JCoins event addForm listener.
 *
 * @author		2019-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		de.wcflabs.calendar.jcoins
 */
class JCoinsCalenderAddFormListener implements IParameterizedEventListener {
	/**
	 * instance of EventAddForm
	 */
	protected $eventObj;
	
	/**
	 * event data
	 */
	protected $jCoinsFee = 0;
	protected $jCoinsReFee = 0;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$this->eventObj = $eventObj;
		$this->$eventName();
	}
	
	/**
	 * Handles the readData event. Only in Edit!
	 */
	protected function readData() {
		
		if (empty($_POST)) {
			$this->jCoinsFee= $this->eventObj->event->jCoinsFee;
			$this->jCoinsReFee= $this->eventObj->event->jCoinsReFee;
		}
	}
	
	/**
	 * Handles the assignVariables event.
	 */
	protected function assignVariables() {
		WCF::getTPL()->assign([
				'jCoinsFee' => $this->jCoinsFee,
				'jCoinsReFee' => $this->jCoinsReFee
		]);
	}
	
	/**
	 * Handles the readFormParameters event.
	 */
	protected function readFormParameters() {
		if (isset($_POST['jCoinsFee'])) $this->jCoinsFee = intval($_POST['jCoinsFee']);
		if (isset($_POST['jCoinsReFee'])) $this->jCoinsReFee = intval($_POST['jCoinsReFee']);
	}
	
	/**
	 * Handles the validate event.
	 */
	protected function validate() {
		// nothing ufn
	}
	
	/**
	 * Handles the save event.
	 */
	protected function save() {
		$this->eventObj->additionalFields = array_merge($this->eventObj->additionalFields, [
				'jCoinsFee' => $this->jCoinsFee,
				'jCoinsReFee' => $this->jCoinsReFee
		]);
	}
}

