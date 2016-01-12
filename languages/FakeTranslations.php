<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * This files contains fake translation calls.
 *
 * It is never called in the program, but it is used to enter
 * strings that should be translated, but are not called by
 * the _() function in the code.
 */

// Validation messages
_('Value is required and can\'t be empty');
_('Invalid type given, value should be string, integer or float');

//Login: Zend_Auth_Adapter_DbTable
// Gems_Validate_GemsPasswordUsername
_('Installation not complete! Login is not yet possible!');
_('Your account is temporarily blocked, please wait %s minutes');
_('You are not allowed to login from this location.');

// Gems_Validate_IPRanges
_("One or more IPs are illegal.");

// Gems_Validate_OneOf
_("Either '%description%' or '%fieldDescription%' must be entered.");

// Gems\Validate\ValidateSurveyExportCode
_('A duplicate export code matching \'%value%\' was found.');

// MUtil_Validate_Date_DateAfter
_("Date should be '%dateAfter%' or later.");
_("Should be empty if valid from date is not set.");

// MUtil_Validate_Date_DateBefore
_("Date should be '%dateBefore%' or earlier.");

// MUtil_Validate_Date_IsDate
_('%value% is not a valid date.');

// MUtil_Validate_Db_UniqueValue
_('No record matching %value% was found.');
_('A duplicate record matching \'%value%\' was found.');

// MUtil_Validate_File_IsRelativePath
_('Only relative paths are allowed');

// MUtil_Validate_File_Path
_("'%pattern%' characters are forbidden in a path");

// MUtil_Validate_ElevenTest
_("This is not a valid %testDescription%.");
_("A %testDescription% cannot contain letters.");
_("%value% is too long for a %testDescription%. Should be %length% digits.");
_("%value% is too short for a %testDescription%. Should be %length% digits.");

// MUtil_Validate_IsConfirmed
_("Must be the same as %fieldDescription%.");

// MUtil_Validate_IsNot
_("This value is not allowed.");

// MUtil_Validate_NotEqualTo
_('Values may not be the same.');

// MUtil_Validate_Phone
_("'%value%' is not a phone number (e.g. +12 (0)34-567 890).");

// MUtil_Validate_Pdf
_('Unsupported PDF version: %value% Use PDF versions 1.0 - 1.4 to avoid this problem.');

// MUtil_Validate_Require
_("To set '%description%' you have to set '%fieldDescription%'.");

// MUtil_Validate_SimpleEmail
_("Invalid type given, value should be string, integer or float");
_("'%value%' is not an email address (e.g. name@somewhere.com).");

// MUtil_Validate_SimpleEmails
_("'%value%' is not a series of email addresses (e.g. name@somewhere.com, nobody@nowhere.org).");

// Zend_Validate_Digit
_("'%value%' must contain only digits");
_("'%value%' is an empty string");
_("Invalid type given. String, integer or float expected");

// Zend_Validate_File_Extension but altered in FormBridge
_("Only %extension% files are accepted.");

// Zend_Validate_GreaterThan
_("'%value%' is not greater than '%min%'");

/*
_("Invalid type given, value should be a string");
_("'%value%' is no valid email address in the basic format local-part@hostname");
_("'%hostname%' is no valid hostname for email address '%value%'");
_("'%hostname%' does not appear to have a valid MX record for the email address '%value%'");
_("'%hostname%' is not in a routable network segment. The email address '%value%' should not be resolved from public network.");
_("'%localPart%' can not be matched against dot-atom format");
_("'%localPart%' can not be matched against quoted-string format");
_("'%localPart%' is no valid local part for email address '%value%'");
_("'%value%' exceeds the allowed length");
*/

