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
use wcf\system\WCF;

/**
 * JCoins event addForm listener.
 */
class JCoinsCalenderAddFormListener implements IParameterizedEventListener
{
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
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        $this->eventObj = $eventObj;
        $this->{$eventName}();
    }

    /**
     * Handles the readData event. Only in Edit!
     */
    protected function readData()
    {
        if (empty($_POST)) {
            $this->jCoinsFee = $this->eventObj->event->jCoinsFee;
            $this->jCoinsReFee = $this->eventObj->event->jCoinsReFee;
        }
    }

    /**
     * Handles the assignVariables event.
     */
    protected function assignVariables()
    {
        WCF::getTPL()->assign([
            'jCoinsFee' => $this->jCoinsFee,
            'jCoinsReFee' => $this->jCoinsReFee,
        ]);
    }

    /**
     * Handles the readFormParameters event.
     */
    protected function readFormParameters()
    {
        if (isset($_POST['jCoinsFee'])) {
            $this->jCoinsFee = \intval($_POST['jCoinsFee']);
        }
        if (isset($_POST['jCoinsReFee'])) {
            $this->jCoinsReFee = \intval($_POST['jCoinsReFee']);
        }
    }

    /**
     * Handles the validate event.
     */
    protected function validate()
    {
        // nothing ufn
    }

    /**
     * Handles the save event.
     */
    protected function save()
    {
        $this->eventObj->additionalFields = \array_merge($this->eventObj->additionalFields, [
            'jCoinsFee' => $this->jCoinsFee,
            'jCoinsReFee' => $this->jCoinsReFee,
        ]);
    }
}
