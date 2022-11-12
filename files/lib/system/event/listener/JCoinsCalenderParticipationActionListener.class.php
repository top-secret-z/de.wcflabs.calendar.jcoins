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

use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\user\jcoins\UserJCoinsStatementHandler;
use wcf\system\WCF;

/**
 * JCoins listener for participation action.
 */
class JCoinsCalenderParticipationActionListener implements IParameterizedEventListener
{
    /**
     * @inheritdoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!MODULE_JCOINS) {
            return;
        }

        $action = $eventObj->getActionName();

        // new decision, either by user or owner / mod
        if ($action == 'create') {
            $returnValues = $eventObj->getReturnValues();
            $participation = $returnValues['returnValues'];
            $params = $eventObj->getParameters();

            // only on yes
            if ($params['data']['decision'] != 'yes') {
                return;
            }
            //if ($participation->userID != WCF::getUser()->userID) return;

            $eventDate = $participation->getEventDate();
            $event = $eventDate->getEvent();

            UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.fee', $eventDate, [
                'amount' => -1 * $event->jCoinsFee,
                'userID' => $participation->userID,
            ]);

            UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.fee.owner', $eventDate, [
                'amount' => $event->jCoinsFee,
                'userID' => $event->userID,
                'participant' => $participation->username,
            ]);
        }

        // deletion by user
        if ($action == 'delete') {
            $objects = $eventObj->getObjects();
            $participation = $objects[0]->getDecoratedObject();
            $eventDate = $participation->getEventDate();
            $event = $eventDate->getEvent();

            if ($event->jCoinsReFee && $participation->decision == 'yes') {
                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee', $eventDate, [
                    'amount' => $event->jCoinsReFee,
                    'userID' => $participation->userID,
                ]);

                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee.owner', $eventDate, [
                    'amount' => -1 * $event->jCoinsReFee,
                    'userID' => $event->userID,
                    'participant' => $participation->username,
                ]);
            }
        }

        // deletion by owner / mod
        if ($action == 'removeParticipant') {
            $objects = $eventObj->getObjects();
            $participation = $objects[0]->getDecoratedObject();
            $eventDate = $participation->getEventDate();
            $event = $eventDate->getEvent();

            if ($event->jCoinsReFee) {
                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee', $eventDate, [
                    'amount' => $event->jCoinsReFee,
                    'userID' => $participation->userID,
                ]);

                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee.owner', $eventDate, [
                    'amount' => -1 * $event->jCoinsReFee,
                    'userID' => $event->userID,
                    'participant' => $participation->username,
                ]);
            }
        }

        // decision changed, only by user
        if ($action == 'update') {
            $objects = $eventObj->getObjects();
            $participation = $objects[0]->getDecoratedObject();
            $eventDate = $participation->getEventDate();
            $event = $eventDate->getEvent();

            $params = $eventObj->getParameters();

            $newDecision = $params['data']['decision'];
            $oldDecision = $participation->decision;
            if ($newDecision == $oldDecision) {
                return;
            }

            // participation
            if ($newDecision == 'yes' && $event->jCoinsFee) {
                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.fee', $eventDate, [
                    'amount' => -1 * $event->jCoinsFee,
                    'userID' => WCF::getUser()->userID,
                ]);

                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.fee.owner', $eventDate, [
                    'amount' => $event->jCoinsFee,
                    'userID' => $event->userID,
                    'participant' => WCF::getUser()->username,
                ]);
            }

            // participation withdrawn
            if ($oldDecision == 'yes' && $event->jCoinsReFee) {
                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee', $eventDate, [
                    'amount' => $event->jCoinsReFee,
                    'userID' => WCF::getUser()->userID,
                ]);

                UserJCoinsStatementHandler::getInstance()->create('de.wcflabs.jcoins.statement.calendar.refee.owner', $eventDate, [
                    'amount' => -1 * $event->jCoinsReFee,
                    'userID' => $event->userID,
                    'participant' => WCF::getUser()->username,
                ]);
            }
        }
    }
}
