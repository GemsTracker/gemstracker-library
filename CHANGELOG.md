# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)

## [1.9.2]
### Added user functionality
- Add email to locations (#878)
- Add multi source LS installations (#876)
- Add patientnumber to e-mail templates (#879)
- Add RTF export to DossierExport (#703)
- Allow difference between values and labels in select and multiselect track fields (#776)
- Allow mailability to be set at multiple levels (#603)
- Allow save & resume from LimeSurvey using GT (#702)
- Allow single click copy of tables and other objects to the clipboard (#705)
- Allow some (limited) option for repeating parts of tracks (#697)
- Auto mail job should also be able to send different kind of messages (#636)
- Displaying answers depending on role (#23)
- Epic screen integration (#751)
- Extend Repeat rounds with from date (#808)
- Forbid the export of certain surveys (#693)
- One click copy of token in traffic light view (#723)
- Prefill gtAgeYears, gtAgeMonth and gtGender (#704)
- Reset sent mail - using checkbox in Token edit screen (#674)
- Send again button in communication log (#668)
- Simplify use of LS with test / acceptance / production environments (#30)
- Survey display option to show all instances, but only answered questions (#689)
- Trafficlight screen does not remember last clicked token (#719)
- Use labels instead of values for (Multi)Select track fields (#877)

### Fixed
- Activity search snippet shows result when non should be there (#736)
- AgeCondition should revert to today when valid from not set (#818)
- Answers in traffic light screen do not show up inline (#722)
- Canvas nodes in Dossier Export corrupt Word document (#717)
- Birthday sometimes stored incorrectly (#666)
- Calculating token from and until date before condition results in strange outcomes (#724)
- Cannot perform installation with table translations initialized (#712)
- Clicking on cell in Per period overview stopped working (#716)
- Database filtering error on multi dimensional multiOption (#695)
- Date fields use dates from inactive appointments (#731)
- DateSelectorAbstract is dependent on older MySQL beahaviour (#743)
- Default email templates have spelling errors (#711)
- Default view of MailLog does not show the time the mail was send (#667)
- Do not show more than 2000 patient numbers in the systemuser show screen (#675)
- Double field codes are allowed in a track (#737)
- Embedded login sometimes fails with error instead of correct error log (#662)
- Export of By patient Overview always returns empty output (#683)
- Export to Word chokes on & and h2 elements in questions (#653)
- Field conditions use (translated) value labels (#880)
- From e-mail address should be just that (#669)
- HtmlElement access on non-existing content generates only useless notices (#696)
- Later answers overwrite set answers in some before answering events (#759)
- Limesurvey 4+ fieldmap error (#542)
- Mailcodes show up ugly in survey maintenance search screen when there is only 1 survey mail code (#690)
- New site install default language not correct (#714)
- pr.ask privilege is not in guest role (#735)
- Preg_match() notice in upgrade compatibility check (#698)
- PrefillAnswers script fails when track fieldcodes are equal but differ in case (#687)
- Prevent error message when getimagesize() fails to read a file (#663)
- Print button in modal dialog shows empty page (#681)
- Relation_about fields in mail are empty when there is no relation (#715)
- RepeatRoundsEvent does not calculated correctly when using other value (#840)
- RespondentRelations field error in TokenMailer (#664)
- Roles table overview has incompete records (#709)
- Score in traffic light screen does not show up inline (#720)
- SelectModel drops sort columns silently (#672)
- SQL error in getOrganizationsByCode (#665)
- Source synching gives an error on expired surveys in a newly attached source (#750)
- Spelling mistakes in Site setup information (#710)
- Stored password fields empty stored passwords when nothing is entered (#718)
- Submitting last survey generates fatal error (#707)
- Survey sometimes tries to load empty events (#661)
- The answer popup during an embedded login has inconsistent output (#679)
- The parent child information for surveys is incorrect (#654)
- Token display for respondent ignores token round order (#721)
- Token overview by period creates an error (#706)
- Token overviews fail on relations with a slash in the name (#684)
- Token return is set to organisation url, even when logged in (#660)
- Url site management blocks access before update (#655)
- Url site management does not use specified layout and locale (#656)
- Use of currentUser in DbLookup kills the API (#730)
- Watermark in empty form-fields (#8)
- When using RespondentTrack->setEndDate the date should be set to manual (#670)

### Interface improvements
- Better error handling when respondent does not exists during Rounds checking (#819)
- By period Token overview totals do not match (#685)
- Document the db (#16)
- Dossier export cannot open in a new tab in EPD environments (#691)
- Focus tracking does not work on sub forms and element (#671)
- Mail template subject line should be a required field (#676)
- Remove label 'Not checked' from LimeSurvey answers (#692)

### Programmability
- Remember in the session that the user is embedded (#680)
- Remember date of last import of appointments (#694)

### Security
- Update externals in templates to recent versions (#193)


## [1.9.1]
[1.9.1]: https://github.com/GemsTracker/gemstracker-library/compare/1.9.0...1.9.1
### Added
- Reorganize and extend the documentation (#639)

### Added user functionality
- Add a new track field type for linking track to multiple other tracks (#621)
- Add an external survey name to a survey (#626)
- Add an external track name to a track (#625)
- Add insertable survey only shows part of the title (#602)
- Added Url site management (#618)
- Allow relation to ask for a token mail resend (#601)
- Allow mailability to be set at multiple levels (#603)
- Enable inserting more than one survey at a time (#617)
- Enable password editing on all sites (#648)
- Debug options for survey events (#613)
- Make display of family name in ask screen optional (#641)
  
### Interface improvements
- Add a header to the round editor to show where the conditions start (#635)
- Add group information for limesurvey questionnaires to survey preview and answer views (#34)
- Add survey question display to survey-maintenance-show questions (#541)
- Add translation of specific database fields, like Track name, survey name (#568)
- Enable removal of existing passwords and SSN's (#629)
- Enabled time use when editing and displaying Token valid from and until dates (#634)
- Improve mail template form use with multiple languages (#614)
- Show more information when editing appointment fields (#627)
- Show the most recently added track first in the track overviews of patients (#622)
- Track builder: filter survey list (#362)

### Programmability
- Add translation of specific database fields, like Track name, survey name (#568)
- Enable PDF export using PHP Office (#597)

### Security
- Feedback bij niet verstuurde reset mail (#278)
- Inappropriate access control for logs (#642)
- Inappropriate access for some track pages (#643)
- Prevent the wrong patient being shown during embedded login (#607)
- Prevent user defined query columns (#647)
- Remove the '1' privilege from the role editor (#631)

### Fixed
- Adding an insertable survey to an emptied phase goes wrong (#609)
- Appointment changes can affect conditions (#590)
- Appointment fields lack context during some track creation (#623)
- Codebook export screen improvements (#630)
- Error message WHERE token = 'xxx' (#595)
- Extend length if log IP address storage (#606)
- Incorrect valid until date set when putting a token in LimeSurvey (#612)
- Make email test language a choice (#61)  
- Make synchronize LS surveys multibyte save (#598)
- PDF Open in browser downloads (#645)
- Remove token events from the listener after use (#611)
- Respondent Token does not switch to the correct organization and language (#646)
- Subscribe action generates fatal error (#638)
- The default roles no longer show the ask screen (#604)
- The unit tests seem to have been broken for quite a while (#619)
- Token 'Assigned by' shows Track assigner (#616)
- Use the organization contact name in e-mail from (#321)
- When using an appointment filter with a minimum start date difference tracks are repeatedly created (#628)
  
## [1.9.0]
[1.9.0]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.7...1.9.0
This is a sub-major version upgrade because of the introduction of modules 
### Added user functionality
- Add Mailjob id to mail log (#512)
- Add respondent export to Word as an option (#528)
- Add track level conditions (#569)
- Add translations of database fields for Track name and track fields (#568)
- Added NOT ANY en NOT ALL agenda filters (#557)
- Allow activity / procedure agenda filter to select on NOT activity (#567)
- Allow filtering in mailjobs on a specific relation (#572)
- Allow the respondents to ask for a token resend (#580)
- Consent changes are logged and displayed automatically (#59)
- EPD login setup extended with new display, security and routing options (#551)
- Episodes of care can use appointment filters and changes can trigger track creation (#378)
- LimeSurvey source usable by different installations (#574)
- Log changes in field values (#583)
- Log out on answering survey (#223)
- Manually block recalculation of track fields (#564)

### Interface improvements
- Added German, French and Italian translations (#549)
- Added max and min answer time to survey Duration calculated (#576)
- Adding environment version to header simplified (#524)
- Allow text searches in Track compliance and summary overviews (#526)
- Allow use of project level CHANGELOG.md files (#525)
- Combine multiple excel export files as sheets in one excel file (#515)
- Form menu / cancel buttons display aligned with submit buttons (#562)
- Guide users to change password page when they use the back button to the reset password page (#578)
- Mailjob stepper (next, previous) like rounds have (#518)
- Remove required-asterisk from token ask screen (#575)
- Removed ask screen as default for logged in users (#222)
- Rename primary function to primary group (#566)
- Renamed Track field filter to Appointment filter (#558)
- Round Icon selection shows icon during selection (#45)
- Show more round information in Track compliance (#527)
- Simplify the display of the InsertSurveySnippet (#584)
- Track fields stepper (next, previous) like rounds have (#518)
- When changing answers: autoextend valid until date (#582)

### Programmability
- Allow included images to be used with an email template (#563)
- Allow the creation of external modules for Gemstracker (#553)
- Create relational submodels (#559) 
- Force sending of mails in batch and number of jobs and mails sent (#561)
- PHP 5.6 incompatibility issue (#555)
- Support for PHP versions higher than 7.3 (#506)
- Use Redis for cache (#548)
- Use Events in Gemstracker (#553)

### Subscribe & unsubscribe
- Log respondent id during unsubscribe (#547)
- Throttle the subscribe and unsubscribe screens (#537)

### Fixed
- Automatically created directories get no permissions on linux (#546)
- Clearing parent role does not work in role editor (#565)
- Conditions should have a unique name (#581)
- Deleting rounds does not check for use of round in other rounds (#509)
- During patient edit the organization can be changed (#566)
- Error when sending mail from template editor (#279)
- Expired surveys in LimeSurvey should not be imported (#573)
- Form errors are sometimes not translated (#532)
- Incorrect translation item in automatic mailjob configuration #536
- It is impossible not to show the parent 'Cancel' button in respondent show (#552)
- Mail filter 'before expiration' is not working (#543)
- Mail jobs with round filter have incorrect cache tag (#513)
- Mailing to fall-back email does not work (#522)
- Notice in Trackdata when no survey languages can be found (#523)
- Review rights currently not assigned to any group (#405)
- Setup => Agenda sub-items do not filter correctly (#585)
- Source status check (ping) not logged (#535)
- The button to search all respondents could not be made invisible (#522)
- Track fields get wrong dependencies (#517)
- When inserting survey a manually set end date is not stored (#577)


## [1.8.7]
[1.8.7]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.6...1.8.7
### Added
- Add a database dump option that creates a dump without patient information (#435)
- Add a survey codebook export (#456)
- Add year/month option to survey answer export (#465)
- Add diagnosis as an appointment item (#375)
- Allow mails to be send to the staff (#477)
- Organizational fall-back email address for tokens (#32)
- Enable use of respondent data in track definitions (#469)
- New condition to use answer from the track (#461)
- Event to assign relations from survey answers (#458)
- Unsubscribe option for participants (#386)

### Interface improvements
- Allow to filter tracks on valid from/until dates (#459)
- Allow to disable or reset two factor authentication for a user by the admin (#475)
- Continue later option should not show when emailing not allowed (#470)
- Overview track field utilization can now filter on track dates (#441)

### Export
- Multi survey answer export has no defaults for export types (#473)
- Order of fields jumps in survey answer export (#472)
- Answer export preview shows no or incorrect data for track fields (#466)
- Questions without a label can not be exported (#450)
- Answer export for all surveys runs out of memory (#442)
- Export of tracks with conditions of type And/Or is incomplete (#446)

### Fixed
- Deleting a used condition breaks round viewing (#452)
- Deleting a track throws notices (#451)
- Notice when trying to add a survey when no track exists (#449)
- Masked fields should not be searchable in text fields (#455)
- Calendar does not correctly mask information (#457)
- Reset password form should not tell if a user is not found in the system (#454)
- User role not transformed to text after refresh (#474)
- Import using text throws a notice (#471)
- Location names should be longer (#468)
- Appointment import does not allow timezone specification (#467)
- Empty parameter for condition creates duplicates on import (#460)
- Template for linked accounts includes hidden chars (#444)
- Email with ampersand is not accepted (#431)
- Manual execution of mail jobs is not default logged (#407)

## [1.8.6]
[1.8.6]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.5...1.8.6
### Security
- Token valid dates are also injected in the survey system (#385)
- A basic continue later option was added to allow respondents to pause answering surveys (#387)

### Added
- Appointments have extra filter options for creating a second track instance, e.g. on a start date difference (419)
- Compliance overview now provides a better export (#381)
- Round conditions can now filter for contained text (#384)
- Round conditions can now filter for a value in a list (#415)
- Round conditions can be combined using AND / OR operators (#416)
- Round conditions now allow to use the gender of the respondent or the relation (#390)
- Organization contact email will now be notified when cron has not run on time, can be switched off (#172)
- Organizations can automatically create 'default' tracks for new or changed respondents (#420)
- Respondents can be enabled to self-subscribe and unsubscribe (#418)
- Bulk check for respondent update events for organizations (#420)
- Boolean trackfield was added (#399)
- A visual overview of a track in now available in the track builder (#408)

### Interface improvements
- Source survey id is visible in survey maintenance screen (#403)
- The progressbar will reach 100% now in Internet Explorer (#400)
- The project user can now switch to superadmin group (#404)

### Fixed
- Use of mcrypt no longer required, uses OpenSSL instead (#334)
- When exporting multiple survey results at the same time, SPSS export now adds a syntax file for each survey (#391)
- Used tracks can no longer be deleted, only deactivated (#414)
- Deleting a track also deletes linked rounds and fields (#414)

## [1.8.5] - 2018-10-10
[1.8.5]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.4...1.8.5
### Added
- Survey answers can now be exported to R format (#213)

### Changed
- Export classes can now add instructions for the downloaded file(s) (#371)
- The getRespondent() method in a controller was changed to public to allow better logging (#360)
- The meta.Content-Security-Policy was moved to the headers section (#352)
- Changelog now allows .md extension for markdown formatting, including github issue links (#351)
- Mailjobs can now be executed manually. This allows a combination of automatic and semi automatic as well as deactived jobs (#361)
- Agenda setup now allows to select on the filter attribute (#353)
- SPSS export no longer cuts of text answers at 64 chars and will default to numeric more often for list type answers (#335)
- Communication templates now use token as default source instead of staff (#367)

### Removed
- The old ExcelHtml and Stata exports were removed, the new Excel and Stata exports remain (#342)
- getFullQuestionList was removed from LimeSurvey source, as it was not in the interface and unused (#186)

### Fixed
- Do not show name in Compliance and Field overview when user may not see the name (#374)
- Programming errors show debug trace in error log (#373)
- List elements in forms are no longer translated if form is set to disable translator (#370)
- Logging the organization for is improved, and logging survey export is now on by default (#360 #242)
- While browsing database tables the pagination now works when the number of items is changed (#346)
- Answer import of csv files now autosenses for colon or semicolon separator (#358)
- Bigger files can be handled during import without running out of memory (#354)
- LimeSurvey source now supports the ranking question (#341)
- Deleted tokens can be found again in overviews (#356)
- LDAP user domain is no longer hardcoded (#350)
- Respondent email can be set to empty when importing (#349)
- Tokens dates are updated when condition changes (#349)
- Fixes for login sequence (#363 #347 #365)
- Appointments can create a new track when there is no pre-existing track (#355)
- When viewing a mailjob selected token overview can be sorted by clicking on headers (#366)

## [1.8.4] - 2018-08-20
[1.8.4]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.3...1.8.4
### Added
- Appointments can now be grouped into HL7 care episodes (#306)
- Conditions can determine if a round is applicable or not (#42), this reduces the need for track events
- New before answering and after completion events synchronize track fields with code the same as answer codes (#55)
- More options for appointments to create a track (#294)
- Patients van have different e-mail addresses at each organization in a project (#310)
- Privileges can be exported with their assignments (#313)

### Fixed
- Many rare bugs solved and speed and interface improvements
- Reset password did not work when the password was expired (#307)
- Sort links stopped working after search (#312)

### Interface improvements
- Added token states incomplete and partially answered (#280)
- Answers of partially answered tokens can be seen (#280)
- Groups and organizations IP Filters can use subnet masks and asterisk range notations, including IP6 addresses (#28)
- Monitor job overviews (#194)
- Token status is shown in show token screen (#280)

### Programmability
- Simplified project specific login procedures (#298)
- Projects can allow organizations to look into each others tokens and tracks (#300)
- Upgrade Compatibility Checks for deprecated project code (#269)

### Security
- CSV Injection protection
- Enhanced parameter filtering for added security (#298)
- Enhanced password hashing (#177, #209, #257)
- LDAP Authentication enabled (#317)
- Two factor authentication for users (#237)

### Track Builder
- Conditions (on track fields) can be set for rounds (#42)
- Extended Open Rosa support including for nested rows and survey answering
- Organizations can share tracks and tokens (#300)
- Track can be created from appointments even when an older track is open (#294)
- When redoing a survey, the answers are injected before answering instead of during replacement (#301)

## [1.8.3]
[1.8.3]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.2...1.8.3
### Added
- Automatic mail
  - Email can now also be sent x days before survey expiry

## [1.8.2]
[1.8.2]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.1...1.8.2
### Added
- You can change the organization(s) a patient belongs to (and move his/her tracks)
- Correct token button added to menu
- Memo field type for tracks
- (Re)import imported files

- Automatic mail
  - Preview option for automatic mailjobs
  - Automatic mailjobs can now filter for relations, check your jobs!
  - Automatic mail execution can be logged to file when set in the project.ini
- Interface improvements:
  - When a token is corrected links are visible when seeing the token to open the original / copy
  - Improved interface for communication templates and automatic mail
  - The Contact => Bugs and GemsTracker pages have been refreshed with a default bugs url (use Roles to make invisible)
  - Editing staff and organizations uses extra defaults for smoother creation
  - Different respondent search, edit and show screens can be set for a user group
  - Different respondent edit and show screens can be set for each organization, overruling user group settings
  - Different token ask screens can be set for each organization
- Export improvements:
  - Answers from inactive patients can now be exported
  - Depending on rights patient numbers, patient gender and birth year and month can be exported with answers
- Rights:
  - The types of staff that a user can create/edit are determined at the group level
  - A default group for new users can be set at the user group level
  - Roles only determine what menu items are allowed, you no longer need to have a right to assign it to another staff
  - Private data can now be hidden or (partially) masked for groups, e.g. researchers need not see patient names
  - Added "site administrator" role and group between local admin and super admin
  - Administrators may now have the right to switch the used group to any they may set
- Security:
  - The full Gems version number is only displayed after login
  - All recalculate, check, synchronize, patch and run commands log the item they are started for
  - Delete, deactivate and reactivate actions are logged correctly
  - New security headers and meta tags can be set from the project.ini
  - All staff users have to follow the password rules for staff, even if their role does not inherit staff
- Programmability:
  - Before field save events allows changing the fields after their new values have been calculated
  - Respondent changed events can be set at the organization level
  - It is easier to change part of the display in ShowTrackTokenSnippet
  - Less compile now uses relativeUrls during compilation, added logoFile and logoHeight variables

### Fixed
- Blocked users were not blocked
- Surveys could be answered during maintenance mode
- The APC Cache was cleared incorrectly
- The var/tmp directory is created when needed and it does not exist
- Users can no longer export data from patients in other organizations

- Testing:
  - On acceptance and demonstration mail only respondent mails are bounce, staff mails are sent to receiver
  - Administrators may now have the right to switch the used group to any they may set

## [1.8.1]
[1.8.1]: https://github.com/GemsTracker/gemstracker-library/compare/1.8.0...1.8.1
### Added
- Rounds can be sorted using drag and drop

### Changed
- Round icons are now in the token table just like the round descriptions

### Automatic email
- Mail jobs are now executed in batch, one job at a time
- Individual jobs can be executed from the interface
- Jobs can be sorted manually using the sort button, check the sort order after upgrade!

### Export
- For survey responses, when available the respondent relation is exported (field name and relation id)
- Various bugfixes, check output carefully

## [1.8.0]
[1.8.0]: https://github.com/GemsTracker/gemstracker-library/compare/1.7.1...1.8.0
- Searching for respondents by track or lack of track
- Customize respondents screen using Snippets\Respondent\RespondentTableSnippet
- Track structure can be exported, imported and merged with an existing track
- Tracks answers, fields and rounds can be checked at the respondent and respondent track level
- (Re)checking for answers now possible at the single token level
- When using the gemsdata__responses table, views will be created for each survey
- A new after completion event allows the setting of the informed consent through a survey
- Fixed survey activation when survey not active in source
- LimeSurvey equation questions now use help text for question or question code when empty
- A new cron job checks whether the mail cron job has finished correctly
- The check cron job is also checked before each login
- Imports through the interface are logged in the activity log
- The menu remains fully visible when an error occurs
- Staff import now respects organization default user class
- Inserted surveys now have class 'inserted' added to the row in track overview
- Most search screens have been updated and all work the same

## Pre 1.8.0
Changes were deleted from the changelog. Check the history in [GitHub](https://github.com/GemsTracker/gemstracker-library) if you are really interested.
