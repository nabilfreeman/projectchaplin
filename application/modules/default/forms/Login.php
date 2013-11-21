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
class Zend_Form_Element_Html extends Zend_Form_Element_Xhtml {
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formNote';

    public function isValid($value, $context = null) {
        return true;
    }
}

class default_Form_Login extends Zend_Form
{
    public function init()
    {
    
        $this->setMethod('post');
        $this->setAction('/login?redirect=' . $this->_redirect_url);

        $username = new Zend_Form_Element_Text('username');
        $username->setAttrib('placeholder', 'Username');
        $username->removeDecorator('Label');
        $username->setAttrib('required', 'true');

        $password = new Zend_Form_Element_Password('password');
        $password->setAttrib('placeholder', 'Password');
        $password->removeDecorator('Label');
        $password->setAttrib('required', 'true');

        $submit = new Zend_Form_Element_Submit('Login');
        $register = new Zend_Form_Element_Submit('Register');

        $submit->removeDecorator('DtDdWrapper');
        $register->removeDecorator('DtDdWrapper');

        $this->addElements(array($username, $password, $submit, $register));

        $tag = new Zend_Form_Element_Html('forgotPassword');
        $tag->setValue('<a href="forgot">I don\'t know my password</a>');
        $tag->removeDecorator('Label');
        $this->addElement($tag);

    }
}
