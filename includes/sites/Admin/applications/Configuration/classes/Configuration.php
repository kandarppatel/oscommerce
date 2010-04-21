<?php
/*
  osCommerce Online Merchant $osCommerce-SIG$
  Copyright (c) 2010 osCommerce (http://www.oscommerce.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License v2 (1991)
  as published by the Free Software Foundation.
*/

  class OSCOM_Site_Admin_Application_Configuration_Configuration {
    public static function get($id) {
      $OSCOM_Database = OSCOM_Registry::get('Database');

      $Qcfg = $OSCOM_Database->query('select * from :table_configuration where configuration_id = :configuration_id');
      $Qcfg->bindInt(':configuration_id', $id);
      $Qcfg->execute();

      $result = $Qcfg->toArray();

      return $result;
    }

    public static function getAll($group_id) {
      $OSCOM_Database = OSCOM_Registry::get('Database');

      $Qcfg = $OSCOM_Database->query('select * from :table_configuration where configuration_group_id = :configuration_group_id order by sort_order');
      $Qcfg->bindInt(':configuration_group_id', $group_id);
      $Qcfg->execute();

      $result = array('entries' => array());

      while ( $Qcfg->next() ) {
        $result['entries'][] = $Qcfg->toArray();

        if ( !osc_empty($Qcfg->value('use_function')) ) {
          $result['entries'][sizeof($result['entries'])-1]['configuration_value'] = osc_call_user_func($Qcfg->value('use_function'), $Qcfg->value('configuration_value'));
        }
      }

      $result['total'] = $Qcfg->numberOfRows();

      return $result;
    }

    public static function find($search) {
      $OSCOM_Database = OSCOM_Registry::get('Database');

      $in_group = array();

      foreach ( osc_toObjectInfo(self::getAllGroups())->get('entries') as $group ) {
        $in_group[] = $group['configuration_group_id'];
      }

      $result = array('entries' => array());

      $Qcfg = $OSCOM_Database->query('select * from :table_configuration where (configuration_key like :configuration_key or configuration_value like :configuration_value) and configuration_group_id in (:configuration_group_id) order by configuration_key');
      $Qcfg->bindValue(':configuration_key', '%' . $search . '%');
      $Qcfg->bindValue(':configuration_value', '%' . $search . '%');
      $Qcfg->bindRaw(':configuration_group_id', implode(',', $in_group));
      $Qcfg->execute();

      while ( $Qcfg->next() ) {
        $result['entries'][] = $Qcfg->toArray();

        if ( !osc_empty($Qcfg->value('use_function')) ) {
          $result['entries'][sizeof($result['entries'])-1]['configuration_value'] = osc_call_user_func($Qcfg->value('use_function'), $Qcfg->value('configuration_value'));
        }
      }

      $result['total'] = $Qcfg->numberOfRows();

      return $result;
    }

    public static function save($parameter) {
      $OSCOM_Database = OSCOM_Registry::get('Database');

      $Qcfg = $OSCOM_Database->query('select configuration_id from :table_configuration where configuration_key = :configuration_key');
      $Qcfg->bindValue(':configuration_key', key($parameter));
      $Qcfg->execute();

      if ( $Qcfg->numberOfRows() === 1 ) {
        $Qupdate = $OSCOM_Database->query('update :table_configuration set configuration_value = :configuration_value, last_modified = now() where configuration_key = :configuration_key');
        $Qupdate->bindValue(':configuration_value', $parameter[key($parameter)]);
        $Qupdate->bindValue(':configuration_key', key($parameter));
        $Qupdate->setLogging(null, $Qcfg->valueInt('configuration_id'));
        $Qupdate->execute();

        if ( $Qupdate->affectedRows() ) {
          OSCOM_Cache::clear('configuration');

          return true;
        }
      }

      return false;
    }

    public static function getAllGroups() {
      $OSCOM_Database = OSCOM_Registry::get('Database');

      $Qgroups = $OSCOM_Database->query('select * from :table_configuration_group where visible = 1 order by sort_order, configuration_group_title');
      $Qgroups->execute();

      $result = array('entries' => array());

      while ( $Qgroups->next() ) {
        $result['entries'][] = $Qgroups->toArray();
      }

      $result['total'] = $Qgroups->numberOfRows();

      return $result;
    }

    public static function getGroupTitle($id) {
      $OSCOM_Database = OSCOM_Registry::get('Database');

      $Qcg = $OSCOM_Database->query('select configuration_group_title from :table_configuration_group where configuration_group_id = :configuration_group_id');
      $Qcg->bindInt(':configuration_group_id', $id);
      $Qcg->execute();

      $result = $Qcg->value('configuration_group_title');

      return $result;
    }
  }
?>
