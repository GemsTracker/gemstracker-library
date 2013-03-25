<?php

/**
 * Handles call like an openRosa compliant server. Implements the api as described on
 * https://bitbucket.org/javarosa/javarosa/wiki/OpenRosaAPI
 *
 * To implement, place the controller in the right directory and allow access without login to the
 * following actions:
 *  formList        - Lists the forms available
 *  submission      - Handles receiving a submitted form
 *  download        - Download a form
 */
class Gems_Default_OpenrosaAction extends Gems_Controller_BrowseEditAction
{
    /**
     * This holds the path to the location where the form definitions will be stored.
     * Will be set on init to: GEMS_ROOT_DIR . '/var/uploads/openrosa/forms/';
     *
     * @var string
     */
    public $formDir;
    
    /**
     * This holds the path to the location where the uploaded responses and their 
     * backups will be stored.
     * 
     * Will be set on init to: GEMS_ROOT_DIR . '/var/uploads/openrosa/';
     *
     * @var string
     */
    public $responseDir;

    /**
     * @var Zend_Auth
     */
    protected $auth;

    /**
     * This lists the actions that need http-auth. Only applies to the actions that
     * the openRosa application needs.
     *
     * ODK Collect: http://code.google.com/p/opendatakit/wiki/ODKCollect
     *
     * @var array Array of actions
     */
    protected $authActions = array('formlist', 'submission', 'download');
    
    /**
     * This can be used to generate barcodes, use the action 
     * 
     * /openrosa/barcode/code/<tokenid>
     * 
     * example:
     * /openrosa/barocde/code/22pq-grkq
     * 
     * The image will be a png
     */
    public function barcodeAction()
    {
        $code = $this->getRequest()->getParam('code', 'empty');
        Zend_Layout::getMvcInstance()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        
        $barcodeOptions = array('text' => $code);
        $rendererOptions = array();
        $barcode = Zend_Barcode::render('code128', 'image', $barcodeOptions, $rendererOptions);
        $barcode->render();
    }

    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getOpenRosaFormModel();

        $model->set('TABLE_ROWS', 'label', $this->_('Responses'), 'elementClass', 'Exhibitor');
        
        return $model;
    }

    /**
     * This action should serve the right form to the downloading application
     * it should also handle expiration / availability of forms
     */
    public function downloadAction()
    {
        $filename = $this->getRequest()->getParam('form');
        $filename = basename($filename);    //Strip paths

        $file = $this->formDir . $filename;

        if (!empty($filename) && file_exists($file)) {
            $this->getHelper('layout')->disableLayout();
            $this->getResponse()->setHeader('Content-Type', 'application/xml; charset=utf-8');
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        } else {
            $this->getResponse()->setHttpResponseCode(404);
            $this->html->div("form $filename not found");
        }
    }

    /**
     * Accessible via formList as defined in the menu and standard for openRosa clients
     */
    public function formlistAction()
    {
        //first create the baseurl of the form http(s)://projecturl/openrosa/download/form/
        $helper  = new Zend_View_Helper_ServerUrl();
        $baseUrl = $helper->serverUrl() . Zend_Controller_Front::getInstance()->getBaseUrl() . '/openrosa/download/form/';

        //As we don't have forms defined yet, we pass in an array, but ofcourse this should be dynamic
        //and come from a helper method
        $model = $this->getModel();
        $rawForms = $model->load(array('gof_form_active'=>1));
        foreach($rawForms as $form) {
            $forms[] = array(
                'formID'      => $form['gof_form_id'],
                'name'        => $form['gof_form_title'],
                'version'     => $form['gof_form_version'],
                'hash'        => md5($form['gof_form_id'].$form['gof_form_version']),
                'downloadUrl' => $baseUrl . $form['gof_form_xml']
            );
        }
        
        //Now make it a rosaresponse
        $this->makeRosaResponse();

        $xml = $this->getXml('xforms xmlns="http://openrosa.org/xforms/xformsList"');
        foreach ($forms as $form) {
            $xform = $xml->addChild('xform');
            foreach ($form as $key => $value) {
                $xform->addChild($key, $value);
            }
        }

        echo $xml->asXML();
    }

    public function getTopic($count = 1)
    {
        return 'OpenRosa Form';
    }

    public function getTopicTitle()
    {
        return 'OpenRosa Forms';
    }
    
    /**
     * Create an xml response
     *
     * @param string $rootNode
     * @return SimpleXMLElement
     */
    protected function getXml($rootNode)
    {
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');

        $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNode />");

        return $xml;
    }

    public function init()
    {
        parent::init();

        $this->responseDir = GEMS_ROOT_DIR . '/var/uploads/openrosa/';
        $this->formDir = $this->responseDir . 'forms/';
    }

    /**
     * Each rosa response should have the x-openrosa-version header and disable the layout to allow
     * for xml repsonses if needed. We don't need a menu etc. on the openrosa responses
     */
    protected function makeRosaResponse()
    {
        $this->getHelper('layout')->disableLayout();
        $this->getResponse()->setHeader('X-OpenRosa-Version', '1.0');
    }
    
    /**
     * Handles receiving and storing the data from a form, files are stored on actual upload process
     * this only handles storing form data and can be used for resubmission too.
     * 
     * @param type $xmlFile
     * @return string DeviceID or false on failure
     */
    private function processReceivedForm($answerXmlFile)
    {
        //Log what we received
        $log     = Gems_Log::getLogger();
        //$log->log(print_r($xmlFile, true), Zend_Log::ERR);

        $xml = simplexml_load_file($answerXmlFile);

        $formId      = $xml->attributes()->id;
        $formVersion = $xml->attributes()->version;
        //Lookup what form belongs to this formId and then save
        $model       = $this->getModel();
        $filter      = array(
            //'gof_form_active'       => 1,
            'gof_form_id'      => $formId,
            'gof_form_version' => $formVersion,
        );
        if ($formData          = $model->loadFirst($filter)) {
            $form = new OpenRosa_Tracker_Source_OpenRosa_Form($this->formDir . $formData['gof_form_xml']);
            $form->saveAnswer($answerXmlFile);

            $deviceId = $xml->DeviceId[0];
            return $deviceId;
        } else {
            return false;
        }
    }
    

    /**
     * Implements HTTP Basic auth
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $action = strtolower($this->getRequest()->getActionName());
        if (in_array($action, $this->authActions)) {
            $auth       = Zend_Auth::getInstance();
            $this->auth = $auth;

            if (!$auth->hasIdentity()) {
                $config = array(
                    'accept_schemes' => 'basic',
                    'realm'          => GEMS_PROJECT_NAME,
                    'nonce_timeout'  => 3600,
                );
                $adapter         = new Zend_Auth_Adapter_Http($config);
                $basicResolver   = new Zend_Auth_Adapter_Http_Resolver_File();

                //This is a basic resolver, use username:realm:password
                //@@TODO: move to a better db stored authentication system
                
                $basicResolver->setFile(GEMS_ROOT_DIR . '/var/settings/pwd.txt');
                $adapter->setBasicResolver($basicResolver);
                $request  = $this->getRequest();
                $response = $this->getResponse();

                assert($request instanceof Zend_Controller_Request_Http);
                assert($response instanceof Zend_Controller_Response_Http);

                $adapter->setRequest($request);
                $adapter->setResponse($response);

                $result = $auth->authenticate($adapter);
                
                if (!$result->isValid()) {
                    $adapter->getResponse()->sendResponse();
                    print 'Unauthorized';
                    exit;
                }
            }
        }
    }
    
    public function scanresponsesAction()
    {
        $model = $this->getModel();

        //Perform a scan of the form directory, to update the database of forms
        $eDir = dir($this->responseDir);

        $formCnt  = 0;
        $addCnt   = 0;
        $rescan = $this->getRequest()->getParam('rescan', false);
        while (false !== ($filename = $eDir->read())) {
            $ext = substr($filename, -4);
            if ($ext == '.xml' || ($ext == '.bak' && $rescan)) {
                if ($rescan) {
                    $oldname = $filename;
                    $filename = substr($oldname, 0, -4) . '.xml';
                    rename($this->responseDir . $oldname, $this->responseDir . $filename);
                }
                $files[] = $filename;
                $formCnt++;                
            }
        }

        foreach ($files as $filename) {
            $result = $this->processReceivedForm($this->responseDir . $filename);
            if ($result !== false) {
                $addCnt++;
            }
        }
        $cache = GemsEscort::getInstance()->cache;
        $cache->clean();

        $this->html[] = sprintf('Checked %s responses and added %s responses', $formCnt, $addCnt);
    }

    /**
     * Accepts the form
     *
     * Takes two roundtrips:
     * - first we get a HEAD request that should be answerd with
     *   responsecode 204
     * - then we get a post that only submits $_FILES (so actual $_POST will be empty)
     *   this will be an xml file for the actuel response and optionally images and/or video
     *   proper responses are
     *      201 received and stored
     *      202 received ok, not stored
     */
    public function submissionAction()
    {
        $this->makeRosaResponse();

        if ($this->getRequest()->isHead()) {
            $this->getResponse()->setHttpResponseCode(204);
        } elseif ($this->getRequest()->isPost()) {
            //Post
            // We get $_FILES variable holding the formresults as xml and all possible
            // attachments like photo's and video's
            $upload = new Zend_File_Transfer_Adapter_Http();

            // We should really add some validators here see http://framework.zend.com/manual/en/zend.file.transfer.validators.html
            // Returns all known internal file information
            $files = $upload->getFileInfo();

            foreach ($files as $file => $info) {
                // file uploaded ?
                if (!$upload->isUploaded($file)) {
                    print "Why haven't you uploaded the file ?";
                    continue;
                }

                // validators are ok ?
                if (!$upload->isValid($file)) {
                    print "Sorry but $file is not what we wanted";
                    continue;
                }
            }

            //Dit moet een filter worden (rename filter) http://framework.zend.com/manual/en/zend.file.transfer.filters.html
            $upload->setDestination($this->responseDir);

            //Hier moeten we denk ik eerst de xml_submission_file uitlezen, en daar
            //iets mee doen
            if ($upload->receive('xml_submission_file')) {
                $xmlFile = $upload->getFileInfo('xml_submission_file');
                $answerXmlFile = $xmlFile['xml_submission_file']['tmp_name'];
                $deviceId = $this->processReceivedForm($answerXmlFile);
                if ($deviceId === false) {
                    //form not accepted!
                    foreach ($xml->children() as $child) {
                        $log->log($child->getName() . ' -> ' . $child, Zend_Log::ERR);
                    }
                } else {                
                    //$log->log(print_r($files, true), Zend_Log::ERR);
                    //$log->log($deviceId, Zend_Log::ERR);
                    foreach ($upload->getFileInfo() as $file => $info) {
                        if ($info['received'] != 1) {
                            //Rename to deviceid_md5(time)_filename
                            //@@TODO: move to form subdir, for better separation
                            $upload->addFilter('Rename', $deviceId . '_' . md5(time()) . '_' . $info['name'], $file);
                        }
                    }

                    //Now receive the other files
                    if (!$upload->receive()) {
                        $messages = $upload->getMessages();
                        echo implode("\n", $messages);
                    }
                    $this->getResponse()->setHttpResponseCode(201); //Form received ok                
                }
            }
        }
    }
}