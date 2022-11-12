<?php

/*
 * Copyright by Udo Zaydowicz.
 * Modified by SoftCreatR.dev.
 *
 * License: http://opensource.org/licenses/lgpl-license.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
namespace calendar\system\event\listener;

use wcf\data\user\User;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\NamedUserException;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\system\WCF;

/**
 * JCoins listener for participation dialog.
 */
class JCoinsCalenderParticipationListener implements IParameterizedEventListener
{
    /**
     * @inheritdoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!MODULE_JCOINS) {
            return;
        }

        // participation form to check for sufficient JCoins
        if ($eventObj->getActionName() == 'getParticipationForm') {
            if (JCOINS_ALLOW_NEGATIVE) {
                return;
            }

            // data
            $objects = $eventObj->getObjects();
            $eventDate = $objects[0]->getDecoratedObject();
            $event = $eventDate->getEvent();

            // author must not pay
            if ($event->userID == WCF::getUser()->userID) {
                return;
            }

            // moderators may enter, and go negative on participation
            if (WCF::getSession()->getPermission('mod.calendar.canEditEvent')) {
                return;
            }

            // participant may open
            if ($eventDate->isParticipant(WCF::getUser()->userID)) {
                return;
            }

            // check JCoins
            if ($event->jCoinsFee > WCF::getUser()->jCoinsAmount) {
                throw new NamedUserException(WCF::getLanguage()->getDynamicVariable('wcf.jcoins.amount.tooLow'));
            }
        }

        // event date cancellation / deletion
        $action = $eventObj->getActionName();
        if ($action == 'cancel' || $action == 'delete') {
            // data
            $objects = $eventObj->getObjects();
            foreach ($objects as $object) {
                $eventDate = $object->getDecoratedObject();
                $event = $eventDate->getEvent();

                // skip if no refee, deleted after end or cancelled at any time
                if (!$event->jCoinsReFee) {
                    continue;
                }
                if ($eventDate->cancelTime > 0) {
                    continue;
                }
                if ($action == 'delete' && $eventDate->endTime < TIME_NOW) {
                    continue;
                }

                // get affected users
                $userIDs = [];
                $conditionBuilder = new PreparedStatementConditionBuilder();
                $conditionBuilder->add('eventDateID = ?', [$eventDate->eventDateID]);
                $conditionBuilder->add('decision = ?', ['yes']);
                $conditionBuilder->add('userID IS NOT NULL');
                $sql = "SELECT    userID
                        FROM    calendar" . WCF_N . "_event_date_participation
                " . $conditionBuilder;
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($conditionBuilder->getParameters());
                while ($userID = $statement->fetchColumn()) {
                    $userIDs[] = $userID;
                }

                if (!empty($userIDs)) {
                    foreach ($userIDs as $userID) {
                        $user = new User($userID);
                        if (!$user->userID) {
                            continue;
                        }

                        UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee', $eventDate, [
                            'amount' => $event->jCoinsReFee,
                            'userID' => $user->userID,
                        ]);

                        UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee.owner', $eventDate, [
                            'amount' => -1 * $event->jCoinsReFee,
                            'userID' => $event->userID,
                            'participant' => $user->username,
                        ]);
                    }
                }
            }
        }
    }
}
