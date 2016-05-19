<?php
/***********************************************
* File      :   webservicedevice.php
* Project   :   Z-Push
* Descr     :   Device remote administration tasks
*               used over webservice e.g. by the
*               Mobile Device Management Plugin for Kopano.
*
* Created   :   23.12.2011
*
* Copyright 2007 - 2015 Zarafa Deutschland GmbH
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License, version 3,
* as published by the Free Software Foundation with the following additional
* term according to sec. 7:
*
* According to sec. 7 of the GNU Affero General Public License, version 3,
* the terms of the AGPL are supplemented with the following terms:
*
* "Zarafa" is a registered trademark of Zarafa B.V.
* "Z-Push" is a registered trademark of Zarafa Deutschland GmbH
* The licensing of the Program under the AGPL does not imply a trademark license.
* Therefore any rights, title and interest in our trademarks remain entirely with us.
*
* However, if you propagate an unmodified version of the Program you are
* allowed to use the term "Z-Push" to indicate that you distribute the Program.
* Furthermore you may use our trademarks where it is necessary to indicate
* the intended purpose of a product or service provided you use it in accordance
* with honest practices in industrial or commercial matters.
* If you want to propagate modified versions of the Program under the name "Z-Push",
* you may only do so if you have a written permission by Zarafa Deutschland GmbH
* (to acquire a permission please contact Zarafa at trademark@zarafa.com).
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* Consult LICENSE file for details
************************************************/
include ('lib/utils/zpushadmin.php');

class WebserviceDevice {

    /**
     * Returns a list of all known devices of the Request::GetGETUser()
     *
     * @access public
     * @return array
     */
    public function ListDevicesDetails() {
        $user = Request::GetGETUser();
        $devices = ZPushAdmin::ListDevices($user);
        $output = array();

        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::ListDevicesDetails(): found %d devices of user '%s'", count($devices), $user));
        ZPush::GetTopCollector()->AnnounceInformation(sprintf("Retrieved details of %d devices", count($devices)), true);

        foreach ($devices as $devid)
            $output[] = ZPushAdmin::GetDeviceDetails($devid, $user);

        return $output;
    }

    /**
     * Remove all state data for a device of the Request::GetGETUser()
     *
     * @param string    $deviceId       the device id
     *
     * @access public
     * @return boolean
     * @throws SoapFault
     */
    public function RemoveDevice($deviceId) {
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::RemoveDevice('%s'): remove device state data of user '%s'", $deviceId, Request::GetGETUser()));

        if (! ZPushAdmin::RemoveDevice(Request::GetGETUser(), $deviceId)) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }

        ZPush::GetTopCollector()->AnnounceInformation(sprintf("Removed device id '%s'", $deviceId), true);
        return true;
    }

    /**
     * Marks a device of the Request::GetGETUser() to be remotely wiped
     *
     * @param string    $deviceId       the device id
     *
     * @access public
     * @return boolean
     * @throws SoapFault
     */
    public function WipeDevice($deviceId) {
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::WipeDevice('%s'): mark device of user '%s' for remote wipe", $deviceId, Request::GetGETUser()));

        if (! ZPushAdmin::WipeDevice(Request::GetAuthUser(), Request::GetGETUser(), $deviceId)) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }

        ZPush::GetTopCollector()->AnnounceInformation(sprintf("Wipe requested - device id '%s'", $deviceId), true);
        return true;
    }

    /**
     * Marks a device of the Request::GetGETUser() for resynchronization.
     *
     * @param string    $deviceId       the device id
     *
     * @access public
     * @return boolean
     * @throws SoapFault
     */
    public function ResyncDevice($deviceId) {
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::ResyncDevice('%s'): mark device of user '%s' for resynchronization", $deviceId, Request::GetGETUser()));

        if (! ZPushAdmin::ResyncDevice(Request::GetGETUser(), $deviceId)) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }

        ZPush::GetTopCollector()->AnnounceInformation(sprintf("Resync requested - device id '%s'", $deviceId), true);
        return true;
    }

    /**
     * Marks a folder of a device of the Request::GetGETUser() for resynchronization.
     *
     * @param string    $deviceId       the device id
     * @param string    $folderId       the folder id
     *
     * @access public
     * @return boolean
     * @throws SoapFault
     */
    public function ResyncFolder($deviceId, $folderId) {
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        $folderId = preg_replace("/[^A-Za-z0-9]/", "", $folderId);
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::ResyncFolder('%s','%s'): mark folder of a device of user '%s' for resynchronization", $deviceId, $folderId, Request::GetGETUser()));

        if (! ZPushAdmin::ResyncFolder(Request::GetGETUser(), $deviceId, $folderId)) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }

        ZPush::GetTopCollector()->AnnounceInformation(sprintf("Folder resync requested - device id '%s', folder id '%s", $deviceId, $folderId), true);
        return true;
    }

    /**
     * Returns a list of all additional folders of the given device and the Request::GetGETUser().
     *
     * @param string    $deviceId       device id that should be listed.
     *
     * @access public
     * @return array
     */
    public function AdditionalFolderList($deviceId) {
        $user = Request::GetGETUser();
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        $folders = ZPushAdmin::AdditionalFolderList($user, $deviceId);
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::AdditionalFolderList(): found %d folders for device '%s' of user '%s'", count($folders), $deviceId, $user));
        ZPush::GetTopCollector()->AnnounceInformation(sprintf("Retrieved details of %d folders", count($folders)), true);

        return $folders;
    }

    /**
     * Adds an additional folder to the given device and the Request::GetGETUser().
     *
     * @param string    $deviceId       device id the folder should be added to.
     * @param string    $add_store      the store where this folder is located, e.g. "SYSTEM" (for public folder) or an username/email address.
     * @param string    $add_folderid   the folder id of the additional folder.
     * @param string    $add_name       the name of the additional folder (has to be unique for all folders on the device).
     * @param string    $add_type       AS foldertype of SYNC_FOLDER_TYPE_USER_*
     *
     * @access public
     * @return boolean
     */
    public function AdditionalFolderAdd($deviceId, $add_store, $add_folderid, $add_name, $add_type) {
        $user = Request::GetGETUser();
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        $add_folderid = preg_replace("/[^A-Za-z0-9]/", "", $add_folderid);
        $add_type = preg_replace("/[^0-9]/", "", $add_type);

        $status = ZPushAdmin::AdditionalFolderAdd($user, $deviceId, $add_store, $add_folderid, $add_name, $add_type);
        if (!$status) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::AdditionalFolderAdd(): added folder for device '%s' of user '%s': %s", $deviceId, $user, Utils::PrintAsString($status)));
        ZPush::GetTopCollector()->AnnounceInformation("Added additional folder", true);

        return $status;
    }

    /**
     * Updates the name of an additional folder to the given device and the Request::GetGETUser().
     *
     * @param string    $deviceId       device id of where the folder should be updated.
     * @param string    $add_folderid   the folder id of the additional folder.
     * @param string    $add_name       the name of the additional folder (has to be unique for all folders on the device).
     *
     * @access public
     * @return boolean
     */
    public function AdditionalFolderEdit($deviceId, $add_folderid, $add_name) {
        $user = Request::GetGETUser();
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        $add_folderid = preg_replace("/[^A-Za-z0-9]/", "", $add_folderid);

        $status = ZPushAdmin::AdditionalFolderEdit($user, $deviceId, $add_folderid, $add_name);
        if (!$status) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::AdditionalFolderEdit(): added folder for device '%s' of user '%s': %s", $deviceId, $user, Utils::PrintAsString($status)));
        ZPush::GetTopCollector()->AnnounceInformation("Edited additional folder", true);

        return $status;
    }

    /**
     * Removes an additional folder from the given device and the Request::GetGETUser().
     *
     * @param string    $deviceId       device id of where the folder should be removed.
     * @param string    $add_folderid   the folder id of the additional folder.
     *
     * @access public
     * @return boolean
     */
    public function AdditionalFolderRemove($deviceId, $add_folderid) {
        $user = Request::GetGETUser();
        $deviceId = preg_replace("/[^A-Za-z0-9]/", "", $deviceId);
        $add_folderid = preg_replace("/[^A-Za-z0-9]/", "", $add_folderid);

        $status = ZPushAdmin::AdditionalFolderRemove($user, $deviceId, $add_folderid);
        if (!$status) {
            ZPush::GetTopCollector()->AnnounceInformation(ZLog::GetLastMessage(LOGLEVEL_ERROR), true);
            throw new SoapFault("ERROR", ZLog::GetLastMessage(LOGLEVEL_ERROR));
        }
        ZLog::Write(LOGLEVEL_INFO, sprintf("WebserviceDevice::AdditionalFolderRemove(): removed folder for device '%s' of user '%s': %s", $deviceId, $user, Utils::PrintAsString($status)));
        ZPush::GetTopCollector()->AnnounceInformation("Removed additional folder", true);

        return $status;
    }
}
