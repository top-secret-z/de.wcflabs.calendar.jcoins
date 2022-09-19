<?php
namespace calendar\system\event\listener;
use calendar\data\event\date\participation\EventDateParticipationList;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;

/**
 * JCoins create event and event date listener.
 *
 * @author		2017-2022 Zaydowicz
 * @license		GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package		de.wcflabs.calendar.jcoins
 */
class JCoinsCalenderEventListener implements IParameterizedEventListener {
	/**
	 * @inheritdoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!MODULE_JCOINS) return;
		
		$objects = $eventObj->getObjects();
		
		switch ($eventObj->getActionName()) {
			case 'triggerPublication':
				foreach ($eventObj->getObjects() as $object) {
					if (!$object->isDisabled && $object->userID) {
						UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.event', $object->getDecoratedObject());
					}
				}
				break;
				 
				// 'enable' calls triggerPublication
				
			case 'disable':
				foreach ($eventObj->getObjects() as $object) {
					if (!$object->isDeleted && $object->userID) {
						UserJCoinsStatementHandler::getInstance()->revoke('de.wcflabs.jcoins.statement.calendar.event', $object->getDecoratedObject());
					}
				}
				break;
				
			case 'trash':
				foreach ($eventObj->getObjects() as $object) {
					if (!$object->isDisabled && $object->userID) {
						UserJCoinsStatementHandler::getInstance()->revoke('de.wcflabs.jcoins.statement.calendar.event', $object->getDecoratedObject());
					}
					
					// refee
					$event = $object->getDecoratedObject();
					if ($event->jCoinsReFee) {
						$this->refee($event);
					}
				}
				break;
				
			case 'restore':
				foreach ($eventObj->getObjects() as $object) {
					if (!$object->isDisabled && $object->userID) {
						UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.event', $object->getDecoratedObject());
					}
				}
				
				// fee
				$event = $object->getDecoratedObject();
				if ($event->jCoinsFee) {
					$this->fee($event);
				}
				break;
		}
	}
	
	/**
	 * Refee after trash, if not cancelled / ended
	 */
	public function refee($event) {
		// get participations
		$list = new EventDateParticipationList();
		$list->getConditionBuilder()->add("eventDateID IN (SELECT eventDateID FROM calendar".WCF_N."_event_date WHERE eventID = ? AND cancelTime = ? AND endTime > ?)", [$event->eventID, 0, TIME_NOW]);
		$list->getConditionBuilder()->add("decision LIKE ?", ['yes']);
		$list->readObjects();
		$participations = $list->getObjects();
		if (empty($participations)) return;
		
		// refee
		$eventDates = [];
		foreach ($participations as $participation) {
			if (!isset($eventDates[$participation->eventDateID])) {
				$eventDates[$participation->eventDateID] = $participation->getEventDate();
			}
			
			UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee', $eventDates[$participation->eventDateID], [
					'amount' => $event->jCoinsReFee,
					'userID' => $participation->userID
			]);
			
			UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee.owner', $eventDates[$participation->eventDateID], [
					'amount' => -1 * $event->jCoinsReFee,
					'userID' => $event->userID,
					'participant' => $participation->username
			]);
		}
	}
	
	/**
	 * Fee after restore
	 */
	public function fee($event) {
		// get participations
		$list = new EventDateParticipationList();
		$list->getConditionBuilder()->add("eventDateID IN (SELECT eventDateID FROM calendar".WCF_N."_event_date WHERE eventID = ? AND cancelTime = ? AND endTime > ?)", [$event->eventID, 0, TIME_NOW]);
		$list->getConditionBuilder()->add("decision LIKE ?", ['yes']);
		$list->readObjects();
		$participations = $list->getObjects();
		if (empty($participations)) return;
		
		// fee
		$eventDates = [];
		foreach ($participations as $participation) {
			if (!isset($eventDates[$participation->eventDateID])) {
				$eventDates[$participation->eventDateID] = $participation->getEventDate();
			}
			
			UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.fee', $eventDates[$participation->eventDateID], [
					'amount' => -1 * $event->jCoinsFee,
					'userID' => $participation->userID
			]);
			
			UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.fee.owner', $eventDates[$participation->eventDateID], [
					'amount' => $event->jCoinsFee,
					'userID' => $event->userID,
					'participant' => $participation->username
			]);
		}
	}
}
