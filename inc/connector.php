<?php
/**
 * @author      Tero Tasanen (tero.tasanen@crm-service.fi)
 * @copyright   Copyright (c) 2018 CRM-service Oy
 * @license     https://www.gnu.org/licenses/old-licenses/gpl-2.0-standalone.html
 * @link        https://crm-service.fi
 *
 * CRM-service API Wrapper for the CRM-service WordPress plugin.
 */

declare(strict_types=1);
namespace CRMservice;

class CRMserviceConnector {

  private $crm_url;
  private $crm_apikey;
  private $crm_session;
  private $crm_managers = [];
  private $enabled_modules = ['Leads', 'Potentials'];
  private $enabled_uitypes = [1 => 'String', 2 => 'String', 5 => 'Date', 7 => 'Number', 9 => 'Percent', 11 => 'Phone', 13 => 'Email', 15 => 'Select', 16 => 'Select', 17 => 'Website', 19 => 'String', 20 => 'String', 21 => 'String', 22 => 'String', 24 => 'Address', 33 => 'MultiSelect', 56 => 'Checkbox', 63 => 'Time', 792 => 'DateTime', 53 => 'Number', 700 => 'Number', 702 => 'Number', 704 => 'Number', 101 => 'Number', 510 => 'Number', 701 => 'Number', ];

  public function __construct(string $url, string $api_key) {

    if (!$url) {
      throw new CRMserviceConnectorException('CRMserviceConnector cannot be initialized without CRM-service url');
    }

    if (!$api_key) {
      throw new CRMserviceConnectorException('CRMserviceConnector cannot be initialized without CRM-service API key');
    }

    $this->crm_apikey = $api_key;
    $this->crm_url = $url;

    foreach(['session', 'field', 'entity', 'picklist'] as $manager_name) {
      $this->managers[$manager_name] = $this->initCrmManager($manager_name);
    }

    try {
      $this->crm_session = $this->manager('session')->createSession($this->crm_apikey);
    } catch(\SoapFault $e) {
      // fail silently
      set_error_handler('var_dump', 0); // Never called because of empty mask.
      @trigger_error("");
      restore_error_handler();
    }

    if ( is_soap_fault( $this->crm_session ) ) {
      throw new CRMserviceConnectorException('CRMserviceConnector has SoapFault');
    }
  }

  public function __destruct() {
    try {
      $response = $this->callCrmMethod('session', 'destroySession', [$this->crm_session]);
    } catch(\Exception $_) {
      // ignore silently
    } catch( \TypeError $_ ) {
      // ignore silently
    }
  }

  /**
   * Gets a list of enabled target modules for saving the data.
   *
   * @return    array
   */
  public function getModules() : array {
    return $this->enabled_modules;
  }

  /**
   * Gets a list of available fields for module
   *
   * @param       string  $module    Name of the module
   * @param       string  $locale    Used locale for field labels
   * @return      array
   */
  public function getFieldsFor(string $module, string $locale = 'en_us') : array {
    $this->validateModule($module);

    try {
      $raw_fields = $this->callCrmMethod('field', 'getCrmFields', [$module]);
    } catch( \TypeError $_ ) {
      return array();
    }
    $fields = [];

    foreach($raw_fields as $rf) {
      if (!$this->isFieldAllowed($rf)) continue;

      $field = new \StdClass();
      $field->uitype = $rf->uitype;
      $field->label_orig = $field->label = $rf->label;
      $label_translations = \json_decode($rf->translations, true);

      if ($label_translations && \array_key_exists($locale, $label_translations)) {
        $field->label = $label_translations[$locale];
      }

      $field->name = $rf->name;
      $field->type = $this->getFieldType($rf);

      $field->picklist_values = null;
      if ($rf->picklist_id) {
        $field->picklist_values = $this->getPicklistValues($rf->picklist_id, $locale);
      }

      $fields[] = $field;
    }

    return $fields;
  }

  /**
   * Save data to CRM
   *
   * The data must be an associative array with field name as
   * key and corresponding value as value.
   *
   * @param       string  $module    Name of the module
   * @param       array   $data      Data
   * @return      bool
   */
  public function saveData(string $module, array $data) : bool {
    $this->validateModule($module);

    $fields = $this->getFieldsFor($module);
    $field_names = [];

    foreach($fields as $field) $field_names[] = $field->name;

    foreach($data as $key => $value) {
      if (!\in_array($key, $field_names)) {
        throw new CRMserviceConnectorException("Invalid field name: {$key}");
      }
    }

    $response = $this->callCrmMethod('entity', 'bulkEntitySave', [$module, \json_encode([$data])]);

    return \count(\json_decode($response)) > 0;
  }



  // Private methods used internally by this class

  private function getPicklistValues(int $picklist_id, string $lang) : array {
    $values = $this->callCrmMethod('picklist', 'getPicklistValues', [$picklist_id, $lang]);

    $return = [];
    foreach($values as $value) {
      $return[$value->value] = $value->label;
    }

    return $return;
  }

  private function getFieldType(CrmField $field) : string {
    return $this->enabled_uitypes[intval($field->uitype)];
  }

  private function isFieldAllowed(CrmField $field) : bool {
    return \array_key_exists(intval($field->uitype), $this->enabled_uitypes);
  }

  private function manager(string $type) {
    return $this->managers[$type];
  }

  private function initCrmManager(string $type) {
    $manager_name = "{$type}Manager";
    $class_name = "\CRMservice\\{$manager_name}";

    return new $class_name("{$this->crm_url}/CrmIntegrationWebservice/service.php?class={$manager_name}&wsdl");
  }

  private function validateModule(string $module) : bool {
    if (!\in_array($module, $this->getModules(), true)) {
      throw new CRMserviceConnectorException("Invalid module: {$module}");
    }

    return true;
  }

  private function callCrmMethod(string $manager, string $method, array $args = []) {
    return $this->manager($manager)->$method($this->crm_session, ...$args);
  }
}


class CRMserviceConnectorException extends \ErrorException {
}

/* Wrappers for \SoapClient */
class CrmIntegrationWebserviceSession {
  public $sessionID;
}
class CrmField {
  public $active;
  public $custom;
  public $datatype;
  public $label;
  public $lenght;
  public $name;
  public $picklist_id;
  public $readonly;
  public $required;
  public $translations;
  public $uitype;
}
class PicklistValue {
  public $id;
  public $label;
  public $value;
}

class CrmSoapClient extends \SoapClient {

  private $classmap = [
    'CrmIntegrationWebserviceSession' => '\CRMservice\CrmIntegrationWebserviceSession',
    'CrmField' => '\CRMservice\CrmField',
    'PicklistValue' => '\CRMservice\PicklistValue'
  ];

  public function __construct(string $wsdl, array $options = []) {
    foreach($this->classmap as $key => $value) {
      if(!isset($options['classmap'][$key])) {
        $options['classmap'][$key] = $value;
      }
    }

    if(isset($options['headers'])) {
      $this->__setSoapHeaders($options['headers']);
    }

    $options['exceptions'] = true;
    $options['trace'] = 1;

    try {
      parent::__construct($wsdl, $options);
    } catch(\SoapFault $e) {
      // fail silently
      set_error_handler('var_dump', 0); // Never called because of empty mask.
      @trigger_error("");
      restore_error_handler();
    }
  }
}

class entityManager extends CrmSoapClient {
  public function bulkEntitySave(\CRMservice\CrmIntegrationWebserviceSession $session, string $type, string $datas) {
    return $this->__soapCall('bulkEntitySave', [$session, $type, $datas], ['uri' => 'https://crmservice.fi/CrmIntegrationWebservice', 'soapaction' => '']);
  }
}

class sessionManager extends CrmSoapClient {
  public function createSession(string $apiKey) {
    return $this->__soapCall('createSession', [$apiKey], ['uri' => 'https://crmservice.fi/CrmIntegrationWebservice', 'soapaction' => '']);
  }

  public function destroySession(\CRMservice\CrmIntegrationWebserviceSession $session) {
    return $this->__soapCall('destroySession', [$session], ['uri' => 'https://crmservice.fi/CrmIntegrationWebservice', 'soapaction' => '']);
  }
}

class fieldManager extends CrmSoapClient {
  public function getCrmFields(\CRMservice\CrmIntegrationWebserviceSession $session, string $module) {
    return $this->__soapCall('getCrmFields', [$session, $module], ['uri' => 'https://crmservice.fi/CrmIntegrationWebservice', 'soapaction' => '']);
  }
}

class picklistManager extends CrmSoapClient {
  public function getPicklistValues(\CRMservice\CrmIntegrationWebserviceSession $session, int $picklist_id, string $language) {
    return $this->__soapCall('getPicklistValues', [$session, $picklist_id, $language], ['uri' => 'https://crmservice.fi/CrmIntegrationWebservice', 'soapaction' => '']);
  }

}
