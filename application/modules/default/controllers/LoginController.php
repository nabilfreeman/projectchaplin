<?php
/**
 * This file is part of Project Chaplin.
 *
 * Project Chaplin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Project Chaplin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Project Chaplin. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    Project Chaplin
 * @author     Dan Dart
 * @copyright  2012-2013 Project Chaplin
 * @license    http://www.gnu.org/licenses/agpl-3.0.html GNU AGPL 3.0
 * @version    git
 * @link       https://github.com/dandart/projectchaplin
**/
class LoginController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $this->view->strTitle = 'Login - Chaplin';
        $form = new default_Form_Login();

        if ($this->_helper->flashMessenger->hasMessages()) {
            $this->view->messages = $this->_helper->flashMessenger->getMessages();
        }

        if(!$this->_request->isPost()) {
            return $this->view->assign('form', $form);
        }

        $post = $this->_request->getPost();

        $username = $post['username'];
        $password = $post['password'];

        if(isset($post['Register'])) {
            return $this->_redirect('/login/register');
        }

        if (isset($post['Forgot'])) {
            return $this->_redirect('/login/forgot');
        }
        
        if(!$form->isValid($post)) {
            return $this->view->assign('form', $form);
        }
        
        if(!isset($post['Login'])) {
            $form->password->addError('Invalid Action');
            return $this->view->assign('form', $form);
        }
            
        $adapter = new Chaplin_Auth_Adapter_Database($username, $password);
        $auth = Chaplin_Auth::getInstance();
        $auth->authenticate($adapter);
        if(!$auth->hasIdentity()) {
            $form->password->addError('Wrong username or password. Want to try again?');
            $form->markAsError();
            return $this->view->assign('form', $form);
        }

        $login = new Zend_Session_Namespace('login');
        if (!is_null($login->url)) {
            $this->_redirect($login->url);
            $login->url = null;
            return;
        }

        return $this->_redirect('/');
    }

    public function logoutAction()
    {
        $this->view->layout()->disableLayout();
        $renderer = $this->getHelper('ViewRenderer');
        $renderer->setNoRender(true);

        Chaplin_Auth::getInstance()->clearIdentity();
        $this->_redirect($this->_redirect_url);
    }

    public function registerAction()
    {
        $this->view->strTitle = 'Register - Chaplin';
        $form = new default_Form_UserData_Create();

        if(!$this->_request->isPost()) {
            return $this->view->assign('form', $form);
        }

        $post = $this->_request->getPost();
        
        if(!$form->isValid($post)) {
            return $this->view->assign('form', $form);
        }

        $username = $post['username'];
        $password = $post['password'];
        $password2 = $post['password2'];
        $email = $post['email'];
        $fullname = $post['fullname'];
        // TODO validate email
        // TODO validate username
        // TODO validate password
        // TODO check if user exists
        try
        {
            $user = Chaplin_Model_User::create($username, $password);
            $user->setEmail($email);
            $user->setNick($fullname);
            $user->setUserType(
                new Chaplin_Model_User_Helper_UserType(
                    Chaplin_Model_User_Helper_UserType::ID_USER
                )
            );
            $user->save();
                   
            // AJAX: Success
            return $this->_redirect($this->_redirect_url);
        }
        catch (Zend_Db_Statement_Exception $e) {
            $form->username->addError('Could not create account - a user aleady exists with that name');
            $form->markAsError();
            return $this->view->assign('form', $form);
        }
        catch(Exception $e)
        {
            $form->username->addError('Could not create account. Reason: '.$e->getMessage());
            $form->markAsError();
            return $this->view->assign('form', $form);
        }
    }

    public function forgotAction()
    {
        $this->view->strTitle = 'Forgot - Chaplin';

        $form = new default_Form_Forgot();
        if (!$this->_request->isPost()) {
            return $this->view->form = $form;
        }
        if (!$form->isValid($this->_request->getPost())) {
            return $this->view->form = $form;
        }

        try {
            $modelUser = Chaplin_Gateway::getUser()
                ->getByUsername($form->username->getValue());
            
            Chaplin_Gateway::getEmail()
                ->resetPassword($modelUser);

        } catch (Chaplin_Dao_Exception_User_NotFound $e) {
        }

        $this->_helper->flashMessenger(
            'You should soon receive an email containing<br/>'.
            'instructions on how to set your password.'
        );
        $this->_redirect('/login');
    }

    public function validateAction()
    {
        $this->view->strTitle = 'Validate - Chaplin';
        $strToken = $this->_request->getParam('token', null);
        if (empty($strToken)) {
            $this->_redirect('/login');
        }

        $form = new default_Form_Validate($strToken);

        if (!$this->_request->isPost()) {
            return $this->view->form = $form;
        }

        if (!$form->isValid($this->_request->getPost())) {
            return $this->view->form = $form;
        }

        Chaplin_Gateway::getUser()
            ->updateByToken(
                $strToken,
                $form->password->getValue()
            );

        $this->_helper->flashMessenger(
            'If a user account exists then your password has been set.<br/>'.
            'You can now login below.'
        );
        $this->_redirect('/login');
    }

    public function oauthAction()
    {
        $strVhost = Chaplin_Config_Chaplin::getInstance()->getVhost();

        $oauth = include APPLICATION_PATH.'/config/oauth.php';

        $strProvider = $this->_request->getParam('provider');
        if (!isset($oauth[$strProvider])) {
            die('invalid provider');
        }
        $arrOauth = $oauth[$strProvider];

        $this->_helper->viewRenderer->setNoRender();
        $this->_session = new Zend_Session_Namespace('oauth');

        switch ($arrOauth['oauth_version']) {
            case 2:

                if (is_null($this->_request->getQuery('code'))) {
                    $state = md5(uniqid());
                
                    $this->_session->state = $state;

                    $url = $arrOauth['auth_uri'].
                        '?client_id='.$arrOauth['client_id'].
                        '&response_type=code'.
                        '&scope='.$arrOauth['scope'].
                        '&redirect_uri='.$arrOauth['redirect_uri'].
                        '&state='.$state;

                    return $this->_redirect($url);
                }
                $state = $this->_session->state;
                $getstate = $this->_request->getQuery('state');
                if ($getstate != $state) {
                    die('Unauthorised, buddy. Hey:  ' . $getstate . ' ' . $state);
                }
                $this->_session->state = null;

                $client = new Zend_Http_Client($arrOauth['token_uri']);
                $client->setMethod(Zend_Http_Client::POST);
                $client->setParameterPost([
                    'code' => $_GET['code'],
                    'client_id' => $arrOauth['client_id'],
                    'client_secret' => $arrOauth['client_secret'],
                    'redirect_uri' => $arrOauth['redirect_uri'],
                    'grant_type' => 'authorization_code'
                ]);
                $response = $client->request('POST');
                $body = $response->getBody();

                $callback_decode = $arrOauth['callback_decode'];

                $arrInfo = $callback_decode($body);

                $strAccessToken = $arrInfo['access_token'];
                $infoUri = $arrOauth['info_uri'].'?oauth_token='.   $strAccessToken;

                $client = new Zend_Http_Client($infoUri);
                $response = $client->request();
                break;

            case 1:
                $consumer = new Zend_Oauth_Consumer($arrOauth);
                $arrQuery = $this->_request->getQuery();
                if (empty($arrQuery) &&
                    is_null($this->_session->request_token)
                ) { 
                    $token = $consumer->getRequestToken();
                    $this->_session->request_token = serialize($token);
                    return $consumer->redirect();
                }

                $request_token = unserialize($this->_session->request_token);
                if (!$request_token) {
                    $this->_session->request_token = null;
                    return $this->_redirect($arrOauth['callbackUrl']);
                }

                try {
                    $token = $consumer->getAccessToken(
                        $arrQuery,
                        unserialize($this->_session->request_token)
                    );
                } catch (Zend_Oauth_Exception $e) {
                    $this->_session->request_token = null;
                    return $this->_redirect($arrOauth['callbackUrl']);
                }

                $this->_session->access_token = $token;
                $this->_session->request_token = null;

                $zendClient = $token->getHttpClient($arrOauth);
                $zendClient->setMethod(Zend_Http_Client::GET);
                $zendClient->setUri($arrOauth['info_uri']);
                $response = $zendClient->request();
                break;
            default:
                throw new Exception('unknown api version');
        }

        $arrResponse = Zend_Json::decode($response->getBody());
        $email = $arrResponse[$arrOauth['key_email']];
        echo 'Name: '.$arrResponse[$arrOauth['key_fullname']].'<br/>';
        if (isset($arrOauth['key_firstname']))
            echo 'First Name: '.$arrResponse[$arrOauth['key_firstname']].'<br/>';
        if (isset($arrOauth['key_lastname']))
            echo 'Last Name: '.$arrResponse[$arrOauth['key_lastname']].'<br/>';
        if (isset($arrOauth['key_username']))
            echo 'Username: '.$arrResponse[$arrOauth['key_username']].'<br/>';
        if (isset($arrOauth['key_email']))
            echo 'Email: '.$arrResponse[$arrOauth['key_email']];
    }
}
